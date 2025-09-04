<?php

namespace App\Feature\Customer\Resources;


use App\Models\Customer;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource{

    private bool $light;

    public function __construct($resource, bool $light = false)
    {
        parent::__construct($resource);
        $this->light = $light;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Customer $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->resource->id,
        ];



        return $data;

    }
}
