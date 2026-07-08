<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReadingPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    return [
        'book_id' => ['required', 'exists:books,id'],
        'due_date' => ['required', 'date'],
        'status' => ['sometimes', 'required', 'in:planned,reading,completed'],
    ];
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
}
