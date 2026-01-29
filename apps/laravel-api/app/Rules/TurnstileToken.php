<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileToken implements ValidationRule
{
    public function __construct(
        private readonly ?string $remoteIp = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If disabled, ALWAYS pass - no regression
        if (! config('services.turnstile.enabled')) {
            return;
        }

        // Only validate if enabled AND token provided
        if (empty($value)) {
            $fail(__('validation.turnstile_required'));

            return;
        }

        // Verify with Cloudflare
        $service = app(TurnstileService::class);
        $result = $service->verify((string) $value, $this->remoteIp);

        if (! $result['success']) {
            $fail(__('validation.turnstile_failed'));
        }
    }
}
