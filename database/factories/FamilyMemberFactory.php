<?php

namespace Database\Factories;

use App\Models\FamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FamilyMember> */
class FamilyMemberFactory extends Factory
{
    protected $model = FamilyMember::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->optional()->date(),
            'gender' => fake()->randomElement(['female', 'male', 'other']),
        ];
    }
}
