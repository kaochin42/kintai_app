<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceUpdateRequest extends FormRequest
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
            'clock_out' => ['after:clock_in'],
            'comment' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'comment.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = Carbon::parse($this->clock_in);
            $clockOut = Carbon::parse($this->clock_out);

            foreach ($this->breaks as $index => $break) {
                if (!$break['break_in'] && !$break['break_out']) {
                    continue;
                }

                $breakIn = Carbon::parse($break['break_in']);
                $breakOut = Carbon::parse($break['break_out']);

                if ($breakIn->lt($clockIn) || $breakIn->gt($clockOut)) {
                    $validator->errors()->add('breaks.' . $index . '.break_in', '休憩時間が不適切な値です');
                }

                if ($breakOut->gt($clockOut)) {
                    $validator->errors()->add('breaks.' . $index . '.break_out', '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}
