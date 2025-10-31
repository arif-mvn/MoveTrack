<?php

namespace App\Services;

use App\Enums\SortOrderEnum;
use App\Enums\SourceEvent\SourceEventFieldsEnum;
use App\Enums\SourceEvent\SourceEventFiltersEnum;
use App\Exceptions\ModelNotFoundException;
use App\Models\SourceEvent;
use App\Repository\SourceEventRepository;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class SourceEventService
{
    public function __construct(private readonly SourceEventRepository $repository)
    {
    }

    public function getPaginatedSourceEvents(array $queryParameters = []): LengthAwarePaginator
    {
        $page    = $queryParameters['page'] ?? 1;
        $perPage = $queryParameters['per_page'] ?? 50;

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: array_intersect_key($queryParameters, array_flip(SourceEventFiltersEnum::values())),
            fields: $queryParameters['fields'] ?? [],
            expand: $queryParameters['expand'] ?? [],
            sortBy: $queryParameters['sort_by'] ?? null,
            sortOrder: $queryParameters['sort_order'] ?? SortOrderEnum::DESC,
        );
    }

    /** @throws Exception */
    public function create(array $payload): SourceEvent
    {
        $prepared = $this->prepareCreatePayload($payload);
        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function update(int $id, array $payload): SourceEvent
    {
        $sourceEvent = $this->getById($id);
        $prepared = $this->prepareUpdatePayload($payload);
        return $this->repository->update($sourceEvent, $prepared);
    }

    public function prepareCreatePayload(array $payload): array
    {
        return [
            SourceEventFieldsEnum::SHIPMENT_ID     => $payload[SourceEventFiltersEnum::SHIPMENT_ID] ?? null,
            SourceEventFieldsEnum::EVENT_ID        => $payload[SourceEventFiltersEnum::EVENT_ID] ?? null,
            SourceEventFieldsEnum::SOURCE_ID       => $payload[SourceEventFiltersEnum::SOURCE_ID],
            SourceEventFieldsEnum::SOURCE_EVENT_ID => $payload[SourceEventFiltersEnum::SOURCE_EVENT_ID] ?? null,
            SourceEventFieldsEnum::OCCURRED_AT     => $payload[SourceEventFiltersEnum::OCCURRED_AT] ?? null,
            SourceEventFieldsEnum::PAYLOAD_HASH    => $payload[SourceEventFiltersEnum::PAYLOAD_HASH],
            SourceEventFieldsEnum::PAYLOAD         => $payload[SourceEventFiltersEnum::PAYLOAD],
            SourceEventFieldsEnum::RECEIVED_AT     => $payload[SourceEventFiltersEnum::RECEIVED_AT],
        ];
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            SourceEventFieldsEnum::EVENT_ID        => $payload[SourceEventFiltersEnum::EVENT_ID] ?? null,
            SourceEventFieldsEnum::SOURCE_EVENT_ID => $payload[SourceEventFiltersEnum::SOURCE_EVENT_ID] ?? null,
            SourceEventFieldsEnum::OCCURRED_AT     => $payload[SourceEventFiltersEnum::OCCURRED_AT] ?? null,
            SourceEventFieldsEnum::PAYLOAD         => $payload[SourceEventFiltersEnum::PAYLOAD] ?? null,
            SourceEventFieldsEnum::RECEIVED_AT     => $payload[SourceEventFiltersEnum::RECEIVED_AT] ?? null,
        ];
    }

    public function getById(int $id): SourceEvent
    {
        $sourceEvent = $this->repository->find(filters: [SourceEventFiltersEnum::ID => $id]);
        if (!$sourceEvent) {
            throw new ModelNotFoundException('SourceEvent not found for id: ' . $id);
        }
        return $sourceEvent;
    }

    public function findOrCreate(array $input): SourceEvent
    {
        if (empty($input[SourceEventFiltersEnum::PAYLOAD_HASH])) {
            $input[SourceEventFiltersEnum::PAYLOAD_HASH] = hash(
                algo: 'sha256',
                data: json_encode($input[SourceEventFiltersEnum::PAYLOAD], JSON_UNESCAPED_UNICODE)
            );
        }

        $prepared = $this->prepareCreatePayload($input);
        $existing = $this->repository->find([
            SourceEventFiltersEnum::PAYLOAD_HASH => $prepared[SourceEventFieldsEnum::PAYLOAD_HASH]
        ]);

        if ($existing) {
            return $existing;
        }

        return $this->repository->create($prepared);
    }

    /**
     * Normalize a SourceEvent to a canonical Event using a mapper callback.
     * $mapper receives (SourceEvent $se) and must return an array of one or many canonical event payloads.
     */
    public function normalize(SourceEvent $sourceEvent, callable $mapper): array
    {
        $eventService     = app(\App\Services\EventService::class);
        $shipmentService  = app(\App\Services\ShipmentService::class);

        // Adapter-provided: returns array of canonical event payloads (using your enums/fields)
        $canonicalRows = $mapper($sourceEvent);

        $created = [];
        foreach ($canonicalRows as $row) {
            // 1) leg transitions first (so we know the leg_id to attach to the event)
            /** @var \App\Models\Shipment $shipment */
            $shipment = $shipmentService->getById($row[\App\Enums\Event\EventFiltersEnum::SHIPMENT_ID]);

            [$legId] = $shipmentService->applyLegTransitions(
                shipment: $shipment,
                eventType: $row[\App\Enums\Event\EventFiltersEnum::TYPE],
                explicitSourceId: $row[\App\Enums\Event\EventFiltersEnum::SOURCE_ID] ?? null,
                occurredAt: $row[\App\Enums\Event\EventFiltersEnum::OCCURRED_AT],
            );

            $row[\App\Enums\Event\EventFiltersEnum::LEG_ID] = $legId;

            // 2) create or return existing canonical event
            $event = $eventService->findOrCreate($row);
            $created[] = $event;

            // 3) link SourceEvent → Event (optional but handy)
            $this->update($sourceEvent->id, [
                \App\Enums\SourceEvent\SourceEventFiltersEnum::EVENT_ID    => $event->id,
                \App\Enums\SourceEvent\SourceEventFiltersEnum::OCCURRED_AT => $event->occurred_at,
            ]);

            // 4) header side-effects (delivered, discrepancy, bump last_event_at)
            $authoritative = ($event->source_id === $shipment->status_source_id);
            $eventService->applyShipmentHeaderSideEffects(
                $shipment,
                $event->type,
                $event->occurred_at,
                $authoritative
            );
        }

        return $created;
    }
}
