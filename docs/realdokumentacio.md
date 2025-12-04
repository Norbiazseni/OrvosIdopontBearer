# OrvosIdopontBearer REST API — Dokumentáció

Rövid, tömör API dokumentáció a projekt végpontjaihoz és használatához.

---

## Általános

- Base URL (példa helyben): `http://localhost/orvosIdopontBearer/public/api`
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
Body:
{
  "email": "user@example.com",
  "password": "password"
}
Példa válasz:
{
  "token": "plain-text-token"
}

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
{
  "name": "Név",
  "email": "email@example.com",
  "birth_date": "YYYY-MM-DD"
}
Válasz: 201 Created + patient objektum

PUT `/patients/{id}` — teljes frissítés (csak admin)
DELETE `/patients/{id}` — törlés (csak admin)

---

## Doctors (orvosok)

GET `/doctors` — lista (minden user láthatja)
GET `/doctors/{id}` — részletek

POST `/doctors` — létrehozás (csak admin)
Body:
{
  "name": "Dr. Név",
  "specialization": "szakterület",
  "room": "101"
}

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
{
  "patient_id": 1,
  "doctor_id": 2,
  "appointment_time": "2025-12-20 10:00:00",
  "status": "scheduled"
}
Válasz: 201 Created + appointment objektum

PUT `/appointments/{id}` — teljes frissítés (admin vagy a saját patient-je)
PATCH is supported via same endpoint if implemented
DELETE `/appointments/{id}` — törlés (admin vagy a saját patient-je)

---

## Példa hibaválasz (érvénytelen token)
Response: 401 Unauthorized
{
  "message": "Invalid token"
}

---

## Tesztelés (gyors tippek)

- Factories és seederek használata: database/seeders/DatabaseSeeder.php és factories mappában.
- Futtatás helyben: php artisan migrate:fresh --seed majd php artisan test
- Tesztek API hívásokat imitálnak: actingAs($user) vagy tokennel withHeaders(['Authorization' => 'Bearer '.$token])

---

## Megjegyzések / Tippek fejlesztéshez

- User modell tartalmazza a role mezőt (alap: 'user', admin: 'admin').
- AuthController jelenleg egyszerű token visszaadást használ: createToken(...)->plainTextToken.
- Figyelj a migrations sorendjére: patients és doctors táblák legyenek létrehozva az appointments előtt (foreign key miatt).
- Ha user és patient külön entitás, fontold meg a relációk és jogosultságok pontosítását (pl. user->patient relation).

---