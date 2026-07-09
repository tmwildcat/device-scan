<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inverter Compiler Debug</title>
    <style>
        body { margin: 0; background: #f7f7f5; color: #1f2933; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        main { max-width: 1280px; margin: 0 auto; padding: 24px; }
        h1 { font-size: 24px; margin: 0 0 8px; }
        h2 { font-size: 18px; margin: 24px 0 12px; }
        form, section { background: #fff; border: 1px solid #d8dee4; border-radius: 6px; padding: 16px; margin-bottom: 16px; }
        select, button { font: inherit; padding: 8px 10px; }
        select { min-width: min(760px, 100%); }
        table { width: 100%; border-collapse: collapse; background: #fff; font-size: 13px; }
        th, td { border: 1px solid #d8dee4; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #eef2f6; }
        .muted { color: #65727f; }
        .warning { background: #fff7e6; border-color: #f4cf88; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
        .summary div { border: 1px solid #d8dee4; border-radius: 6px; padding: 10px; background: #fbfcfd; }
        pre { overflow: auto; max-height: 560px; background: #101820; color: #d5e7ff; border-radius: 6px; padding: 14px; }
        details summary { cursor: pointer; font-weight: 700; }
    </style>
</head>
<body>
<main>
    <h1>Inverter Compiler Debug</h1>
    <p class="muted">Temporary development page for inverter corpus PDFs.</p>

    <form method="get" action="{{ route('device-scan.debug.inverter-compiler') }}">
        <label for="file">Inverter corpus PDF</label><br>
        <select id="file" name="file">
            <option value="">Select a PDF...</option>
            @foreach ($files as $file)
                <option value="{{ $file }}" @selected($file === $selected)>{{ $file }}</option>
            @endforeach
        </select>
        <button type="submit">Run compiler</button>
    </form>

    @if ($error)
        <section class="warning">{{ $error }}</section>
    @endif

    @if ($dto)
        <section>
            <h2>Summary</h2>
            <div class="summary">
                <div><strong>Manufacturer</strong><br>{{ $dto->manufacturer ?? '—' }}</div>
                <div><strong>Series</strong><br>{{ $dto->series ?? '—' }}</div>
                <div><strong>Model series</strong><br>{{ $dto->modelSeries ?? '—' }}</div>
                <div><strong>Model name</strong><br>{{ $dto->modelName ?? '—' }}</div>
                <div><strong>Power class</strong><br>{{ $dto->powerClassKw !== null ? $dto->powerClassKw.' kW' : '—' }}</div>
                <div><strong>Display name</strong><br>{{ $dto->displayName ?? '—' }}</div>
                <div><strong>Device type</strong><br>{{ $dto->deviceType ?? '—' }}</div>
                <div><strong>Models</strong><br>{{ count($dto->models) }}</div>
                <div><strong>Warnings</strong><br>{{ count($dto->extractionWarnings) }}</div>
                <div><strong>Quality</strong><br>{{ $dto->extractionQualityGrade ?? '—' }} {{ $dto->extractionQualityScore !== null ? '('.$dto->extractionQualityScore.')' : '' }}</div>
            </div>
            @if ($dto->unsupportedReason)
                <p class="muted">Unsupported reason: {{ $dto->unsupportedReason }}</p>
            @endif
            @if ($dto->extractionQualityReasons !== [])
                <ul class="muted">
                    @foreach ($dto->extractionQualityReasons as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section @class(['warning' => ($dto->validation?->countBySeverity('error') ?? 0) > 0 || ($dto->validation?->countBySeverity('warning') ?? 0) > 0])>
            <h2>Validation</h2>
            @php($issuesBySeverity = collect($dto->validation?->issues ?? [])->groupBy('severity'))
            @php($validationProtection = $dto->protection?->toArray())
            <div class="summary">
                <div><strong>Errors</strong><br>{{ $dto->validation?->countBySeverity('error') ?? 0 }}</div>
                <div><strong>Warnings</strong><br>{{ $dto->validation?->countBySeverity('warning') ?? 0 }}</div>
                <div><strong>Info</strong><br>{{ $dto->validation?->countBySeverity('info') ?? 0 }}</div>
                <div><strong>DC checked</strong><br>{{ $dto->validation?->summary['dc_models_checked'] ?? 0 }}</div>
                <div><strong>AC checked</strong><br>{{ $dto->validation?->summary['ac_models_checked'] ?? 0 }}</div>
                <div><strong>Protection fields</strong><br>{{ $validationProtection ? collect($validationProtection)->except('metadata')->filter()->count() : 0 }}</div>
            </div>
            @forelse (['error', 'warning', 'info'] as $severity)
                @if (($issuesBySeverity[$severity] ?? collect())->isNotEmpty())
                    <h3>{{ ucfirst($severity) }}</h3>
                    <table>
                        <thead>
                        <tr>
                            <th>Code</th>
                            <th>Message</th>
                            <th>Model</th>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($issuesBySeverity[$severity] as $issue)
                            <tr>
                                <td>{{ $issue->code }}</td>
                                <td>{{ $issue->message }}</td>
                                <td>{{ $issue->modelName ?? '—' }}</td>
                                <td>{{ $issue->field ?? '—' }}</td>
                                <td>{{ is_array($issue->value) ? json_encode($issue->value) : ($issue->value ?? '—') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            @empty
                <p class="muted">No validation issues.</p>
            @endforelse
            @if (($dto->validation?->issues ?? []) === [])
                <p class="muted">No validation issues.</p>
            @endif
        </section>

        <section>
            <h2>Models</h2>
            @if ($dto->models !== [])
                <p>{{ implode(', ', $dto->models) }}</p>
            @else
                <p class="muted">No inverter models extracted.</p>
            @endif
        </section>

        @foreach ([['title' => 'DC Input', 'block' => $dto->dcInput], ['title' => 'AC Output', 'block' => $dto->acOutput]] as $group)
            <section>
                <h2>{{ $group['title'] }}</h2>
                @if ($group['block'] && $group['block']->models !== [])
                    <table>
                        <thead>
                        <tr>
                            <th>Model</th>
                            <th>Fields</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($group['block']->models as $model)
                            <tr>
                                <td>{{ $model->model }}</td>
                                <td>
                                    <table>
                                        <tbody>
                                        @foreach ($model->fields as $field => $value)
                                            <tr>
                                                <th>{{ $field }}</th>
                                                <td>{{ is_array($value->value) ? json_encode($value->value) : $value->value }} {{ $value->unit ?? '' }}</td>
                                                <td class="muted">p{{ $value->sourcePage ?? '—' }} · {{ $value->sourceText }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="muted">No {{ strtolower($group['title']) }} fields extracted.</p>
                @endif
            </section>
        @endforeach

        <section>
            <h2>Rated Power Conditions</h2>
            @if ($dto->ratedPowerConditions !== [])
                <table>
                    <thead>
                    <tr>
                        <th>Power</th>
                        <th>Ambient</th>
                        <th>Condition</th>
                        <th>Source</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($dto->ratedPowerConditions as $condition)
                        <tr>
                            <td>{{ $condition->powerKw ?? '—' }} kW</td>
                            <td>{{ $condition->ambientTemperatureC !== null ? $condition->ambientTemperatureC.' °C' : '—' }}</td>
                            <td>{{ $condition->condition ?? '—' }}</td>
                            <td class="muted">p{{ $condition->source?->sourcePage ?? '—' }} · {{ $condition->source?->sourceText }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No temperature-conditioned rated power rows extracted.</p>
            @endif
        </section>

        <section>
            <h2>Protection</h2>
            @php($protection = $dto->protection?->toArray())
            @if ($protection && collect($protection)->except('metadata')->filter()->isNotEmpty())
                <table>
                    <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                        <th>Source</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($protection as $field => $value)
                        @continue($field === 'metadata' || $value === null)
                        <tr>
                            <th>{{ $field }}</th>
                            <td>
                                @if (is_bool($value['value']))
                                    {{ $value['value'] ? 'yes' : 'no' }}
                                @elseif (is_array($value['value']))
                                    {{ json_encode($value['value']) }}
                                @else
                                    {{ $value['value'] }}
                                @endif
                            </td>
                            <td class="muted">p{{ $value['source_page'] ?? '—' }} · {{ $value['source_text'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No protection fields extracted.</p>
            @endif
        </section>

        <section>
            <h2>Central Specific</h2>
            @php($central = $dto->centralSpecific?->toArray())
            @if ($central && collect($central)->except('metadata')->filter()->isNotEmpty())
                <table>
                    <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                        <th>Source</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($central as $field => $value)
                        @continue($field === 'metadata' || $value === null)
                        <tr>
                            <th>{{ $field }}</th>
                            <td>
                                @if (is_bool($value['value']))
                                    {{ $value['value'] ? 'yes' : 'no' }}
                                @elseif (is_array($value['value']))
                                    {{ json_encode($value['value']) }}
                                @else
                                    {{ $value['value'] }} {{ $value['unit'] ?? '' }}
                                @endif
                            </td>
                            <td class="muted">p{{ $value['source_page'] ?? '—' }} · {{ $value['source_text'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No central-specific fields extracted.</p>
            @endif
        </section>

        <section @class(['warning' => $dto->extractionWarnings !== []])>
            <h2>Extraction Warnings</h2>
            @if ($dto->extractionWarnings !== [])
                <ul>
                    @foreach ($dto->extractionWarnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">No warnings.</p>
            @endif
        </section>

        <section>
            <h2>Detected Sections</h2>
            @if ($dto->sections !== [])
                <table>
                    <thead><tr><th>Type</th><th>Title</th><th>Page</th><th>Lines</th></tr></thead>
                    <tbody>
                    @foreach ($dto->sections as $section)
                        <tr><td>{{ $section->type }}</td><td>{{ $section->title }}</td><td>{{ $section->page }}</td><td>{{ $section->startLine }}-{{ $section->endLine }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No sections detected.</p>
            @endif
        </section>

        <section>
            <details>
                <summary>Raw DTO JSON</summary>
                <pre>{{ json_encode($dtoArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        </section>
    @endif
</main>
</body>
</html>
