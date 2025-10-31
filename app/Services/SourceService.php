<?php

namespace App\Services;

use App\Enums\SortOrderEnum;
use App\Enums\Source\SourceFieldsEnum;
use App\Enums\Source\SourceFiltersEnum;
use App\Exceptions\ModelNotFoundException;
use App\Models\Source;
use App\Repository\SourceRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SourceService
{


    public function __construct(private readonly SourceRepository $repository)
    {
    }

    public function getPaginatedSources(array $queryParameters = []): LengthAwarePaginator
    {
        $page       = $queryParameters["page"] ?? 1;
        $perPage    = $queryParameters["per_page"] ?? 20;

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: array_intersect_key($queryParameters, array_flip(SourceFiltersEnum::values())),
            fields: $queryParameters["fields"] ?? [],
            expand: $queryParameters["expand"] ?? [],
            sortBy: $queryParameters["sort_by"] ?? null,
            sortOrder: $queryParameters["sort_order"] ?? SortOrderEnum::ASC,
        );
    }

    /**
     * @param array $payload
     * @return Source
     * @throws Exception
     */
    public function create(array $payload): Source
    {
        $preparePayload = $this->prepareCreatePayload(payload: $payload);

        try {
            DB::beginTransaction();
            $source = $this->repository->create(payload: $preparePayload);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        return $source;
    }
    public function prepareCreatePayload(array $payload): array
    {
        return [
            SourceFieldsEnum::CODE                  => $payload[SourceFiltersEnum::CODE],
            SourceFieldsEnum::NAME                  => $payload[SourceFiltersEnum::NAME],
            SourceFieldsEnum::TYPE                  => $payload[SourceFiltersEnum::TYPE],
            SourceFieldsEnum::CONFIG                => $payload[SourceFiltersEnum::CONFIG] ?? null,
            SourceFieldsEnum::CREDENTIALS_ENCRYPTED => $payload[SourceFiltersEnum::CREDENTIALS_ENCRYPTED] ?? null,
            SourceFieldsEnum::WEBHOOK_SECRET        => $payload[SourceFiltersEnum::WEBHOOK_SECRET] ?? null,
            SourceFieldsEnum::ENABLED               => $payload[SourceFiltersEnum::ENABLED] ?? true,
        ];
    }

    public function update(int $id, array $payload): Source
    {
        $source = $this->getById($id);
        $preparePayload = $this->prepareUpdatePayload(payload: $payload);

        return $this->repository->update($source, $preparePayload);
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            SourceFieldsEnum::NAME                  => $payload[SourceFiltersEnum::NAME] ?? null,
            SourceFieldsEnum::TYPE                  => $payload[SourceFiltersEnum::TYPE] ?? null,
            SourceFieldsEnum::CONFIG                => $payload[SourceFiltersEnum::CONFIG] ?? null,
            SourceFieldsEnum::CREDENTIALS_ENCRYPTED => $payload[SourceFiltersEnum::CREDENTIALS_ENCRYPTED] ?? null,
            SourceFieldsEnum::WEBHOOK_SECRET        => $payload[SourceFiltersEnum::WEBHOOK_SECRET] ?? null,
            SourceFieldsEnum::ENABLED               => $payload[SourceFiltersEnum::ENABLED] ?? null,
        ];
    }

    public function findAllByIds(array $ids): Collection
    {
        return $this->repository->findAll(
            filters: [SourceFiltersEnum::IDS => $ids]
        );
    }

    public function findById(int $id): ?Source
    {
        return $this->repository->find(
            filters: [SourceFiltersEnum::ID => $id]
        );
    }

    public function getById(int $id): Source
    {
        $source = $this->repository->find(filters: [SourceFiltersEnum::ID => $id]);
        if (!$source) {
            throw new ModelNotFoundException('Source not found for id: ' . $id);
        }
        return $source;
    }


    public function mapCarrierCodeToSourceId(): array
    {
        $sources = $this->repository->findAll();
        $map     = [];
        foreach ($sources as $src) {
            $map[$src->code] = $src->id;
        }
        return $map;
    }
}
