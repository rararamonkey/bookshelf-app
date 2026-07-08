<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $readingPlanId = $this->route('reading_plan')?->id ?? $this->route('readingPlan')?->id;

        return [
            'book_id' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'integer',
                'exists:books,id',
                Rule::unique('reading_plans', 'book_id')
                    ->where('user_id', auth()->id())
                    ->ignore($readingPlanId),
            ],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.exists' => '選択した書籍が存在しません。',
            'book_id.unique' => 'この書籍の読書計画はすでに登録されています。',
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '期日は正しい日付で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を入力してください。',
        ];
    }
}