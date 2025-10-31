<?php

namespace App\Services;

use App\Enums\Leg\LegFiltersEnum;
use App\Enums\Leg\LegFieldsEnum;
use App\Enums\SortOrderEnum;
use App\Models\Leg;
use App\Repository\LegRepository;
use App\Exceptions\ModelNotFoundException;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LegService
{
    public function __construct(private readonly LegRepository $repository)
    {
    }

    public function getPaginatedLegs(array $queryParameters = []): LengthAwarePaginator
    {
        $page    = $queryParameters['page'] ?? 1;
        $perPage = $queryParameters['per_page'] ?? 20;

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: array_intersect_key($queryParameters, array_flip(LegFiltersEnum::values())),
            fields: $queryParameters['fields'] ?? [],
            expand: $queryParameters['expand'] ?? [],
            sortBy: $queryParameters['sort_by'] ?? null,
            sortOrder: $queryParameters['sort_order'] ?? SortOrderEnum::ASC,
        );
    }

    /** @throws Exception */
    public function create(array $payload): Leg
    {
        $prepared = $this->prepareCreatePayload($payload);
        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function update(int $id, array $payload): Leg
    {
        $leg = $this->getById($id);
        $prepared = $this->prepareUpdatePayload($payload);
        return $this->repository->update($leg, $prepared);
    }

    public function prepareCreatePayload(array $payload): array
    {
        return [
            LegFieldsEnum::SHIPMENT_ID  => $payload[LegFiltersEnum::SHIPMENT_ID],
            LegFieldsEnum::TYPE         => $payload[LegFiltersEnum::TYPE],
            LegFieldsEnum::SOURCE_ID    => $payload[LegFiltersEnum::SOURCE_ID] ?? null,
            LegFieldsEnum::CARRIER_NAME => $payload[LegFiltersEnum::CARRIER_NAME] ?? null,
            LegFieldsEnum::ROUTE        => $payload[LegFiltersEnum::ROUTE] ?? null,
            LegFieldsEnum::START_AT     => $payload[LegFiltersEnum::START_AT] ?? null,
            LegFieldsEnum::END_AT       => $payload[LegFiltersEnum::END_AT] ?? null,
        ];
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            LegFieldsEnum::TYPE         => $payload[LegFiltersEnum::TYPE] ?? null,
            LegFieldsEnum::SOURCE_ID    => $payload[LegFiltersEnum::SOURCE_ID] ?? null,
            LegFieldsEnum::CARRIER_NAME => $payload[LegFiltersEnum::CARRIER_NAME] ?? null,
            LegFieldsEnum::ROUTE        => $payload[LegFiltersEnum::ROUTE] ?? null,
            LegFieldsEnum::START_AT     => $payload[LegFiltersEnum::START_AT] ?? null,
            LegFieldsEnum::END_AT       => $payload[LegFiltersEnum::END_AT] ?? null,
        ];
    }

    public function getById(int $id): Leg
    {
        $leg = $this->repository->find(filters: [LegFiltersEnum::ID => $id]);
        if (!$leg) {
            throw new ModelNotFoundException('Leg not found for id: ' . $id);
        }
        return $leg;
    }


    public function ensureLeg(int $shipmentId, string $type, ?int $sourceId, ?string $startAt): Leg
    {
        $leg = $this->repository->find([
            LegFiltersEnum::SHIPMENT_ID => $shipmentId,
            LegFiltersEnum::TYPE        => $type,
        ]);

        try {
            DB::beginTransaction();
            if ($leg) {
                // fill source_id if we learned it later
                if ($sourceId && !$leg->{LegFieldsEnum::SOURCE_ID}) {
                    $this->update($leg->id, [LegFiltersEnum::SOURCE_ID => $sourceId]);
                    $leg->refresh();
                }
                // start_at if empty, set it
                if (!$leg->{LegFieldsEnum::START_AT} && $startAt) {
                    $this->update($leg->id, [LegFiltersEnum::START_AT => $startAt]);
                    $leg->refresh();
                }
                return $leg;
            }
            $leg = $this->create([
                LegFiltersEnum::SHIPMENT_ID => $shipmentId,
                LegFiltersEnum::TYPE        => $type,
                LegFiltersEnum::SOURCE_ID   => $sourceId,
                LegFiltersEnum::START_AT    => $startAt,
            ]);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        return $leg;
    }

    /** Close a leg if open */
    public function closeLegIfOpen(int $shipmentId, string $type, string $endAt): void
    {
        $leg = $this->repository->find([
            LegFiltersEnum::SHIPMENT_ID => $shipmentId,
            LegFiltersEnum::TYPE        => $type,
        ]);

        if ($leg && !$leg->{LegFieldsEnum::END_AT}) {
            $this->update($leg->id, [LegFiltersEnum::END_AT => $endAt]);
        }
    }
}
