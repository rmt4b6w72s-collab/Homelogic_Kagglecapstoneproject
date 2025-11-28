<?php

namespace App\Http\Controllers\Api;

use App\Models\PaymentNotificationPreference;
use App\Constants\Modules;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentNotificationPreferenceController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $facilityId = auth()->user()->facility_id;
        $userId = $request->get('user_id');

        $query = PaymentNotificationPreference::where('facility_id', $facilityId);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id'); // Global preferences
        }

        $preferences = $query->first();

        // Return default preferences if none exist
        if (!$preferences) {
            $preferences = new PaymentNotificationPreference();
            $preferences->fill(PaymentNotificationPreference::getDefaultPreferences());
            $preferences->facility_id = $facilityId;
        }

        return $this->success($preferences);
    }

    public function store(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'days_before_due' => 'required|integer|min:0|max:30',
            'notify_on_due_date' => 'boolean',
            'notify_on_overdue' => 'boolean',
            'overdue_reminder_interval_days' => 'required|integer|min:1|max:30',
            'email_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'notification_channels' => 'nullable|array',
        ]);

        $facilityId = auth()->user()->facility_id;
        $userId = $validated['user_id'] ?? null;

        $preferences = PaymentNotificationPreference::updateOrCreate(
            [
                'facility_id' => $facilityId,
                'user_id' => $userId,
            ],
            $validated
        );

        return $this->success($preferences->load('facility', 'user'), 'Notification preferences saved successfully', 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $preferences = PaymentNotificationPreference::findOrFail($id);

        // Ensure user can only update their facility's preferences
        if ($preferences->facility_id !== auth()->user()->facility_id) {
            return $this->error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'days_before_due' => 'sometimes|required|integer|min:0|max:30',
            'notify_on_due_date' => 'boolean',
            'notify_on_overdue' => 'boolean',
            'overdue_reminder_interval_days' => 'sometimes|required|integer|min:1|max:30',
            'email_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'notification_channels' => 'nullable|array',
        ]);

        $preferences->update($validated);

        return $this->success($preferences->load('facility', 'user'), 'Notification preferences updated successfully');
    }
}

