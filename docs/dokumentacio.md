# OrvosIdopontBearer REST API — Dokumentáció

OrvosIdopontBearer — egy Laravel alapú REST API alkalmazás, amely orvosi páciensek, orvosok és időpontok kezelésére szolgál. 

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

## Adatmodell

### User (Felhasználó)
- `id`: Elsődleges kulcs  
- `name`: Felhasználó teljes neve  
- `email`: E-mail cím (egyedi)  
- `email_verified_at`: E-mail ellenőrzés időbélyege *(nullable)*  
- `password`: Hash-elt jelszó  
- `remember_token`: Session / remember token *(nullable)*  
- `created_at`, `updated_at`: Időbélyegek

---

### Patient (Páciens)
- `id`: Elsődleges kulcs  
- `name`: Páciens neve
- `email`: Páciens email címe
- `birth_date`: Születési dátum *(nullable)*  
- `created_at`, `updated_at`: Időbélyegek  

---

### Doctor (Orvos)
- `id`: Elsődleges kulcs   
- `name`: Orvos neve  
- `specialization`: Szakvizsga / specializáció *(nullable)*
- `room`: Szoba megnevezése
- `created_at`, `updated_at`: Időbélyegek  

---

### Appointment (Időpont)
- `id`: Elsődleges kulcs  
- `patient_id`: Foglaláshoz tartozó páciens *(FK)*  
- `doctor_id`: Kapcsolódó orvos *(FK)*  
- `status`: Státusz (pl. `pending`, `confirmed`, `completed`, `cancelled`)  
- `created_at`, `updated_at`: Időbélyegek  


### Adatbázis struktúra

+-------------------------+      +----------------------+         +----------------------+        +-----------------------+
| personal_access_tokens |       |        users         |         |       patients       |        |        doctors        |
+-------------------------+    _1| id (PK)              |         | id (PK)              |        | id (PK)               |
| id (PK)                 | K_/  | name                 |         | name                 |        | name                  |
| tokenable_id (FK)       |      | email (unique)       |         | email (nullable)     |        | specialization        |
| tokenable_type          |      | password             |         | phone (nullable)     |        | phone (nullable)      |
| name                    |      | role ('admin/user')  |         | created_at           |        | created_at            |
| token (unique)          |      | created_at           |         | updated_at           |        | updated_at            |
| abilities               |      | updated_at           |         +----------------------+        +-----------------------+
| last_used_at            |      +----------------------+
| created_at              |
+-------------------------+
                                                                   1
                                                   +-------------------------------------+
                                                   |              appointments            |
                                                   +-------------------------------------+
                                                   | id (PK)                             |
                                                   | patient_id (FK → patients.id)       |
                                                   | doctor_id (FK → doctors.id)         |
                                                   | appointment_time                    |
                                                   | status ('pending','approved',...)   |
                                                   | created_at                          |
                                                   | updated_at                          |
                                                   +-------------------------------------+
                                                     ^                               ^
                                                     |                               |
                                                     |0..N                           |0..N
                                                     |                               |
                                                   patients                        doctors
  


Minden modellnél soft delete alkalmazva, csak kitöröltnek látszik az adat, valójában nem az.

Példa:


```
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;
}

```


## Nem védett végpontok

- GET `/hello` — teszt: visszaad egy JSON üzenetet
- POST `/register` — felhasználó regisztráció
- POST `/login` — bejelentkezés, visszaadja a Bearer tokent

<img width="564" height="281" alt="image" src="https://github.com/user-attachments/assets/6fc19004-4f96-4831-bf96-7c4020a6fca1" />


Példa /login kérés:

