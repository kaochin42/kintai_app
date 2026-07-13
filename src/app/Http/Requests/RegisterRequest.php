<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name'                  => ['required'],
            'email'                 => ['required', 'email', 'unique:users'],
            'password'              => ['required', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    public function messages()
    {
        return [
            'name.required'                   => 'お名前を入力してください',
            'email.required'                  => 'メールアドレスを入力してください',
            'email.email'                     => 'メールアドレスはメール形式で入力してください',
            'password.required'               => 'パスワードを入力してください',
            'password.min'                    => 'パスワードは8文字以上で入力してください',
            'password_confirmation.same'      => 'パスワードと一致しません',
            'password_confirmation.required'  => '確認用パスワードを入力してください',
        ];
    }
}
