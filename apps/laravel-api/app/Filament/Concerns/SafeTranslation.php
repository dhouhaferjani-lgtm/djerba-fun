<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

/**
 * Trait to safely handle translation values that might be malformed (nested arrays).
 *
 * This handles cases where Spatie's HasTranslations trait returns nested arrays
 * like {"en": {"en": "value"}} instead of {"en": "value"} due to earlier bugs.
 */
trait SafeTranslation
{
    /**
     * Safely extract string from potentially nested translation arrays.
     */
    protected function safeTranslation($value, string $default = '-'): string
    {
        return self::extractTranslation($value, $default);
    }

    /**
     * Static version for use in closures and callbacks.
     */
    public static function extractTranslation($value, string $default = '-'): string
    {
        if ($value === null) {
            return $default;
        }

        if (is_string($value)) {
            return $value !== '' ? $value : $default;
        }

        if (is_array($value)) {
            // Try current locale, then English, then any value
            $value = $value[app()->getLocale()] ?? $value['en'] ?? reset($value) ?: $default;

            // If still an array, keep drilling down (handles double-nested arrays)
            while (is_array($value)) {
                $value = reset($value) ?: $default;
            }

            return is_string($value) && $value !== '' ? $value : $default;
        }

        return $default;
    }
}
