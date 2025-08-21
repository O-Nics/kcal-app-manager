<?php

namespace App\Features\User\Resources;

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



        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'provider' => $this->provider,
            'plan' => $this->plan,
            'limitation_profile' => $this->limitation_profile,
            'accept_terms' => $this->accept_terms,
            'email_verified_at' => $this->email_verified_at ? Carbon::parse($this->email_verified_at)->toDateTimeString() : null,
            'created_at' => Carbon::parse($this->created_at)->toDateTimeString(),
            'updated_at' => Carbon::parse($this->updated_at)->toDateTimeString(),

        ];

    }
}
