<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     * Converts camelCase input to snake_case for Laravel.
     */
    protected function prepareForValidation(): void
    {
        $this->merge($this->toSnakeCase($this->all()));
    }

    /**
     * Convert array keys from camelCase to snake_case recursively.
     */
    protected function toSnakeCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $snakeKey = is_string($key) ? Str::snake($key) : $key;

            if (is_array($value)) {
                $result[$snakeKey] = $this->toSnakeCase($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }

        return $result;
    }
}
