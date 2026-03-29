<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DomainController extends Controller
{
    /** Post-login domain picker page. */
    public function select(): Response
    {
        $domains = Domain::where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'domain', 'frontend_url']);

        return Inertia::render('Domains/Select', [
            'domains'       => $domains,
            'activeDomainId'=> session('active_domain_id'),
        ]);
    }

    public function index(): Response
    {
        $domains = Domain::orderByDesc('is_default')->orderBy('name')->get()
            ->map(fn (Domain $d) => [
                'id'           => $d->id,
                'name'         => $d->name,
                'domain'       => $d->domain,
                'frontend_url' => $d->frontend_url,
                'db_host'      => $d->db_host,
                'db_name'      => $d->db_name,
                'is_active'    => $d->is_active,
                'is_default'   => $d->is_default,
            ]);

        return Inertia::render('Domains/Index', [
            'domains'        => $domains,
            'activeDomainId' => session('active_domain_id'),
            'flash'          => ['success' => session('success'), 'error' => session('error')],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Domains/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'domain'       => 'required|string|max:255|unique:mysql.domains,domain',
            'frontend_url' => 'nullable|string|max:500',
            'db_host'      => 'required|string|max:255',
            'db_port'      => 'required|integer|min:1|max:65535',
            'db_name'      => 'required|string|max:255',
            'db_username'  => 'required|string|max:255',
            'db_password'  => 'required|string',
            'is_default'   => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            Domain::where('is_default', true)->update(['is_default' => false]);
        }

        $data['db_password'] = encrypt($data['db_password']);
        $domain = Domain::create($data);

        // auto_select=true: immediately activate this domain and go to dashboard
        if ($request->boolean('auto_select')) {
            session(['active_domain_id' => $domain->id]);
            return redirect()->route('dashboard')->with('success', "Now managing: {$domain->name}");
        }

        return redirect()->route('domains.select')->with('success', "Domain \"{$domain->name}\" added.");
    }

    public function edit(Domain $domain): Response
    {
        return Inertia::render('Domains/Edit', [
            'domain' => [
                'id'           => $domain->id,
                'name'         => $domain->name,
                'domain'       => $domain->domain,
                'frontend_url' => $domain->frontend_url,
                'db_host'      => $domain->db_host,
                'db_port'      => $domain->db_port,
                'db_name'      => $domain->db_name,
                'db_username'  => $domain->db_username,
                'is_active'    => $domain->is_active,
                'is_default'   => $domain->is_default,
            ],
        ]);
    }

    public function update(Request $request, Domain $domain): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'domain'       => "required|string|max:255|unique:mysql.domains,domain,{$domain->id}",
            'frontend_url' => 'nullable|string|max:500',
            'db_host'      => 'required|string|max:255',
            'db_port'      => 'required|integer|min:1|max:65535',
            'db_name'      => 'required|string|max:255',
            'db_username'  => 'required|string|max:255',
            'db_password'  => 'nullable|string',
            'is_active'    => 'boolean',
            'is_default'   => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            Domain::where('is_default', true)->where('id', '!=', $domain->id)->update(['is_default' => false]);
        }

        // Only update password if a new one was entered
        if (empty($data['db_password'])) {
            unset($data['db_password']);
        } else {
            $data['db_password'] = encrypt($data['db_password']);
        }

        $domain->update($data);

        return redirect()->route('domains.index')->with('success', "Domain \"{$domain->name}\" updated.");
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        if ($domain->is_default) {
            return back()->with('error', 'Cannot delete the default domain.');
        }
        $name = $domain->name;
        $domain->delete();

        if (session('active_domain_id') === $domain->id) {
            session()->forget('active_domain_id');
        }

        return redirect()->route('domains.index')->with('success', "Domain \"{$name}\" removed.");
    }

    /** Switch the active domain for the current admin session. */
    public function switchDomain(Request $request): RedirectResponse
    {
        $id       = $request->input('domain_id');
        $redirect = $request->input('redirect', 'back'); // 'dashboard' | 'back'

        if (!$id) {
            session()->forget('active_domain_id');
            $msg = 'Switched to master database.';
        } else {
            $domain = Domain::where('id', $id)->where('is_active', true)->firstOrFail();
            session(['active_domain_id' => $domain->id]);
            $msg = "Now managing: {$domain->name}";
        }

        return $redirect === 'dashboard'
            ? redirect()->route('dashboard')->with('success', $msg)
            : back()->with('success', $msg);
    }

    /**
     * Run migrations on the selected domain's database so it gets
     * the same schema as the master DB (one-click setup for new domains).
     */
    public function syncSchema(Domain $domain): RedirectResponse
    {
        try {
            config(['database.connections.tenant' => $domain->connectionConfig()]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force'    => true,
                '--path'     => 'database/migrations/tenant',
            ]);

            $output = Artisan::output();
            return back()->with('success', "Schema synced for {$domain->domain}. " . trim($output));
        } catch (\Throwable $e) {
            return back()->with('error', 'Schema sync failed: ' . $e->getMessage());
        }
    }
}
