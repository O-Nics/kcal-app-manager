<?php

namespace App\Features\User\Resources;

use App\Http\Resources\User\CustomerResource;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource{

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
    public function toArray($request)
    {

        $customer = null;

        if($this->resource->customer_id != null){
            $customer = Customer::find($this->resource->customer_id);
        }

        return [
            'id' => $this->resource->id,
            'device_id' => $this->resource->device_id,
            'platform' => $this->resource->platform,
            'device_ip' => $this->resource->device_ip,
            'model' => $this->resource->model,
//            'customer' => new CustomerResource($customer,null,null,true),
            'created_at' => $this->resource->created_at != null ? Carbon::parse($this->resource->created_at)->toDateString() : null,
            'updated_at' => $this->resource->updated_at != null ? Carbon::parse($this->resource->updated_at)->toDateTimeString() : null
        ];

    }
}
