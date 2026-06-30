<?php

namespace App\Models;

use App\Domains\Tenant\HasTenant;
use Illuminate\Database\Eloquent\Model;

class TenantUserMapping extends Model
{
    use HasTenant;

    protected $table = 'tenant_users_mapping';

    protected $fillable = [
        'tenant_id',
        'salestrail_user_id',
        'salestrail_email',
        'employee_phone',
        'bitrix_user_id',
    ];
}
