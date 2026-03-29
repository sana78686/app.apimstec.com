<?php

namespace App\Http\Controllers;

use App\Models\DomainCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CredentialController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DomainCredential::orderBy('domain');
        if ($request->filled('search')) {
            $query->where('domain', 'like', '%' . $request->search . '%');
        }
        $paginator = $query->paginate(15)->withQueryString()->through(function ($c) {
            return [
                'id' => $c->id,
                'domain' => $c->domain,
                'email_credentials' => $this->summarizeCredentialArrays($c->email_credentials ?? [], false),
                'plesk_credentials' => $this->summarizeCredentialArrays($c->plesk_credentials ?? [], false),
                'website_credentials' => $this->summarizeCredentialArrays($c->website_credentials ?? [], false),
                'portal_credentials' => $this->summarizeCredentialArrays($c->portal_credentials ?? [], true),
                'notes' => $c->notes,
            ];
        });

        return Inertia::render('Credentials/Index', [
            'credentials' => $paginator,
            'search' => $request->search ?? '',
            'flash' => ['success' => session('success')],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Credentials/Create', []);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'email_credentials' => 'nullable|array',
            'email_credentials.*.username' => 'nullable|string|max:255',
            'email_credentials.*.password' => 'nullable|string|max:2048',
            'plesk_credentials' => 'nullable|array',
            'plesk_credentials.*.username' => 'nullable|string|max:255',
            'plesk_credentials.*.password' => 'nullable|string|max:2048',
            'website_credentials' => 'nullable|array',
            'website_credentials.*.username' => 'nullable|string|max:255',
            'website_credentials.*.password' => 'nullable|string|max:2048',
            'portal_credentials' => 'nullable|array',
            'portal_credentials.*.url' => 'nullable|string|max:2048',
            'portal_credentials.*.username' => 'nullable|string|max:255',
            'portal_credentials.*.password' => 'nullable|string|max:2048',
            'notes' => 'nullable|string|max:4096',
        ]);

        $credential = new DomainCredential;
        $credential->domain = $validated['domain'];
        $credential->notes = $validated['notes'] ?? null;
        $credential->email_credentials = $this->encryptCredentialArrays($validated['email_credentials'] ?? [], [], false);
        $credential->plesk_credentials = $this->encryptCredentialArrays($validated['plesk_credentials'] ?? [], [], false);
        $credential->website_credentials = $this->encryptCredentialArrays($validated['website_credentials'] ?? [], [], false);
        $credential->portal_credentials = $this->encryptCredentialArrays($validated['portal_credentials'] ?? [], [], true);
        $credential->save();

        return redirect()->route('credentials.index')->with('success', 'Credential saved.');
    }

    public function edit(DomainCredential $credential): Response
    {
        return Inertia::render('Credentials/Edit', [
            'credential' => [
                'id' => $credential->id,
                'domain' => $credential->domain,
                'email_credentials' => $this->formatForForm($credential->email_credentials ?? [], false),
                'plesk_credentials' => $this->formatForForm($credential->plesk_credentials ?? [], false),
                'website_credentials' => $this->formatForForm($credential->website_credentials ?? [], false),
                'portal_credentials' => $this->formatForForm($credential->portal_credentials ?? [], true),
                'notes' => $credential->notes,
            ],
        ]);
    }

    public function update(Request $request, DomainCredential $credential): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'email_credentials' => 'nullable|array',
            'email_credentials.*.username' => 'nullable|string|max:255',
            'email_credentials.*.password' => 'nullable|string|max:2048',
            'plesk_credentials' => 'nullable|array',
            'plesk_credentials.*.username' => 'nullable|string|max:255',
            'plesk_credentials.*.password' => 'nullable|string|max:2048',
            'website_credentials' => 'nullable|array',
            'website_credentials.*.username' => 'nullable|string|max:255',
            'website_credentials.*.password' => 'nullable|string|max:2048',
            'portal_credentials' => 'nullable|array',
            'portal_credentials.*.url' => 'nullable|string|max:2048',
            'portal_credentials.*.username' => 'nullable|string|max:255',
            'portal_credentials.*.password' => 'nullable|string|max:2048',
            'notes' => 'nullable|string|max:4096',
        ]);

        $credential->domain = $validated['domain'];
        $credential->notes = $validated['notes'] ?? null;
        $credential->email_credentials = $this->encryptCredentialArrays(
            $validated['email_credentials'] ?? [],
            $credential->email_credentials ?? [],
            false
        );
        $credential->plesk_credentials = $this->encryptCredentialArrays(
            $validated['plesk_credentials'] ?? [],
            $credential->plesk_credentials ?? [],
            false
        );
        $credential->website_credentials = $this->encryptCredentialArrays(
            $validated['website_credentials'] ?? [],
            $credential->website_credentials ?? [],
            false
        );
        $credential->portal_credentials = $this->encryptCredentialArrays(
            $validated['portal_credentials'] ?? [],
            $credential->portal_credentials ?? [],
            true
        );
        $credential->save();

        return redirect()->route('credentials.index')->with('success', 'Credential updated.');
    }

    public function destroy(DomainCredential $credential): RedirectResponse
    {
        $credential->delete();

        return redirect()->route('credentials.index')->with('success', 'Credential deleted.');
    }

    private function summarizeCredentialArrays(array $items, bool $hasUrl): array
    {
        $out = [];
        foreach ($items as $item) {
            $decryptedPassword = null;
            if (isset($item['password'])) {
                try {
                    $decryptedPassword = \Illuminate\Support\Facades\Crypt::decryptString($item['password']);
                } catch (\Throwable $e) {
                    $decryptedPassword = null;
                }
            }
            $row = ['username' => $item['username'] ?? '', 'password' => $decryptedPassword];
            if ($hasUrl) {
                $row['url'] = $item['url'] ?? '';
            }
            $out[] = $row;
        }
        return $out;
    }

    private function formatForForm(array $items, bool $hasUrl): array
    {
        $out = [];
        foreach ($items as $item) {
            $row = [
                'username' => $item['username'] ?? '',
                'password' => isset($item['password']) ? '••••••••' : '',
            ];
            if ($hasUrl) {
                $row['url'] = $item['url'] ?? '';
            }
            $out[] = $row;
        }
        return $out;
    }

    private function encryptCredentialArrays(array $submitted, array $existing, bool $hasUrl): array
    {
        $out = [];
        foreach ($submitted as $i => $item) {
            $password = $item['password'] ?? '';
            $existingItem = $existing[$i] ?? null;
            $encryptedPassword = null;
            if ($password && $password !== '••••••••') {
                $encryptedPassword = \Illuminate\Support\Facades\Crypt::encryptString($password);
            } elseif ($existingItem && isset($existingItem['password'])) {
                $encryptedPassword = $existingItem['password'];
            }
            $row = ['username' => trim($item['username'] ?? '')];
            if ($encryptedPassword) {
                $row['password'] = $encryptedPassword;
            }
            if ($hasUrl) {
                $row['url'] = trim($item['url'] ?? '');
            }
            $out[] = $row;
        }
        return array_filter($out, fn ($r) => ($r['username'] ?? '') !== '' || ($r['url'] ?? '') !== '' || isset($r['password']));
    }
}
