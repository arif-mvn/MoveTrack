<?php

namespace App\Services;

use App\Enums\ShipmentSource\ShipmentSourceFiltersEnum;
use App\Enums\ShipmentSource\ShipmentSourceFieldsEnum;
use App\Enums\SortOrderEnum;
use App\Models\ShipmentSource;
use App\Repository\ShipmentSourceRepository;
use App\Exceptions\ModelNotFoundException;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class ShipmentSourceService
{
    public function __construct(private readonly ShipmentSourceRepository $repository)
    {
    }

    public function getPaginatedShipmentSources(array $queryParameters = []): LengthAwarePaginator
    {
        $page    = $queryParameters['page'] ?? 1;
        $perPage = $queryParameters['per_page'] ?? 20;

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: array_intersect_key($queryParameters, array_flip(ShipmentSourceFiltersEnum::values())),
            fields: $queryParameters['fields'] ?? [],
            expand: $queryParameters['expand'] ?? [],
            sortBy: $queryParameters['sort_by'] ?? null,
            sortOrder: $queryParameters['sort_order'] ?? SortOrderEnum::ASC,
        );
    }

    /** @throws Exception */
    public function create(array $payload): ShipmentSource
    {
        $prepared = $this->prepareCreatePayload($payload);
        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function update(int $id, array $payload): ShipmentSource
    {
        $shipmentSource = $this->getById($id);
        $prepared = $this->prepareUpdatePayload($payload);
        return $this->repository->update($shipmentSource, $prepared);
    }

    public function prepareCreatePayload(array $payload): array
    {
        return [
            ShipmentSourceFieldsEnum::SHIPMENT_ID    => $payload[ShipmentSourceFiltersEnum::SHIPMENT_ID],
            ShipmentSourceFieldsEnum::SOURCE_ID      => $payload[ShipmentSourceFiltersEnum::SOURCE_ID],
            ShipmentSourceFieldsEnum::LAST_SYNCED_AT => $payload[ShipmentSourceFiltersEnum::LAST_SYNCED_AT] ?? null,
        ];
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            ShipmentSourceFieldsEnum::LAST_SYNCED_AT => $payload[ShipmentSourceFiltersEnum::LAST_SYNCED_AT] ?? null,
        ];
    }

    public function getById(int $id): ShipmentSource
    {
        $shipmentSource = $this->repository->find(filters: [ShipmentSourceFiltersEnum::ID => $id]);
        if (!$shipmentSource) {
            throw new ModelNotFoundException('ShipmentSource not found for id: ' . $id);
        }
        return $shipmentSource;
    }
}
