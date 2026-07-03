<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UploadPilotFileRequest;
use App\Models\PilotFile;
use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PilotFileController extends Controller
{
    public function upload(UploadPilotFileRequest $request, PilotSupplier $pilot, PilotFileUploadService $service): RedirectResponse
    {
        $service->upload(
            $pilot,
            $request->file('file'),
            (string) $request->validated('file_type'),
            $request->validated('metadata') ?? [],
            $request->user(),
        );

        return back()->with('status', 'Pilot file uploaded.');
    }

    public function destroy(Request $request, PilotSupplier $pilot, PilotFile $file, PilotFileUploadService $service): RedirectResponse
    {
        abort_unless($file->pilot_supplier_id === $pilot->id, 404);
        Gate::authorize('delete', $file);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $service->deleteFile($file, $request->user(), $validated['reason']);

        return back()->with('status', 'Pilot file deleted.');
    }
}
