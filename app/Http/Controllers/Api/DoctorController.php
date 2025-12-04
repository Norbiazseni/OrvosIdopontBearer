<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;

class DoctorController extends Controller
{
    // Listázás
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Doctor::query();

        // USER-ek ne módosíthassák, de láthatják az összes orvost
        // Ha akarjuk, csak admin láthat mindent, usernek csak listázás
        return response()->json($query->get());
    }

    // Egy orvos lekérése
    public function show($id)
    {
        $doctor = Doctor::findOrFail($id);
        return response()->json($doctor);
    }

    // Új orvos létrehozása (csak admin)
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'specialization' => 'required|string',
            'room' => 'required|string',
        ]);

        $doctor = Doctor::create($data);
        return response()->json($doctor, 201);
    }

    // Orvos adatainak módosítása (csak admin)
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $doctor = Doctor::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string',
            'specialization' => 'sometimes|string',
            'room' => 'sometimes|string',
        ]);

        $doctor->update($data);
        return response()->json($doctor);
    }

    // Orvos törlése (csak admin)
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
