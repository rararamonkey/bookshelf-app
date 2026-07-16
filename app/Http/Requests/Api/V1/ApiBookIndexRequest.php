<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApiBookIndexRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',

            'genre_id.integer' => 'ジャンルIDは数字で指定してください。',
            'genre_id.exists' => '指定されたジャンルは存在しません。',

            'page.integer' => 'ページ番号は数字で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',

            'per_page.integer' => '1ページあたりの件数は数字で指定してください。',
            'per_page.between' => '1ページあたりの件数は1～100件で指定してください。',
        ];
    }
}
