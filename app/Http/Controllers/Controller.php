<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    /**
     * Re-apply the selected site's tenant connection from session before querying Page/Blog/etc.
     * Defensive: avoids stale/wrong DB if connection cache or middleware order ever desyncs (symptom: list works via API, edit 404s).
     */
    protected function reconnectTenantFromSession(): void
    {
        $id = session('active_domain_id');
        if (! $id) {
            return;
        }

        $domain = Domain::query()->where('id', $id)->where('is_active', true)->first();
        if (! $domain) {
            return;
        }

        config(['database.connections.tenant' => $domain->connectionConfig()]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        config(['database.default' => 'tenant']);
    }
}
