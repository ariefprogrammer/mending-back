<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\UserResource\RelationManagers;
use App\Models\Plan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen SaaS';
    protected static ?string $navigationLabel = 'Pelanggan Aplikasi';
    protected static ?string $modelLabel = 'Pelanggan';
    protected static ?string $pluralModelLabel = 'Pelanggan';

    public static function getEloquentQuery(): Builder
    {
        // Hanya user dengan role owner yang dianggap "pelanggan aplikasi" (SaaS)
        return parent::getEloquentQuery()->where('role', 'owner');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('phone')
                ->label('No. HP')
                ->tel(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('outlets_count')
                    ->label('Jumlah Outlet')
                    ->counts('outlets'),

                Tables\Columns\TextColumn::make('currentSubscription.plan.name')
                    ->label('Paket Aktif')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Premium' => 'success',
                        'Free Trial Premium' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('Belum ada paket'),

                Tables\Columns\TextColumn::make('currentSubscription.trial_ends_at')
                    ->label('Trial Berakhir')
                    ->dateTime('d M Y')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d M Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->label('Paket')
                    ->relationship('currentSubscription.plan', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('startTrial')
                    ->label('Mulai Trial Premium')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn (User $record) => $record->currentSubscription?->plan?->slug !== 'trial_premium')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => static::switchPlan($record, 'trial_premium')),

                Tables\Actions\Action::make('upgradeToPremium')
                    ->label('Upgrade ke Premium')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->visible(fn (User $record) => $record->currentSubscription?->plan?->slug !== 'premium')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => static::switchPlan($record, 'premium')),

                Tables\Actions\Action::make('downgradeToFree')
                    ->label('Turunkan ke Free')
                    ->icon('heroicon-o-arrow-down')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->currentSubscription?->plan?->slug !== 'free')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => static::switchPlan($record, 'free')),

                Tables\Actions\Action::make('renewSubscription')
                    ->label('Perpanjang Langganan')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->visible(fn (User $record) => $record->currentSubscription?->plan?->slug === 'premium')
                    ->form([
                        Forms\Components\Select::make('duration_days')
                            ->label('Durasi Perpanjangan')
                            ->options([
                                30 => '1 Bulan (30 hari)',
                                90 => '3 Bulan (90 hari)',
                                365 => '1 Tahun (365 hari)',
                            ])
                            ->default(30)
                            ->required(),

                        Forms\Components\TextInput::make('note')
                            ->label('Catatan (opsional)')
                            ->placeholder('Contoh: No. invoice / metode pembayaran'),
                    ])
                    ->action(function (User $record, array $data) {
                        $subscription = $record->currentSubscription;

                        if (! $subscription) {
                            Notification::make()
                                ->title('Tidak ada langganan aktif untuk diperpanjang')
                                ->danger()
                                ->send();
                            return;
                        }

                        $subscription->extend((int) $data['duration_days'], $data['note'] ?? null);

                        Notification::make()
                            ->title('Langganan berhasil diperpanjang')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    protected static function switchPlan(User $user, string $planSlug): void
    {
        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        $user->subscriptions()->where('status', 'active')->update(['status' => 'expired']);

        $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'trial_ends_at' => $plan->trial_days ? now()->addDays($plan->trial_days) : null,
            'ends_at' => $plan->slug === 'premium' ? now()->addDays(30) : null,
            'started_at' => now(),
        ]);

        Notification::make()
            ->title("Paket berhasil diubah ke {$plan->name}")
            ->success()
            ->send();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubscriptionsRelationManager::class,
            RelationManagers\SubscriptionRenewalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}