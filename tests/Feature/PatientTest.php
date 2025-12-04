<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use PHPUnit\Framework\Attributes\Test;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_create_patient()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        $payload = [
            'name' => 'Norbert Kovács',
            'email' => 'norbert@example.com',
            'birth_date' => '2005-01-01',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/patients', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id','name','email','birth_date','created_at','updated_at']);

        $this->assertDatabaseHas('patients', ['email' => 'norbert@example.com']);
    }

    #[Test]
    public function normal_user_cannot_create_patient()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $payload = [
            'name' => 'Teszt Paci',
            'email' => 'tesztpaci@example.com',
            'birth_date' => '2010-05-05',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/patients', $payload);

        $response->assertStatus(403); // nincs jogosultsága
        $this->assertDatabaseMissing('patients', ['email' => 'tesztpaci@example.com']);
    }

    #[Test]
    public function can_get_patients_list()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;

        Patient::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/patients');

        $response->assertStatus(200)
                 ->assertJsonCount(5); // 5 patient
    }
}

?>