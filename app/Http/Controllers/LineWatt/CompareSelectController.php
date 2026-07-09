<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class CompareSelectController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function __invoke(Request $request): Response
    {
        $records = [
            'data' => [],
            'current_page' => 1,
            'from' => null,
            'last_page' => 1,
            'per_page' => 20,
            'to' => null,
            'total' => 0,
            'links' => [],
        ];
        $seedRecord = null;
        $selectedRecords = collect();
        $q = trim((string) $request->query('q', ''));
        $deviceType = (string) $request->query('device_type', 'module');
        $deviceType = in_array($deviceType, ['module', 'inverter'], true) ? $deviceType : 'module';

        if (Schema::hasTable('compiled_device_records')) {
            $seed = trim((string) $request->query('seed', ''));
            $selectedTokens = $this->identifierTokens((string) $request->query('selected', ''));

            if ($seed !== '') {
                $seedRecord = $this->visibleRecordQuery($request)
                    ->where(fn (Builder $query) => $this->whereIdentifiers($query, [$seed]))
                    ->first();

                if ($seedRecord?->device_type && in_array($seedRecord->device_type, ['module', 'inverter'], true)) {
                    $deviceType = $seedRecord->device_type;
                }
            }

            if ($seedRecord) {
                $selectedTokens[] = (string) ($seedRecord->uuid ?: $seedRecord->id);
            }

            if ($selectedTokens !== []) {
                $selectedRecords = $this->visibleRecordQuery($request)
                    ->where(fn (Builder $query) => $this->whereIdentifiers($query, $selectedTokens))
                    ->where('device_type', $deviceType)
                    ->limit(3)
                    ->get();
            }

            $builder = $this->visibleRecordQuery($request)->where('device_type', $deviceType);

            if ($q !== '') {
                $like = '%'.mb_strtolower($q).'%';
                $builder->where(function (Builder $search) use ($like): void {
                    $search
                        ->whereRaw('LOWER(manufacturer) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(model_series) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(model_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(display_name) LIKE ?', [$like]);
                });
            }

            $records = $builder
                ->latest()
                ->paginate(20)
                ->withQueryString()
                ->through(fn (CompiledDeviceRecord $record): array => $this->recordSummary($record))
                ->toArray();
        }

        return Inertia::render('LineWatt/CompareSelect', [
            'records' => $records,
            'seedRecord' => $seedRecord ? $this->recordSummary($seedRecord) : null,
            'selectedRecords' => $selectedRecords->map(fn (CompiledDeviceRecord $record): array => $this->recordSummary($record))->values(),
            'filters' => [
                'q' => $q,
                'device_type' => $deviceType,
            ],
        ]);
    }

    /**
     * @return Builder<CompiledDeviceRecord>
     */
    private function visibleRecordQuery(Request $request): Builder
    {
        $user = $request->user();

        return CompiledDeviceRecord::query()
            ->with('datasheet')
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $central): void {
                    $central
                        ->where('source_type', 'central_curated')
                        ->whereIn('status', ['published', 'approved', 'compiled', 'review_required']);
                });

                if (! $user) {
                    return;
                }

                if ($user->role === LineWattRole::SUBSCRIBER) {
                    $query->orWhere(function (Builder $tenant) use ($user): void {
                        $tenant->whereIn('source_type', ['tenant_private', 'pvsyst_import'])->where('tenant_id', $user->id);
                    });
                }

                if (in_array($user->role, LineWattRole::partnerRoles(), true)) {
                    $query->orWhere(function (Builder $partner) use ($user): void {
                        $partner->where('source_type', 'partner_submitted')->where('partner_id', $user->id);
                    });
                }
            });
    }

    /**
     * @return list<string>
     */
    private function identifierTokens(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $token): string => trim($token))
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @param list<string> $tokens
     */
    private function whereIdentifiers(Builder $query, array $tokens): void
    {
        $ids = [];
        $uuids = [];

        foreach ($tokens as $token) {
            if (ctype_digit($token)) {
                $ids[] = (int) $token;
            } else {
                $uuids[] = $token;
            }
        }

        $query->where(function (Builder $identifierQuery) use ($ids, $uuids): void {
            if ($ids !== []) {
                $identifierQuery->whereIn('id', $ids);
            }

            if ($uuids !== []) {
                $method = $ids === [] ? 'whereIn' : 'orWhereIn';
                $identifierQuery->{$method}('uuid', $uuids);
            }
        });
    }
}
