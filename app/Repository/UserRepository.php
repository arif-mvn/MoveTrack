<?php

namespace App\Repository;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    const MAX_RETRY = 5;

    /**
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @param array $fields
     * @param array $expand
     * @param string|null $sortBy
     * @param string $sortOrder
     * @return LengthAwarePaginator
     */
    public function getAll(
        int    $page,
        int    $perPage,
        array  $filters = [],
        array  $fields = [],
        array  $expand = [],
        string $sortBy = null,
        string $sortOrder = 'ASC'
    ): LengthAwarePaginator {
        $query = $this->getQuery(filters: $filters, expand: $expand);

        if (count($fields) > 0) {
            $query = $query->select($fields);
        }

        if ($sortBy) {
            $query = $query->orderBy($sortBy, $sortOrder);
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param array $payload
     * @return User
     * @throws Exception
     */
    public function create(array $payload): User
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $user = User::create($payload);
            DB::commit();
            return $user;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(User $user, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                'etag'         => (string) Str::uuid(),
                'lock_version' => ($user->lock_version ?? 0) + 1,
            ]);
            $updated = $user->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception('Max retry exceeded during update');
        }

        return $user->refresh();
    }

    /**
     * @param User $user
     * @return bool|null
     */
    public function delete(User $user): ?bool
    {
        return $user->delete();
    }

    /**
     * @param array $filters
     * @param array $expand
     * @return User|null
     */
    public function find(array $filters = [], array $expand = []): ?User
    {
        return $this->getQuery(filters: $filters, expand: $expand)->first();
    }

    /**
     * @param array $filters
     * @param array $expand
     * @return Collection
     */
    public function findAll(array $filters = [], array $expand = []): Collection
    {
        return $this->getQuery(filters: $filters, expand: $expand)->get();
    }

    /**
     * @param array $filters
     * @param array $expand
     * @return \Illuminate\Database\Eloquent\Builder|HigherOrderWhenProxy
     */
    public function getQuery(array $filters = [], array $expand = []): \Illuminate\Database\Eloquent\Builder|HigherOrderWhenProxy
    {
        return User::query()
            ->when(isset($filters['id']), function ($query) use ($filters) {
                $query->where('id', $filters['id']);
            })
            ->when(isset($filters['email']), function ($query) use ($filters) {
                $query->where('email', $filters['email']);
            })
            ->when(isset($filters['name']), function ($query) use ($filters) {
                $query->where('name', $filters['name']);
            })
            ->when(isset($filters['name_like']), function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['name_like'] . '%');
            })
            ->with($expand);
    }
}
