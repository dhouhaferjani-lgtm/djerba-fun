<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

abstract class BaseResource extends JsonResource
{
    /**
     * Convert array keys to camelCase recursively
     */
    protected function toCamelCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $camelKey = is_string($key) ? Str::camel($key) : $key;

            if (is_array($value)) {
                $result[$camelKey] = $this->toCamelCase($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }
}
