<?php

namespace App\Services\Email;

use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailAttachmentStorageService
{
    /**
     * @param  list<array<string, mixed>>  $attachments
     * @return list<EmailAttachment>
     */
    public function storeAttachments(EmailMessage $email, array $attachments): array
    {
        $stored = [];

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $stored[] = $this->storeAttachment($email, $attachment);
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $attachment
     */
    private function storeAttachment(EmailMessage $email, array $attachment): EmailAttachment
    {
        $filename = $this->sanitizeFilename((string) ($attachment['original_filename'] ?? $attachment['filename'] ?? 'attachment.bin'));
        $storedPath = is_string($attachment['stored_path'] ?? null) ? (string) $attachment['stored_path'] : null;
        $content = $this->attachmentContent($attachment);

        if ($storedPath === null || $storedPath === '' || ! Storage::exists($storedPath)) {
            $storedPath = sprintf('email-attachments/%d/%s-%s', $email->id, Str::uuid()->toString(), $filename);
            Storage::put($storedPath, $content ?? $this->placeholderContent($attachment));
        }

        $size = Storage::exists($storedPath) ? Storage::size($storedPath) : null;
        $checksum = Storage::exists($storedPath) ? hash('sha256', (string) Storage::get($storedPath)) : null;

        return $email->attachments()->create([
            'original_filename' => $filename,
            'stored_path' => $storedPath,
            'mime_type' => $attachment['mime_type'] ?? 'application/octet-stream',
            'size_bytes' => is_numeric($attachment['size_bytes'] ?? null) ? (int) $attachment['size_bytes'] : $size,
            'checksum' => is_string($attachment['checksum'] ?? null) ? $attachment['checksum'] : $checksum,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attachment
     */
    private function attachmentContent(array $attachment): ?string
    {
        $content = $attachment['content'] ?? null;

        if (! is_string($content)) {
            return null;
        }

        if (($attachment['content_base64'] ?? false) === true) {
            $decoded = base64_decode($content, true);

            return $decoded === false ? null : $decoded;
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $attachment
     */
    private function placeholderContent(array $attachment): string
    {
        return json_encode([
            'placeholder' => true,
            'original_filename' => $attachment['original_filename'] ?? $attachment['filename'] ?? null,
            'mime_type' => $attachment['mime_type'] ?? null,
            'size_bytes' => $attachment['size_bytes'] ?? null,
        ], JSON_PRETTY_PRINT);
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = Str::of($filename)->replaceMatches('/[^A-Za-z0-9._-]+/', '_')->trim('_')->toString();

        return $filename === '' ? 'attachment.bin' : $filename;
    }
}
