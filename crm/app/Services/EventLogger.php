<?php

namespace App\Services;

use App\Models\EventLogEntry;
use App\Models\Site;

/**
 * Append-only аудит (FR-021, FR-033). Секрети НІКОЛИ не записуються.
 */
class EventLogger
{
    public static function record(
        string $eventType,
        ?Site $site = null,
        string $actorLabel = 'system',
        ?int $actorUserId = null,
        ?string $field = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        array $metadata = []
    ): void {
        EventLogEntry::create([
            'occurred_at' => now(),
            'actor_user_id' => $actorUserId,
            'actor_label' => $actorLabel,
            'site_id' => $site?->id,
            'site_domain' => $site?->domain,
            'event_type' => $eventType,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);
    }
}
