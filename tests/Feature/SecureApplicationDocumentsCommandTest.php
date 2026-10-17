<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureApplicationDocumentsCommandTest extends TestCase
{
    public function test_command_moves_existing_public_documents_to_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $application = Application::query()->create([
            'user_id' => User::factory()->create()->id,
            'service_type_id' => ServiceType::query()->where('is_active', true)->firstOrFail()->id,
        ]);
        $path = 'applications/'.$application->tracking_id.'/old-document.pdf';
        Storage::disk('public')->put($path, 'legacy file contents');
        $application->documents()->create([
            'document_type' => 'citizenship_certificate',
            'file_path' => $path,
            'file_type' => 'application/pdf',
        ]);

        $this->artisan('applications:secure-documents')
            ->expectsOutput('Moved 1 application document(s) to private storage.')
            ->assertSuccessful();

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('legacy file contents', Storage::disk('local')->get($path));
    }

    public function test_command_fails_when_a_document_record_has_no_stored_file(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $application = Application::query()->create([
            'user_id' => User::factory()->create()->id,
            'service_type_id' => ServiceType::query()->where('is_active', true)->firstOrFail()->id,
        ]);
        ApplicationDocument::query()->create([
            'application_id' => $application->id,
            'document_type' => 'citizenship_certificate',
            'file_path' => 'applications/missing-document.pdf',
            'file_type' => 'application/pdf',
        ]);

        $this->artisan('applications:secure-documents')
            ->expectsOutput('Document file is missing from both disks: applications/missing-document.pdf')
            ->assertFailed();
    }
}
