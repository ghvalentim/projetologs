<?php

namespace App\Filament\Resources\DepartmentsResource\Pages;

use App\Filament\Resources\DepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function paginateTableQuery(Builder $query) : \Illuminate\Contracts\Pagination\Paginator
    {
        return $query->withCount('licenses')->paginate($this->getTableRecordsPerPage());
    }
}
