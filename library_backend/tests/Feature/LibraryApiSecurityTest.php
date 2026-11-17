<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_borrowings_requires_authentication(): void
    {
        $response = $this->getJson('/api/borrowings');

        $response->assertUnauthorized();
    }

    public function test_invalid_token_cannot_access_borrowings(): void
    {
        $response = $this->withHeader(
            'Authorization',
            'Bearer invalid-token-12345'
        )->getJson('/api/borrowings');

        $response->assertUnauthorized();
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

    public function test_librarian_can_access_notification_summary(): void
    {
        $librarian = User::factory()->create([
            'role' => 'librarian',
        ]);

        $response = $this->actingAs($librarian)
            ->getJson('/api/notifications/summary');

        $response->assertOk();
    }

public function test_borrower_can_only_view_own_borrowings(): void
{
    $borrowerOne = User::factory()->create([
        'role' => 'borrower',
    ]);

    $borrowerTwo = User::factory()->create([
        'role' => 'borrower',
    ]);

    $book = \App\Models\Book::create([
        'title' => 'Test Book',
        'author' => 'Test Author',
        'isbn' => 'TEST-ISO-001',
        'category' => 'Testing',
        'total_copies' => 2,
        'available_copies' => 0,
    ]);

    $loanOne = \App\Models\Loan::create([
        'borrower_id' => $borrowerOne->id,
        'book_id' => $book->id,
        'borrowed_at' => now()->subDays(10),
        'due_at' => now()->subDays(3),
        'status' => 'overdue',
    ]);

    \App\Models\Loan::create([
        'borrower_id' => $borrowerTwo->id,
        'book_id' => $book->id,
        'borrowed_at' => now()->subDays(8),
        'due_at' => now()->subDays(1),
        'status' => 'overdue',
    ]);

    $response = $this->actingAs($borrowerOne)
        ->getJson('/api/borrowings');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $loanOne->id,
            'borrower_id' => $borrowerOne->id,
        ]);
}

public function test_login_rejects_incorrect_password(): void
{
    User::factory()->create([
        'email' => 'test.login@example.test',
        'password' => 'CorrectPassword123!',
        'role' => 'borrower',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test.login@example.test',
        'password' => 'WrongPassword123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonMissingPath('token');
}

public function test_librarian_can_view_all_borrowings(): void
{
    $librarian = User::factory()->create([
        'role' => 'librarian',
    ]);

    $borrowerOne = User::factory()->create([
        'role' => 'borrower',
    ]);

    $borrowerTwo = User::factory()->create([
        'role' => 'borrower',
    ]);

    $book = \App\Models\Book::create([
        'title' => 'Test Book',
        'author' => 'Test Author',
        'isbn' => 'TEST-LIB-001',
        'category' => 'Testing',
        'total_copies' => 2,
        'available_copies' => 0,
    ]);

    \App\Models\Loan::create([
        'borrower_id' => $borrowerOne->id,
        'book_id' => $book->id,
        'borrowed_at' => now()->subDays(10),
        'due_at' => now()->subDays(3),
        'status' => 'overdue',
    ]);

    \App\Models\Loan::create([
        'borrower_id' => $borrowerTwo->id,
        'book_id' => $book->id,
        'borrowed_at' => now()->subDays(8),
        'due_at' => now()->subDays(1),
        'status' => 'overdue',
    ]);

    $response = $this->actingAs($librarian)
        ->getJson('/api/borrowings');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
}


public function test_logout_revokes_access_token(): void
{
    $user = User::factory()->create([
        'role' => 'borrower',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $logoutResponse = $this->withToken($token)
        ->postJson('/api/logout');

    $logoutResponse->assertOk();

    // Clear cached authentication guards before testing the revoked token.
    $this->app['auth']->forgetGuards();

    $protectedResponse = $this->withToken($token)
        ->getJson('/api/borrowings');

    $protectedResponse->assertUnauthorized();
}

public function test_registration_rejects_invalid_input(): void
{
    $response = $this->postJson('/api/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => '123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'email',
            'password',
        ]);
}

public function test_registration_rejects_duplicate_email(): void
{
    User::factory()->create([
        'name' => 'Existing Borrower',
        'email' => 'existing@example.test',
        'password' => 'Password123!',
        'role' => 'borrower',
    ]);

    $response = $this->postJson('/api/register', [
        'name' => 'Another Borrower',
        'email' => 'existing@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
}

public function test_successful_registration_creates_borrower_account(): void
{
    $response = $this->postJson('/api/register', [
        'name' => 'New Test Borrower',
        'email' => 'newborrower@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
            'token',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newborrower@example.test',
        'role' => 'borrower',
    ]);
}

}