<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_create_appointment()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        $payload = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/appointments', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id','patient_id','doctor_id','appointment_time','status','created_at','updated_at'
                 ]);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'scheduled',
        ]);
    }

    #[Test]
    public function normal_user_cannot_create_appointment()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        $payload = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/appointments', $payload);

        $response->assertStatus(403); // normál user nem hozhat létre időpontot
        $this->assertDatabaseMissing('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    #[Test]
    public function can_get_appointments_list()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $appointments = Appointment::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/appointments');

        $response->assertStatus(200)
                 ->assertJsonCount(3) // három időpontot vártunk
                 ->assertJsonStructure([
                     '*' => ['id','patient_id','doctor_id','appointment_time','status','created_at','updated_at']
                 ]);
    }

    #[Test]
    public function admin_can_update_appointment()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $appointment = Appointment::factory()->create([
            'status' => 'scheduled'
        ]);

        $payload = ['status' => 'completed'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->putJson("/api/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'completed']);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed'
        ]);
    }

    #[Test]
    public function admin_can_delete_appointment()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $appointment = Appointment::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }
}
