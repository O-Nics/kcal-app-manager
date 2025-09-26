<?php

namespace App\Features\Customer\Resources;


use App\Models\Customer;
use App\Support\Resources\ActivityLevelResource;
use App\Support\Resources\GoalResource;
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
            'user_id' => $this->resource->user_id,
            //DocumentRessources
            'goal' => new GoalResource($this->goal),
            'activity_level_id' => new ActivityLevelResource($this->activityLevel),
            'gender' => $this->resource->gender,
            'username' => $this->resource->username,
            'height' => $this->resource->height,
            'type_water' => $this->resource->type_water,
            'height_unit' => $this->resource->height_unit,
            'weight_unit' => $this->resource->weight_unit,
            'obj_kcals' => $this->resource->obj_kcals,
            'train_kcal' => $this->resource->train_kcal,
            'rest_kcal' => $this->resource->rest_kcal,
            'obj_steps' => $this->resource->obj_steps,
            'time_to_achieve' => $this->resource->time_to_achieve,
            'max_weight_hebdo_change' => $this->resource->max_weight_hebdo_change,
            'obj_weight_hebdo_change' => $this->resource->obj_weight_hebdo_change,
            'start_weight' => $this->resource->start_weight,
            'wanted_weight' => $this->resource->wanted_weight,
            'obj_water' => $this->resource->obj_water,
            'volume_water_by_each' => $this->resource->volume_water_by_each,
            'obj_proteins' => $this->resource->obj_proteins,
            'obj_fat' => $this->resource->obj_fat,
            'obj_carbs' => $this->resource->obj_carbs,
            'is_owner' => (bool) $this->resource->is_owner,
            'profile_locked' => (bool) $this->resource->profile_locked,
            'accept_marketing' => (bool) $this->resource->accept_marketing,
            'birth_date' => $this->resource->birth_date,
            'form_passed_at' => $this->resource->form_passed_at,
            'training_days_of_week' => $this->resource->training_days_of_week,
            'last_login_at' => $this->resource->last_login_at,
            'last_paywall_at' => $this->resource->last_paywall_at,
            'rated_app_at' => $this->resource->rated_app_at,
            'picture_from_provider' => $this->resource->picture_from_provider,
            'profile_image' => $this->whenLoaded('profileImage'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at,


        ];



        return $data;

    }
}
