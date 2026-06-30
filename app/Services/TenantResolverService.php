<?php

namespace App\Services;

use App\Models\Tenant;

class TenantResolverService
{
    /**
     * Resolve tenant by its UUID.
     */
    public function resolveByUuid(string $uuid): Tenant
    {
        return Tenant::where('uuid', $uuid)->firstOrFail();
    }
}
