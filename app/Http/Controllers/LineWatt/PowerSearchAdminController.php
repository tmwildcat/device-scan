<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Models\CompiledDeviceRecord;
use App\Models\PowerSearchCategory;
use App\Models\PowerSearchOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PowerSearchAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LineWatt/PowerSearchAdmin', [
            'categories' => PowerSearchCategory::query()
                ->with(['options' => fn ($query) => $query->orderBy('sort_order')->orderBy('label')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'recentAssignments' => CompiledDeviceRecord::query()
                ->with('powerSearchOptions.category')
                ->whereHas('powerSearchOptions')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => [
                    'id' => $record->id,
                    'display_name' => $record->display_name ?: $record->model_name ?: $record->model_series,
                    'manufacturer' => $record->manufacturer,
                    'tags' => $record->powerSearchOptions->map(fn (PowerSearchOption $option): string => $option->label)->values()->all(),
                ]),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'scope' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        PowerSearchCategory::query()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'scope' => $validated['scope'] ?: 'all',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Power Search category created.');
    }

    public function storeOption(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'power_search_category_id' => ['required', 'exists:power_search_categories,id'],
            'label' => ['required', 'string', 'max:160'],
            'scope' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:80'],
            'subtype' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reference_source' => ['nullable', 'string', 'max:255'],
        ]);

        PowerSearchOption::query()->create([
            ...$validated,
            'slug' => Str::slug($validated['label']),
            'scope' => $validated['scope'] ?: 'all',
            'is_active' => true,
        ]);

        return back()->with('success', 'Power Search option created.');
    }

    public function assign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'compiled_device_record_id' => ['required', 'exists:compiled_device_records,id'],
            'power_search_option_ids' => ['array'],
            'power_search_option_ids.*' => ['integer', 'exists:power_search_options,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = CompiledDeviceRecord::query()->findOrFail($validated['compiled_device_record_id']);
        $sync = collect($validated['power_search_option_ids'] ?? [])
            ->mapWithKeys(fn (int $id): array => [$id => [
                'source' => 'curated',
                'notes' => $validated['notes'] ?? null,
                'assigned_by' => $request->user()?->id,
                'assigned_at' => now(),
            ]])
            ->all();

        $record->powerSearchOptions()->syncWithoutDetaching($sync);

        return back()->with('success', 'Power Search tags assigned.');
    }
}
