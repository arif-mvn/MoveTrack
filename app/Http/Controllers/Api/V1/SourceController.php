<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SourceIndexRequest;
use App\Http\Resources\V1\Source\SourceResource;
use App\Services\SourceService;

class SourceController extends Controller
{
    public function __construct(private readonly SourceService $service) {}

    public function index(SourceIndexRequest $request)
    {
        $data = $this->service->getPaginatedSources($request->validated());
        return SourceResource::collection($data);
    }
}
