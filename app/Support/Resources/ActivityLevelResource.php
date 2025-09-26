<?php

namespace App\Support\Resources;


use App\Feature\Customer\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLevelResource extends JsonResource{



    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param User $request
     * @return array
     */
    public function toArray($request)
    {

        $data = [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'value' => $this->resource->value,
            'code' => $this->resource->code,
        ];



        return $data;

    }
}
