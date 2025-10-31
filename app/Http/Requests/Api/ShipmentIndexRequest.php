<?php

namespace App\Http\Requests\Api;

use App\Enums\ResponseTypEnum;
use App\Enums\Shipment\ShipmentExpandsEnum;
use App\Enums\Shipment\ShipmentFieldsEnum;
use App\Enums\SortOrderEnum;
use Illuminate\Validation\Rule;

class ShipmentIndexRequest extends BaseIndexRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "response_type" => ["required", Rule::in(ResponseTypEnum::values())],
            "id"     => ["nullable", "integer", "min:1"],
            "code"   => ["nullable", "max:2", "min:2"],
            "region_id"    => ["nullable", "integer", "min:1"],
            "created_at"   => ["nullable", "array", "min:2", "max:2"],
            "created_at.*" => ["nullable", "date_format:Y-m-d H:i:s"],
            "fields"       => ["nullable", "array"],
            "fields.*"     => ["nullable", Rule::in(ShipmentFieldsEnum::values())],
            "sort_by"      => ["nullable", Rule::in(ShipmentFieldsEnum::values())],
            "sort_order"   => ["nullable", Rule::in(SortOrderEnum::values())],
            "page"         => ["nullable", "integer", "min:1"],
            "per_page"     => ["nullable", "integer", "min:1"],
            "expand"       => ["nullable", "array"],
            "expand.*"     => ["nullable", "string", Rule::in(ShipmentExpandsEnum::values())],
        ];
    }
}
