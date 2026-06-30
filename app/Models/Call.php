<?php

namespace App\Models;

use App\Domains\Tenant\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'salestrail_call_id',
        'bitrix_call_id',
        'employee_phone',
        'customer_phone',
        'inbound',
        'answered',
        'duration',
        'recording_url',
        'recording_uploaded',
        'synced_to_bitrix',
        'started_at',
        'finished_at',
        'payload',
    ];

    protected $casts = [
        'inbound' => 'boolean',
        'answered' => 'boolean',
        'recording_uploaded' => 'boolean',
        'synced_to_bitrix' => 'boolean',
        'payload' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
