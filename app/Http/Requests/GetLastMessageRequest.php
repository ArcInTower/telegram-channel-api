<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLastMessageRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'channel' => 'required|string|min:1|max:100|regex:/^[a-zA-Z0-9_@]+$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'channel.required' => 'Channel username is required',
            'channel.regex' => 'Channel username can only contain letters, numbers, underscores and @ symbol',
        ];
    }
}
