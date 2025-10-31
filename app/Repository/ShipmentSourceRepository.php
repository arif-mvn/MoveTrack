<?php

namespace App\Repository;

use App\Enums\ShipmentSource\ShipmentSourceFieldsEnum;
use App\Enums\ShipmentSource\ShipmentSourceFiltersEnum;
use App\Models\ShipmentSource;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ShipmentSourceRepository
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
            $query = $query->orderBy(ShipmentSourceFieldsEnum::ID, 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $payload): ShipmentSource
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $shipmentSource = ShipmentSource::create($payload);
            DB::commit();
            return $shipmentSource;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(ShipmentSource $shipmentSource, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                'etag'         => (string) Str::uuid(),
                'lock_version' => $shipmentSource->lock_version + 1,
            ]);
            $updated = $shipmentSource->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception('Max retry exceeded during update');
        }

        return $shipmentSource->refresh();
    }

    public function delete(ShipmentSource $shipmentSource): ?bool
    {
        return $shipmentSource->delete();
    }

    public function find(array $filters = [], array $expand = []): ?ShipmentSource
    {
        return $this->getQuery(filters: $filters, expand: $expand)->first();
    }

    public function findAll(array $filters = [], array $expand = []): Collection
    {
        return $this->getQuery(filters: $filters, expand: $expand)->get();
    }

    public function getQuery(array $filters = [], array $expand = []): \Illuminate\Database\Eloquent\Builder|HigherOrderWhenProxy
    {
        return ShipmentSource::query()
            ->when(isset($filters[ShipmentSourceFiltersEnum::ID]), function ($query) use ($filters) {
                $query->where(ShipmentSourceFieldsEnum::ID, $filters[ShipmentSourceFiltersEnum::ID]);
            })
            ->when(isset($filters[ShipmentSourceFiltersEnum::SHIPMENT_ID]), function ($query) use ($filters) {
                $query->where(ShipmentSourceFieldsEnum::SHIPMENT_ID, $filters[ShipmentSourceFiltersEnum::SHIPMENT_ID]);
            })
            ->when(isset($filters[ShipmentSourceFiltersEnum::SOURCE_ID]), function ($query) use ($filters) {
                $query->where(ShipmentSourceFieldsEnum::SOURCE_ID, $filters[ShipmentSourceFiltersEnum::SOURCE_ID]);
            })
            ->when(isset($filters[ShipmentSourceFiltersEnum::LAST_SYNCED_AT]), function ($query) use ($filters) {
                $value = $filters[ShipmentSourceFiltersEnum::LAST_SYNCED_AT];
                if (is_array($value)) {
                    $query->whereBetween(ShipmentSourceFieldsEnum::LAST_SYNCED_AT, $value);
                } else {
                    $query->where(ShipmentSourceFieldsEnum::LAST_SYNCED_AT, $value);
                }
            })
            ->when(isset($filters[ShipmentSourceFiltersEnum::CREATED_AT]), function ($query) use ($filters) {
                $value = $filters[ShipmentSourceFiltersEnum::CREATED_AT];
                if (is_array($value)) {
                    $query->whereBetween(ShipmentSourceFieldsEnum::CREATED_AT, $value);
                } else {
                    $query->where(ShipmentSourceFieldsEnum::CREATED_AT, $value);
                }
            })
            ->with($expand);
    }
}
