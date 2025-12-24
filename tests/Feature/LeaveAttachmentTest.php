<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Users\Models\User;
use Modules\Users\Models\VacationType;
use Modules\Users\Models\UserVacation;
use Modules\Users\Models\LeaveAttachment;
use Tests\TestCase;
use Modules\Users\Enums\StatusEnum;

class LeaveAttachmentTest extends TestCase
{
    // use RefreshDatabase; // Commented out to avoid wiping existing DB if not configured for testing

    public function test_can_attach_files_to_sick_leave()
    {
        Storage::fake('public');

        $user = User::first(); // Use existing user
        if (!$user) {
            $this->markTestSkipped('No users found in DB');
        }
        $this->actingAs($user);

        $type = VacationType::firstOrCreate(['name' => 'Sick Leave'], ['default_days' => 10]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/vacation/add_user_vacation', [
            'vacation_type_id' => $type->id,
            'from_date' => now()->next('Monday')->format('Y-m-d'),
            'to_date' => now()->next('Tuesday')->format('Y-m-d'),
            'attachments' => [$file],
        ]);

        $response->assertStatus(200);

        $vacationId = $response->json('Vacation.id');
        $this->assertNotNull($vacationId);

        $this->assertDatabaseHas('leave_attachments', [
            'user_vacation_id' => $vacationId,
            'file_name' => 'document.pdf',
        ]);

        // Verify file exists
        $attachment = LeaveAttachment::where('user_vacation_id', $vacationId)->first();
        Storage::disk('public')->assertExists($attachment->file_path);

        // Clean up
        UserVacation::find($vacationId)->delete();
    }

    public function test_can_attach_single_file_to_sick_leave()
    {
        Storage::fake('public');

        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users found in DB');
        }
        $this->actingAs($user);

        $type = VacationType::firstOrCreate(['name' => 'Sick Leave'], ['default_days' => 10]);

        $file = UploadedFile::fake()->create('single_document.pdf', 100);

        // Send as single file, not array
        $response = $this->postJson('/api/vacation/add_user_vacation', [
            'vacation_type_id' => $type->id,
            'from_date' => now()->next('Wednesday')->addWeek()->format('Y-m-d'),
            'to_date' => now()->next('Thursday')->addWeek()->format('Y-m-d'),
            'attachments' => $file,
        ]);

        $response->assertStatus(200);

        $vacationId = $response->json('Vacation.id');
        $this->assertNotNull($vacationId);

        $this->assertDatabaseHas('leave_attachments', [
            'user_vacation_id' => $vacationId,
            'file_name' => 'single_document.pdf',
        ]);

        // Clean up
        if ($vacation = UserVacation::find($vacationId)) {
            $vacation->delete();
        }
    }

    public function test_attachments_are_deleted_when_vacation_is_deleted()
    {
        Storage::fake('public');
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users found in DB');
        }
        $this->actingAs($user);

        $type = VacationType::firstOrCreate(['name' => 'Sick Leave'], ['default_days' => 10]);
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/vacation/add_user_vacation', [
            'vacation_type_id' => $type->id,
            'from_date' => now()->next('Friday')->format('Y-m-d'),
            'to_date' => now()->next('Monday')->addWeek()->format('Y-m-d'), // Ensure next week Monday
            'attachments' => [$file],
        ]);

        $vacationId = $response->json('Vacation.id');
        $attachment = LeaveAttachment::where('user_vacation_id', $vacationId)->first();
        $this->assertNotNull($attachment);

        // Delete vacation
        UserVacation::find($vacationId)->delete();

        $this->assertDatabaseMissing('leave_attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_attachments_are_returned_in_response()
    {
        Storage::fake('public');
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users found in DB');
        }
        $this->actingAs($user);

        $type = VacationType::firstOrCreate(['name' => 'Sick Leave'], ['default_days' => 10]);
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/vacation/add_user_vacation', [
            'vacation_type_id' => $type->id,
            'from_date' => now()->next('Wednesday')->format('Y-m-d'),
            'to_date' => now()->next('Thursday')->format('Y-m-d'),
            'attachments' => [$file],
        ]);

        $vacationId = $response->json('Vacation.id');

        $response = $this->getJson('/api/vacation/show_user_vacations?per_page=100');

        $response->assertStatus(200);

        // Find the vacation in the list
        $vacations = $response->json('data.vacations.data');

        $this->assertNotEmpty($vacations);
        $targetVacation = $vacations[0];

        $this->assertNotNull($targetVacation);
        $this->assertArrayHasKey('attachments', $targetVacation);
        $this->assertCount(1, $targetVacation['attachments']);
        $this->assertEquals('document.pdf', $targetVacation['attachments'][0]['file_name']);
        $this->assertArrayHasKey('file_url', $targetVacation['attachments'][0]);

        // Clean up
        if ($vacation = UserVacation::find($vacationId)) {
            $vacation->delete();
        }
    }
}
