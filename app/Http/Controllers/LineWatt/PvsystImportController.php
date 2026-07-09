<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Pvsyst\PvsystImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PvsystImportController extends Controller
{
    public function store(Request $request, PvsystImportService $imports): RedirectResponse
    {
        $validated = $request->validate([
            'device_type' => ['required', 'string', Rule::in(['module', 'inverter'])],
            'input_type' => ['required', 'string', Rule::in(['paste', 'xlsx'])],
            'mapping_template' => ['required', 'string', Rule::in(['pvsyst_module_component', 'pvsyst_inverter_component'])],
            'manufacturer' => ['required', 'string', 'max:120'],
            'model_name' => ['required', 'string', 'max:180'],
            'series' => ['nullable', 'string', 'max:180'],
            'pvsyst_data' => ['nullable', 'string', 'required_if:input_type,paste'],
            'pvsyst_file' => ['nullable', 'file', 'max:10240', 'required_if:input_type,xlsx'],
        ]);

        if ($validated['device_type'] === 'module' && $validated['mapping_template'] !== 'pvsyst_module_component') {
            return back()->withErrors(['mapping_template' => 'Choose the PVSyst Module Component mapping for module imports.'])->withInput();
        }

        if ($validated['device_type'] === 'inverter' && $validated['mapping_template'] !== 'pvsyst_inverter_component') {
            return back()->withErrors(['mapping_template' => 'Choose the PVSyst Inverter Component mapping for inverter imports.'])->withInput();
        }

        $file = $request->file('pvsyst_file');

        if ($validated['input_type'] === 'xlsx' && $file && ! in_array(strtolower($file->getClientOriginalExtension()), ['xlsx'], true)) {
            return back()->withErrors(['pvsyst_file' => 'Upload a PVSyst components.xlsx file.'])->withInput();
        }

        $result = $imports->import($request->user(), $validated, $file);

        return redirect()
            ->route('my-library.records.review', ['record' => $result->compiledRecord['uuid'] ?: $result->compiledRecord['id']])
            ->with('success', 'Structured component data imported from PVSyst.');
    }
}
