<?php

namespace App\Repository;

use App\Enums\Event\EventFieldsEnum;
use App\Enums\Event\EventFiltersEnum;
use App\Models\Event;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EventRepository
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
            $query = $query->orderBy(EventFieldsEnum::ID, 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $payload): Event
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $event = Event::create($payload);
            DB::commit();
            return $event;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(Event $event, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                'etag'         => (string) Str::uuid(),
                'lock_version' => $event->lock_version + 1,
            ]);
            $updated = $event->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception('Max retry exceeded during update');
        }

        return $event->refresh();
    }

    public function delete(Event $event): ?bool
    {
        return $event->delete();
    }

    public function find(array $filters = [], array $expand = []): ?Event
    {
        return $this->getQuery(filters: $filters, expand: $expand)->first();
    }

    public function findAll(array $filters = [], array $expand = []): Collection
    {
        return $this->getQuery(filters: $filters, expand: $expand)->get();
    }

    public function getQuery(array $filters = [], array $expand = []): \Illuminate\Database\Eloquent\Builder|HigherOrderWhenProxy
    {
        return Event::query()
            ->when(isset($filters[EventFiltersEnum::ID]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::ID, $filters[EventFiltersEnum::ID]);
            })
            ->when(isset($filters[EventFiltersEnum::SHIPMENT_ID]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::SHIPMENT_ID, $filters[EventFiltersEnum::SHIPMENT_ID]);
            })
            ->when(isset($filters[EventFiltersEnum::LEG_ID]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::LEG_ID, $filters[EventFiltersEnum::LEG_ID]);
            })
            ->when(isset($filters[EventFiltersEnum::SOURCE_ID]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::SOURCE_ID, $filters[EventFiltersEnum::SOURCE_ID]);
            })
            ->when(isset($filters[EventFiltersEnum::TYPE]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::TYPE, $filters[EventFiltersEnum::TYPE]);
            })
            ->when(isset($filters[EventFiltersEnum::OCCURRED_AT]), function ($query) use ($filters) {
                $value = $filters[EventFiltersEnum::OCCURRED_AT];
                if (is_array($value)) {
                    $query->whereBetween(EventFieldsEnum::OCCURRED_AT, $value);
                } else {
                    $query->where(EventFieldsEnum::OCCURRED_AT, $value);
                }
            })
            ->when(isset($filters[EventFiltersEnum::SOURCE_KIND]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::SOURCE_KIND, $filters[EventFiltersEnum::SOURCE_KIND]);
            })
            ->when(isset($filters[EventFiltersEnum::STATUS_CODE]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::STATUS_CODE, $filters[EventFiltersEnum::STATUS_CODE]);
            })
            ->when(isset($filters[EventFiltersEnum::RAW_TEXT]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::RAW_TEXT, $filters[EventFiltersEnum::RAW_TEXT]);
            })
            ->when(isset($filters[EventFiltersEnum::RAW_TEXT]), function ($query) use ($filters) { // like search variant
                $query->where(EventFieldsEnum::RAW_TEXT, 'like', '%' . $filters[EventFiltersEnum::RAW_TEXT] . '%');
            })
            ->when(isset($filters[EventFiltersEnum::VISIBILITY]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::VISIBILITY, $filters[EventFiltersEnum::VISIBILITY]);
            })
            ->when(isset($filters[EventFiltersEnum::AUTHORITATIVE]), function ($query) use ($filters) {
                $query->where(EventFieldsEnum::AUTHORITATIVE, $filters[EventFiltersEnum::AUTHORITATIVE]);
            })
            ->when(isset($filters[EventFiltersEnum::CREATED_AT]), function ($query) use ($filters) {
                $value = $filters[EventFiltersEnum::CREATED_AT];
                if (is_array($value)) {
                    $query->whereBetween(EventFieldsEnum::CREATED_AT, $value);
                } else {
                    $query->where(EventFieldsEnum::CREATED_AT, $value);
                }
            })
            ->with($expand);
    }
}
