<?php

namespace App\Console\Commands;

use App\Models\ApplicationDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SecureApplicationDocuments extends Command
{
    protected $signature = 'applications:secure-documents';

    protected $description = 'Move existing application uploads from public storage to private storage';

    public function handle(): int
    {
        $publicDisk = Storage::disk('public');
        $privateDisk = Storage::disk('local');
        $moved = 0;
        $missing = 0;

        ApplicationDocument::query()
            ->select(['id', 'file_path'])
            ->orderBy('id')
            ->chunkById(100, function ($documents) use (
                $publicDisk,
                $privateDisk,
                &$moved,
                &$missing,
            ): void {
                foreach ($documents as $document) {
                    $path = $document->file_path;

                    if (! Str::startsWith($path, 'applications/')) {
                        $this->warn("Skipping unexpected document path for record {$document->id}.");
                        $missing++;

                        continue;
                    }

                    if ($privateDisk->exists($path)) {
                        if ($publicDisk->exists($path) && ! $publicDisk->delete($path)) {
                            throw new RuntimeException("Could not remove the public copy of {$path}.");
                        }

                        continue;
                    }

                    if (! $publicDisk->exists($path)) {
                        $this->warn("Document file is missing from both disks: {$path}");
                        $missing++;

                        continue;
                    }

                    $stream = $publicDisk->readStream($path);

                    if (! is_resource($stream)) {
                        throw new RuntimeException("Could not read the public document: {$path}");
                    }

                    try {
                        $written = $privateDisk->writeStream($path, $stream);
                    } finally {
                        fclose($stream);
                    }

                    if (! $written || ! $privateDisk->exists($path)) {
                        throw new RuntimeException("Could not store the private document: {$path}");
                    }

                    if (! $publicDisk->delete($path)) {
                        $privateDisk->delete($path);

                        throw new RuntimeException("Could not remove the public copy of {$path}.");
                    }

                    $moved++;
                }
            });

        $this->info("Moved {$moved} application document(s) to private storage.");

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
