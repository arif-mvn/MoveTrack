<?php

namespace App\Repository;

use App\Enums\SourceEvent\SourceEventFieldsEnum;
use App\Enums\SourceEvent\SourceEventFiltersEnum;
use App\Models\SourceEvent;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;

class SourceEventRepository
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
            $query = $query->orderBy(SourceEventFieldsEnum::ID, 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $payload): SourceEvent
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $sourceEvent = SourceEvent::create($payload);
            DB::commit();
            return $sourceEvent;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(SourceEvent $sourceEvent, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                'etag'         => (string) Str::uuid(),
                'lock_version' => $sourceEvent->lock_version + 1,
            ]);
            $updated = $sourceEvent->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception('Max retry exceeded during update');
        }

        return $sourceEvent->refresh();
    }

    public function delete(SourceEvent $sourceEvent): ?bool
    {
        return $sourceEvent->delete();
    }

    public function find(array $filters = [], array $expand = []): ?SourceEvent
    {
        return $this->getQuery(filters: $filters, expand: $expand)->first();
    }

    public function findAll(array $filters = [], array $expand = []): Collection
    {
        return $this->getQuery(filters: $filters, expand: $expand)->get();
    }

    public function getQuery(array $filters = [], array $expand = []): \Illuminate\Database\Eloquent\Builder|HigherOrderWhenProxy
    {
        return SourceEvent::query()
            ->when(isset($filters[SourceEventFiltersEnum::ID]), function ($query) use ($filters) {
                $query->where(SourceEventFieldsEnum::ID, $filters[SourceEventFiltersEnum::ID]);
            })
            ->when(isset($filters[SourceEventFiltersEnum::SHIPMENT_ID]), function ($query) use ($filters) {
                $query->where(SourceEventFieldsEnum::SHIPMENT_ID, $filters[SourceEventFiltersEnum::SHIPMENT_ID]);
            })
            ->when(isset($filters[SourceEventFiltersEnum::EVENT_ID]), function ($query) use ($filters) {
                $query->where(SourceEventFieldsEnum::EVENT_ID, $filters[SourceEventFiltersEnum::EVENT_ID]);
            })
            ->when(isset($filters[SourceEventFiltersEnum::SOURCE_ID]), function ($query) use ($filters) {
                $query->where(SourceEventFieldsEnum::SOURCE_ID, $filters[SourceEventFiltersEnum::SOURCE_ID]);
            })
            ->when(isset($filters[SourceEventFiltersEnum::SOURCE_EVENT_ID]), function ($query) use ($filters) {
                $query->where(SourceEventFieldsEnum::SOURCE_EVENT_ID, $filters[SourceEventFiltersEnum::SOURCE_EVENT_ID]);
            })
            ->when(isset($filters[SourceEventFiltersEnum::OCCURRED_AT]), function ($query) use ($filters) {
                $value = $filters[SourceEventFiltersEnum::OCCURRED_AT];
                if (is_array($value)) {
                    $query->whereBetween(SourceEventFieldsEnum::OCCURRED_AT, $value);
                } else {
                    $query->where(SourceEventFieldsEnum::OCCURRED_AT, $value);
                }
            })
            ->when(isset($filters[SourceEventFiltersEnum::PAYLOAD_HASH]), function ($query) use ($filters) {
                $query->where(SourceEventFieldsEnum::PAYLOAD_HASH, $filters[SourceEventFiltersEnum::PAYLOAD_HASH]);
            })
            ->when(isset($filters[SourceEventFiltersEnum::RECEIVED_AT]), function ($query) use ($filters) {
                $value = $filters[SourceEventFiltersEnum::RECEIVED_AT];
                if (is_array($value)) {
                    $query->whereBetween(SourceEventFieldsEnum::RECEIVED_AT, $value);
                } else {
                    $query->where(SourceEventFieldsEnum::RECEIVED_AT, $value);
                }
            })
            ->when(isset($filters[SourceEventFiltersEnum::CREATED_AT]), function ($query) use ($filters) {
                $value = $filters[SourceEventFiltersEnum::CREATED_AT];
                if (is_array($value)) {
                    $query->whereBetween(SourceEventFieldsEnum::CREATED_AT, $value);
                } else {
                    $query->where(SourceEventFieldsEnum::CREATED_AT, $value);
                }
            })
            ->with($expand);
    }
}
