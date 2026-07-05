<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => CompanyType::Customer,
            'status' => CompanyStatus::Active,
            'website' => fake()->optional()->url(),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
        ];
    }

    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CompanyType::Vendor,
        ]);
    }

    public function partner(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CompanyType::Partner,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanyStatus::Inactive,
        ]);
    }
}
