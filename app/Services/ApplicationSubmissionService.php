<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ApplicationSubmissionService
{
    /**
     * Persist a citizen's application, private documents, and initial audit entry atomically.
     *
     * A tracking-ID collision is retried, and uploaded files are removed if persistence fails.
     *
     * @param  array<int, UploadedFile>  $documents
     */
    public function submit(User $citizen, ServiceType $serviceType, array $documents): Application
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $storedPaths = [];
            $trackingId = $this->newTrackingId();

            try {
                return DB::transaction(function () use (
                    $citizen,
                    $serviceType,
                    $documents,
                    &$storedPaths,
                    $trackingId,
                ): Application {
                    $application = Application::query()->create([
                        'tracking_id' => $trackingId,
                        'user_id' => $citizen->id,
                        'service_type_id' => $serviceType->id,
                        'status' => 'submitted',
                    ]);

                    foreach (array_values($serviceType->required_documents ?? []) as $index => $documentType) {
                        $document = $documents[$index] ?? null;

                        if (! $document instanceof UploadedFile) {
                            throw new RuntimeException('A required application document is missing.');
                        }

                        $path = $document->store('applications/'.$trackingId, 'local');

                        if ($path === false) {
                            throw new RuntimeException('The application document could not be stored.');
                        }

                        $storedPaths[] = $path;

                        $application->documents()->create([
                            'document_type' => $documentType,
                            'file_path' => $path,
                            'file_type' => $document->getMimeType() ?? 'application/octet-stream',
                        ]);
                    }

                    $application->statusHistories()->create([
                        'old_status' => null,
                        'new_status' => 'submitted',
                        'changed_by' => $citizen->id,
                    ]);

                    return $application;
                });
            } catch (QueryException $exception) {
                $this->removeStoredFiles($storedPaths, $exception);

                if (! $this->isTrackingIdCollision($exception) || $attempt === 5) {
                    throw $exception;
                }
            } catch (Throwable $exception) {
                $this->removeStoredFiles($storedPaths, $exception);

                throw $exception;
            }
        }

        throw new RuntimeException('A unique application tracking ID could not be generated.');
    }

    private function newTrackingId(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $trackingId = 'NSP-'.Str::upper(Str::random(12));

            if (! Application::query()->where('tracking_id', $trackingId)->exists()) {
                return $trackingId;
            }
        }

        throw new RuntimeException('A unique application tracking ID could not be generated.');
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function removeStoredFiles(array $paths, Throwable $cause): void
    {
        if ($paths !== [] && ! Storage::disk('local')->delete($paths)) {
            throw new RuntimeException(
                'The submission failed and its uploaded documents could not be cleaned up.',
                previous: $cause,
            );
        }
    }

    private function isTrackingIdCollision(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'applications_tracking_id_unique')
            || str_contains($message, 'applications.tracking_id');
    }
}
