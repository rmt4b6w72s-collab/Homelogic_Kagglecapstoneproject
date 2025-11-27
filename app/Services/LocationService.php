<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * Maximum allowed distance for login in kilometers
     */
    public const MAX_LOGIN_DISTANCE_KM = 5;

    /**
     * Earth's radius in kilometers
     */
    private const EARTH_RADIUS_KM = 6371;

    /**
     * Calculate the distance between two coordinates using the Haversine formula
     * 
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Haversine formula
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Check if distance is within allowed range
     * 
     * @param float $distanceKm Distance in kilometers
     * @return bool True if within allowed range
     */
    public function isWithinAllowedDistance(float $distanceKm): bool
    {
        return $distanceKm <= self::MAX_LOGIN_DISTANCE_KM;
    }

    /**
     * Geocode an address to coordinates using OpenStreetMap Nominatim
     * 
     * @param string $address Address to geocode
     * @return array|null ['latitude' => float, 'longitude' => float] or null on failure
     */
    public function geocodeAddress(string $address): ?array
    {
        if (empty(trim($address))) {
            return null;
        }

        try {
            // OpenStreetMap Nominatim API (free, no API key required)
            $url = 'https://nominatim.openstreetmap.org/search';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => config('location.geocoding_user_agent', config('app.name', 'Evergreen') . ' Location Service'),
                ])
                ->get($url, [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    return [
                        'latitude' => (float) $data[0]['lat'],
                        'longitude' => (float) $data[0]['lon'],
                    ];
                }
            }

            Log::warning('Geocoding failed for address', [
                'address' => $address,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Geocoding exception', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get location from IP address using IP geolocation service
     * 
     * @param string $ipAddress IP address
     * @return array|null ['latitude' => float, 'longitude' => float] or null on failure
     */
    public function getLocationFromIp(string $ipAddress): ?array
    {
        // Skip private/local IPs
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            Log::warning('IP geolocation skipped for private/local IP', ['ip' => $ipAddress]);
            return null;
        }

        try {
            // Using ip-api.com (free tier, no API key required for basic usage)
            $url = "http://ip-api.com/json/{$ipAddress}";
            
            $response = Http::timeout(5)
                ->get($url, [
                    'fields' => 'status,lat,lon',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['status']) && $data['status'] === 'success' 
                    && isset($data['lat']) && isset($data['lon'])) {
                    return [
                        'latitude' => (float) $data['lat'],
                        'longitude' => (float) $data['lon'],
                    ];
                }
            }

            Log::warning('IP geolocation failed', [
                'ip' => $ipAddress,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('IP geolocation exception', [
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Validate coordinates
     * 
     * @param float|null $latitude
     * @param float|null $longitude
     * @return bool
     */
    public function validateCoordinates(?float $latitude, ?float $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        // Latitude must be between -90 and 90
        if ($latitude < -90 || $latitude > 90) {
            return false;
        }

        // Longitude must be between -180 and 180
        if ($longitude < -180 || $longitude > 180) {
            return false;
        }

        return true;
    }

    /**
     * Format distance for display
     * 
     * @param float $distanceKm Distance in kilometers
     * @return string Formatted distance
     */
    public function formatDistance(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return round($distanceKm * 1000) . ' meters';
        }

        return number_format($distanceKm, 2) . ' km';
    }
}

