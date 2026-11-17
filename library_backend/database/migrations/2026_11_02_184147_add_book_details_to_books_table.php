<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Book details are already created by the create_books_table migration.
    }

    public function down(): void
    {
        // No changes to reverse because this migration is intentionally a no-op.
    }
};