<?php

namespace App\Services;

use App\Enums\Event\EventFieldsEnum;
use App\Enums\Event\EventFiltersEnum;
use App\Enums\Event\EventTypeEnum;
use App\Enums\Shipment\ShipmentFieldsEnum;
use App\Enums\Shipment\ShipmentFiltersEnum;
use App\Enums\SortOrderEnum;
use App\Exceptions\ModelNotFoundException;
use App\Models\Event;
use App\Models\Shipment;
use App\Repository\EventRepository;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EventService
{
    public function __construct(private readonly EventRepository $repository)
    {
    }

    public function getPaginatedEvents(array $queryParameters = []): LengthAwarePaginator
    {
        $page    = $queryParameters['page'] ?? 1;
        $perPage = $queryParameters['per_page'] ?? 50; // events often more

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: array_intersect_key($queryParameters, array_flip(EventFiltersEnum::values())),
            fields: $queryParameters['fields'] ?? [],
            expand: $queryParameters['expand'] ?? [],
            sortBy: $queryParameters['sort_by'] ?? null,
            sortOrder: $queryParameters['sort_order'] ?? SortOrderEnum::DESC,
        );
    }

    /** @throws Exception */
    public function create(array $payload): Event
    {
        $prepared = $this->prepareCreatePayload($payload);
        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function update(int $id, array $payload): Event
    {
        $event      = $this->getById($id);
        $prepared   = $this->prepareUpdatePayload($payload);
        return $this->repository->update($event, $prepared);
    }

    public function prepareCreatePayload(array $payload): array
    {
        return [
            EventFieldsEnum::SHIPMENT_ID   => $payload[EventFiltersEnum::SHIPMENT_ID],
            EventFieldsEnum::LEG_ID        => $payload[EventFiltersEnum::LEG_ID] ?? null,
            EventFieldsEnum::SOURCE_ID     => $payload[EventFiltersEnum::SOURCE_ID] ?? null,
            EventFieldsEnum::TYPE          => $payload[EventFiltersEnum::TYPE],
            EventFieldsEnum::OCCURRED_AT   => $payload[EventFiltersEnum::OCCURRED_AT],
            EventFieldsEnum::SOURCE_KIND   => $payload[EventFiltersEnum::SOURCE_KIND],
            EventFieldsEnum::STATUS_CODE   => $payload[EventFiltersEnum::STATUS_CODE] ?? null,
            EventFieldsEnum::RAW_TEXT      => $payload[EventFiltersEnum::RAW_TEXT] ?? null,
            EventFieldsEnum::LOCATION      => $payload[EventFiltersEnum::LOCATION] ?? null,
            EventFieldsEnum::ACTOR         => $payload[EventFiltersEnum::ACTOR] ?? null,
            EventFieldsEnum::EVIDENCE      => $payload[EventFiltersEnum::EVIDENCE] ?? null,
            EventFieldsEnum::VISIBILITY    => $payload[EventFiltersEnum::VISIBILITY] ?? null,
            EventFieldsEnum::AUTHORITATIVE => $payload[EventFiltersEnum::AUTHORITATIVE] ?? true,
        ];
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            EventFieldsEnum::LEG_ID        => $payload[EventFiltersEnum::LEG_ID] ?? null,
            EventFieldsEnum::SOURCE_ID     => $payload[EventFiltersEnum::SOURCE_ID] ?? null,
            EventFieldsEnum::TYPE          => $payload[EventFiltersEnum::TYPE] ?? null,
            EventFieldsEnum::OCCURRED_AT   => $payload[EventFiltersEnum::OCCURRED_AT] ?? null,
            EventFieldsEnum::STATUS_CODE   => $payload[EventFiltersEnum::STATUS_CODE] ?? null,
            EventFieldsEnum::RAW_TEXT      => $payload[EventFiltersEnum::RAW_TEXT] ?? null,
            EventFieldsEnum::LOCATION      => $payload[EventFiltersEnum::LOCATION] ?? null,
            EventFieldsEnum::ACTOR         => $payload[EventFiltersEnum::ACTOR] ?? null,
            EventFieldsEnum::EVIDENCE      => $payload[EventFiltersEnum::EVIDENCE] ?? null,
            EventFieldsEnum::VISIBILITY    => $payload[EventFiltersEnum::VISIBILITY] ?? null,
            EventFieldsEnum::AUTHORITATIVE => $payload[EventFiltersEnum::AUTHORITATIVE] ?? null,
        ];
    }

    public function findById(int $id): ?Event
    {
        return $this->repository->find(filters: [EventFiltersEnum::ID => $id]);
    }

    public function getById(int $id): Event
    {
        $event = $this->repository->find(filters: [EventFiltersEnum::ID => $id]);

        if (!$event) {
            throw new ModelNotFoundException('Event not found for id: ' . $id);
        }

        return $event;
    }


    public function findOrCreate(array $payload): Event
    {
        $prepared = $this->prepareCreatePayload($payload);
        $existing = $this->repository->find([
            EventFiltersEnum::SHIPMENT_ID => $prepared[EventFieldsEnum::SHIPMENT_ID],
            EventFiltersEnum::LEG_ID      => $prepared[EventFieldsEnum::LEG_ID] ?? null,
            EventFiltersEnum::TYPE        => $prepared[EventFieldsEnum::TYPE],
            EventFiltersEnum::OCCURRED_AT => $prepared[EventFieldsEnum::OCCURRED_AT],
        ]);
        try {
            DB::beginTransaction();
            if ($existing) {
                $delta = array_filter([
                    EventFieldsEnum::RAW_TEXT   => $prepared[EventFieldsEnum::RAW_TEXT] ?? null,
                    EventFieldsEnum::LOCATION   => $prepared[EventFieldsEnum::LOCATION] ?? null,
                    EventFieldsEnum::ACTOR      => $prepared[EventFieldsEnum::ACTOR] ?? null,
                    EventFieldsEnum::STATUS_CODE=> $prepared[EventFieldsEnum::STATUS_CODE] ?? null,
                ], fn ($v) => !is_null($v));

                if ($delta) {
                    $this->update($existing->id, $delta);
                    $existing->refresh();
                }
                return $existing;
            }

            $event = $this->repository->create($prepared);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        return $event;
    }

    /**
     * Update shipment header truth for key event types.
     * (Authoritative = MoveOn; courier-only sets discrepancy.)
     */
    public function applyShipmentHeaderSideEffects(Shipment $shipment, string $eventType, string $occurredAt, bool $authoritative): void
    {
        $changed = false;

        if ($eventType === EventTypeEnum::DELIVERED) {
            if ($authoritative) {
                $shipment->{ShipmentFieldsEnum::CURRENT_STATUS} = 'DELIVERED';
                $shipment->{ShipmentFieldsEnum::DELIVERED_AT}   = $occurredAt;
                $shipment->{ShipmentFieldsEnum::STATUS_DISCREPANCY} = false;
                $changed = true;
            } else {
                // Courier delivered first
                if (!$shipment->{ShipmentFieldsEnum::DELIVERED_AT}) {
                    $shipment->{ShipmentFieldsEnum::DELIVERED_AT_COURIER} = $occurredAt;
                    $shipment->{ShipmentFieldsEnum::STATUS_DISCREPANCY}   = true;
                    $changed = true;
                }
            }
        }

        // Always bump last_event_at
        if ($shipment->{ShipmentFieldsEnum::LAST_EVENT_AT} < $occurredAt) {
            $shipment->{ShipmentFieldsEnum::LAST_EVENT_AT} = $occurredAt;
            $changed = true;
        }

        if ($changed) {
            app(ShipmentService::class)->update($shipment->id, [
                ShipmentFiltersEnum::CURRENT_STATUS        => $shipment->{ShipmentFieldsEnum::CURRENT_STATUS},
                ShipmentFiltersEnum::DELIVERED_AT          => $shipment->{ShipmentFieldsEnum::DELIVERED_AT},
                ShipmentFiltersEnum::DELIVERED_AT_COURIER  => $shipment->{ShipmentFieldsEnum::DELIVERED_AT_COURIER},
                ShipmentFiltersEnum::STATUS_DISCREPANCY    => $shipment->{ShipmentFieldsEnum::STATUS_DISCREPANCY},
                ShipmentFiltersEnum::LAST_EVENT_AT         => $shipment->{ShipmentFieldsEnum::LAST_EVENT_AT},
            ]);
        }
    }
}

