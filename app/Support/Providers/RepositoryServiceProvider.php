<?php


namespace App\Support\Providers;




use App\Support\Repositories\Brand\BrandRepository;
use App\Support\Repositories\Brand\BrandRepositoryEloquent;
use App\Support\Repositories\Customer\CustomerRepository;
use App\Support\Repositories\Customer\CustomerRepositoryEloquent;
use App\Support\Repositories\Document\DocumentRepository;
use App\Support\Repositories\Document\DocumentRepositoryEloquent;
use App\Support\Repositories\Ingredient\IngredientRepository;
use App\Support\Repositories\Ingredient\IngredientRepositoryEloquent;
use App\Support\Repositories\PasswordReset\PasswordResetRepository;
use App\Support\Repositories\PasswordReset\PasswordResetRepositoryEloquent;
use App\Support\Repositories\Session\SessionRepository;
use App\Support\Repositories\Session\SessionRepositoryEloquent;
use App\Support\Repositories\User\UserRepository;
use App\Support\Repositories\User\UserRepositoryEloquent;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(UserRepository::class, UserRepositoryEloquent::class);
        $this->app->bind(CustomerRepository::class, CustomerRepositoryEloquent::class);
        $this->app->bind(DocumentRepository::class, DocumentRepositoryEloquent::class);
        $this->app->bind(SessionRepository::class, SessionRepositoryEloquent::class);
        $this->app->bind(PasswordResetRepository::class, PasswordResetRepositoryEloquent::class);
        $this->app->bind(IngredientRepository::class, IngredientRepositoryEloquent::class);
        $this->app->bind(BrandRepository::class, BrandRepositoryEloquent::class);

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
