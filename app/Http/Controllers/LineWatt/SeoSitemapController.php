<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Seo\SitemapBuilder;
use Illuminate\Http\Response;

class SeoSitemapController extends Controller
{
    public function __construct(private readonly SitemapBuilder $sitemaps) {}

    public function index(): Response
    {
        return response($this->sitemaps->index(), 200)->header('Content-Type', 'application/xml');
    }

    public function manufacturers(): Response
    {
        return $this->urlSet('manufacturer');
    }

    public function datasheets(): Response
    {
        return $this->urlSet('datasheet');
    }

    public function models(): Response
    {
        return $this->urlSet('model');
    }

    public function technology(): Response
    {
        return $this->urlSet('technology');
    }

    public function applications(): Response
    {
        return $this->urlSet('application');
    }

    private function urlSet(string $kind): Response
    {
        return response($this->sitemaps->urlSet($kind), 200)->header('Content-Type', 'application/xml');
    }
}
