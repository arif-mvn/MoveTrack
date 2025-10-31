<?php

namespace App\Http\Requests\Api;

use App\Enums\ResponseTypEnum;
use Illuminate\Foundation\Http\FormRequest;

class BaseIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    protected function prepareForValidation()
    {
        $values = [];

        if ($this->has("fields")) {
            $values["fields"][] = "id";
            if ($this->filled("fields")) {
                $values["fields"] = array_merge(
                    $values["fields"],
                    explode(",", $this->get("fields"))
                );
            }
            $values["fields"] = array_unique($values["fields"]);
        }

        if ($this->has("expand") && $this->filled("expand")) {
            $values["expand"] = explode(",", $this->get("expand"));
        }

        if ($this->has("created_at") && $this->filled("created_at")) {
            $createdAt = $this->get("created_at");

            if (is_array($createdAt) && count($createdAt) === 1) {
                $values["created_at"] = [$createdAt[0], $createdAt[0]];
            } elseif(is_array($createdAt) && count($createdAt) >= 3) {
                $createdAt = array_slice($createdAt, -2);
                $values["created_at"] = $createdAt;
            }
        }

        $this->merge($values);

        if (!$this->has("response_type")) {
            $this->merge(
                [
                    "response_type" => ResponseTypEnum::REGULAR
                ]
            );
        }
    }
}
