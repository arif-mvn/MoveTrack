<?php

namespace App\Services;

use App\Enums\SortOrderEnum;
use App\Enums\WebhookDelivery\WebhookDeliveryFieldsEnum;
use App\Enums\WebhookDelivery\WebhookDeliveryFiltersEnum;
use App\Exceptions\ModelNotFoundException;
use App\Models\WebhookDelivery;
use App\Repository\WebhookDeliveryRepository;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class WebhookDeliveryService
{
    public function __construct(private readonly WebhookDeliveryRepository $repository)
    {
    }

    public function getPaginatedWebhookDeliveries(array $queryParameters = []): LengthAwarePaginator
    {
        $page    = $queryParameters['page'] ?? 1;
        $perPage = $queryParameters['per_page'] ?? 50;

        return $this->repository->getAll(
            page: $page,
            perPage: $perPage,
            filters: array_intersect_key($queryParameters, array_flip(WebhookDeliveryFiltersEnum::values())),
            fields: $queryParameters['fields'] ?? [],
            expand: $queryParameters['expand'] ?? [],
            sortBy: $queryParameters['sort_by'] ?? null,
            sortOrder: $queryParameters['sort_order'] ?? SortOrderEnum::DESC,
        );
    }

    /** @throws Exception */
    public function create(array $payload): WebhookDelivery
    {
        $prepared = $this->prepareCreatePayload($payload);
        return $this->repository->create($prepared);
    }

    /** @throws Exception */
    public function update(int $id, array $payload): WebhookDelivery
    {
        $delivery = $this->getById($id);
        $prepared = $this->prepareUpdatePayload($payload);
        return $this->repository->update($delivery, $prepared);
    }

    public function prepareCreatePayload(array $payload): array
    {
        return [
            WebhookDeliveryFieldsEnum::SOURCE_ID    => $payload[WebhookDeliveryFiltersEnum::SOURCE_ID],
            WebhookDeliveryFieldsEnum::SIGNATURE    => $payload[WebhookDeliveryFiltersEnum::SIGNATURE] ?? null,
            WebhookDeliveryFieldsEnum::REQUEST_BODY => $payload[WebhookDeliveryFiltersEnum::REQUEST_BODY],
            WebhookDeliveryFieldsEnum::STATUS       => $payload[WebhookDeliveryFiltersEnum::STATUS],
            WebhookDeliveryFieldsEnum::ERROR        => $payload[WebhookDeliveryFiltersEnum::ERROR] ?? null,
            WebhookDeliveryFieldsEnum::RECEIVED_AT  => $payload[WebhookDeliveryFiltersEnum::RECEIVED_AT],
            WebhookDeliveryFieldsEnum::PROCESSED_AT => $payload[WebhookDeliveryFiltersEnum::PROCESSED_AT] ?? null,
        ];
    }

    public function prepareUpdatePayload(array $payload): array
    {
        return [
            WebhookDeliveryFieldsEnum::STATUS       => $payload[WebhookDeliveryFiltersEnum::STATUS] ?? null,
            WebhookDeliveryFieldsEnum::ERROR        => $payload[WebhookDeliveryFiltersEnum::ERROR] ?? null,
            WebhookDeliveryFieldsEnum::PROCESSED_AT => $payload[WebhookDeliveryFiltersEnum::PROCESSED_AT] ?? null,
        ];
    }

    public function getById(int $id): WebhookDelivery
    {
        $delivery = $this->repository->find(filters: [WebhookDeliveryFiltersEnum::ID => $id]);
        if (!$delivery) {
            throw new ModelNotFoundException('WebhookDelivery not found for id: ' . $id);
        }
        return $delivery;
    }

    public function acceptAndProcess(array $payload, callable $toSourceEventPayload): WebhookDelivery
    {
        $delivery = $this->create($this->prepareCreatePayload($payload));

        try {
            // Build SourceEvent payload from the delivery (adapter-provided).
            $sePayload = $toSourceEventPayload($delivery);

            // Use findOrCreate so payload_hash is auto-computed and idempotent.
            $se = app(SourceEventService::class)->findOrCreate($sePayload);

            // Optionally normalize immediately (or queue this)
            app(SourceEventService::class)->normalize($se, [$this->resolveAdapterBySourceId($se->source_id), 'map']);

            $this->update($delivery->id, [
                WebhookDeliveryFiltersEnum::STATUS       => 'processed',
                WebhookDeliveryFiltersEnum::PROCESSED_AT => now()->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            $this->update($delivery->id, [
                WebhookDeliveryFiltersEnum::STATUS => 'failed',
                WebhookDeliveryFiltersEnum::ERROR  => $e->getMessage(),
            ]);
            throw $e;
        }

        return $delivery->refresh();
    }

// Helper if you prefer resolution here:
    private function resolveAdapterBySourceId(int $sourceId)
    {
        $source = app(SourceService::class)->getById($sourceId);
        return match ($source->code) {
            'YunExpress'   => app(\App\Adapters\YunExpressAdapter::class),
            'imile'        => app(\App\Adapters\IMileAdapter::class),
            'steadfast'    => app(\App\Adapters\SteadfastAdapter::class),
            'redx'         => app(\App\Adapters\RedxAdapter::class),
            default        => throw new \RuntimeException('No adapter for source: '.$source->code),
        };
    }

}
