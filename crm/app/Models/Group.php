<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = ['name'];

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_group');
    }
}
