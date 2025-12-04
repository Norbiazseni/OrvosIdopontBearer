<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use PHPUnit\Framework\Attributes\Test;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_create_doctor()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $payload = [
            'name' => 'Dr. Kiss Péter',
            'specialization' => 'Cardiology',
            'room' => '101',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/doctors', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id','name','specialization','room','created_at','updated_at']);

        $this->assertDatabaseHas('doctors', ['name' => 'Dr. Kiss Péter']);
    }

    #[Test]
    public function normal_user_cannot_create_doctor()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $payload = [
            'name' => 'Dr. Teszt',
            'specialization' => 'Neurology',
            'room' => '102',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/doctors', $payload);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('doctors', ['name' => 'Dr. Teszt']);
    }

    #[Test]
    public function can_get_doctors_list()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        Doctor::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/doctors');

        $response->assertStatus(200)
                 ->assertJsonCount(5);
    }
}
