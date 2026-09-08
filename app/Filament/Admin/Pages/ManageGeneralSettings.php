<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageGeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan Umum';
    protected static string $view = 'filament.admin.pages.manage-general-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public function mount(): void
    {
        $this->form->fill([
            'admin_whatsapp_number' => Setting::get('admin_whatsapp_number'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('admin_whatsapp_number')
                ->label('Nomor WhatsApp Admin')
                ->helperText('Format: 628xxxxxxxxxx (tanpa + atau 0 di depan)')
                ->required()
                ->tel(),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('admin_whatsapp_number', $data['admin_whatsapp_number']);

        Notification::make()
            ->title('Pengaturan berhasil disimpan')
            ->success()
            ->send();
    }
}