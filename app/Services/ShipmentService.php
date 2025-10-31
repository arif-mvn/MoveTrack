<?php

namespace App\Services;

use App\Enums\Event\EventTypeEnum;
use App\Enums\Leg\LegTypeEnum;
use App\Enums\Shipment\ShipmentFieldsEnum;
use App\Enums\Shipment\ShipmentFiltersEnum;
use App\Enums\SortOrderEnum;
use App\Exceptions\ModelNotFoundException;
use App\Models\Shipment;
use App\Repository\ShipmentRepository;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class ShipmentService
{
    public function __construct(private readonly ShipmentRepository $repository)
    {
    }

    public function getPaginatedShipments(array $queryParameters = []): LengthAwarePaginator
    {
        $page    = $queryParameters['page'] ?? 1;
        $perPage = $queryParameters['per_page'] ?? 20;

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: array_intersect_key($queryParameters, array_flip(ShipmentFiltersEnum::values())),
            fields: $queryParameters['fields'] ?? [],
            expand: $queryParameters['expand'] ?? [],
            sortBy: $queryParameters['sort_by'] ?? null,
            sortOrder: $queryParameters['sort_order'] ?? SortOrderEnum::ASC,
        );
    }

    /** @throws Exception */
    public function create(array $payload): Shipment
    {
        $prepared = $this->prepareCreatePayload($payload);
        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function update(int $id, array $payload): Shipment
    {
        $shipment = $this->getById($id);
        $prepared = $this->prepareUpdatePayload($payload);
        return $this->repository->update($shipment, $prepared);
    }

    public function prepareCreatePayload(array $payload): array
    {
        return [
            ShipmentFieldsEnum::MODE               => $payload[ShipmentFiltersEnum::MODE],
            ShipmentFieldsEnum::CURRENT_STATUS     => $payload[ShipmentFiltersEnum::CURRENT_STATUS],
            ShipmentFieldsEnum::STATUS_SOURCE_ID   => $payload[ShipmentFiltersEnum::STATUS_SOURCE_ID],
            ShipmentFieldsEnum::DELIVERED_AT       => $payload[ShipmentFiltersEnum::DELIVERED_AT] ?? null,
            ShipmentFieldsEnum::DELIVERED_AT_COURIER => $payload[ShipmentFiltersEnum::DELIVERED_AT_COURIER] ?? null,
            ShipmentFieldsEnum::STATUS_DISCREPANCY => $payload[ShipmentFiltersEnum::STATUS_DISCREPANCY] ?? false,
            ShipmentFieldsEnum::LAST_EVENT_AT      => $payload[ShipmentFiltersEnum::LAST_EVENT_AT],
            ShipmentFieldsEnum::LAST_SYNCED_AT     => $payload[ShipmentFiltersEnum::LAST_SYNCED_AT] ?? null,
            ShipmentFieldsEnum::SUMMARY_TIMESTAMPS => $payload[ShipmentFiltersEnum::SUMMARY_TIMESTAMPS] ?? null,
        ];
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            ShipmentFieldsEnum::CURRENT_STATUS       => $payload[ShipmentFiltersEnum::CURRENT_STATUS] ?? null,
            ShipmentFieldsEnum::DELIVERED_AT         => $payload[ShipmentFiltersEnum::DELIVERED_AT] ?? null,
            ShipmentFieldsEnum::DELIVERED_AT_COURIER => $payload[ShipmentFiltersEnum::DELIVERED_AT_COURIER] ?? null,
            ShipmentFieldsEnum::STATUS_DISCREPANCY   => $payload[ShipmentFiltersEnum::STATUS_DISCREPANCY] ?? null,
            ShipmentFieldsEnum::LAST_EVENT_AT        => $payload[ShipmentFiltersEnum::LAST_EVENT_AT] ?? null,
            ShipmentFieldsEnum::LAST_SYNCED_AT       => $payload[ShipmentFiltersEnum::LAST_SYNCED_AT] ?? null,
            ShipmentFieldsEnum::SUMMARY_TIMESTAMPS   => $payload[ShipmentFiltersEnum::SUMMARY_TIMESTAMPS] ?? null,
        ];
    }

    public function findById(int $id): ?Shipment
    {
        return $this->repository->find(filters: [ShipmentFiltersEnum::ID => $id]);
    }

    public function getById(int $id): Shipment
    {
        $shipment = $this->repository->find(filters: [ShipmentFiltersEnum::ID => $id]);

        if (!$shipment) {
            throw new ModelNotFoundException('Shipment not found for id: ' . $id);
        }

        return $shipment;
    }

    public function applyLegTransitions(Shipment $shipment, string $eventType, ?int $explicitSourceId, string $occurredAt): array
    {
        $legService         = app(LegService::class);
        $identifierService  = app(IdentifierService::class);

        $shipmentId = $shipment->id;
        $legId      = null;

        switch ($eventType) {
            // Procurement
            case EventTypeEnum::PROCUREMENT_IN_PROGRESS:
                $leg = $legService->ensureLeg($shipmentId, LegTypeEnum::ORIGIN_PROCUREMENT, null, $occurredAt);
                $legId = $leg->id;
                break;

            case EventTypeEnum::READY_FOR_TRANSPORT:
            case EventTypeEnum::HANDOVER_TO_SHIP:
            case EventTypeEnum::HANDOVER_TO_AIRLINE:
                // Close procurement, ensure linehaul
                $legService->closeLegIfOpen($shipmentId, LegTypeEnum::ORIGIN_PROCUREMENT, $occurredAt);
                $leg = $legService->ensureLeg($shipmentId, LegTypeEnum::LINEHAUL, $explicitSourceId, $occurredAt);
                $legId = $leg->id;
                break;

            // Destination WH
            case EventTypeEnum::ARRIVED_DEST_WAREHOUSE:
                $legService->closeLegIfOpen($shipmentId, LegTypeEnum::LINEHAUL, $occurredAt);
                $leg = $legService->ensureLeg($shipmentId, LegTypeEnum::DESTINATION_WH, null, $occurredAt);
                $legId = $leg->id;
                break;

            // Handed to last-mile / last-mile lifecycle
            case EventTypeEnum::HANDED_TO_LAST_MILE:
            case EventTypeEnum::DELIVERY_REQUEST_CREATED:
            case EventTypeEnum::DELIVERY_PROCESSING:
            case EventTypeEnum::DELIVERY_READY:
            case EventTypeEnum::DELIVERY_SHIPPED:
            case EventTypeEnum::OUT_FOR_DELIVERY_ASSIGNED:
            case EventTypeEnum::OUT_FOR_DELIVERY_STARTED:
            case EventTypeEnum::DELIVERY_FAILED:
            case EventTypeEnum::DELIVERY_RETURNED:
            case EventTypeEnum::DELIVERED:
                // Close destination_wh when shipping starts
                if (in_array($eventType, [
                    EventTypeEnum::HANDED_TO_LAST_MILE,
                    EventTypeEnum::DELIVERY_SHIPPED
                ], true)) {
                    $legService->closeLegIfOpen($shipmentId, LegTypeEnum::DESTINATION_WH, $occurredAt);
                }

                // Try infer last-mile source from identifiers if not passed
                $sourceId = $explicitSourceId
                    ?? $identifierService->guessLastMileSourceId($shipmentId, app(SourceService::class)->mapCarrierCodeToSourceId());

                $leg = $legService->ensureLeg($shipmentId, LegTypeEnum::LAST_MILE, $sourceId, $occurredAt);
                $legId = $leg->id;

                // Close last-mile on terminal events
                if (in_array($eventType, [
                    EventTypeEnum::DELIVERED,
                    EventTypeEnum::DELIVERY_FAILED,
                    EventTypeEnum::DELIVERY_RETURNED
                ], true)) {
                    $legService->closeLegIfOpen($shipmentId, LegTypeEnum::LAST_MILE, $occurredAt);
                }
                break;
        }

        return [$legId, $shipment->refresh()];
    }

}
