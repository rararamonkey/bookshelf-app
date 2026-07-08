<?php

namespace App\Console\Commands;

use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    protected $signature = 'reading-plans:send-reminders';

    protected $description = '読書計画の期限リマインダー通知を送信します';

    public function handle(): int
    {
        $tomorrowPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', '!=', 'completed')
            ->whereDate('due_date', now()->addDay()->toDateString())
            ->get();

        foreach ($tomorrowPlans as $plan) {
            $plan->user->notify(
                new ReadingPlanReminderNotification($plan, 'tomorrow')
            );
        }

        $overduePlans = ReadingPlan::with(['user', 'book'])
            ->where('status', '!=', 'completed')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($overduePlans as $plan) {
            $plan->user->notify(
                new ReadingPlanReminderNotification($plan, 'overdue')
            );
        }

        $this->info('読書計画リマインダー通知を送信しました。');

        return self::SUCCESS;
    }
}