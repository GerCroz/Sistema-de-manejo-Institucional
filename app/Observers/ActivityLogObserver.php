<?php

namespace App\Observers;

use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class ActivityLogObserver
{
    /**
     * Handle the Activity "created" event.
     */
    public function created(Activity $activity): void
    {
        $logger = Log::channel('audit');

        if (! $logger) {
            return;
        }

        $logger->info("AUDIT_TRAIL: [{$activity->event}] on {$activity->subject_type} #{$activity->subject_id}", [
            'activity_id' => $activity->id,
            'event' => $activity->event,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'causer_type' => $activity->causer_type,
            'causer_id' => $activity->causer_id,
            'causer_name' => $activity->causer?->name ?? 'System/Console',
            'properties' => $activity->properties?->toArray(),
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'recorded_at' => now()->toIso8601String(),
        ]);
    }
}
