<?php

namespace App\Jobs;

use App\Enums\Identifier\IdentifierFiltersEnum;
use App\Enums\Identifier\IdentifierFieldsEnum;
use App\Enums\SourceEvent\SourceEventFiltersEnum;
use App\Services\IdentifierService;
use App\Services\SourceService;
use App\Services\SourceEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncShipmentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $shipmentId,
        public array $expectedSourceIds = [] // optional hint
    ) {}

    public function handle(
        IdentifierService  $identifierService,
        SourceService      $sourceService,
        SourceEventService $sourceEventService,
    ): void {
        // 1) Figure out which last-mile sources to hit
        //    - from identifiers (preferred)
        //    - or from constructor hints
        $map = $sourceService->mapCarrierCodeToSourceId(); // your private method; make it public or expose another getter

        $identifiers = $identifierService->getPaginatedIdentifiers([
            'page' => 1, 'per_page' => 100,
            IdentifierFiltersEnum::SHIPMENT_ID => $this->shipmentId,
        ])->getCollection();

        $targets = [];
        foreach ($identifiers as $id) {
            if (in_array($id->{IdentifierFieldsEnum::SCOPE}, ['last_mile','courier'], true)) {
                $code = $id->{IdentifierFieldsEnum::CARRIER_CODE};
                if (isset($map[$code])) $targets[$map[$code]] = true;
            }
        }
        foreach ($this->expectedSourceIds as $sid) $targets[$sid] = true;

        $sourceIds = array_keys($targets);
        if (empty($sourceIds)) return;

        // 2) For each target, call its adapter → ingest → normalize
        foreach ($sourceIds as $sid) {
            // Example: resolve adapter by source code
            $source = $sourceService->getById($sid);
            $adapter = $this->resolveAdapter($source->code); // method below

            [$rawPayload, $providerEventId] = $adapter->pull($this->shipmentId);

            $se = $sourceEventService->findOrCreate([
                SourceEventFiltersEnum::SHIPMENT_ID     => $this->shipmentId,
                SourceEventFiltersEnum::SOURCE_ID       => $sid,
                SourceEventFiltersEnum::SOURCE_EVENT_ID => $providerEventId,
                SourceEventFiltersEnum::PAYLOAD         => $rawPayload,
                SourceEventFiltersEnum::RECEIVED_AT     => now()->toDateTimeString(),
            ]);

            $sourceEventService->normalize($se, [$adapter, 'map']); // adapter->map returns canonical rows (like YunExpressNormalizer::map)
        }
    }

    private function resolveAdapter(string $sourceCode)
    {
        return match ($sourceCode) {
            'YunExpress'   => app(\App\Adapters\YunExpressAdapter::class),
            'imile'        => app(\App\Adapters\IMileAdapter::class),
            'steadfast'    => app(\App\Adapters\SteadfastAdapter::class),
            'redx'         => app(\App\Adapters\RedxAdapter::class),
            default        => throw new \RuntimeException('No adapter for source: '.$sourceCode),
        };
    }
}
