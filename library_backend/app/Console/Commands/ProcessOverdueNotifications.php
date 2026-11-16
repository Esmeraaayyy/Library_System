<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\NotificationLog;
use Illuminate\Console\Command;

class ProcessOverdueNotifications extends Command
{
    protected $signature = 'library:process-overdue';

    protected $description = 'Detect overdue books and create notification logs';

    public function handle(): int
    {
        $today = now()->toDateString();

        $overdueLoans = Loan::whereNull('returned_at')
            ->whereDate('due_at', '<', $today)
            ->whereIn('status', ['active', 'overdue'])
            ->get();

        $createdCount = 0;

        foreach ($overdueLoans as $loan) {
            if ($loan->status === 'active') {
                $loan->status = 'overdue';
                $loan->save();
            }

            $notification = NotificationLog::firstOrCreate(
                [
                    'loan_id' => $loan->id,
                    'notification_type' => 'overdue_reminder',
                ],
                [
                    'borrower_id' => $loan->borrower_id,
                    'status' => 'pending',
                    'attempts' => 0,
                ]
            );

            if ($notification->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        $this->info('Overdue notification processing completed.');
        $this->info('Overdue loans found: ' . $overdueLoans->count());
        $this->info('New notification logs created: ' . $createdCount);

        return self::SUCCESS;
    }
}
