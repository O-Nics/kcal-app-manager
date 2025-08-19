<?php
namespace App\Support\Repositories;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseRepository
{

    protected string $model;

    protected function query():Builder{
        return $this->model::query();
    }

    protected function queryWithTrashed():Builder{
        return $this->model::query()->withTrashed();
    }
}
