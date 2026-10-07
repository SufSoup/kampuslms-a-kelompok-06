<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_only_gets_their_own_notifications(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $otherUser = User::factory()->mahasiswa()->create();

        $userNotification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\AssignmentCreated',
            'data' => ['message' => 'Tugas baru tersedia.'],
        ]);
        $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\AssignmentCreated',
            'data' => ['message' => 'Notifikasi privat pengguna lain.'],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $userNotification->id)
            ->assertJsonPath('data.0.data.message', 'Tugas baru tersedia.')
            ->assertJsonMissing(['message' => 'Notifikasi privat pengguna lain.']);
    }

    public function test_authenticated_user_can_mark_their_notification_as_read(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\AssignmentCreated',
            'data' => ['message' => 'Tugas baru tersedia.'],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('id', $notification->id)
            ->assertJsonPath('data.message', 'Tugas baru tersedia.')
            ->assertJsonStructure(['read_at']);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $otherUser = User::factory()->mahasiswa()->create();
        $notification = $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\AssignmentCreated',
            'data' => ['message' => 'Notifikasi privat pengguna lain.'],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_notifications_require_authentication(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $this->postJson('/api/v1/notifications/00000000-0000-0000-0000-000000000000/read')
            ->assertUnauthorized();
    }
}
