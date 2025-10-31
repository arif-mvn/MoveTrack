<?php

namespace App\Services;

use App\Enums\Identifier\IdentifierFiltersEnum;
use App\Enums\Identifier\IdentifierFieldsEnum;
use App\Models\Shipment;
use Illuminate\Support\Carbon;

class TrackingService
{
    public function __construct(
        private readonly IdentifierService $identifiers,
        private readonly ShipmentService   $shipments,
        private readonly SourceService     $sources,
    ) {}

    /** Resolve identifier → shipment and decide staleness */
    public function resolveShipmentByIdentifier(string $value, ?string $carrierCode, ?string $scope): ?Shipment
    {
        $filters = [ IdentifierFiltersEnum::VALUE => $value ];
        if ($carrierCode) $filters[IdentifierFiltersEnum::CARRIER_CODE] = $carrierCode;
        if ($scope)       $filters[IdentifierFiltersEnum::SCOPE]        = $scope;

        $idModel = $this->identifiers->findAll($filters)->first()
            ?? $this->identifiers->findAll([IdentifierFiltersEnum::VALUE => $value])->first();

        return $idModel
            ? $this->shipments->getById($idModel->{IdentifierFieldsEnum::SHIPMENT_ID})
            : null;
    }

    public function isStale(Shipment $shipment): bool
    {
        $cutoff = Carbon::now()->subHours(2);
        return !$shipment->last_synced_at || $shipment->last_synced_at->lt($cutoff);
    }
}
