<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\FormAutofillOutput;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormAutofillOutputDownloadController extends Controller
{
    public function __invoke(FormAutofillOutput $output): StreamedResponse
    {
        abort_unless($output->stored_path !== null && Storage::exists($output->stored_path), 404);

        return Storage::download($output->stored_path, $output->filename);
    }
}
