<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TrackIndexRequest;
use App\Http\Resources\V1\TimelineResource;
use App\Models\Shipment;
use App\Services\IdentifierService;
use App\Services\ShipmentService;
use App\Services\SourceService;
use App\Jobs\SyncShipmentJob;
use App\Enums\Identifier\IdentifierFiltersEnum;
use App\Enums\Identifier\IdentifierFieldsEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TrackController extends Controller
{
    public function __construct(
        private readonly IdentifierService $identifiers,
        private readonly ShipmentService   $shipments,
        private readonly SourceService     $sources
    ) {}

    /**
     * GET /v1/track/{value}?carrier_code=YunExpress&scope=last_mile&expand[]=shipment.legs.source
     */
    public function show($value, TrackIndexRequest $request)
    {
        $q = $request->validated();

        // 1) resolve identifier → shipment
        $filters = [ IdentifierFiltersEnum::VALUE => $value ];
        if (!empty($q['carrier_code'])) $filters[IdentifierFiltersEnum::CARRIER_CODE] = $q['carrier_code'];
        if (!empty($q['scope']))        $filters[IdentifierFiltersEnum::SCOPE]        = $q['scope'];

        $idModel = $this->identifiers->findById(
            optional($this->identifiers->getPaginatedIdentifiers($filters))->first()?->id ?? 0
        );
        if (!$idModel) {
            // fallback: loose search by value only (e.g., PB code with default moveon)
            $idModel = $this->identifiers->findAll([IdentifierFiltersEnum::VALUE => $value])->first();
        }
        if (!$idModel) {
            return response()->json(['message' => 'Tracking ID not found'], 404);
        }

        /** @var Shipment $shipment */
        $shipment = $this->shipments->getById($idModel->{IdentifierFieldsEnum::SHIPMENT_ID});
        $shipment->loadMissing(['legs.source','statusSource','events.source','events.leg']);

        // 2) staleness check → enqueue sync if stale or force_sync=true
        $now = Carbon::now();
        $stalenessCutoff = $now->clone()->subHours( (strtolower($shipment->mode) === 'last_mile') ? 1 : 2 );
        $stale = !$shipment->last_synced_at || $shipment->last_synced_at->lt($stalenessCutoff);
        if ($stale || ($q['force_sync'] ?? false)) {
            // best-effort: which sources? infer via identifiers
            $hintIds = array_values($this->sources->mapCarrierCodeToSourceId());
            dispatch(new SyncShipmentJob($shipment->id, $hintIds));
        }

        // 3) build segmented timeline response
        $events = $shipment->events->sortBy('occurred_at'); // already eager loaded
        $segments = [
            'origin_procurement' => ['source' => $shipment->legs->firstWhere('type','origin_procurement')?->source, 'events' => $events->where('leg.type','origin_procurement')->values()],
            'linehaul'           => ['source' => $shipment->legs->firstWhere('type','linehaul')?->source,           'events' => $events->where('leg.type','linehaul')->values()],
            'destination_wh'     => ['source' => $shipment->legs->firstWhere('type','destination_wh')?->source,     'events' => $events->where('leg.type','destination_wh')->values()],
            'last_mile'          => ['source' => $shipment->legs->firstWhere('type','last_mile')?->source,          'events' => $events->where('leg.type','last_mile')->values()],
        ];

        $etag = sha1(($shipment->etag ?? '') . ($shipment->updated_at?->toJSON() ?? ''));
        if ($request->header('If-None-Match') === $etag) {
            return response()->noContent(304);
        }

        return response()->json([
            'shipment'=>$shipment,
            'segments'=>$segments,
            'etag'           => $etag,
            'stale'          => (bool)$stale,
            'last_synced_at' => optional($shipment->last_synced_at)->toDateTimeString(),
        ]);
    }
}
