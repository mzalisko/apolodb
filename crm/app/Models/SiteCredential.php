<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteCredential extends Model
{
    protected $fillable = [
        'site_id',
        'secret_encrypted',
        'sig_version',
        'state',
        'issued_at',
        'revoked_at',
        'last_used_at',
    ];

    protected $casts = [
        // encrypted-at-rest у відновлюваній формі (Конституція v2.0.1): app-encrypter,
        // ключ у APP_KEY (.env), НЕ в репозиторії/БД. Читання атрибута повертає plaintext.
        'secret_encrypted' => 'encrypted',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isActive(): bool
    {
        return $this->state === 'active';
    }

    /** Розшифрований секрет підпису (для переобчислення HMAC на бекенді). */
    public function secret(): string
    {
        return (string) $this->secret_encrypted;
    }
}
