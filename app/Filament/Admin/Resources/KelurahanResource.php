<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KelurahanResource\Pages;
use App\Models\Kelurahan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class KelurahanResource extends Resource
{
    protected static ?string $model = Kelurahan::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Wilayah';

    protected static ?string $navigationLabel = 'Kelurahan/Desa';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('kecamatan_id')
                ->label('Kecamatan')
                ->relationship('kecamatan', 'name')
                ->searchable()
                ->required(),
            TextInput::make('code')
                ->label('Kode')
                ->maxLength(20),
            TextInput::make('name')
                ->label('Nama Kelurahan/Desa')
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
                    ->label('Nama Kelurahan/Desa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kecamatan.name')
                    ->label('Kecamatan')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kecamatan_id')
                    ->label('Kecamatan')
                    ->relationship('kecamatan', 'name')
                    ->searchable(),
            ])
            ->defaultSort('name')
            ->paginated([25, 50, 100]);
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
            'index' => Pages\ListKelurahans::route('/'),
            'edit' => Pages\EditKelurahan::route('/{record}/edit'),
        ];
    }
}