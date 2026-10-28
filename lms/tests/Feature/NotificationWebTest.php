<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_their_notifications_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifikasi');
    }

    public function test_notifications_page_only_displays_the_authenticated_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createNotification($user, ['message' => 'Notifikasi milik saya']);
        $this->createNotification($otherUser, ['message' => 'Notifikasi milik pengguna lain']);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifikasi milik saya')
            ->assertDontSee('Notifikasi milik pengguna lain');
    }

    public function test_user_can_mark_their_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotification($user, ['title' => 'Tugas baru']);

        $this->actingAs($user)
            ->post(route('notifications.read', ['id' => $notification->id]))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', 'Notifikasi berhasil ditandai sudah dibaca.');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $this->createNotification($otherUser, ['title' => 'Notifikasi privat']);

        $this->actingAs($user)
            ->post(route('notifications.read', ['id' => $notification->id]))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_guest_cannot_access_notifications_page_or_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotification($user, ['title' => 'Notifikasi privat']);

        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));

        $this->post(route('notifications.read', ['id' => $notification->id]))
            ->assertRedirect(route('login'));
    }

    public function test_navbar_shows_the_authenticated_users_unread_count(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user, ['title' => 'Belum dibaca satu']);
        $this->createNotification($user, ['title' => 'Belum dibaca dua']);
        $readNotification = $this->createNotification($user, ['title' => 'Sudah dibaca']);
        $readNotification->markAsRead();

        $this->createNotification(User::factory()->create(), ['title' => 'Milik user lain']);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('data-notification-unread-count="2"', false);
    }

    public function test_notifications_page_supports_pagination_after_fifteen_items(): void
    {
        $user = User::factory()->create();

        for ($index = 1; $index <= 16; $index++) {
            $this->createNotification($user, ['message' => "Notifikasi {$index}"]);
        }

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertViewHas('notifications', fn ($notifications) => $notifications->count() === 15);

        $this->get(route('notifications.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('notifications', fn ($notifications) => $notifications->count() === 1);
    }

    public function test_notification_api_accepts_web_session_for_navbar_polling(): void
    {
        $user = User::factory()->create([
            'email' => 'polling@kampuslms.test',
            'password' => 'password',
        ]);
        $this->createNotification($user, ['title' => 'Tugas baru']);

        $this->post(route('login.post'), [
            'email' => 'polling@kampuslms.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->getJson('/api/v1/notifications?page=1')
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 1);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createNotification(User $user, array $data): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'data' => $data,
        ]);
    }
}
