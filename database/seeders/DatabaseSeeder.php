<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ USERS
        User::factory()->count(3)->admin()->create(); // 3 admin
        User::factory()->count(3)->create();         // 3 normál user

        // 2️⃣ PATIENTS
        $patients = Patient::factory()->count(10)->create();

        // 3️⃣ DOCTORS
        $doctors = Doctor::factory()->count(5)->create();

        // 4️⃣ APPOINTMENTS
        // Már létező patient/doctor rekordokból választ
        Appointment::factory()->count(20)->create([
            'patient_id' => function () use ($patients) {
                return $patients->random()->id;
            },
            'doctor_id' => function () use ($doctors) {
                return $doctors->random()->id;
            }
        ]);
    }
}
