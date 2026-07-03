<?php

namespace App\Services\Supply\Pilot;

use App\Enums\PilotFileType;
use App\Models\PilotFile;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PilotFileUploadService
{
    /**
     * @var list<string>
     */
    private array $allowedExtensions = ['csv', 'txt', 'xlsx', 'xls', 'pdf', 'eml', 'html', 'json'];

    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function upload(PilotSupplier $pilot, UploadedFile $file, string $fileType, array $metadata, User $user): array
    {
        if (! in_array($fileType, PilotFileType::values(), true)) {
            throw ValidationException::withMessages([
                'file_type' => 'Unsupported pilot file type.',
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, $this->allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported pilot file extension.',
            ]);
        }

        $maxKilobytes = (int) config('supply.pilot.max_upload_size_kb', 10240);

        if (($file->getSize() ?: 0) > $maxKilobytes * 1024) {
            throw ValidationException::withMessages([
                'file' => 'Pilot file exceeds the configured maximum size.',
            ]);
        }

        $directory = trim((string) config('supply.pilot.storage_path', 'pilot'), '/').'/'.$pilot->id.'/'.$fileType;
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'pilot-file';
        $filename = $baseName.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$extension;
        $checksum = hash_file('sha256', $file->getRealPath());
        $storedPath = Storage::disk('local')->putFileAs($directory, $file, $filename);

        $pilotFile = PilotFile::query()->create([
            'pilot_supplier_id' => $pilot->id,
            'file_type' => $fileType,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => $checksum,
            'metadata_json' => $metadata,
            'uploaded_by_user_id' => $user->id,
        ]);

        $this->auditLogService->write('pilot_file_uploaded', $pilotFile, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'file_type' => $fileType,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'checksum' => $checksum,
        ], $pilot->company_id);

        return ['file' => $pilotFile];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteFile(PilotFile $file, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A delete reason is required.',
            ]);
        }

        $pilot = $file->pilotSupplier()
            ->select(['id', 'company_id'])
            ->firstOrFail();

        $oldValues = $file->only(['file_type', 'original_filename', 'stored_path', 'checksum']);

        if ($file->stored_path !== '' && Storage::disk('local')->exists($file->stored_path)) {
            Storage::disk('local')->delete($file->stored_path);
        }

        $file->delete();

        $this->auditLogService->write('pilot_file_deleted', $pilot, $user, $oldValues, null, [
            'pilot_supplier_id' => $pilot->id,
            'pilot_file_id' => $file->id,
            'reason' => $reason,
        ], $pilot->company_id);

        return ['deleted' => true];
    }
}
