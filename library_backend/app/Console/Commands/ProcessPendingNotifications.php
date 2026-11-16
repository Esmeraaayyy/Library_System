<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('library:process-pending-notifications')]
#[Description('Process pending notifications with bounded retries and recovery')]
class ProcessPendingNotifications extends Command
{
    private const MAX_ATTEMPTS = 3;

    protected function retryDelay(int $attempt): int
    {
        return match ($attempt) {
            1 => 1,
            2 => 5,
            default => 0,
        };
    }

    public function handle(): int
    {
        $notifications = NotificationLog::whereIn('status', ['pending', 'retrying'])
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->get();

        foreach ($notifications as $notification) {

            if ($notification->attempts >= self::MAX_ATTEMPTS) {
                $notification->update([
                    'status' => 'failed',
                    'next_retry_at' => null,
                    'error_message' => 'Maximum retry attempts reached. Manual review required.',
                ]);

                Log::warning('Notification escalated for manual review', [
                    'notification_id' => $notification->id,
                    'loan_id' => $notification->loan_id,
                    'attempts' => $notification->attempts,
                ]);

                continue;
            }

            $attempt = $notification->attempts + 1;

            $notification->update([
                'attempts' => $attempt,
                'last_attempt_at' => now(),
            ]);

            // PERMANENT FAILURE TEST
            if ($notification->notification_type === 'permanent_failure_test') {

                $notification->update([
                    'status' => 'failed',
                    'next_retry_at' => null,
                    'error_message' => 'Permanent notification service failure. Manual review required.',
                ]);

                Log::warning('Permanent notification failure escalated for manual review', [
                    'notification_id' => $notification->id,
                    'loan_id' => $notification->loan_id,
                    'attempts' => $attempt,
                ]);

                $this->error(
                    "Notification {$notification->id}: permanent failure. Manual review required."
                );

                continue;
            }

            // CONTROLLED TRANSIENT FAILURE SIMULATION
            // First attempt fails temporarily.
            // Second attempt succeeds.
            $simulatedSuccess = $attempt >= 2;

            if ($simulatedSuccess) {

                $notification->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'next_retry_at' => null,
                    'error_message' => null,
                ]);

                Log::info('Notification simulation successful', [
                    'notification_id' => $notification->id,
                    'attempt' => $attempt,
                ]);

                $this->info(
                    "Notification {$notification->id}: simulated success."
                );

            } else {

                $delay = $this->retryDelay($attempt);

                $notification->update([
                    'status' => 'retrying',
                    'next_retry_at' => now()->addMinutes($delay),
                    'error_message' => 'Simulated temporary notification service failure.',
                ]);

                Log::warning('Notification attempt failed; retry scheduled', [
                    'notification_id' => $notification->id,
                    'attempt' => $attempt,
                    'retry_delay_minutes' => $delay,
                ]);

                $this->warn(
                    "Notification {$notification->id}: temporary failure. Retry scheduled."
                );
            }
        }

        $this->info('Pending notification processing completed.');

        return self::SUCCESS;
    }
}