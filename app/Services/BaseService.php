<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    public function __construct(protected BaseRepository $repository) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int|string $id): ?Model
    {
        return $this->repository->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Model $model, array $data): bool
    {
        return $this->repository->update($model, $data);
    }

    public function delete(Model $model): bool
    {
        return $this->repository->delete($model);
    }
}
