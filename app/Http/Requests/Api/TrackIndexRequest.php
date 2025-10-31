<?php

namespace App\Http\Requests\Api;

use App\Enums\SortOrderEnum;
use App\Enums\Source\SourceExpandsEnum;
use App\Enums\Source\SourceFieldsEnum;
use Illuminate\Validation\Rule;

class TrackIndexRequest extends BaseIndexRequest
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
            'carrier_code' => ['nullable','string','max:50'],
            'scope'        => ['nullable','string','max:50'], // e.g., internal|courier|last_mile
            'force_sync'   => ['nullable','boolean'],
            "created_at"   => ["nullable", "array", "min:2", "max:2"],
            "created_at.*" => ["nullable", "date_format:Y-m-d H:i:s"],
            "fields"       => ["nullable", "array"],
            "fields.*"     => ["nullable", Rule::in(SourceFieldsEnum::values())],
            "sort_by"      => ["nullable", Rule::in(SourceFieldsEnum::values())],
            "sort_order"   => ["nullable", Rule::in(SortOrderEnum::values())],
            "page"         => ["nullable", "integer", "min:1"],
            "per_page"     => ["nullable", "integer", "min:1"],
            "expand"       => ["nullable", "array"],
            "expand.*"     => ["nullable", "string", Rule::in(SourceExpandsEnum::values())],
        ];
    }
}
