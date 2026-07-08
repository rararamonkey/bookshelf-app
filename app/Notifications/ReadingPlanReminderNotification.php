<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ReadingPlan $readingPlan,
        public string $type
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $bookTitle = $this->readingPlan->book->title;

        $message = match ($this->type) {
            'tomorrow' => "『{$bookTitle}』の読了期限は明日です。",
            'overdue' => "『{$bookTitle}』の読了期限を過ぎています。",
            default => "『{$bookTitle}』の読書計画を確認してください。",
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'book_title' => $bookTitle,
            'type' => $this->type,
            'message' => $message,
            'url' => route('reading-plans.index'),
        ];
    }
}