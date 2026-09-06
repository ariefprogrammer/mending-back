<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KecamatanResource\Pages;
use App\Models\Kecamatan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class KecamatanResource extends Resource
{
    protected static ?string $model = Kecamatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Wilayah';

    protected static ?string $navigationLabel = 'Kecamatan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('kabupaten_id')
                ->label('Kabupaten/Kota')
                ->relationship('kabupaten', 'name')
                ->searchable()
                ->required(),
            TextInput::make('code')
                ->label('Kode')
                ->maxLength(20),
            TextInput::make('name')
                ->label('Nama Kecamatan')
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
                    ->label('Nama Kecamatan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kabupaten.name')
                    ->label('Kabupaten/Kota')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kabupaten_id')
                    ->label('Kabupaten/Kota')
                    ->relationship('kabupaten', 'name')
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
            'index' => Pages\ListKecamatans::route('/'),
            'edit' => Pages\EditKecamatan::route('/{record}/edit'),
        ];
    }
}