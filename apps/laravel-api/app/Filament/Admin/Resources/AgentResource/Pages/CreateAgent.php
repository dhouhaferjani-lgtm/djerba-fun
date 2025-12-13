<?php

namespace App\Filament\Admin\Resources\AgentResource\Pages;

use App\Filament\Admin\Resources\AgentResource;
use App\Models\Agent;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate API credentials
        $credentials = Agent::generateCredentials();

        $data['api_key'] = $credentials['api_key_hashed'];
        $data['api_secret'] = $credentials['api_secret_encrypted'];

        // Store plain credentials temporarily for display
        session()->flash('agent_credentials', [
            'api_key' => $credentials['api_key'],
            'api_secret' => $credentials['api_secret'],
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        $credentials = session('agent_credentials');

        if ($credentials) {
            Notification::make()
                ->title('Agent Created Successfully')
                ->body('Please save the API credentials shown below. They will not be displayed again.')
                ->success()
                ->persistent()
                ->send();

            Notification::make()
                ->title('API Credentials')
                ->body("API Key: {$credentials['api_key']}\n\nAPI Secret: {$credentials['api_secret']}")
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
