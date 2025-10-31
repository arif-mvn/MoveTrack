<?php

namespace App\Repository;

use App\Enums\Source\SourceFieldsEnum;
use App\Enums\Source\SourceFiltersEnum;
use App\Models\Source;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;

class SourceRepository
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
        string $sortOrder = "ASC"
    ): LengthAwarePaginator
    {
        $query = $this->getQuery(filters: $filters, expand: $expand);

        if (count($fields) > 0) {
            $query = $query->select($fields);
        }

        if ($sortBy) {
            $query = $query->orderBy($sortBy, $sortOrder);
        } else {
            $query = $query->orderBy(SourceFieldsEnum::ID, "desc");
        }

        return $query->paginate($perPage, ["*"], 'page', $page);
    }

    /**
     * @param array $payload
     * @return Source
     * @throws Exception
     */
    public function create(array $payload): Source
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $source = Source::create($payload);
            DB::commit();

            return $source;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(Source $source, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => $source->lock_version + 1
            ]);
            $updated = $source->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception("Max retry exceeded during update");
        }

        return $source->refresh();
    }


    /**
     * @param Source $source
     * @return bool|null
     */
    public function delete(Source $source): ?bool
    {
        return $source->delete();
    }

    /**
     * @param array $filters
     * @param array $expand
     * @return Source|null
     */
    public function find(array $filters = [], array $expand = []): ?Source
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
        return Source::query()
            ->when(isset($filters[SourceFiltersEnum::ID]), function ($query) use ($filters) {
                $query->where(SourceFieldsEnum::ID, $filters[SourceFiltersEnum::ID]);
            })
            ->when(isset($filters[SourceFiltersEnum::IDS]), function ($query) use ($filters) {
                $query->whereIn(SourceFieldsEnum::ID, $filters[SourceFiltersEnum::IDS]);
            })
            ->when(isset($filters[SourceFiltersEnum::CODE]), function ($query) use ($filters) {
                $query->where(SourceFieldsEnum::CODE, $filters[SourceFiltersEnum::CODE]);
            })
            ->when(isset($filters[SourceFiltersEnum::NAME]), function ($query) use ($filters) {
                $query->where(SourceFieldsEnum::NAME, "like", "%" . $filters[SourceFiltersEnum::NAME] . "%");
            })
            ->when(isset($filters[SourceFiltersEnum::TYPE]), function ($query) use ($filters) {
                $query->where(SourceFieldsEnum::TYPE, $filters[SourceFiltersEnum::TYPE]);
            })
            ->when(isset($filters[SourceFiltersEnum::ENABLED]), function ($query) use ($filters) {
                $query->where(SourceFieldsEnum::ENABLED, $filters[SourceFiltersEnum::ENABLED]);
            })
            ->when(isset($filters[SourceFiltersEnum::CREATED_AT]), function ($query) use ($filters) {
                $query->whereBetween(SourceFieldsEnum::CREATED_AT, $filters[SourceFiltersEnum::CREATED_AT]);
            })
            ->with($expand);
    }
}
