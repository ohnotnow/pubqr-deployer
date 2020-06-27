<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Deployment;
use Faker\Generator as Faker;

$factory->define(Deployment::class, function (Faker $faker) {
    return [
        'email' => $faker->email,
        'api_key' => $faker->text(20),
        'url' => $faker->url,
        'shop_name' => $faker->text(10),
        'uuid' => $faker->uuid,
    ];
});
