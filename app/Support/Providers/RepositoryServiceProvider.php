<?php


namespace App\Support\Providers;




use App\Support\Repositories\Customer\CustomerRepository;
use App\Support\Repositories\Customer\CustomerRepositoryEloquent;
use App\Support\Repositories\Document\DocumentRepository;
use App\Support\Repositories\Document\DocumentRepositoryEloquent;
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
