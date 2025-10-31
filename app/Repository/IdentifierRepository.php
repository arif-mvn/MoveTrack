<?php

namespace App\Repository;

use App\Enums\Identifier\IdentifierFieldsEnum;
use App\Enums\Identifier\IdentifierFiltersEnum;
use App\Models\Identifier;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class IdentifierRepository
{
    const MAX_RETRY = 5;

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
            $query = $query->orderBy(IdentifierFieldsEnum::ID, 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $payload): Identifier
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $identifier = Identifier::create($payload);
            DB::commit();
            return $identifier;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(Identifier $identifier, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                'etag'         => (string) Str::uuid(),
                'lock_version' => $identifier->lock_version + 1,
            ]);
            $updated = $identifier->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception('Max retry exceeded during update');
        }

        return $identifier->refresh();
    }

    public function delete(Identifier $identifier): ?bool
    {
        return $identifier->delete();
    }

    public function find(array $filters = [], array $expand = []): ?Identifier
    {
        return $this->getQuery(filters: $filters, expand: $expand)->first();
    }

    public function findAll(array $filters = [], array $expand = []): Collection
    {
        return $this->getQuery(filters: $filters, expand: $expand)->get();
    }

    public function getQuery(array $filters = [], array $expand = []): \Illuminate\Database\Eloquent\Builder|HigherOrderWhenProxy
    {
        return Identifier::query()
            ->when(isset($filters[IdentifierFiltersEnum::ID]), function ($query) use ($filters) {
                $query->where(IdentifierFieldsEnum::ID, $filters[IdentifierFiltersEnum::ID]);
            })
            ->when(isset($filters[IdentifierFiltersEnum::SHIPMENT_ID]), function ($query) use ($filters) {
                $query->where(IdentifierFieldsEnum::SHIPMENT_ID, $filters[IdentifierFiltersEnum::SHIPMENT_ID]);
            })
            ->when(isset($filters[IdentifierFiltersEnum::SCOPE]), function ($query) use ($filters) {
                $query->where(IdentifierFieldsEnum::SCOPE, $filters[IdentifierFiltersEnum::SCOPE]);
            })
            ->when(isset($filters[IdentifierFiltersEnum::CARRIER_CODE]), function ($query) use ($filters) {
                $query->where(IdentifierFieldsEnum::CARRIER_CODE, $filters[IdentifierFiltersEnum::CARRIER_CODE]);
            })
            ->when(isset($filters[IdentifierFiltersEnum::VALUE]), function ($query) use ($filters) {
                $query->where(IdentifierFieldsEnum::VALUE, $filters[IdentifierFiltersEnum::VALUE]);
            })
            ->when(isset($filters[IdentifierFiltersEnum::CREATED_AT]), function ($query) use ($filters) {
                $value = $filters[IdentifierFiltersEnum::CREATED_AT];
                if (is_array($value)) {
                    $query->whereBetween(IdentifierFieldsEnum::CREATED_AT, $value);
                } else {
                    $query->where(IdentifierFieldsEnum::CREATED_AT, $value);
                }
            })
            ->with($expand);
    }
}
