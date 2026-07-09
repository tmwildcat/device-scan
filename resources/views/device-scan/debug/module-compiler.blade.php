<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Module Compiler Debug</title>
    <style>
        body {
            margin: 0;
            background: #f7f7f5;
            color: #1f2933;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        h1, h2 {
            margin: 0 0 12px;
        }

        h1 {
            font-size: 24px;
        }

        h2 {
            font-size: 18px;
            margin-top: 28px;
        }

        form, section {
            background: #ffffff;
            border: 1px solid #d8dee4;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
        }

        select, button {
            font: inherit;
            padding: 8px 10px;
        }

        select {
            min-width: min(720px, 100%);
        }

        button {
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            font-size: 14px;
        }

        th, td {
            border: 1px solid #d8dee4;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eef2f6;
            font-weight: 700;
        }

        .muted {
            color: #65727f;
        }

        .warning {
            background: #fff7e6;
            border-color: #f4cf88;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
        }

        .summary div {
            border: 1px solid #d8dee4;
            border-radius: 6px;
            padding: 10px;
            background: #fbfcfd;
        }

        .severity-error {
            background: #fff1f0;
            border-color: #ffccc7;
        }

        .severity-warning {
            background: #fff7e6;
            border-color: #f4cf88;
        }

        .severity-info {
            background: #eef6ff;
            border-color: #b7d9ff;
        }

        pre {
            overflow: auto;
            max-height: 560px;
            background: #101820;
            color: #d5e7ff;
            border-radius: 6px;
            padding: 14px;
        }

        details summary {
            cursor: pointer;
            font-weight: 700;
        }
    </style>
