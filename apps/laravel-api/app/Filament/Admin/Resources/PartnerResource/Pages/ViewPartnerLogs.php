<?php

namespace App\Filament\Admin\Resources\PartnerResource\Pages;

use App\Filament\Admin\Resources\PartnerResource;
use App\Models\Partner;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewPartnerLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PartnerResource::class;

    protected static string $view = 'filament.admin.resources.partner-resource.pages.view-partner-logs';

    public Partner $record;

    public function mount(int|string $record): void
    {
        $this->record = Partner::findOrFail($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->record->auditLogs()->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('response_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 300 && $state < 400 => 'info',
                        $state >= 400 && $state < 500 => 'warning',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),

                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('response_status')
                    ->label('Status Code')
                    ->options([
                        '200' => '200 OK',
                        '400' => '400 Bad Request',
                        '401' => '401 Unauthorized',
                        '403' => '403 Forbidden',
                        '404' => '404 Not Found',
                        '429' => '429 Too Many Requests',
                        '500' => '500 Server Error',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public function getTitle(): string
    {
        return "Audit Logs: {$this->record->name}";
    }
}
