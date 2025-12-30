<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGateway extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'driver',
        'is_enabled',
        'is_default',
        'priority',
        'configuration',
        'test_mode',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'test_mode' => 'boolean',
            'priority' => 'integer',
            'configuration' => 'array',
        ];
    }

    /**
     * Scope to get only enabled gateways.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to get the default gateway.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true)->where('is_enabled', true);
    }

    /**
     * Scope to order by priority (ascending - lower numbers first).
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc')->orderBy('display_name', 'asc');
    }

    /**
     * Get a configuration value.
     *
     * @param  string  $key  The configuration key
     * @param  mixed  $default  Default value if key doesn't exist
     * @return mixed
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $config = $this->configuration ?? [];

        return data_get($config, $key, $default);
    }

    /**
     * Set a configuration value.
     *
     * @param  string  $key  The configuration key
     * @param  mixed  $value  The value to set
     */
    public function setConfigValue(string $key, mixed $value): void
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->configuration = $config;
    }

    /**
     * Check if this gateway is currently enabled.
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    /**
     * Check if this is the default gateway.
     */
    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    /**
     * Enable the gateway.
     */
    public function enable(): void
    {
        $this->update(['is_enabled' => true]);
    }

    /**
     * Disable the gateway.
     */
    public function disable(): void
    {
        $this->update(['is_enabled' => false]);
    }

    /**
     * Set this gateway as the default.
     * Unsets any other gateway marked as default.
     */
    public function setAsDefault(): void
    {
        self::query()->update(['is_default' => false]);
        $this->update(['is_default' => true, 'is_enabled' => true]);
    }

    /**
     * Get the full configuration with defaults for the driver.
     */
    public function getFullConfiguration(): array
    {
        $config = $this->configuration ?? [];

        // Add driver-specific defaults
        return match ($this->driver) {
            'stripe' => array_merge([
                'publishable_key' => '',
                'secret_key' => '',
                'webhook_secret' => '',
            ], $config),
            'clicktopay' => array_merge([
                'merchant_id' => '',
                'api_key' => '',
                'shared_secret' => '',
            ], $config),
            'bank_transfer' => array_merge([
                'bank_name' => '',
                'account_number' => '',
                'routing_number' => '',
                'iban' => '',
                'swift_code' => '',
                'instructions' => '',
            ], $config),
            'offline' => array_merge([
                'instructions' => '',
            ], $config),
            default => $config,
        };
    }

    /**
     * Get the driver instance name for the PaymentGatewayManager.
     */
    public function getDriverName(): string
    {
        return $this->driver;
    }

    /**
     * Get badge color for status display.
     */
    public function getStatusColor(): string
    {
        return $this->is_enabled ? 'success' : 'gray';
    }

    /**
     * Get badge label for status display.
     */
    public function getStatusLabel(): string
    {
        return $this->is_enabled ? 'Enabled' : 'Disabled';
    }
}
