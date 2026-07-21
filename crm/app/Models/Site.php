<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'site_identifier',
        'active_credential_id',
        'deactivated_at',
    ];

    protected $casts = [
        'deactivated_at' => 'datetime',
    ];

    public function status(): HasOne
    {
        return $this->hasOne(SiteStatus::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(SiteCredential::class);
    }

    public function activeCredential(): BelongsTo
    {
        return $this->belongsTo(SiteCredential::class, 'active_credential_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'site_group');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EventLogEntry::class);
    }
}
