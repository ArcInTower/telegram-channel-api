<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetSinglePollRequest extends FormRequest
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
            'channel' => ['required', 'string', 'regex:/^[a-zA-Z0-9_@]+$/'],
            'messageId' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'channel.required' => 'Channel parameter is required.',
            'channel.regex' => 'Invalid channel format. Use only letters, numbers, underscore and @.',
            'messageId.required' => 'Message ID is required.',
            'messageId.integer' => 'Message ID must be a number.',
            'messageId.min' => 'Message ID must be a positive number.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->route('channel'),
            'messageId' => $this->route('messageId'),
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => [[
                'status' => '400',
                'title' => 'Bad Request',
                'detail' => $validator->errors()->first(),
            ]],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'api_version' => 'v2',
            ],
            'jsonapi' => [
                'version' => '1.1',
            ],
        ], 400));
    }

    /**
     * Get the channel name
     */
    public function getChannel(): string
    {
        return $this->validated()['channel'];
    }

    /**
     * Get the message ID
     */
    public function getMessageId(): int
    {
        return (int) $this->validated()['messageId'];
    }
}