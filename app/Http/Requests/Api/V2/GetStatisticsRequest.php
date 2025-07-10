<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetStatisticsRequest extends FormRequest
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
        $maxDays = config('telegram.max_statistics_days', 15);

        return [
            'channel' => ['required', 'string', 'regex:/^[a-zA-Z0-9_@]+$/'],
            'days' => ['required', 'integer', 'min:1', 'max:' . $maxDays],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $maxDays = config('telegram.max_statistics_days', 15);

        return [
            'channel.required' => 'Channel parameter is required.',
            'channel.regex' => 'Invalid channel format. Use only letters, numbers, underscore and @.',
            'days.required' => 'Days parameter is required.',
            'days.integer' => 'Days must be a number.',
            'days.min' => 'Days must be at least 1.',
            'days.max' => "Days must be between 1 and {$maxDays}.",
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->route('channel'),
            'days' => $this->route('days', 7),
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
     * Get the days parameter
     */
    public function getDays(): int
    {
        return $this->validated()['days'];
    }
}
