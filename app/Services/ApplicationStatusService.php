<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ApplicationStatusService
{
    /**
     * Apply an authorized officer status transition and append its audit history.
     *
     * The application row is locked during the transaction; invalid transitions or missing
     * required remarks leave the persisted application unchanged.
     */
    public function transition(
        User $officer,
        Application $application,
        ApplicationStatus $nextStatus,
        ?string $remark,
    ): Application {
        return DB::transaction(function () use ($officer, $application, $nextStatus, $remark): Application {
            $lockedApplication = Application::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $officer->can('update', $lockedApplication)) {
                abort(403);
            }

            $currentStatus = ApplicationStatus::tryFrom($lockedApplication->status);

            if ($currentStatus === null || ! $currentStatus->canTransitionTo($nextStatus)) {
                throw ValidationException::withMessages([
                    'status' => 'This status transition is not allowed.',
                ]);
            }

            if ($nextStatus->requiresRemark() && blank($remark)) {
                throw ValidationException::withMessages([
                    'remark' => 'A remark is required for this decision.',
                ]);
            }

            $lockedApplication->forceFill([
                'status' => $nextStatus->value,
                'current_officer_id' => $officer->id,
            ])->save();

            $lockedApplication->statusHistories()->create([
                'old_status' => $currentStatus->value,
                'new_status' => $nextStatus->value,
                'remark' => $remark,
                'changed_by' => $officer->id,
            ]);

            return $lockedApplication;
        });
    }

    /**
     * Accept replacement documents from the owning citizen after a correction request.
     *
     * The status change, documents, and history entry are persisted together; stored files are
     * cleaned up if the database operation fails.
     *
     * @param  array<int, UploadedFile>  $documents
     */
    public function resubmit(
        User $citizen,
        Application $application,
        array $documents,
    ): Application {
        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $citizen,
                $application,
                $documents,
                &$storedPaths,
            ): Application {
                $lockedApplication = Application::query()
                    ->whereKey($application->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $citizen->can('resubmit', $lockedApplication)) {
                    abort(403);
                }

                $requiredDocuments = array_values(
                    $lockedApplication->serviceType->required_documents ?? [],
                );

                foreach ($requiredDocuments as $index => $documentType) {
                    $document = $documents[$index] ?? null;

                    if (! $document instanceof UploadedFile) {
                        throw ValidationException::withMessages([
                            'documents.'.$index => 'A replacement document is required.',
                        ]);
                    }

                    $path = $document->store(
                        'applications/'.$lockedApplication->tracking_id,
                        'local',
                    );

                    if ($path === false) {
                        throw new RuntimeException('The replacement document could not be stored.');
                    }

                    $storedPaths[] = $path;

                    $lockedApplication->documents()->create([
                        'document_type' => $documentType,
                        'file_path' => $path,
                        'file_type' => $document->getMimeType() ?? 'application/octet-stream',
                    ]);
                }

                $lockedApplication->forceFill(['status' => ApplicationStatus::UnderReview->value])->save();

                $lockedApplication->statusHistories()->create([
                    'old_status' => ApplicationStatus::CorrectionRequired->value,
                    'new_status' => ApplicationStatus::UnderReview->value,
                    'remark' => 'Citizen submitted replacement documents.',
                    'changed_by' => $citizen->id,
                ]);

                return $lockedApplication;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== [] && ! Storage::disk('local')->delete($storedPaths)) {
                throw new RuntimeException(
                    'The resubmission failed and its uploaded documents could not be cleaned up.',
                    previous: $exception,
                );
            }

            throw $exception;
        }
    }
}
