<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
            'student_number' => 'required|string|unique:students',
            'name' => 'required|string|max:255',
            'name_transcription' => 'nullable|string|max:255',
            'email' => 'required|email|unique:students',
            'grade' => 'required|string|max:255',
            'class' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_number.required' => '学籍番号は必須です',
            'student_number.unique' => 'この学籍番号は既に使用されています',
            'name.required' => '名前は必須です',
            'email.required' => 'メールアドレスは必須です',
            'email.email' => '有効なメールアドレスを入力してください',
            'email.unique' => 'このメールアドレスは既に使用されています',
            'grade.required' => '学年は必須です',
            'class.required' => 'クラスは必須です',
        ];
    }
}
