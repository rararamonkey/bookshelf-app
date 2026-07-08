<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    protected $signature = 'reading-plans:send-reminders';

    protected $description = '期限を過ぎた読書計画のリマインダー通知を送信します。';

    public function handle(): int
    {
        $plans = ReadingPlan::with(['user', 'book'])
            ->whereIn('status', [
                ReadingPlanStatus::Planned,
                ReadingPlanStatus::Reading,
            ])
            ->whereDate('target_date', '<', today())
            ->get();

        $plans->each(function (ReadingPlan $plan): void {
            $alreadyNotified = $plan->user->notifications()
                ->where('type', ReadingPlanReminder::class)
                ->where('data->reading_plan_id', $plan->id)
                ->whereDate('created_at', today())
                ->exists();

            if (! $alreadyNotified) {
                $plan->user->notify(new ReadingPlanReminder($plan));
            }
        });

        $this->info("{$plans->count()}件の読書計画を確認しました。");

        return self::SUCCESS;
    }
}