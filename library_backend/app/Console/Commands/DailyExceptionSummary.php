<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('library:daily-exception-summary')]
#[Description('Generate a daily summary of notification exceptions')]
class DailyExceptionSummary extends Command
{
    public function handle(): int
    {
        $pending = NotificationLog::where('status', 'pending')->count();

        $retrying = NotificationLog::where('status', 'retrying')->count();

        $failed = NotificationLog::where('status', 'failed')->count();

        $total = NotificationLog::count();

        $summary = [
            'date' => now()->toDateString(),
            'total_notifications' => $total,
            'pending' => $pending,
            'retrying' => $retrying,
            'failed_manual_review' => $failed,
        ];

        Log::info('Daily notification exception summary', $summary);

        $this->info('DAILY NOTIFICATION EXCEPTION SUMMARY');
        $this->line('Date: ' . $summary['date']);
        $this->line('Total notifications: ' . $total);
        $this->line('Pending: ' . $pending);
        $this->line('Retrying: ' . $retrying);
        $this->line('Failed / Manual review: ' . $failed);

        return self::SUCCESS;
    }
}
