<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteStatus extends Model
{
    protected $table = 'site_statuses';

    protected $primaryKey = 'site_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'status',
        'last_seen_at',
        'last_status_change_at',
        'updated_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_status_change_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
