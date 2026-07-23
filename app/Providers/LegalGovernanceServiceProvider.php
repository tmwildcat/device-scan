<?php

namespace App\Providers;

use App\LegalGovernance\Adapters\LineWattLegalPdfRenderer;
use App\LegalGovernance\Contracts\LegalAuditContract;
use App\LegalGovernance\Contracts\LegalIdentityResolverContract;
use App\LegalGovernance\Contracts\LegalPdfRendererContract;
use App\LegalGovernance\Services\LegalAuditService;
use Illuminate\Support\ServiceProvider;

final class LegalGovernanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('legal-governance.php'), 'legal-governance');
        $this->app->bind(LegalIdentityResolverContract::class, config('legal-governance.identity_resolver'));
        $this->app->bind(LegalPdfRendererContract::class, LineWattLegalPdfRenderer::class);
        $this->app->bind(LegalAuditContract::class, LegalAuditService::class);
    }

    public function boot(): void {}
}
