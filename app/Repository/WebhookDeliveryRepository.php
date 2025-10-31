<?php

namespace App\Repository;

use App\Enums\WebhookDelivery\WebhookDeliveryFieldsEnum;
use App\Enums\WebhookDelivery\WebhookDeliveryFiltersEnum;
use App\Models\WebhookDelivery;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Support\Str;

class WebhookDeliveryRepository
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
        string $sortOrder = 'ASC'
    ): LengthAwarePaginator {
        $query = $this->getQuery(filters: $filters, expand: $expand);

        if (count($fields) > 0) {
            $query = $query->select($fields);
        }

        if ($sortBy) {
            $query = $query->orderBy($sortBy, $sortOrder);
        } else {
            $query = $query->orderBy(WebhookDeliveryFieldsEnum::ID, 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param array $payload
     * @return WebhookDelivery
     * @throws Exception
     */
    public function create(array $payload): WebhookDelivery
    {
        try {
            DB::beginTransaction();
            $payload = array_merge($payload, [
                "etag"         => (string) Str::uuid(),
                "lock_version" => 1,
            ]);
            $webhookDelivery = WebhookDelivery::create($payload);
            DB::commit();
            return $webhookDelivery;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function update(WebhookDelivery $webhookDelivery, array $changes)
    {
        $attempt = 1;
        do {
            $changes = array_merge($changes, [
                'etag'         => (string) Str::uuid(),
                'lock_version' => $webhookDelivery->lock_version + 1,
            ]);
            $updated = $webhookDelivery->update($changes);
            $attempt++;
        } while (!$updated && $attempt <= self::MAX_RETRY);

        if (!$updated && $attempt > self::MAX_RETRY) {
            throw new Exception('Max retry exceeded during update');
        }

        return $webhookDelivery->refresh();
    }

    /**
     * @param WebhookDelivery $webhookDelivery
     * @return bool|null
     */
    public function delete(WebhookDelivery $webhookDelivery): ?bool
    {
        return $webhookDelivery->delete();
    }

    /**
     * @param array $filters
     * @param array $expand
     * @return WebhookDelivery|null
     */
    public function find(array $filters = [], array $expand = []): ?WebhookDelivery
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
        return WebhookDelivery::query()
            ->when(isset($filters[WebhookDeliveryFiltersEnum::ID]), function ($query) use ($filters) {
                $query->where(WebhookDeliveryFieldsEnum::ID, $filters[WebhookDeliveryFiltersEnum::ID]);
            })
            ->when(isset($filters[WebhookDeliveryFiltersEnum::SOURCE_ID]), function ($query) use ($filters) {
                $query->where(WebhookDeliveryFieldsEnum::SOURCE_ID, $filters[WebhookDeliveryFiltersEnum::SOURCE_ID]);
            })
            ->when(isset($filters[WebhookDeliveryFiltersEnum::SIGNATURE]), function ($query) use ($filters) {
                $query->where(WebhookDeliveryFieldsEnum::SIGNATURE, $filters[WebhookDeliveryFiltersEnum::SIGNATURE]);
            })
            ->when(isset($filters[WebhookDeliveryFiltersEnum::STATUS]), function ($query) use ($filters) {
                $query->where(WebhookDeliveryFieldsEnum::STATUS, $filters[WebhookDeliveryFiltersEnum::STATUS]);
            })
            ->when(isset($filters[WebhookDeliveryFiltersEnum::ERROR]), function ($query) use ($filters) {
                $query->where(WebhookDeliveryFieldsEnum::ERROR, $filters[WebhookDeliveryFiltersEnum::ERROR]);
            })
            ->when(isset($filters[WebhookDeliveryFiltersEnum::RECEIVED_AT]), function ($query) use ($filters) {
                $value = $filters[WebhookDeliveryFiltersEnum::RECEIVED_AT];
                if (is_array($value)) {
                    $query->whereBetween(WebhookDeliveryFieldsEnum::RECEIVED_AT, $value);
                } else {
                    $query->where(WebhookDeliveryFieldsEnum::RECEIVED_AT, $value);
                }
            })
            ->when(isset($filters[WebhookDeliveryFiltersEnum::PROCESSED_AT]), function ($query) use ($filters) {
                $value = $filters[WebhookDeliveryFiltersEnum::PROCESSED_AT];
                if (is_array($value)) {
                    $query->whereBetween(WebhookDeliveryFieldsEnum::PROCESSED_AT, $value);
                } else {
                    $query->where(WebhookDeliveryFieldsEnum::PROCESSED_AT, $value);
                }
            })
            ->when(isset($filters[WebhookDeliveryFiltersEnum::CREATED_AT]), function ($query) use ($filters) {
                $value = $filters[WebhookDeliveryFiltersEnum::CREATED_AT];
                if (is_array($value)) {
                    $query->whereBetween(WebhookDeliveryFieldsEnum::CREATED_AT, $value);
                } else {
                    $query->where(WebhookDeliveryFieldsEnum::CREATED_AT, $value);
                }
            })
            ->with($expand);
    }
}
