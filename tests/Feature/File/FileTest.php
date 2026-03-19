<?php

namespace Tests\Feature\File;

use App\Models\Expense;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_can_upload_file(): void
    {
        $user = User::factory()->create();
        $expense = Expense::create([
            'user_id' => $user->id,
            'title' => 'Grocery',
            'amount' => 1500,
            'spent_at' => '2024-01-15',
            'is_recurring' => false,
        ]);

        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/files', [
            'file' => $file,
            'fileable_type' => 'expense',
            'fileable_id' => $expense->id,
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_can_list_files_for_resource(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/files?fileable_type=expense&fileable_id=1');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_can_delete_own_file(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'user_id' => $user->id,
            'fileable_type' => 'App\\Models\\Expense',
            'fileable_id' => 1,
            'original_name' => 'receipt.pdf',
            'stored_name' => 'receipt_stored.pdf',
            'path' => 'uploads/receipt_stored.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/files/{$file->id}");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_cannot_delete_other_users_file(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $file = File::create([
            'user_id' => $other->id,
            'fileable_type' => 'App\\Models\\Expense',
            'fileable_id' => 1,
            'original_name' => 'receipt.pdf',
            'stored_name' => 'receipt_stored.pdf',
            'path' => 'uploads/receipt_stored.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/files/{$file->id}");
        $response->assertStatus(403);
    }

    public function test_file_upload_requires_file(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/files', [
            'fileable_type' => 'expense',
            'fileable_id' => 1,
        ]);
        $response->assertStatus(422);
    }

    public function test_file_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/files');
        $response->assertStatus(401);
    }
}
