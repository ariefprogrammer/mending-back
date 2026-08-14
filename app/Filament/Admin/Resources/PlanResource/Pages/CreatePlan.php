<?php

namespace App\Filament\Admin\Resources\PlanResource\Pages;

use App\Filament\Admin\Resources\PlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['restrict_menus'])) {
            $data['allowed_menus'] = null;
        }

        unset($data['restrict_menus']);

        return $data;
    }
}