<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ApiBookStoreRequest extends FormRequest
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
        'user_id' => ['required', 'integer', 'exists:users,id'],
        'title' => ['required', 'string', 'max:255'],
        'author' => ['required', 'string', 'max:255'],
        'isbn' => ['nullable', 'digits:13', 'unique:books,isbn'],
        'published_date' => ['nullable', 'date'],
        'description' => ['nullable', 'string'],
        'image_url' => ['nullable', 'url', 'max:255'],
        'genres' => ['required', 'array', 'min:1'],
        'genres.*' => ['integer', 'exists:genres,id'],
    ];
}

public function attributes(): array
{
    return [
        'user_id' => '登録者',
        'title' => 'タイトル',
        'author' => '著者',
        'isbn' => 'ISBN',
        'published_date' => '出版日',
        'description' => '説明',
        'image_url' => '画像URL',
        'genres' => 'ジャンル',
        'genres.*' => 'ジャンル',
    ];
}
}
