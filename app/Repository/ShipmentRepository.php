<?php

namespace App\Repository;

use App\Enums\Shipment\ShipmentFieldsEnum;
use App\Enums\Shipment\ShipmentFiltersEnum;
use App\Models\Shipment;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;

class ShipmentRepository
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
            $query = $query->orderBy(ShipmentFieldsEnum::ID, 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param array $payload
     * @return Shipment
     * @throws Exception
     */
    public function create(array $payload): Shipment
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $shipment = Shipment::create($payload);
            DB::commit();
            return $shipment;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(Shipment $shipment, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                'etag'         => (string) Str::uuid(),
                'lock_version' => $shipment->lock_version + 1,
            ]);
            $updated = $shipment->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception('Max retry exceeded during update');
        }

        return $shipment->refresh();
    }

    /**
     * @param Shipment $shipment
     * @return bool|null
     */
    public function delete(Shipment $shipment): ?bool
    {
        return $shipment->delete();
    }

    /**
     * @param array $filters
     * @param array $expand
     * @return Shipment|null
     */
    public function find(array $filters = [], array $expand = []): ?Shipment
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
        return Shipment::query()
            ->when(isset($filters[ShipmentFiltersEnum::ID]), function ($query) use ($filters) {
                $query->where(ShipmentFieldsEnum::ID, $filters[ShipmentFiltersEnum::ID]);
            })
            ->when(isset($filters[ShipmentFiltersEnum::MODE]), function ($query) use ($filters) {
                $query->where(ShipmentFieldsEnum::MODE, $filters[ShipmentFiltersEnum::MODE]);
            })
            ->when(isset($filters[ShipmentFiltersEnum::CURRENT_STATUS]), function ($query) use ($filters) {
                $query->where(ShipmentFieldsEnum::CURRENT_STATUS, $filters[ShipmentFiltersEnum::CURRENT_STATUS]);
            })
            ->when(isset($filters[ShipmentFiltersEnum::STATUS_SOURCE_ID]), function ($query) use ($filters) {
                $query->where(ShipmentFieldsEnum::STATUS_SOURCE_ID, $filters[ShipmentFiltersEnum::STATUS_SOURCE_ID]);
            })
            ->when(isset($filters[ShipmentFiltersEnum::DELIVERED_AT]), function ($query) use ($filters) {
                $value = $filters[ShipmentFiltersEnum::DELIVERED_AT];
                if (is_array($value)) {
                    $query->whereBetween(ShipmentFieldsEnum::DELIVERED_AT, $value);
                } else {
                    $query->where(ShipmentFieldsEnum::DELIVERED_AT, $value);
                }
            })
            ->when(isset($filters[ShipmentFiltersEnum::DELIVERED_AT_COURIER]), function ($query) use ($filters) {
                $value = $filters[ShipmentFiltersEnum::DELIVERED_AT_COURIER];
                if (is_array($value)) {
                    $query->whereBetween(ShipmentFieldsEnum::DELIVERED_AT_COURIER, $value);
                } else {
                    $query->where(ShipmentFieldsEnum::DELIVERED_AT_COURIER, $value);
                }
            })
            ->when(isset($filters[ShipmentFiltersEnum::STATUS_DISCREPANCY]), function ($query) use ($filters) {
                $query->where(ShipmentFieldsEnum::STATUS_DISCREPANCY, $filters[ShipmentFiltersEnum::STATUS_DISCREPANCY]);
            })
            ->when(isset($filters[ShipmentFiltersEnum::LAST_EVENT_AT]), function ($query) use ($filters) {
                $value = $filters[ShipmentFiltersEnum::LAST_EVENT_AT];
                if (is_array($value)) {
                    $query->whereBetween(ShipmentFieldsEnum::LAST_EVENT_AT, $value);
                } else {
                    $query->where(ShipmentFieldsEnum::LAST_EVENT_AT, $value);
                }
            })
            ->when(isset($filters[ShipmentFiltersEnum::LAST_SYNCED_AT]), function ($query) use ($filters) {
                $value = $filters[ShipmentFiltersEnum::LAST_SYNCED_AT];
                if (is_array($value)) {
                    $query->whereBetween(ShipmentFieldsEnum::LAST_SYNCED_AT, $value);
                } else {
                    $query->where(ShipmentFieldsEnum::LAST_SYNCED_AT, $value);
                }
            })
            ->when(isset($filters[ShipmentFiltersEnum::CREATED_AT]), function ($query) use ($filters) {
                $value = $filters[ShipmentFiltersEnum::CREATED_AT];
                if (is_array($value)) {
                    $query->whereBetween(ShipmentFieldsEnum::CREATED_AT, $value);
                } else {
                    $query->where(ShipmentFieldsEnum::CREATED_AT, $value);
                }
            })
            ->with($expand);
    }
}
