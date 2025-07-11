<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowStatisticsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if channel is blocked
        $channel = $this->route('channel');
        if ($channel) {
            $normalizedChannel = strtolower(ltrim($channel, '@'));
            $blockedChannels = config('telegram.blocked_channels', []);

            if (in_array($normalizedChannel, array_map('strtolower', $blockedChannels))) {
                return false;
            }
        }

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
            'days' => ['nullable', 'integer', 'min:1', 'max:' . $maxDays],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $maxDays = config('telegram.max_statistics_days', 15);

        return [
            'channel.required' => 'Channel username is required.',
            'channel.regex' => 'Invalid channel format. Use only letters, numbers, underscore and @.',
            'days.integer' => 'Days must be a number.',
            'days.min' => 'Days must be at least 1.',
            'days.max' => "Days cannot exceed {$maxDays}.",
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Merge route parameters with request data
        $this->merge([
            'channel' => $this->route('channel'),
            'days' => $this->route('days') ?? 7,
        ]);
    }

    /**
     * Get the validated channel name
     */
    public function getChannel(): string
    {
        return $this->validated()['channel'];
    }

    /**
     * Get the validated days parameter
     */
    public function getDays(): int
    {
        return $this->validated()['days'] ?? 7;
    }
}
