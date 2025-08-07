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
            'title_transcription' => 'nullable|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'published_date' => 'nullable|date',
            'isbn' => 'nullable|string|max:20',
            'pages' => 'nullable|integer|min:1',
            'price' => 'nullable|integer|min:0',
            'ndc' => 'nullable|string|max:100',
            'reading_status' => ['required', Rule::enum(ReadingStatus::class)],
            'acceptance_date' => 'nullable|date',
            'acceptance_type' => 'nullable|string|max:255',
            'acceptance_source' => 'nullable|string|max:255',
            'discard' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'title_transcription.max' => 'タイトルのヨミは255文字以内で入力してください。',
            'author.required' => '著者は必須です。',
            'author.max' => '著者は255文字以内で入力してください。',
            'published_date.date' => '出版日は正しい日付形式で入力してください。',
            'pages.integer' => 'ページ数は数値で入力してください。',
            'pages.min' => 'ページ数は1以上で入力してください。',
            'price.integer' => '価格は数値で入力してください。',
            'price.min' => '価格は0以上で入力してください。',
            'reading_status.required' => '読書状況は必須です。',
            'acceptance_date.date' => '受け入れ日は正しい日付形式で入力してください。',
            'acceptance_type.max' => '受け入れ種別は255文字以内で入力してください。',
            'acceptance_source.max' => '受け入れ元は255文字以内で入力してください。',
            'discard.max' => '廃棄情報は255文字以内で入力してください。',
        ];
    }
}
