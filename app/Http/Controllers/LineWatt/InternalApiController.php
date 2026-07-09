<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Models\CompiledDeviceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalApiController extends Controller
{
    public function health(Request $request): JsonResponse
    {
        $application = $request->attributes->get('internal_application');

        return response()->json([
            'status' => 'ok',
            'application' => $application?->name,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = CompiledDeviceRecord::query()
            ->where('source_type', 'central_curated')
            ->where('status', 'published');

        if ($deviceType = $request->query('device_type')) {
            $query->where('device_type', $deviceType);
        }

        if ($manufacturer = $request->query('manufacturer')) {
            $query->whereRaw('lower(manufacturer) like ?', ['%'.strtolower((string) $manufacturer).'%']);
        }

        if ($keyword = $request->query('q')) {
            $query->where(function ($inner) use ($keyword): void {
                $like = '%'.strtolower((string) $keyword).'%';

                $inner->whereRaw('lower(manufacturer) like ?', [$like])
                    ->orWhereRaw('lower(model_series) like ?', [$like])
                    ->orWhereRaw('lower(model_name) like ?', [$like])
                    ->orWhereRaw('lower(display_name) like ?', [$like])
                    ->orWhereRaw('lower(technology) like ?', [$like]);
            });
        }

        $records = $query
            ->latest()
            ->limit(min((int) $request->query('limit', 25), 50))
            ->get()
            ->map(fn (CompiledDeviceRecord $record) => $this->recordPayload($record));

        return response()->json([
            'data' => $records,
        ]);
    }

    public function record(string $record): JsonResponse
    {
        $compiledRecord = CompiledDeviceRecord::query()
            ->where('source_type', 'central_curated')
            ->where('status', 'published')
            ->where(function ($query) use ($record): void {
                $query->where('uuid', $record);

                if (ctype_digit($record)) {
                    $query->orWhere('id', (int) $record);
                }
            })
            ->firstOrFail();

        return response()->json([
            'data' => $this->recordPayload($compiledRecord),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function recordPayload(CompiledDeviceRecord $record): array
    {
        return [
            'uuid' => $record->uuid,
            'device_type' => $record->device_type,
            'manufacturer' => $record->manufacturer,
            'series' => $record->series,
            'family' => $record->family,
            'technology' => $record->technology,
            'model_series' => $record->model_series,
            'model_name' => $record->model_name,
            'display_name' => $record->display_name,
            'power_class_w' => $record->power_class_w,
            'power_class_kw' => $record->power_class_kw,
            'validation_grade' => $record->validation_grade,
            'validation_status' => $record->validation_status,
            'status' => $record->status,
            'updated_at' => optional($record->updated_at)->toIso8601String(),
        ];
    }
}
