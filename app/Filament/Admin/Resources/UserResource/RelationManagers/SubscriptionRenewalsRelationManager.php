<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionRenewalsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptionRenewals';

    protected static ?string $title = 'Riwayat Perpanjangan';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('subscription.plan.name')
                    ->label('Paket'),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Durasi')
                    ->formatStateUsing(fn (int $state) => "{$state} hari"),

                Tables\Columns\TextColumn::make('extended_from')
                    ->label('Dari')
                    ->dateTime('d M Y'),

                Tables\Columns\TextColumn::make('extended_until')
                    ->label('Sampai')
                    ->dateTime('d M Y'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->limit(30),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diproses')
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}