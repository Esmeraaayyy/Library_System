<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Book;
use App\Models\Loan;
use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRetryTest extends TestCase
{
    use RefreshDatabase;

    private function createNotification(string $type = 'overdue_reminder'): NotificationLog
    {
        $borrower = User::factory()->create([
            'role' => 'borrower',
        ]);

        $book = Book::create([
            'isbn' => 'TEST-RETRY-' . uniqid(),
            'title' => 'Retry Test Book',
            'author' => 'Test Author',
            'category' => 'Testing',
            'total_copies' => 1,
            'available_copies' => 0,
        ]);

        $loan = Loan::create([
            'borrower_id' => $borrower->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
            'status' => 'overdue',
        ]);

        return NotificationLog::create([
            'loan_id' => $loan->id,
            'borrower_id' => $borrower->id,
            'notification_type' => $type,
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }

    public function test_temporary_failure_schedules_retry(): void
    {
        $notification = $this->createNotification();

        $this->artisan('library:process-pending-notifications')
            ->assertSuccessful();

        $notification->refresh();

        $this->assertSame('retrying', $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertNotNull($notification->next_retry_at);
        $this->assertNotNull($notification->error_message);
    }

public function test_notification_recovers_after_temporary_failure(): void
{
    $notification = $this->createNotification();

    // First attempt: temporary failure.
    $this->artisan('library:process-pending-notifications')
        ->assertSuccessful();

    $notification->refresh();

    $this->assertSame('retrying', $notification->status);
    $this->assertSame(1, $notification->attempts);

    // Make the scheduled retry available.
    $notification->update([
        'next_retry_at' => now()->subMinute(),
    ]);

    // Second attempt: simulated success.
    $this->artisan('library:process-pending-notifications')
        ->assertSuccessful();

    $notification->refresh();

    $this->assertSame('sent', $notification->status);
    $this->assertSame(2, $notification->attempts);
    $this->assertNotNull($notification->sent_at);
    $this->assertNull($notification->next_retry_at);
    $this->assertNull($notification->error_message);
}

public function test_permanent_failure_is_escalated_for_manual_review(): void
{
    $notification = $this->createNotification('permanent_failure_test');

    $this->artisan('library:process-pending-notifications')
        ->assertSuccessful();

    $notification->refresh();

    $this->assertSame('failed', $notification->status);
    $this->assertSame(1, $notification->attempts);
    $this->assertNull($notification->next_retry_at);
    $this->assertStringContainsString(
        'Manual review required',
        $notification->error_message
    );
}

public function test_maximum_retry_attempts_are_enforced(): void
{
    $notification = $this->createNotification();

    $notification->update([
        'status' => 'retrying',
        'attempts' => 3,
        'next_retry_at' => null,
    ]);

    $this->artisan('library:process-pending-notifications')
        ->assertSuccessful();

    $notification->refresh();

    $this->assertSame('failed', $notification->status);
    $this->assertSame(3, $notification->attempts);
    $this->assertNull($notification->next_retry_at);
    $this->assertStringContainsString(
        'Maximum retry attempts reached',
        $notification->error_message
    );
}
}
