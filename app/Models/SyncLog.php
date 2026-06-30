<?php

namespace App\Models;

use App\Domains\Tenant\HasTenant;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'call_id',
        'action',
        'request',
        'response',
        'status',
        'retries',
    ];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
    ];
}
