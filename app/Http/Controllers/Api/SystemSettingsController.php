<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SystemSettingsController extends Controller
{
    /**
     * Get super admin theme colors
     */
    public function getSuperAdminTheme(): JsonResponse
    {
        // Check if user is super admin
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json([
                'error' => 'Unauthorized. Super admin access required.',
            ], 403);
        }

        $theme = SystemSetting::getSuperAdminTheme();

        return response()->json([
            'data' => $theme,
        ]);
    }

    /**
     * Update super admin theme colors
     */
    public function updateSuperAdminTheme(Request $request): JsonResponse
    {
        // Check if user is super admin
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json([
                'error' => 'Unauthorized. Super admin access required.',
            ], 403);
        }

        $validated = $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        SystemSetting::set('super_admin_primary_color', $validated['primary_color'], 'string', 'Primary color for super admin interface');
        SystemSetting::set('super_admin_secondary_color', $validated['secondary_color'], 'string', 'Secondary color for super admin interface');
        SystemSetting::set('super_admin_accent_color', $validated['accent_color'], 'string', 'Accent color for super admin interface');

        $theme = SystemSetting::getSuperAdminTheme();

        return response()->json([
            'message' => 'Super admin theme colors updated successfully',
            'data' => $theme,
        ]);
    }

    /**
     * Get all system settings (for admin use)
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json([
                'error' => 'Unauthorized. Super admin access required.',
            ], 403);
        }

        $settings = SystemSetting::all()->mapWithKeys(function ($setting) {
            return [$setting->key => SystemSetting::get($setting->key)];
        });

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Get super admin branding settings
     */
    public function getBranding(): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json([
                'error' => 'Unauthorized. Super admin access required.',
            ], 403);
        }

        $branding = [
            'company_name' => SystemSetting::get('super_admin_company_name', 'HomeLogic360'),
            'logo' => SystemSetting::get('super_admin_logo', null),
            'favicon' => SystemSetting::get('super_admin_favicon', null),
            'primary_color' => SystemSetting::get('super_admin_primary_color', '#1E3A5F'),
            'secondary_color' => SystemSetting::get('super_admin_secondary_color', '#86EFAC'),
            'accent_color' => SystemSetting::get('super_admin_accent_color', '#FFFFFF'),
        ];

        // Add full URLs for logo and favicon if they exist
        if ($branding['logo']) {
            $branding['logo_url'] = Storage::disk('public')->exists($branding['logo']) 
                ? Storage::disk('public')->url($branding['logo'])
                : null;
        } else {
            $branding['logo_url'] = asset('images/logonew.png'); // Default logo
        }

        if ($branding['favicon']) {
            $branding['favicon_url'] = Storage::disk('public')->exists($branding['favicon']) 
                ? Storage::disk('public')->url($branding['favicon'])
                : null;
        } else {
            $branding['favicon_url'] = null;
        }

        return response()->json([
            'data' => $branding,
        ]);
    }

    /**
     * Update super admin branding settings
     */
    public function updateBranding(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return response()->json([
                'error' => 'Unauthorized. Super admin access required.',
            ], 403);
        }

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:512',
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        // Update company name
        if (isset($validated['company_name'])) {
            SystemSetting::set('super_admin_company_name', $validated['company_name'], 'string', 'Company name for super admin branding');
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            $oldLogo = SystemSetting::get('super_admin_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $logo = $request->file('logo');
            $logoPath = $logo->store('branding/logos', 'public');
            SystemSetting::set('super_admin_logo', $logoPath, 'string', 'Logo for super admin branding');
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            // Delete old favicon if exists
            $oldFavicon = SystemSetting::get('super_admin_favicon');
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }

            $favicon = $request->file('favicon');
            $faviconPath = $favicon->store('branding/favicons', 'public');
            SystemSetting::set('super_admin_favicon', $faviconPath, 'string', 'Favicon for super admin branding');
        }

        // Update colors - only update if provided and not empty
        if (isset($validated['primary_color']) && !empty($validated['primary_color'])) {
            SystemSetting::set('super_admin_primary_color', $validated['primary_color'], 'string', 'Primary color for super admin interface');
        }
        if (isset($validated['secondary_color']) && !empty($validated['secondary_color'])) {
            SystemSetting::set('super_admin_secondary_color', $validated['secondary_color'], 'string', 'Secondary color for super admin interface');
        }
        if (isset($validated['accent_color']) && !empty($validated['accent_color'])) {
            SystemSetting::set('super_admin_accent_color', $validated['accent_color'], 'string', 'Accent color for super admin interface');
        }

        // Return updated branding
        return $this->getBranding();
    }
}
