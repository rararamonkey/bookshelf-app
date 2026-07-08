<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReadingPlan;
use App\Models\Book;
use App\Http\Requests\ReadingPlanRequest;

class ReadingPlanController extends Controller
{
    public function index(Request $request)
{
    $query = auth()->user()
        ->readingPlans()
        ->with('book');

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    $readingPlans = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view('reading-plans.index', compact('readingPlans'));
}

public function create()
{
    $books = Book::orderBy('title')->get();

    return view('reading-plans.create', compact('books'));
}

public function store(ReadingPlanRequest $request)
{
    auth()->user()->readingPlans()->create([
        'book_id' => $request->book_id,
        'due_date' => $request->due_date,
        'status' => 'planned',
    ]);

    return redirect()->route('reading-plans.index')
        ->with('success', '読書計画を登録しました。');
}

public function updateStatus(ReadingPlan $readingPlan)
{
    $this->authorize('update', $readingPlan);

    $nextStatus = match ($readingPlan->status) {
        'planned' => 'reading',
        'reading' => 'completed',
        default => 'completed',
    };

    $readingPlan->update([
        'status' => $nextStatus,
    ]);

    return redirect()
        ->route('reading-plans.index')
        ->with('success', '読書状態を更新しました。');
}
public function edit(ReadingPlan $readingPlan)
{
    $this->authorize('update', $readingPlan);

    $books = Book::orderBy('title')->get();

    return view('reading-plans.edit', compact('readingPlan', 'books'));
}

public function update(ReadingPlanRequest $request, ReadingPlan $readingPlan)
{
    $this->authorize('update', $readingPlan);

    $readingPlan->update($request->validated());

    return redirect()->route('reading-plans.index')
        ->with('success', '読書計画を更新しました。');
}
public function destroy(ReadingPlan $readingPlan)
{
    $this->authorize('delete', $readingPlan);

    $readingPlan->delete();

    return redirect()->route('reading-plans.index')
        ->with('success', '読書計画を削除しました。');
}
}
