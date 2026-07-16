<?php

namespace App\Policies;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * 読書計画を編集・更新できるか判定する。
     *
     * 所有者本人かつ、
     * 未読・読書中・期限切れの場合のみ編集可能。
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id
            && in_array($readingPlan->status, [
                ReadingPlanStatus::Planned,
                ReadingPlanStatus::Reading,
                ReadingPlanStatus::Expired,
            ], true);
    }

    /**
     * 読書計画を削除できるか判定する。
     *
     * 所有者本人のみ削除可能。
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * 読書を開始できるか判定する。
     *
     * 所有者本人かつ、
     * 未読の場合のみ読書中へ変更可能。
     */
    public function start(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id
            && $readingPlan->status === ReadingPlanStatus::Planned;
    }

    /**
     * 読書計画を読了にできるか判定する。
     *
     * 所有者本人かつ、
     * 未読・読書中・期限切れの場合のみ読了可能。
     */
    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id
            && in_array($readingPlan->status, [
                ReadingPlanStatus::Planned,
                ReadingPlanStatus::Reading,
                ReadingPlanStatus::Expired,
            ], true);
    }
}
