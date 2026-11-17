<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

public function test_borrower_cannot_access_notification_list(): void
{
    $borrower = User::factory()->create([
        'role' => 'borrower',
    ]);

    $response = $this->actingAs($borrower)
        ->getJson('/api/notifications');

    $response->assertForbidden();
}

public function test_librarian_can_access_notification_list(): void
{
    $librarian = User::factory()->create([
        'role' => 'librarian',
    ]);

    $response = $this->actingAs($librarian)
        ->getJson('/api/notifications');

    $response->assertOk();
}

public function test_supervisor_can_access_notification_list(): void
{
    $supervisor = User::factory()->create([
        'role' => 'supervisor',
    ]);

    $response = $this->actingAs($supervisor)
        ->getJson('/api/notifications');

    $response->assertOk();
}

public function test_supervisor_can_access_notification_summary(): void
{
    $supervisor = User::factory()->create([
        'role' => 'supervisor',
    ]);

    $response = $this->actingAs($supervisor)
        ->getJson('/api/notifications/summary');

    $response->assertOk()
        ->assertJsonStructure([
            'total',
            'pending',
            'retrying',
            'sent',
            'failed',
        ]);
}

public function test_borrower_cannot_access_notification_summary(): void
{
    $borrower = User::factory()->create([
        'role' => 'borrower',
    ]);

    $response = $this->actingAs($borrower)
        ->getJson('/api/notifications/summary');

    $response->assertForbidden();
}

public function test_notification_summary_returns_correct_counts(): void
{
    $librarian = User::factory()->create([
        'role' => 'librarian',
    ]);

    $response = $this->actingAs($librarian)
        ->getJson('/api/notifications/summary');

    $response->assertOk()
        ->assertJsonStructure([
            'total',
            'pending',
            'retrying',
            'sent',
            'failed',
        ]);

    $this->assertSame(
        $response->json('total'),
        $response->json('pending')
        + $response->json('retrying')
        + $response->json('sent')
        + $response->json('failed')
    );
}

public function test_notification_summary_counts_all_statuses_correctly(): void
{
    $librarian = User::factory()->create([
        'role' => 'librarian',
    ]);

    $borrower = User::factory()->create([
        'role' => 'borrower',
    ]);

    $book = \App\Models\Book::create([
        'isbn' => 'TEST-SUM-001',
        'title' => 'Summary Test Book',
        'author' => 'Test Author',
        'category' => 'Testing',
        'total_copies' => 4,
        'available_copies' => 0,
    ]);

    $loan = \App\Models\Loan::create([
        'borrower_id' => $borrower->id,
        'book_id' => $book->id,
        'borrowed_at' => now()->subDays(10),
        'due_at' => now()->subDays(3),
        'status' => 'overdue',
    ]);

    foreach (['pending', 'retrying', 'sent', 'failed'] as $status) {
        \App\Models\NotificationLog::create([
            'loan_id' => $loan->id,
            'borrower_id' => $borrower->id,
            'notification_type' => 'overdue_reminder',
            'status' => $status,
            'attempts' => 1,
        ]);
    }

    $response = $this->actingAs($librarian)
        ->getJson('/api/notifications/summary');

    $response->assertOk()
        ->assertJson([
            'total' => 4,
            'pending' => 1,
            'retrying' => 1,
            'sent' => 1,
            'failed' => 1,
        ]);
}

}
