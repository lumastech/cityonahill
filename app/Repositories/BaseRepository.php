<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    public function __construct(protected Model $model) {}

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int|string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /** @param array<string, mixed> $criteria */
    public function findBy(array $criteria): ?Model
    {
        return $this->model->where($criteria)->first();
    }

    /** @param array<string, mixed> $criteria */
    public function findAllBy(array $criteria): Collection
    {
        return $this->model->where($criteria)->get();
    }
}
