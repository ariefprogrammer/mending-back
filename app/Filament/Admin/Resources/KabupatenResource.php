<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KabupatenResource\Pages;
use App\Models\Kabupaten;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class KabupatenResource extends Resource
{
    protected static ?string $model = Kabupaten::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Wilayah';

    protected static ?string $navigationLabel = 'Kabupaten/Kota';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('province_id')
                ->label('Provinsi')
                ->relationship('province', 'name')
                ->searchable()
                ->required(),
            TextInput::make('code')
                ->label('Kode')
                ->maxLength(20),
            TextInput::make('name')
                ->label('Nama Kabupaten/Kota')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Kabupaten/Kota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('province.name')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('province_id')
                    ->label('Provinsi')
                    ->relationship('province', 'name')
                    ->searchable(),
            ])
            ->defaultSort('name');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKabupatens::route('/'),
            'edit' => Pages\EditKabupaten::route('/{record}/edit'),
        ];
    }
}