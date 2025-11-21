<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
