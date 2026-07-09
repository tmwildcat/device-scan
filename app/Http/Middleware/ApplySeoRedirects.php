<?php

namespace App\Http\Middleware;

use App\Models\SeoRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ApplySeoRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && Schema::hasTable('seo_redirects')) {
            $path = '/'.ltrim($request->path(), '/');
            $redirect = SeoRedirect::query()
                ->where('active', true)
                ->where('source_path', $path)
                ->first();

            if ($redirect !== null) {
                return redirect($redirect->target_path, $redirect->status_code);
            }
        }

        return $next($request);
    }
}
