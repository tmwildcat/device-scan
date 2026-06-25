<?php

namespace App\Http\Controllers\DeviceScan;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Process\Process;

class UploadController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('DeviceScan/Upload');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_type' => ['required', 'string', 'in:module,inverter'],
            'datasheet' => ['required', 'file', 'mimes:pdf', 'max:25600'],
        ]);

        $path = $request->file('datasheet')->store('device-scan/tmp', 'public');

        session([
            'device_scan.last_upload' => [
                'device_type' => $validated['device_type'],
                'path' => $path,
                'url' => route('device-scan.preview'),
                'preview_image_url' => route('device-scan.preview-image'),
                'original_name' => $request->file('datasheet')->getClientOriginalName(),
            ],
        ]);

        return redirect()->route('device-scan.review', [
            'deviceType' => $validated['device_type'],
        ]);
    }

    public function preview()
{
    $upload = session('device_scan.last_upload');

    abort_unless($upload && isset($upload['path']), 404);

    $disk = Storage::disk('public');

    abort_unless($disk->exists($upload['path']), 404);

    $absolutePath = $disk->path($upload['path']);
    $filename = $this->safeFilename($upload['original_name'] ?? 'datasheet.pdf');

    return response()->make(file_get_contents($absolutePath), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$filename.'"',
        'Content-Length' => (string) filesize($absolutePath),
        'Cache-Control' => 'private, max-age=3600',
        'X-Content-Type-Options' => 'nosniff',
    ]);
}

    public function previewImage()
    {
        $upload = session('device_scan.last_upload');

        abort_unless($upload && isset($upload['path']), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($upload['path']), 404);

        $pdfPath = $disk->path($upload['path']);

        $previewDir = storage_path('app/public/device-scan/previews');

        if (! is_dir($previewDir)) {
            mkdir($previewDir, 0755, true);
        }

        $hash = md5($upload['path']);
        $outputBase = $previewDir.'/'.$hash;
        $imagePath = $outputBase.'.png';

        if (! file_exists($imagePath)) {
            $process = new Process([
                'pdftoppm',
                '-png',
                '-singlefile',
                '-f',
                '1',
                '-l',
                '1',
                '-r',
                '150',
                $pdfPath,
                $outputBase,
            ]);

            $process->setTimeout(30);
            $process->run();

            abort_unless(
                $process->isSuccessful() && file_exists($imagePath),
                500,
                'Could not generate PDF preview image.'
            );
        }

        return response()->file($imagePath, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function safeFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], '', $filename);
    }
}