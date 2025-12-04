# OrvosIdopontBearer REST API — Dokumentáció

Rövid, tömör API dokumentáció a projekt végpontjaihoz és használatához.

---

## Általános

- Base URL: `http://localhost/orvosIdopontBearer/public/api`
- Adatbázis neve: orvos_idopont_bearer
- Auth: Bearer token (Laravel Sanctum). A token a `/login` végponttal szerezhető be.
- Hibák:
  - 400 Bad Request — rossz kérés
  - 401 Unauthorized — hiányzó/érvénytelen token
  - 403 Forbidden — nincs jogosultság
  - 404 Not Found — nem található erőforrás
  - 500+ — szerverhiba

---

## Nem védett végpontok

- GET `/hello` — teszt: visszaad egy JSON üzenetet
- POST `/register` — felhasználó regisztráció
- POST `/login` — bejelentkezés, visszaadja a Bearer tokent

Példa /login kérés:
Content-Type: application/json
```
Body:
{
  "email": "user@example.com",
  "password": "password"
}
Példa válasz:
{
  "token": "plain-text-token"
}
```
---

## Védett végpontok (auth:sanctum)

Fejléc:
Authorization: Bearer {token}
Accept: application/json

Általános jogosultságok:
- admin: minden erőforrást lát/kezel
- user: csak a saját rekordjaihoz fér hozzá (patients/appointments), nem hozhat létre orvost/egyéb admin műveleteket

---

## Patients (páciensek)

GET `/patients` — lista
- admin: minden pacient
- user: csak saját record (feltételezve user.id = patient.id)

GET `/patients/{id}` — részletek (403, ha nincs jogosultság)

POST `/patients` — létrehozás (csak admin)
Body (példa):
```
{
  "name": "Név",
  "email": "email@example.com",
  "birth_date": "YYYY-MM-DD"
}
```
Válasz: 201 Created + patient objektum

PUT `/patients/{id}` — teljes frissítés (csak admin)

DELETE `/patients/{id}` — törlés (csak admin)

---

## Doctors (orvosok)

GET `/doctors` — lista (minden user láthatja)
GET `/doctors/{id}` — részletek

POST `/doctors` — létrehozás (csak admin)
Body:
```
{
  "name": "Dr. Név",
  "specialization": "szakterület",
  "room": "101"
}
```

PUT `/doctors/{id}` — módosítás (csak admin)

DELETE `/doctors/{id}` — törlés (csak admin)

---

## Appointments (időpontok)

GET `/appointments` — lista
- admin: minden időpont
- user: csak sajátjai (appointment.patient_id === user.id)
Lehetőség szűrésre query paramokkal:
- ?doctor_id=#
- ?status=scheduled|completed|cancelled

GET `/appointments/{id}` — részletek (403, ha nem jogosult)

POST `/appointments` — létrehozás (jelen implementáció: csak admin hozhat létre)
Body:
```
{
  "patient_id": 1,
  "doctor_id": 2,
  "appointment_time": "2025-12-20 10:00:00",
  "status": "scheduled"
}
```
Válasz: 201 Created + appointment objektum

PUT `/appointments/{id}` — teljes frissítés (admin vagy a saját patient-je)


DELETE `/appointments/{id}` — törlés (admin vagy a saját patient-je)

---

## Példa hibaválasz (érvénytelen token)
```
Response: 401 Unauthorized
{
  "message": "Invalid token"
}
```
---

## Tesztelés

- Factories és seederek használata: database/seeders/DatabaseSeeder.php és factories mappában.
### Factory-k:

-AppointmentFactory.php

```
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition()
    {
        return [
            'patient_id' => Patient::factory(),   // új Patient rekordot ad hozzá
            'doctor_id' => Doctor::factory(),     // új Doctor rekordot ad hozzá
            'appointment_time' => $this->faker->dateTimeBetween('+1 days', '+1 month'),
            'status' => $this->faker->randomElement(['scheduled','completed','cancelled']),
        ];
    }

}
```
Az AppointmentFactory automatikusan létrehoz időpontokat a teszteléshez vagy seedeléshez. Minden új rekordhoz új pácienst és orvost generál, valamint véletlenszerű időpontot és státuszt rendel (scheduled, completed, cancelled).

-DoctorFactory.php

```
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
```
Ez a DoctorFactory automatikusan létrehoz orvosokat teszteléshez vagy seedeléshez, véletlenszerű nevet, szakterületet és szobaszámot rendel minden új rekordhoz.

-PatientFactory.php

```
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Patient;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'birth_date' => $this->faker->date(),
        ];
    }
}

?>
```
Ez a PatientFactory automatikusan létrehoz pácienseket teszteléshez vagy seedeléshez, véletlenszerű nevet, egyedi e-mail címet és születési dátumot generálva minden új rekordhoz.

-UserFactory.php

```
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // jelszó minden usernek: password
            'remember_token' => Str::random(10),
            'role' => 'user', // alap user, admin a seederben külön
        ];
    }

    // Admin állapot
    public function admin()
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}

```
Ez a UserFactory automatikusan létrehoz felhasználókat teszteléshez vagy seedeléshez. Minden usernek ad egy nevet, egyedi e-mail címet, alap jelszót (password), valamint egy role mezőt (user), és tartalmaz egy admin helper-t is, amivel könnyen készíthetünk admin jogosultságú felhasználót a seederben.

- Futtatás helyben: php artisan migrate:fresh --seed majd php artisan test
- Tesztek API hívásokat imitálnak: actingAs($user) vagy tokennel withHeaders(['Authorization' => 'Bearer '.$token])