</head>
<body>
<main>
    <h1>Module Compiler Debug</h1>
    <p class="muted">Temporary development page for corpus PDFs.</p>

    <form method="get" action="{{ route('device-scan.debug.module-compiler') }}">
        <label for="file">Module corpus PDF</label><br>
        <select id="file" name="file">
            <option value="">Select a PDF...</option>
            @foreach ($files as $file)
                <option value="{{ $file }}" @selected($file === $selected)>{{ $file }}</option>
            @endforeach
        </select>
        <button type="submit">Run compiler</button>
    </form>

    @if ($error)
        <section class="warning">
            {{ $error }}
        </section>
    @endif

    @if ($selected && ! $dto)
        <section class="warning">
            Compiler did not return a DTO for {{ $selected }}.
        </section>
    @endif

    @if ($dto)
        @php
            $validationIssues = collect($dto->validation?->issues ?? [])->groupBy('severity');
            $sourceValue = fn ($value) => $value ? trim(($value->value ?? '').' '.($value->unit ?? '')) : null;
        @endphp

        <section>
            <h2>Summary</h2>
            <div class="summary">
                <div><strong>Manufacturer</strong><br>{{ $dto->manufacturer ?? '—' }}</div>
                <div><strong>Series</strong><br>{{ $dto->series ?? '—' }}</div>
                <div><strong>Family</strong><br>{{ $dto->family ?? '—' }}</div>
                <div><strong>Technology</strong><br>{{ $dto->technology ?? '—' }}</div>
                <div><strong>Model series / ratings</strong><br>{{ count($dto->models) }}</div>
                <div><strong>Validation</strong><br>
                    {{ $dto->validation?->toArray()['counts']['error'] ?? 0 }} errors,
                    {{ $dto->validation?->toArray()['counts']['warning'] ?? 0 }} warnings
                </div>
            </div>
        </section>

        <section>
            <h2>Ratings / Model Series</h2>
            @if ($dto->models !== [])
                <p>{{ implode(', ', $dto->models) }}</p>
            @else
                <p class="muted">No model series extracted. Ratings may still be represented by power class.</p>
            @endif
        </section>

        <section>
            <h2>STC Electrical Output</h2>

            @if ($dto->electricalStc && $dto->electricalStc->models !== [])
                <table>
                    <thead>
                    <tr>
                            <th>Display</th>
                            <th>Model Series</th>
                            <th>Explicit Variants</th>
                            <th>Power class W</th>
                        <th>Pmax W</th>
                        <th>Voc V</th>
                        <th>Vmp V</th>
                        <th>Isc A</th>
                        <th>Imp A</th>
                        <th>Efficiency %</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($dto->electricalStc->models as $model)
                        <tr>
                            <td>{{ $model->displayName }}</td>
                            <td>{{ $model->modelSeries ?? '—' }}</td>
                            <td>{{ implode(', ', $model->modelVariants) ?: '—' }}</td>
                            <td>{{ $model->powerClassW }}</td>
                            <td>{{ $model->ratedMaxPowerW?->value }}</td>
                            <td>{{ $model->openCircuitVoltageV?->value }}</td>
                            <td>{{ $model->maximumPowerVoltageV?->value }}</td>
                            <td>{{ $model->shortCircuitCurrentA?->value }}</td>
                            <td>{{ $model->maximumPowerCurrentA?->value }}</td>
                            <td>{{ $model->moduleEfficiencyPercent?->value }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No STC electrical output was extracted.</p>
            @endif
        </section>

        <section>
            <h2>Mechanical</h2>
            @if ($dto->mechanical)
                <table>
                    <tbody>
                    @foreach ($dto->mechanical->toArray() as $field => $value)
                        @continue($field === 'metadata' || $value === null)
                        <tr>
                            <th>{{ $field }}</th>
                            <td>{{ $value['value'] ?? '' }} {{ $value['unit'] ?? '' }}</td>
                            <td class="muted">p{{ $value['source_page'] ?? '—' }} · {{ $value['source_text'] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No mechanical block extracted.</p>
            @endif
        </section>

        <section>
            <h2>Operating</h2>
            @if ($dto->operatingConditions)
                <table>
                    <tbody>
                    @foreach ($dto->operatingConditions->toArray() as $field => $value)
                        @continue($field === 'metadata' || $value === null)
                        <tr>
                            <th>{{ $field }}</th>
                            <td>{{ $value['value'] ?? '' }} {{ $value['unit'] ?? '' }}</td>
                            <td class="muted">p{{ $value['source_page'] ?? '—' }} · {{ $value['source_text'] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No operating conditions block extracted.</p>
            @endif
        </section>

        <section>
            <h2>Temperature</h2>
            @if ($dto->temperatureCharacteristics)
                <table>
                    <tbody>
                    @foreach ($dto->temperatureCharacteristics->toArray() as $field => $value)
                        @continue($field === 'metadata' || $value === null)
                        <tr>
                            <th>{{ $field }}</th>
                            <td>{{ $value['value'] ?? '' }} {{ $value['unit'] ?? '' }}</td>
                            <td class="muted">p{{ $value['source_page'] ?? '—' }} · {{ $value['source_text'] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No temperature characteristics block extracted.</p>
            @endif
        </section>

        <section>
            <h2>Warranty</h2>
            @if ($dto->warranty)
                <table>
                    <tbody>
                    @foreach ($dto->warranty->toArray() as $field => $value)
                        @continue($field === 'metadata' || $value === null)
                        <tr>
                            <th>{{ $field }}</th>
                            <td>{{ $value['value'] ?? '' }} {{ $value['unit'] ?? '' }}</td>
                            <td class="muted">p{{ $value['source_page'] ?? '—' }} · {{ $value['source_text'] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No warranty block extracted.</p>
            @endif
        </section>

        <section>
            <h2>Packaging / Certifications</h2>
            @if ($dto->packaging)
                <h3>Packaging</h3>
                <table>
                    <tbody>
                    @foreach ($dto->packaging->toArray() as $field => $value)
                        @continue($field === 'metadata' || $value === null)
                        <tr>
                            <th>{{ $field }}</th>
                            <td>{{ $value['value'] ?? '' }} {{ $value['unit'] ?? '' }}</td>
                            <td class="muted">p{{ $value['source_page'] ?? '—' }} · {{ $value['source_text'] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No packaging fields extracted.</p>
            @endif

            @if ($dto->certifications && $dto->certifications->items !== [])
                <h3>Certifications</h3>
                <ul>
                    @foreach ($dto->certifications->items as $item)
                        <li>{{ $item->value }} <span class="muted">p{{ $item->sourcePage ?? '—' }} · {{ $item->sourceText }}</span></li>
                    @endforeach
                </ul>
            @else
                <p class="muted">No certification strings extracted.</p>
            @endif
        </section>

        <section @class(['severity-error' => ($dto->validation?->hasErrors() ?? false), 'severity-warning' => ! ($dto->validation?->hasErrors() ?? false) && (($dto->validation?->toArray()['counts']['warning'] ?? 0) > 0)])>
            <h2>Validation Issues</h2>
            @if (($dto->validation?->issues ?? []) !== [])
                @foreach (['error', 'warning', 'info'] as $severity)
                    @if (($validationIssues[$severity] ?? collect())->isNotEmpty())
                        <h3>{{ ucfirst($severity) }}</h3>
                        <table>
                            <thead>
                            <tr>
                                <th>Code</th>
                                <th>Model</th>
                                <th>Field</th>
                                <th>Value</th>
                                <th>Message</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($validationIssues[$severity] as $issue)
                                <tr>
                                    <td>{{ $issue->code }}</td>
                                    <td>{{ $issue->model ?? '—' }}</td>
                                    <td>{{ $issue->field ?? '—' }}</td>
                                    <td>{{ $issue->value ?? '—' }}</td>
                                    <td>{{ $issue->message }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                @endforeach
            @else
                <p class="muted">No validation issues.</p>
            @endif
        </section>

        <section>
            <h2>Detected Sections</h2>

            @if ($dto->sections !== [])
                <table>
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Page</th>
                        <th>Lines</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($dto->sections as $section)
                        <tr>
                            <td>{{ $section->type }}</td>
                            <td>{{ $section->title }}</td>
                            <td>{{ $section->page }}</td>
                            <td>{{ $section->startLine }}-{{ $section->endLine }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No sections detected.</p>
            @endif
        </section>

        <section @class(['warning' => $dto->warnings !== []])>
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
            <details>
                <summary>Raw DTO JSON</summary>
                <pre>{{ json_encode($dtoArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        </section>
    @endif
</main>
</body>
</html>
