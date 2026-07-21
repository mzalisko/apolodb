<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventLogEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'occurred_at',
        'actor_user_id',
        'actor_label',
        'site_id',
        'site_domain',
        'event_type',
        'field',
        'old_value',
        'new_value',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
