<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\ExportFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportDownloadController extends Controller
{
    public function __invoke(ExportFile $exportFile): StreamedResponse
    {
        Gate::authorize('download', $exportFile);

        abort_unless(Storage::exists($exportFile->stored_path), 404);

        return Storage::download($exportFile->stored_path, $exportFile->filename);
    }
}
