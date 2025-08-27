<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => $this->faker->uuid(),
            'platform' => $this->faker->randomElement(['ios', 'android', 'web']),
            'device_ip' => $this->faker->ipv4(),
            'access_token_id' => $this->faker->randomNumber(),
            'refresh_token_id' => $this->faker->randomNumber(),
            'model' => $this->faker->word(),
            'customer_id' => Str::uuid(),
        ];
    }
}
