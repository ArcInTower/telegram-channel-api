<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class UserAnonymizationService
{
    /**
     * Check if a username should be anonymized
     */
    public function shouldAnonymize(string $username): bool
    {
        $anonymizedUsers = Config::get('telegram.anonymized_users', []);
        $normalizedUsername = $this->normalizeUsername($username);

        return in_array($normalizedUsername, array_map([$this, 'normalizeUsername'], $anonymizedUsers));
    }

    /**
     * Anonymize a username by showing first and last letter with asterisks
     */
    public function anonymize(string $username): string
    {
        if (!$this->shouldAnonymize($username)) {
            return $username;
        }

        // Preserve @ if present
        $hasAt = str_starts_with($username, '@');
        $usernameToAnonymize = $hasAt ? substr($username, 1) : $username;

        $length = mb_strlen($usernameToAnonymize);

        if ($length <= 2) {
            $anonymized = str_repeat('*', $length);
        } elseif ($length === 3) {
            $anonymized = mb_substr($usernameToAnonymize, 0, 1) . '*' . mb_substr($usernameToAnonymize, -1);
        } else {
            $firstChar = mb_substr($usernameToAnonymize, 0, 1);
            $lastChar = mb_substr($usernameToAnonymize, -1);
            $asterisks = str_repeat('*', $length - 2);
            $anonymized = $firstChar . $asterisks . $lastChar;
        }

        return $hasAt ? '@' . $anonymized : $anonymized;
    }

    /**
     * Process data to anonymize usernames in various formats
     */
    public function processData($data)
    {
        if (is_string($data)) {
            return $this->anonymize($data);
        }

        if (is_array($data)) {
            return $this->processArray($data);
        }

        if (is_object($data)) {
            return $this->processObject($data);
        }

        return $data;
    }

    /**
     * Process array data recursively
     */
    private function processArray(array $data): array
    {
        $processed = [];

        foreach ($data as $key => $value) {
            if ($this->isUsernameField($key)) {
                $processed[$key] = $this->anonymize($value);
            } elseif (is_array($value) || is_object($value)) {
                $processed[$key] = $this->processData($value);
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    /**
     * Process object data recursively
     */
    private function processObject($data)
    {
        $processed = clone $data;

        foreach (get_object_vars($data) as $key => $value) {
            if ($this->isUsernameField($key)) {
                $processed->$key = $this->anonymize($value);
            } elseif (is_array($value) || is_object($value)) {
                $processed->$key = $this->processData($value);
            }
        }

        return $processed;
    }

    /**
     * Check if a field name represents a username
     */
    private function isUsernameField(string $fieldName): bool
    {
        $usernameFields = [
            'username',
            'user_name',
            'from',
            'sender',
            'author',
            'created_by',
            'updated_by',
            'user',
            'name',
        ];

        return in_array(strtolower($fieldName), $usernameFields);
    }

    /**
     * Normalize username by removing @ and converting to lowercase
     */
    private function normalizeUsername(string $username): string
    {
        return strtolower(ltrim($username, '@'));
    }
}
