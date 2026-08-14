<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Manajemen SaaS';
    protected static ?string $navigationLabel = 'Paket Langganan';
    protected static ?string $modelLabel = 'Paket';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Paket')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Contoh: free, trial_premium, premium'),

            Forms\Components\TextInput::make('transaction_limit')
                ->label('Batas Transaksi')
                ->numeric()
                ->helperText('Kosongkan jika tidak dibatasi (unlimited)'),

            Forms\Components\TextInput::make('trial_days')
                ->label('Lama Trial (hari)')
                ->numeric()
                ->helperText('Kosongkan jika paket ini bukan trial'),

            Forms\Components\Toggle::make('restrict_menus')
                ->label('Batasi akses menu')
                ->helperText('Kalau dimatikan, paket ini bisa akses semua menu')
                ->live()
                ->afterStateHydrated(function (Forms\Components\Toggle $component, $record) {
                    $component->state($record ? ! is_null($record->allowed_menus) : false);
                }),

            Forms\Components\CheckboxList::make('allowed_menus')
                ->label('Menu yang Boleh Diakses')
                ->options([
                    'pelanggan' => 'Pelanggan',
                    'deposit' => 'Deposit',
                    'layanan' => 'Layanan',
                    'karyawan' => 'Karyawan',
                    'kehadiran' => 'Kehadiran',
                    'aset' => 'Aset',
                    'bahan' => 'Bahan',
                    'pemasukan' => 'Pemasukan',
                    'pengeluaran' => 'Pengeluaran',
                    'buku_kas' => 'Buku Kas',
                    'formulir' => 'Formulir',
                    'outlet' => 'Outlet',
                ])
                ->columns(2)
                ->visible(fn (Forms\Get $get) => $get('restrict_menus'))
                ->dehydrated(fn (Forms\Get $get) => $get('restrict_menus')),

            Forms\Components\TextInput::make('price')
                ->label('Harga')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->default(0),

            Forms\Components\Toggle::make('is_default')
                ->label('Jadikan paket default saat registrasi baru'),

            Forms\Components\Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge(),

                Tables\Columns\TextColumn::make('transaction_limit')
                    ->label('Batas Transaksi')
                    ->placeholder('Unlimited'),

                Tables\Columns\TextColumn::make('trial_days')
                    ->label('Trial (hari)')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Jumlah Pelanggan')
                    ->counts('subscriptions'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}