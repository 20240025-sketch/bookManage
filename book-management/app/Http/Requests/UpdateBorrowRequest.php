<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBorrowRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'book_id' => 'exists:books,id',
            'student_id' => 'exists:students,id',
            'borrowed_date' => 'date',
            'returned_date' => 'nullable|date|after_or_equal:borrowed_date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.exists' => '指定された本が見つかりません',
            'student_id.exists' => '指定された生徒が見つかりません',
            'borrowed_date.date' => '借りた日は正しい日付形式で入力してください',
            'returned_date.date' => '返却日は正しい日付形式で入力してください',
            'returned_date.after_or_equal' => '返却日は借りた日と同じかそれ以降の日付である必要があります',
        ];
    }
}
