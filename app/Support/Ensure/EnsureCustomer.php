<?php

namespace App\Support\Ensure;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;

class EnsureCustomer {

    public function userCanManageCustomer(User $user, Customer $customer): bool {
        if($user->customers()->where('id', $customer->id)->exists()) {
            return true;
        }
        else{
            abort(401, "Action non autorisée");
        }
    }

//    public function CustomerCanManageIngredient(Customer $customer, Ingredient $ingredient): bool {
//        if($ingredient->creator === $customer && !$ingredient->verified) {
//            return true;
//        }
//        abort(401, "Action non autorisée");
//    }
//
//    public function CustomerCanManageRecipe(Customer $customer, Recipe $recipe): bool {
//        if($recipe->creator === $customer && !$recipe->verified) {
//            return true;
//        }
//        abort(401, "Action non autorisée");
//    }




}
