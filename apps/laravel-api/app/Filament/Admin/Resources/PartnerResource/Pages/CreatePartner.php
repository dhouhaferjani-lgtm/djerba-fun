<?php

namespace App\Filament\Admin\Resources\PartnerResource\Pages;

use App\Filament\Admin\Resources\PartnerResource;
use App\Models\Partner;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    /**
     * Store plain credentials temporarily to display after creation.
     */
    protected array $generatedCredentials = [];

    /**
     * Mutate the form data before creating the partner.
     * Automatically generate API credentials.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate API credentials
        $credentials = Partner::generateCredentials();

        // Store plain credentials for display
        $this->generatedCredentials = [
            'api_key' => $credentials['api_key'],
            'api_secret' => $credentials['api_secret'], // Plain secret for one-time display
        ];

        // Add encrypted credentials to data
        $data['api_key'] = $credentials['api_key'];
        $data['api_secret'] = $credentials['api_secret_encrypted'];

        return $data;
    }

    /**
     * Get the success notification with API credentials.
     */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Partner created successfully')
            ->body(
                '**IMPORTANT: Save these credentials - they will not be shown again!**' . "\n\n" .
                '**API Key:** `' . $this->generatedCredentials['api_key'] . '`' . "\n" .
                '**API Secret:** `' . $this->generatedCredentials['api_secret'] . '`' . "\n\n" .
                'The partner can now use these credentials in API requests via X-Partner-Key and X-Partner-Secret headers.'
            )
            ->persistent()
            ->duration(null);
    }
}
