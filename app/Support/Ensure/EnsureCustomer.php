<?php

namespace App\Support\Ensure;

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;

class EnsureCustomer {

    public function userCanManageCustomer(?User $user, Customer $customer): bool {
        $user = $user ?? auth()->user();
        if($user->customers()->where('id', $customer->id)->exists()) {
            return true;
        }
        else{
            abort(401, "Action non autorisée");
        }
    }

    public function customerCanManageBrand(Customer $customer, Brand $brand): bool {

        if($brand->customer_id === $customer->id && !$brand->is_verified) {
            return true;
        }

        abort(401, "Action non autorisée");
    }

    public function customerCanManageIngredient(Customer $customer, Ingredient $ingredient): bool {
        if($ingredient->customer_id === $customer->id && !$ingredient->verified) {
            return true;
        }
        abort(401, "Action non autorisée");
    }
//
//    public function CustomerCanManageRecipe(Customer $customer, Recipe $recipe): bool {
//        if($recipe->creator === $customer && !$recipe->verified) {
//            return true;
//        }
//        abort(401, "Action non autorisée");
//    }




}
