<?php

namespace App\Adapters;

interface ICourierAdapter {
    /** @return array{0: array $rawPayload, 1: ?string $providerEventId} */
    public function pull(int $shipmentId): array;
    /** @return list<array> canonical event payloads for EventService::findOrCreate */
    public static function map(\App\Models\SourceEvent $se): array;
}
