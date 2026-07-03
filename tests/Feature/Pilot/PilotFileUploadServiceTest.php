<?php

use App\Enums\PilotFileType;
use App\Models\AuditLog;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotFileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('uploads pilot files privately with checksum and audit', function (): void {
    Storage::fake('local');
    $pilot = PilotSupplier::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $file = UploadedFile::fake()->createWithContent('sales.csv', "SKU,Date,Qty\nSKU-1001,2026-01-01,12\n");

    $result = app(PilotFileUploadService::class)->upload($pilot, $file, PilotFileType::SalesHistorySample->value, ['notes' => 'sample'], $admin);

    expect($result['file']->stored_path)->toStartWith('pilot/'.$pilot->id.'/sales_history_sample/')
        ->and($result['file']->checksum)->not->toBeEmpty()
        ->and(str_starts_with($result['file']->stored_path, 'public/'))->toBeFalse()
        ->and(Storage::disk('local')->exists($result['file']->stored_path))->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'pilot_file_uploaded')->exists())->toBeTrue();
});

it('rejects unsupported file extensions', function (): void {
    Storage::fake('local');
    $pilot = PilotSupplier::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $file = UploadedFile::fake()->create('malware.exe', 1);

    expect(fn () => app(PilotFileUploadService::class)->upload($pilot, $file, PilotFileType::Other->value, [], $admin))
        ->toThrow(ValidationException::class);
});

it('deletes pilot files only with a reason', function (): void {
    Storage::fake('local');
    $pilot = PilotSupplier::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $file = UploadedFile::fake()->createWithContent('form.pdf', 'fake pdf');
    $pilotFile = app(PilotFileUploadService::class)->upload($pilot, $file, PilotFileType::ManufacturerOrderForm->value, [], $admin)['file'];

    expect(fn () => app(PilotFileUploadService::class)->deleteFile($pilotFile, $admin, ''))->toThrow(ValidationException::class);

    app(PilotFileUploadService::class)->deleteFile($pilotFile->fresh(), $admin, 'Wrong file.');

    expect(Storage::disk('local')->exists($pilotFile->stored_path))->toBeFalse()
        ->and(AuditLog::query()->where('event_type', 'pilot_file_deleted')->exists())->toBeTrue();
});
