<?php

use App\Http\Controllers\LineWatt\InternalApiController;
use App\Http\Controllers\LineWatt\McpGatewayController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal')
    ->middleware('throttle:60,1')
    ->group(function (): void {
        Route::get('/health', [InternalApiController::class, 'health'])
            ->middleware('internal.app')
            ->name('api.internal.health');

        Route::get('/library/search', [InternalApiController::class, 'search'])
            ->middleware('internal.app:library.search')
            ->name('api.internal.library.search');

        Route::get('/library/records/{record}', [InternalApiController::class, 'record'])
            ->middleware('internal.app:library.view_record')
            ->name('api.internal.library.records.show');

        Route::get('/mcp/tools', [McpGatewayController::class, 'tools'])
            ->middleware('internal.app:mcp.tools')
            ->name('api.internal.mcp.tools');

        Route::post('/mcp/call', [McpGatewayController::class, 'call'])
            ->middleware('internal.app:mcp.tools')
            ->name('api.internal.mcp.call');
    });
