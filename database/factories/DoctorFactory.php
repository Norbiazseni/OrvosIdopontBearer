<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Doctor;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'specialization' => $this->faker->word(),
            'room' => $this->faker->numberBetween(100, 500),
        ];
    }
}

?>