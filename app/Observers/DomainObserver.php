<?php

namespace App\Observers;

use App\Models\Domain;
use App\Support\CorsAllowedOriginsBuilder;

class DomainObserver
{
    public function saved(Domain $domain): void
    {
        CorsAllowedOriginsBuilder::forgetCache();
    }

    public function deleted(Domain $domain): void
    {
        CorsAllowedOriginsBuilder::forgetCache();
    }

    public function restored(Domain $domain): void
    {
        CorsAllowedOriginsBuilder::forgetCache();
    }
}
