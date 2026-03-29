<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Load domains + active domain once per page (shared to all Inertia pages)
        // Wrapped in try/catch — domains table may not exist yet on a fresh deployment
        $activeDomain = null;
        $allDomains   = collect();
        try {
            $activeDomainId = $request->session()->get('active_domain_id');
            $activeDomain   = $activeDomainId
                ? Domain::where('id', $activeDomainId)->first(['id', 'name', 'domain', 'frontend_url'])
                : null;

            $allDomains = $request->user()
                ? Domain::where('is_active', true)->orderByDesc('is_default')->orderBy('name')
                    ->get(['id', 'name', 'domain', 'frontend_url'])
                : collect();
        } catch (\Throwable $e) {
            // Domains table may not exist yet (migration pending) — fail gracefully
        }

        return [
            ...parent::share($request),
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
            'domains'       => $allDomains,
            'activeDomain'  => $activeDomain,
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'email_verified_at' => $request->user()->email_verified_at,
                    'roles' => $request->user()->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'slug' => $r->slug]),
                    'permissions' => $request->user()->isAdmin()
                        ? ['*']
                        : $request->user()->roles->flatMap->permissions->pluck('slug')->unique()->values()->all(),
                ] : null,
            ],
        ];
    }
}
