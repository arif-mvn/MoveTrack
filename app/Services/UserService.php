<?php

namespace App\Services;

use App\Repository\UserRepository;
use App\Models\User;
use App\Exceptions\ModelNotFoundException; // added
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    public function getPaginatedUsers(array $queryParameters = []): LengthAwarePaginator
    {
        $page    = $queryParameters['page'] ?? 1;
        $perPage = $queryParameters['per_page'] ?? 20;

        $allowedFilters = ['id','email','name','name_like'];
        $filters = array_filter(
            array_intersect_key($queryParameters, array_flip($allowedFilters)),
            fn($v) => $v !== null
        );

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: $filters,
            fields: $queryParameters['fields'] ?? [],
            expand: $queryParameters['expand'] ?? [],
            sortBy: $queryParameters['sort_by'] ?? null,
            sortOrder: $queryParameters['sort_order'] ?? 'asc',
        );
    }

    /** @throws Exception */
    public function create(array $payload): User
    {
        $prepared = $this->prepareCreatePayload($payload);
        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function update(int $id, array $payload): User
    {
        $user = $this->getById($id); // use getById
        $prepared = $this->prepareUpdatePayload($payload);
        return $this->repository->update($user, $prepared);
    }

    public function prepareCreatePayload(array $payload): array
    {
        return [
            'name'     => $payload['name'],
            'email'    => $payload['email'],
            'password' => $payload['password'], // should already be hashed by model cast
        ];
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            'name'     => $payload['name'] ?? null,
            'email'    => $payload['email'] ?? null,
            'password' => $payload['password'] ?? null,
        ];
    }

    public function getById(int $id): User
    {
        $user = $this->repository->find(filters: ['id' => $id]);
        if (!$user) {
            throw new ModelNotFoundException('User not found for id: ' . $id);
        }
        return $user;
    }
}
