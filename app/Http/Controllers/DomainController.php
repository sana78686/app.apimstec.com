<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Support\TenantArtisanDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DomainController extends Controller
{
    /**
     * Test raw DB credentials (from Add/Edit form before saving).
     * Returns JSON so Vue can show inline result without page reload.
     */
    public function testConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_name'     => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'required|string',
        ]);

        return $this->connectionTestJsonResponse(
            $data['db_host'], $data['db_port'],
            $data['db_name'], $data['db_username'], $data['db_password']
        );
    }

    /**
     * Test the saved (encrypted) credentials of an existing domain.
     */
    public function testSavedConnection(Domain $domain): \Illuminate\Http\JsonResponse
    {
        $cfg = $domain->connectionConfig();

        return $this->connectionTestJsonResponse(
            $cfg['host'], $cfg['port'],
            $cfg['database'], $cfg['username'], $cfg['password']
        );
    }

    /**
     * @return array{0: bool, 1: string} Whether the connection succeeded, and a status or error message.
     */
    private function verifyDatabaseConnection(string $host, int $port, string $db, string $user, string $pass): array
    {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            return [true, 'Connection successful — credentials are correct!'];
        } catch (\PDOException $e) {
            return [false, $e->getMessage()];
        }
    }

    private function connectionTestJsonResponse(string $host, int $port, string $db, string $user, string $pass): \Illuminate\Http\JsonResponse
    {
        [$ok, $message] = $this->verifyDatabaseConnection($host, $port, $db, $user, $pass);

        return response()->json(['success' => $ok, 'message' => $message]);
    }

    /** @throws ValidationException */
    private function assertDatabaseCredentialsWork(string $host, int $port, string $db, string $user, string $pass): void
    {
        [$ok, $message] = $this->verifyDatabaseConnection($host, $port, $db, $user, $pass);

        if (! $ok) {
            throw ValidationException::withMessages([
                'db_connection' => ['Could not connect to the database. '.$message],
            ]);
        }
    }

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
                'id'             => $d->id,
                'name'           => $d->name,
                'domain'         => $d->domain,
                'frontend_url'   => $d->frontend_url,
                'db_host'        => $d->db_host,
                'db_name'        => $d->db_name,
                'is_active'      => $d->is_active,
                'is_default'     => $d->is_default,
                /** Schema buttons only for a separate site DB — never the CMS master */
                'can_run_schema' => ! $d->targetsMasterDatabase(),
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

        $this->assertDatabaseCredentialsWork(
            $data['db_host'],
            (int) $data['db_port'],
            $data['db_name'],
            $data['db_username'],
            $data['db_password'],
        );

        if (!empty($data['is_default'])) {
            Domain::where('is_default', true)->update(['is_default' => false]);
        }

        $data['db_password'] = encrypt($data['db_password']);
        $domain = Domain::create($data);

        // auto_select=true: immediately activate this domain and go to dashboard
        if ($request->boolean('auto_select')) {
            session(['active_domain_id' => $domain->id]);
            $this->syncTenantEnvFile($domain);

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

        $plainPassword = $data['db_password'] ?? '';
        if ($plainPassword === '') {
            $plainPassword = $domain->decryptedPassword();
        }

        $this->assertDatabaseCredentialsWork(
            $data['db_host'],
            (int) $data['db_port'],
            $data['db_name'],
            $data['db_username'],
            $plainPassword,
        );

        // Only update password if a new one was entered
        if (empty($data['db_password'])) {
            unset($data['db_password']);
        } else {
            $data['db_password'] = encrypt($data['db_password']);
        }

        $domain->update($data);

        if ((int) session('active_domain_id') === (int) $domain->id) {
            $this->syncTenantEnvFile($domain->fresh());
        }

        return redirect()->route('domains.index')->with('success', "Domain \"{$domain->name}\" updated.");
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        if ($domain->is_default) {
            return back()->with('error', 'Cannot delete the default domain.');
        }
        $name = $domain->name;
        $domain->delete();

        if ((int) session('active_domain_id') === (int) $domain->id) {
            session()->forget('active_domain_id');
            $this->syncTenantEnvFile(null);
        }

        return redirect()->route('domains.index')->with('success', "Domain \"{$name}\" removed.");
    }

    /** Switch the active domain for the current admin session. */
    public function switchDomain(Request $request): RedirectResponse
    {
        $id       = $request->input('domain_id');
        $redirect = $request->input('redirect', 'back'); // 'dashboard' | 'back'

        if (! $id) {
            $request->session()->forget('active_domain_id');
            $this->syncTenantEnvFile(null);

            return redirect()
                ->route('domains.select')
                ->with('success', 'Select a website to manage its content.');
        }

        $domain = Domain::where('id', $id)->where('is_active', true)->firstOrFail();
        session(['active_domain_id' => $domain->id]);
        $this->syncTenantEnvFile($domain);
        $msg = "Now managing: {$domain->name}";

        return $redirect === 'dashboard'
            ? redirect()->route('dashboard')->with('success', $msg)
            : back()->with('success', $msg);
    }

    /**
     * Mirror active site DB into .env as CMS_TENANT_* (optional single-server workflow).
     */
    private function syncTenantEnvFile(?Domain $domain): void
    {
        TenantArtisanDatabase::syncEnvToFile($domain);
    }

    /**
     * Run pending migrations on the domain's database — safe, no data loss.
     * Adds any new tables/columns that don't exist yet.
     */
    public function syncSchema(Domain $domain): RedirectResponse
    {
        try {
            TenantArtisanDatabase::prepare($domain);

            Artisan::call('migrate', [
                '--database' => TenantArtisanDatabase::CONNECTION,
                '--force'    => true,
            ]);

            $output = Artisan::output();
            $target = TenantArtisanDatabase::label($domain);

            return back()->with('success', "Schema synced for \"{$domain->name}\" on {$target}. ".trim($output));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Schema sync failed: '.$e->getMessage());
        } finally {
            TenantArtisanDatabase::restore();
        }
    }

    /**
     * Drop all tables in the domain's database, then re-run all migrations.
     * Uses manual table dropping instead of `migrate:fresh` to avoid the
     * default-connection seeder issue (seeder runs on mysql, not tenant).
     * USE WITH CAUTION — destroys all data in the tenant DB.
     */
    public function migrateFresh(Domain $domain): RedirectResponse
    {
        try {
            TenantArtisanDatabase::prepare($domain);
            $conn = DB::connection(TenantArtisanDatabase::CONNECTION);

            // Disable FK checks so we can drop tables in any order
            $conn->statement('SET FOREIGN_KEY_CHECKS=0');
            $tables = $conn->select('SHOW TABLES');
            foreach ($tables as $row) {
                $table = array_values((array) $row)[0];
                $conn->statement('DROP TABLE IF EXISTS `'.$table.'`');
            }
            $conn->statement('SET FOREIGN_KEY_CHECKS=1');

            Artisan::call('migrate', [
                '--database' => TenantArtisanDatabase::CONNECTION,
                '--force'    => true,
            ]);

            $output  = trim(Artisan::output());
            $summary = $output ?: 'All migrations ran successfully.';
            $target  = TenantArtisanDatabase::label($domain);

            return back()->with('success', "Fresh migrate complete for \"{$domain->name}\" on {$target}. All tables rebuilt. {$summary}");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Fresh migration failed: '.$e->getMessage());
        } finally {
            TenantArtisanDatabase::restore();
        }
    }
    /**
     * Roll back the last batch of migrations on the domain's database.
     */
    public function rollbackSchema(Domain $domain): RedirectResponse
    {
        try {
            TenantArtisanDatabase::prepare($domain);

            Artisan::call('migrate:rollback', [
                '--database' => TenantArtisanDatabase::CONNECTION,
                '--force'    => true,
            ]);

            $output  = trim(Artisan::output());
            $summary = $output ?: 'Last migration batch rolled back.';
            $target  = TenantArtisanDatabase::label($domain);

            return back()->with('success', "Rollback complete for \"{$domain->name}\" on {$target}. {$summary}");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Rollback failed: '.$e->getMessage());
        } finally {
            TenantArtisanDatabase::restore();
        }
    }
}
