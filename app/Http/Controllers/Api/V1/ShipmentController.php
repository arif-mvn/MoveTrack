<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ShipmentIndexRequest;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use App\Http\Resources\V1\TimelineResource;
use App\Models\Shipment;
use App\Services\EventService;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShipmentService $shipments,
        private readonly EventService    $events
    ) {}

    public function index(ShipmentIndexRequest $request)
    {
        $data = $this->shipments->getPaginatedShipments($request->validated());
        // ETag for collections (weak)
        return ShipmentResource::collection($data)->additional([
            'meta' => ['etag' => sha1(json_encode($data->items()))]
        ]);
    }

    public function show($id, Request $request)
    {
        /** @var Shipment $shipment */
        $shipment = $this->shipments->getById((int)$id);

        $etag = sha1($shipment->etag ?? ($shipment->updated_at?->toJSON() ?? $shipment->id));
        if ($request->header('If-None-Match') === $etag) {
            return response()->noContent(304);
        }

        $expand = (array) $request->query('expand', []);
        $shipment->loadMissing(array_intersect($expand, ['statusSource','legs.source']));

        return (new ShipmentResource($shipment))
            ->additional(['meta' => ['etag' => $etag]]);
    }

    /** canonical, segmented timeline by leg/source */
    public function timeline($id, Request $request)
    {
        $shipment = $this->shipments->getById((int)$id);
        $shipment->loadMissing(['legs.source']);

        $events = $shipment->events()->with('source')
            ->orderBy('occurred_at','asc')->get();

        $segments = [
            'origin_procurement' => [
                'source' => $shipment->legs->firstWhere('type','origin_procurement')?->source,
                'events' => $events->filter(fn($e)=>$e->leg?->type==='origin_procurement')]
            ,
            'linehaul'           => [
                'source' => $shipment->legs->firstWhere('type','linehaul')?->source ?? null,
                'events' => $events->filter(fn($e)=>$e->leg?->type==='linehaul')
            ],
            'destination_wh'     => [
                'source' => $shipment->legs->firstWhere('type','destination_wh')?->source ?? null,
                'events' => $events->filter(fn($e)=>$e->leg?->type==='destination_wh')
            ],
            'last_mile'          => [
                'source' => $shipment->legs->firstWhere('type','last_mile')?->source,
                'events' => $events->filter(fn($e)=>$e->leg?->type==='last_mile')
            ],
        ];
return response()->json(['shipment' => $shipment, 'segments' => $segments]);
        return new TimelineResource(['shipment' => $shipment, 'segments' => $segments]);
    }
}
