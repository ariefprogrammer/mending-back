<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutletResource\Pages;
use App\Filament\Resources\OutletResource\RelationManagers;
use App\Models\Outlet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OutletResource extends Resource
{
    protected static ?string $model = Outlet::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name') // Mengambil nama owner dari tabel users
                            ->required()
                            ->searchable()
                            ->label('Owner / User'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('province')->required(),
                        Forms\Components\TextInput::make('city')->required(),
                        Forms\Components\TextInput::make('kecamatan')->required(),
                        Forms\Components\TextInput::make('kelurahan')->required(),
                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name') // Menampilkan nama pemilik
                    ->label('Owner')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Outlet')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Kota')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Anda bisa menambah filter berdasarkan kota di sini nanti
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutlets::route('/'),
            'create' => Pages\CreateOutlet::route('/create'),
            'edit' => Pages\EditOutlet::route('/{record}/edit'),
        ];
    }
}
