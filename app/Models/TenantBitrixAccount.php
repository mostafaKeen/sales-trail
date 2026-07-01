<?php

namespace App\Models;

use App\Domains\Tenant\HasTenant;
use Illuminate\Database\Eloquent\Model;

class TenantBitrixAccount extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'bitrix_domain',
        'webhook_url',
        'client_id',
        'client_secret',
        'access_token',
        'refresh_token',
        'external_line_id',
        'member_id',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];
}
