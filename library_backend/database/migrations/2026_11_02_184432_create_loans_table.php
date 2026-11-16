
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('borrower_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('book_id')
                ->constrained('books')
                ->restrictOnDelete();

            $table->date('borrowed_at');
            $table->date('due_at');
            $table->date('returned_at')->nullable();

            $table->enum('status', [
                'active',
                'overdue',
                'returned',
                'disputed',
                'closed',
            ])->default('active');

            $table->timestamps();

            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
