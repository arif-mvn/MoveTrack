<?php

namespace App\Services;

use App\Enums\Identifier\IdentifierFiltersEnum;
use App\Enums\Identifier\IdentifierFieldsEnum;
use App\Enums\Identifier\IdentifierScopeEnum;
use App\Enums\SortOrderEnum;
use App\Exceptions\ModelNotFoundException;
use App\Models\Identifier;
use App\Repository\IdentifierRepository;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class IdentifierService
{
    public function __construct(private readonly IdentifierRepository $repository)
    {
    }

    public function getPaginatedIdentifiers(array $queryParameters = []): LengthAwarePaginator
    {
        $page    = $queryParameters['page'] ?? 1;
        $perPage = $queryParameters['per_page'] ?? 20;

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: array_intersect_key($queryParameters, array_flip(IdentifierFiltersEnum::values())),
            fields: $queryParameters['fields'] ?? [],
            expand: $queryParameters['expand'] ?? [],
            sortBy: $queryParameters['sort_by'] ?? null,
            sortOrder: $queryParameters['sort_order'] ?? SortOrderEnum::ASC,
        );
    }

    /** @throws Exception */
    public function create(array $payload): Identifier
    {
        $prepared = $this->prepareCreatePayload($payload);

        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function findOrCreate(array $payload): Identifier
    {
        $prepared = $this->prepareCreatePayload($payload);
        $existing = $this->repository->find([
            IdentifierFiltersEnum::SCOPE        => $prepared[IdentifierFieldsEnum::SCOPE],
            IdentifierFiltersEnum::CARRIER_CODE => $prepared[IdentifierFieldsEnum::CARRIER_CODE],
            IdentifierFiltersEnum::VALUE        => $prepared[IdentifierFieldsEnum::VALUE],
        ]);
        if ($existing) {
            // Ensure it’s linked to the shipment
            if ((int)$existing->{IdentifierFieldsEnum::SHIPMENT_ID} !== (int)$prepared[IdentifierFieldsEnum::SHIPMENT_ID]) {
                throw new \RuntimeException('Identifier already exists for another shipment.');
            }
            return $existing;
        }

        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function update(int $id, array $payload): Identifier
    {
        $identifier = $this->getById($id);
        $prepared =  $this->prepareUpdatePayload($payload);
        return $this->repository->update($identifier, $prepared);
    }

    public function prepareCreatePayload(array $payload): array
    {
        return [
            IdentifierFieldsEnum::SHIPMENT_ID  => $payload[IdentifierFiltersEnum::SHIPMENT_ID],
            IdentifierFieldsEnum::SCOPE        => $payload[IdentifierFiltersEnum::SCOPE],
            IdentifierFieldsEnum::CARRIER_CODE => $payload[IdentifierFiltersEnum::CARRIER_CODE],
            IdentifierFieldsEnum::VALUE        => $payload[IdentifierFiltersEnum::VALUE],
        ];
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            IdentifierFieldsEnum::SCOPE        => $payload[IdentifierFiltersEnum::SCOPE] ?? null,
            IdentifierFieldsEnum::CARRIER_CODE => $payload[IdentifierFiltersEnum::CARRIER_CODE] ?? null,
            IdentifierFieldsEnum::VALUE        => $payload[IdentifierFiltersEnum::VALUE] ?? null,
        ];
    }

    public function findById(int $id): ?Identifier
    {
        return $this->repository->find(filters: [IdentifierFiltersEnum::ID => $id]);
    }

    public function getById(int $id): Identifier
    {
        $identifier = $this->repository->find(filters: [IdentifierFiltersEnum::ID => $id]);

        if (!$identifier) {
            throw new ModelNotFoundException('Identifier not found for id: ' . $id);
        }

        return $identifier;
    }

    /** Guess last-mile carrier Source ID for a shipment (using known identifiers) */
    public function guessLastMileSourceId(int $shipmentId, array $carrierCodeToSourceId): ?int
    {
        $all = $this->repository->findAll([
            IdentifierFiltersEnum::SHIPMENT_ID => $shipmentId,
        ]);

        // Prefer explicit "last_mile" scope
        $lm = $all->firstWhere(IdentifierFieldsEnum::SCOPE, IdentifierScopeEnum::LAST_MILE);
        if ($lm && isset($carrierCodeToSourceId[$lm->{IdentifierFieldsEnum::CARRIER_CODE}])) {
            return $carrierCodeToSourceId[$lm->{IdentifierFieldsEnum::CARRIER_CODE}];
        }

        // Fallback to any courier scope
        $courier = $all->firstWhere(IdentifierFieldsEnum::SCOPE, IdentifierScopeEnum::COURIER);
        if ($courier && isset($carrierCodeToSourceId[$courier->{IdentifierFieldsEnum::CARRIER_CODE}])) {
            return $carrierCodeToSourceId[$courier->{IdentifierFieldsEnum::CARRIER_CODE}];
        }

        return null;
    }
}