Content-Type: application/json
```
Body:
{
  "email": "liliane47@example.com",
  "password": "password"
}
Példa válasz:
{
    "token": "3|CxgDpQXEol85wrdwlgoVJbhZ2mJGEVENZd7c48C2a54f3084"
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

<img width="656" height="467" alt="image" src="https://github.com/user-attachments/assets/7f388b5a-17d0-4b65-a371-acd8df1abb71" />

---

## Patients (páciensek)

<img width="271" height="142" alt="image" src="https://github.com/user-attachments/assets/32f1ea7a-bb1c-430d-8880-ab186eded42a" />


GET `/patients` — lista
- admin: mindenkit lát
- user: csak saját record (feltételezve user.id = patient.id)

GET `/patients/{id}` — részletek a páciensről (403, ha nincs jogosultság)

POST `/patients` — létrehozás (csak admin tud létrehozni)

Body (példa):
```
{
  "name": "Norbert Kovács",
  "email": "norbert@example.com",
  "birth_date": "2005-01-01"
}
```
Válasz: 201 Created + patient objektum
```
{
    "name": "Norbert Kovács",
    "email": "norbert@example.com",
    "birth_date": "2005-01-01",
    "updated_at": "2025-12-04T09:50:41.000000Z",
    "created_at": "2025-12-04T09:50:41.000000Z",
    "id": 11
}
```

PUT `/patients/{id}` — teljes frissítés (csak admin)
```
{
  "name": "Norbert Kovács Updated",
  "email": "norbert_new@example.com"
}
```

DELETE `/patients/{id}` — törlés (csak admin)

---

## Doctors (orvosok)

<img width="268" height="135" alt="image" src="https://github.com/user-attachments/assets/f0380069-118a-46c9-acc1-1c99378feb60" />



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

<img width="288" height="141" alt="image" src="https://github.com/user-attachments/assets/5ee0e6a6-ca86-40c2-af9e-50e624b5d047" />



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

### Hitelesítés és Jogosultságok 

### Token-alapú Autentifikáció
- Minden hitelesített végpont `Authorization: Bearer {token}` header-t igényel
- A token bejelentkezéskor jön vissza
- A tokeneket a `personal_access_tokens` táblában tároljuk

### Szerepek

1. **Normál felhasználó** (`role = user`)
   - Saját profil megtekintése és módosítása
   - Erőforrások megtekintése
   - Saját foglalások létrehozása, olvasása és törlése (CRUD részben)
   - Foglalások státusza nem módosítható

2. **Adminisztrátor** (`role = admin`)
   - Összes felhasználó kezelése
   - Erőforrások teljes kezelése (pl. páciensek, orvosok, időpontok)
   - Összes foglalás megtekintése és kezelése
   - Foglalás státuszának módosítása


---

## Factory, Seedelés és Tesztelés

- Factories és seederek használata: database/seeders/DatabaseSeeder.php és factories mappában.
- Futtatás helyben: php artisan migrate:fresh --seed majd php artisan test
- Tesztek API hívásokat imitálnak: actingAs($user) vagy tokennel withHeaders(['Authorization' => 'Bearer '.$token])

### Factory-k:

**-AppointmentFactory.php**

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

**-DoctorFactory.php**

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
A DoctorFactory automatikusan létrehoz orvosokat teszteléshez vagy seedeléshez, véletlenszerű nevet, szakterületet és szobaszámot rendel minden új rekordhoz.

**-PatientFactory.php**

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
A PatientFactory automatikusan létrehoz pácienseket teszteléshez vagy seedeléshez, véletlenszerű nevet, egyedi e-mail címet és születési dátumot generálva minden új rekordhoz.

**-UserFactory.php**

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
A UserFactory automatikusan létrehoz felhasználókat teszteléshez vagy seedeléshez. Minden usernek ad egy nevet, egyedi e-mail címet, alap jelszót (password), valamint egy role mezőt (user), és tartalmaz egy admin helper-t is, amivel könnyen készíthetünk admin jogosultságú felhasználót a seederben.

## Seedelés:

Ez a **DatabaseSeeder** felelős az adatbázis feltöltéséért tesztelés vagy fejlesztés során. Létrehoz:

1. Felhasználókat – 3 admin és 3 normál user.
2. Pácienseket – 10 darab véletlenszerű rekord.
3. Orvosokat – 5 darab véletlenszerű rekord.
4. Időpontokat – 20 darab foglalás, ahol a patient_id és doctor_id már létező páciensekből és orvosokból kerül kiválasztásra, így valódi kapcsolatok jönnek létre az adatok között.


```
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
```

**Seedelés futtatása**: `php artisan db:seed`

## Tesztelés

<img width="647" height="132" alt="image" src="https://github.com/user-attachments/assets/28ab8f3b-61fd-4d49-ac75-87715e4e9cf8" />


-AppointmentTest.php

1. admin_can_create_appointment() - Egy admin felhasználó sikeresen létre tud-e hozni egy új időpontot.
2. normal_user_cannot_create_appointment() - Egy sima (nem admin) felhasználó nem hozhat létre időpontot.
3. can_get_appointments_list() - Egy admin le tudja-e kérni az összes időpontot az API-n keresztül.
4. admin_can_update_appointment() - Az admin módosíthatja egy létező időpont adatait.
5. admin_can_delete_appointment() - Az admin jogosult-e időpontot törölni.

-DoctorTest.php

1. admin_can_create_doctor() - Egy admin felhasználó képes-e új orvost létrehozni az API-n keresztül.
2. normal_user_cannot_create_doctor() - Egy normál (nem admin) felhasználó ne tudjon új orvost létrehozni.
3. can_get_doctors_list() - Egy admin le tudja-e kérni az összes orvost az API-n keresztül.

-PatientTest.php

1. admin_can_create_patient() - Egy admin felhasználó képes-e új pácienst létrehozni az API-n keresztül.
2. normal_user_cannot_create_patient() - Egy normál (nem admin) felhasználó ne hozhasson létre új pácienst.
3. can_get_patients_list() - Egy admin le tudja-e kérni az összes pácienst az API-ból.

11 (+1 próbateszt) tesztet tartalmaz, melyek közül mind sikerrel lefut.

**Tesztek futtatása**: `php artisan test`






