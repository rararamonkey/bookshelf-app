<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価を選択してください。',
            'rating.integer' => '評価は数字で入力してください。',
            'rating.between' => '評価は1～5で選択してください。',

            'comment.required' => 'レビューを入力してください。',
            'comment.string' => 'レビューの形式が正しくありません。',
            'comment.max' => 'レビューは1000文字以内で入力してください。',
        ];
    }
}