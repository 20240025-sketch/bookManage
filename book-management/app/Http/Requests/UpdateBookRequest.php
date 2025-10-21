<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookId = $this->route('book'); // 更新対象のbook ID
        
        return [
            'title' => 'required|string|max:255',
            'title_transcription' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'published_date' => 'nullable|date',
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($bookId) {
                    // ISBNが入力されている場合のみ重複チェック（自分自身は除外）
                    if (!empty($value)) {
                        $exists = \App\Models\Book::where('isbn', $value)
                            ->where('id', '!=', $bookId)
                            ->exists();
                        if ($exists) {
                            $fail('このISBNは既に他の書籍で登録されています。');
                        }
                    }
                },
            ],
            'pages' => 'nullable|integer|min:1',
            'price' => 'nullable|integer|min:0',
            'ndc' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1|max:999',
            'acceptance_date' => 'nullable|date',
            'acceptance_type' => 'nullable|string|max:255',
            'acceptance_source' => 'nullable|string|max:255',
            'discard' => 'nullable|string|max:255',
            'storage_location' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'title_transcription.max' => 'タイトルのヨミは255文字以内で入力してください。',
            'author.max' => '著者は255文字以内で入力してください。',
            'published_date.date' => '出版日は正しい日付形式で入力してください。',
            'pages.integer' => 'ページ数は数値で入力してください。',
            'pages.min' => 'ページ数は1以上で入力してください。',
            'price.integer' => '価格は数値で入力してください。',
            'price.min' => '価格は0以上で入力してください。',
            'quantity.required' => '冊数は必須です。',
            'quantity.integer' => '冊数は数値で入力してください。',
            'quantity.min' => '冊数は1以上で入力してください。',
            'quantity.max' => '冊数は999以下で入力してください。',
            'acceptance_date.date' => '受け入れ日は正しい日付形式で入力してください。',
            'acceptance_type.max' => '受け入れ種別は255文字以内で入力してください。',
            'acceptance_source.max' => '受け入れ元は255文字以内で入力してください。',
            'discard.max' => '廃棄情報は255文字以内で入力してください。',
        ];
    }
}
