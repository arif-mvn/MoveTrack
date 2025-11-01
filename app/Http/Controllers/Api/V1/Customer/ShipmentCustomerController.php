<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\Shipment\ShipmentExpandsEnum;
use App\Exceptions\ModelNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ShipmentIndexRequest;
use App\Http\Resources\V1\Shipment\ShipmentCollectionResource;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use App\Services\ShipmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ShipmentCustomerController extends Controller
{
    public function __construct(private readonly ShipmentService $shipmentService) {}

    public function index(ShipmentIndexRequest $request): JsonResponse|ShipmentCollectionResource
    {
        try {
            $data = $this->shipmentService->getPaginatedShipments($request->validated());

            return (new ShipmentCollectionResource($data));
        } catch (\Exception $exception) {

            return response()->json([
                'message' => 'Something went wrong while fetching shipments, Please try later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $identifier): JsonResponse|ShipmentResource
    {
        try {
            $shipment = $this->shipmentService->getOrPullByIdentifier(
                identifier: $identifier,
                expand: [
                    ShipmentExpandsEnum::LEGS_SOURCE,
                    ShipmentExpandsEnum::STATUS_SOURCE,
                    ShipmentExpandsEnum::EVENTS_SOURCE,
                    ShipmentExpandsEnum::EVENTS_LEG,
                ]
            );

            return (new ShipmentResource($shipment));
        } catch (ModelNotFoundException $exception) {

            return response()->json([
                'message' => $exception->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {

            return response()->json([
                'message' => 'Something went wrong while fetching shipment, Please try later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
    }
}
