<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\ReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画一覧を表示する。
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status');

        $readingPlans = ReadingPlan::with('book.genres')
            ->whereBelongsTo($request->user())
            ->when($currentStatus, function ($query, string $currentStatus) {
                $query->where('status', $currentStatus);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成画面を表示する。
     */
    public function create(): View
    {
        $books = Book::query()
            ->orderBy('title')
            ->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を登録する。
     */
    public function store(ReadingPlanRequest $request): RedirectResponse
    {
        $request->user()->readingPlans()->create([
            'book_id' => $request->integer('book_id'),
            'target_date' => $request->date('target_date'),
            'status' => ReadingPlanStatus::Planned,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    /**
     * 読書計画編集画面を表示する。
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する。
     */
    public function update(
        ReadingPlanRequest $request,
        ReadingPlan $readingPlan
    ): RedirectResponse {
        $this->authorize('update', $readingPlan);

        $updateData = [
            'target_date' => $request->date('target_date'),
        ];

        if ($readingPlan->status === ReadingPlanStatus::Expired) {
            $updateData['status'] = ReadingPlanStatus::Planned;
        }

        $readingPlan->update($updateData);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書を開始する。
     */
    public function start(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('start', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Reading,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書を開始しました。');
    }

    /**
     * 読書計画を読了にする。
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了にしました。');
    }

    /**
     * 読書計画を削除する。
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}
