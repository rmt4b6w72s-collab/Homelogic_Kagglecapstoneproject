<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogAuthentication
{
    /**
     * Handle the event.
     */
    public function handle(Login|Logout $event): void
    {
        if ($event instanceof Login) {
            if ($event->user) {
                ActivityLogService::login($event->user, [
                    'guard' => $event->guard,
                ]);
            }
        } elseif ($event instanceof Logout) {
            // Only log logout if user exists (may be null if already logged out)
            if ($event->user) {
                ActivityLogService::logout($event->user, [
                    'guard' => $event->guard,
                ]);
            }
        }
    }
}


