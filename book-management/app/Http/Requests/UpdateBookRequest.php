<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ReadingStatus;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1000|max:2100',
            'isbn' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:100',
            'reading_status' => ['required', Rule::enum(ReadingStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者は必須です。',
            'author.max' => '著者は255文字以内で入力してください。',
            'publication_year.integer' => '出版年は数値で入力してください。',
            'publication_year.min' => '出版年は1000年以降で入力してください。',
            'publication_year.max' => '出版年は2100年以前で入力してください。',
            'reading_status.required' => '読書状況は必須です。',
        ];
    }
}
