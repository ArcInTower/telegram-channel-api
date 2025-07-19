<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetPollsRequest extends FormRequest
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
            'period' => ['sometimes', 'in:1hour,1day,7days,30days,3months,6months,1year'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'offset' => ['sometimes', 'integer', 'min:0'],
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
            'period.in' => 'Invalid period. Valid options: 1hour, 1day, 7days, 30days, 3months, 6months, 1year.',
            'limit.integer' => 'Limit must be a number.',
            'limit.min' => 'Limit must be at least 1.',
            'limit.max' => 'Limit cannot exceed 100.',
            'offset.integer' => 'Offset must be a number.',
            'offset.min' => 'Offset cannot be negative.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->route('channel'),
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
     * Get the period
     */
    public function getPeriod(): string
    {
        return $this->validated()['period'] ?? '7days';
    }

    /**
     * Get the limit
     */
    public function getLimit(): int
    {
        return $this->validated()['limit'] ?? 50;
    }

    /**
     * Get the offset
     */
    public function getOffset(): ?int
    {
        return $this->validated()['offset'] ?? null;
    }
}