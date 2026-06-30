<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'uuid',
        'company_name',
        'status',
        'timezone',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });
    }

    public function salestrailAccount(): HasOne
    {
        return $this->hasOne(TenantSalestrailAccount::class);
    }

    public function bitrixAccount(): HasOne
    {
        return $this->hasOne(TenantBitrixAccount::class);
    }

    public function userMappings(): HasMany
    {
        return $this->hasMany(TenantUserMapping::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }
}
