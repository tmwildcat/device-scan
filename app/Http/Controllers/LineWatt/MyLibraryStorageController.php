<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Storage\PrivateStorageUsageCalculator;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MyLibraryStorageController extends Controller
{
    public function __invoke(Request $request, PrivateStorageUsageCalculator $storage): Response
    {
        $usage = $storage->forUser($request->user());

        return Inertia::render('LineWatt/MyLibraryStorage', [
            'summary' => $usage['summary'],
            'breakdown' => $usage['breakdown'],
            'items' => $usage['items']->values(),
            'futureActions' => [
                'archive' => 'Archive',
                'empty_trash' => 'Empty Trash',
            ],
        ]);
    }

    public function destroy(Request $request, string $item): RedirectResponse
    {
        $this->deleteItem($request, $item, $request->boolean('delete_dependents'));

        return back()->with('status', 'Storage item deleted.');
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['string'],
            'delete_dependents' => ['boolean'],
        ]);

        foreach ($validated['items'] as $item) {
            $this->deleteItem($request, $item, (bool) ($validated['delete_dependents'] ?? false));
        }

        return back()->with('status', 'Selected storage items deleted.');
    }

    private function deleteItem(Request $request, string $item, bool $deleteDependents): void
    {
        [$type, $identifier] = array_pad(explode(':', $item, 2), 2, null);

        if ($type === 'record' && $identifier) {
            $record = $this->privateRecords($request)->where(function ($query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })->firstOrFail();

            $this->deleteArtifact($record->compiled_disk, $record->compiled_path);
            $record->delete();

            return;
        }

        if ($type === 'datasheet' && $identifier) {
            $datasheet = $this->privateDatasheets($request)
                ->with('compiledRecords')
                ->where(function ($query) use ($identifier): void {
                    $query->where('uuid', $identifier);
                    if (ctype_digit($identifier)) {
                        $query->orWhere('id', (int) $identifier);
                    }
                })
                ->firstOrFail();

            if ($datasheet->compiledRecords->isNotEmpty() && ! $deleteDependents) {
                abort(422, 'This PDF has dependent Engineering Records. Choose dependent deletion explicitly.');
            }

            foreach ($datasheet->compiledRecords as $record) {
                $this->deleteArtifact($record->compiled_disk, $record->compiled_path);
                $record->delete();
            }

            $this->deleteArtifact($datasheet->datasheet_disk, $datasheet->datasheet_path);
            $datasheet->delete();
        }
    }

    private function privateRecords(Request $request)
    {
        return CompiledDeviceRecord::query()
            ->whereIn('source_type', $this->privateSourceTypes())
            ->where(function ($query) use ($request): void {
                $query->where('tenant_id', $request->user()?->id)->orWhereNull('tenant_id');
            });
    }

    private function privateDatasheets(Request $request)
    {
        return DeviceDatasheet::query()
            ->whereIn('source_type', $this->privateSourceTypes())
            ->where(function ($query) use ($request): void {
                $query->where('tenant_id', $request->user()?->id)->orWhereNull('tenant_id');
            });
    }

    private function deleteArtifact(?string $disk, ?string $path): void
    {
        if (! $disk || ! $path) {
            return;
        }

        Storage::disk($disk)->delete($path);
    }

    /**
     * @return list<string>
     */
    private function privateSourceTypes(): array
    {
        return ['tenant_private', 'pvsyst_import'];
    }
}
