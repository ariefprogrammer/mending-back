<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Riwayat Langganan';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('plan_id')
                ->label('Paket')
                ->options(Plan::pluck('name', 'id'))
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'active' => 'Aktif',
                    'expired' => 'Berakhir',
                    'cancelled' => 'Dibatalkan',
                ])
                ->required(),

            Forms\Components\DateTimePicker::make('trial_ends_at')
                ->label('Trial Berakhir'),

            Forms\Components\DateTimePicker::make('started_at')
                ->label('Mulai')
                ->required(),

            Forms\Components\DateTimePicker::make('ends_at')
                ->label('Berakhir'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Paket'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'expired' => 'gray',
                        'cancelled' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Berakhir')
                    ->dateTime('d M Y')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y'),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y')
                    ->placeholder('-'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('started_at', 'desc');
    }
}