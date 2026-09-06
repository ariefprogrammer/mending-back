<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProvinceResource\Pages;
use App\Models\Province;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';

    protected static ?string $navigationGroup = 'Wilayah';

    protected static ?string $navigationLabel = 'Provinsi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')
                ->label('Kode')
                ->maxLength(20),
            TextInput::make('name')
                ->label('Nama Provinsi')
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
                    ->label('Nama Provinsi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kabupatens_count')
                    ->label('Jml Kabupaten')
                    ->counts('kabupatens'),
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
            'index' => Pages\ListProvinces::route('/'),
            'edit' => Pages\EditProvince::route('/{record}/edit'),
        ];
    }
}