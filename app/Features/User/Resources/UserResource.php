<?php

namespace App\Features\User\Resources;

use App\Features\Customer\Resources\CustomerResource;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource{

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {



        $data = [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'provider_id' => $this->resource->provider_id,
            'provider' => $this->resource->provider,
            'plan' => $this->resource->plan,
            'limitation_profile' => (bool) $this->resource->limitation_profile,
            'accept_terms' => (bool) $this->resource->accept_terms,
            'email_verified_at' => $this->resource->email_verified_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at,
            'customers' => CustomerResource::collection($this->resource->customers)
        ];

        return $data;
    }
}
