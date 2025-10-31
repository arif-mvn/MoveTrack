<?php

namespace App\Repository;

use App\Enums\Leg\LegFieldsEnum;
use App\Enums\Leg\LegFiltersEnum;
use App\Models\Leg;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class LegRepository
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
            $query = $query->orderBy(LegFieldsEnum::ID, 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $payload): Leg
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $leg = Leg::create($payload);
            DB::commit();
            return $leg;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(Leg $leg, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                'etag'         => (string) Str::uuid(),
                'lock_version' => $leg->lock_version + 1,
            ]);
            $updated = $leg->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception('Max retry exceeded during update');
        }

        return $leg->refresh();
    }

    public function delete(Leg $leg): ?bool
    {
        return $leg->delete();
    }

    public function find(array $filters = [], array $expand = []): ?Leg
    {
        return $this->getQuery(filters: $filters, expand: $expand)->first();
    }

    public function findAll(array $filters = [], array $expand = []): Collection
    {
        return $this->getQuery(filters: $filters, expand: $expand)->get();
    }

    public function getQuery(array $filters = [], array $expand = []): \Illuminate\Database\Eloquent\Builder|HigherOrderWhenProxy
    {
        return Leg::query()
            ->when(isset($filters[LegFiltersEnum::ID]), function ($query) use ($filters) {
                $query->where(LegFieldsEnum::ID, $filters[LegFiltersEnum::ID]);
            })
            ->when(isset($filters[LegFiltersEnum::SHIPMENT_ID]), function ($query) use ($filters) {
                $query->where(LegFieldsEnum::SHIPMENT_ID, $filters[LegFiltersEnum::SHIPMENT_ID]);
            })
            ->when(isset($filters[LegFiltersEnum::TYPE]), function ($query) use ($filters) {
                $query->where(LegFieldsEnum::TYPE, $filters[LegFiltersEnum::TYPE]);
            })
            ->when(isset($filters[LegFiltersEnum::SOURCE_ID]), function ($query) use ($filters) {
                $query->where(LegFieldsEnum::SOURCE_ID, $filters[LegFiltersEnum::SOURCE_ID]);
            })
            ->when(isset($filters[LegFiltersEnum::CARRIER_NAME]), function ($query) use ($filters) {
                $query->where(LegFieldsEnum::CARRIER_NAME, $filters[LegFiltersEnum::CARRIER_NAME]);
            })
            ->when(isset($filters[LegFiltersEnum::START_AT]), function ($query) use ($filters) {
                $value = $filters[LegFiltersEnum::START_AT];
                if (is_array($value)) {
                    $query->whereBetween(LegFieldsEnum::START_AT, $value);
                } else {
                    $query->where(LegFieldsEnum::START_AT, $value);
                }
            })
            ->when(isset($filters[LegFiltersEnum::END_AT]), function ($query) use ($filters) {
                $value = $filters[LegFiltersEnum::END_AT];
                if (is_array($value)) {
                    $query->whereBetween(LegFieldsEnum::END_AT, $value);
                } else {
                    $query->where(LegFieldsEnum::END_AT, $value);
                }
            })
            ->with($expand);
    }
}
