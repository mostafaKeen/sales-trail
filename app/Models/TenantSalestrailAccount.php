<?php

namespace App\Models;

use App\Domains\Tenant\HasTenant;
use Illuminate\Database\Eloquent\Model;

class TenantSalestrailAccount extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'api_url',
        'api_key',
        'webhook_secret',
        'user',
        'password',
    ];
}
