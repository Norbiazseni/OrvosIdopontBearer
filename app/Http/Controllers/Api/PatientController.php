<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Patient::query();

        if ($user->role !== 'admin') {
            // User csak a saját recordját látja, feltételezzük user_id = patient_id
            $query->where('id', $user->id);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $patient = Patient::findOrFail($id);

        if ($user->role !== 'admin' && $patient->id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($patient);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:patients',
            'birth_date' => 'required|date'
        ]);

        $patient = Patient::create($data);
        return response()->json($patient, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $patient = Patient::findOrFail($id);

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:patients,email,'.$id,
            'birth_date' => 'sometimes|date'
        ]);

        $patient->update($data);
        return response()->json($patient);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $patient = Patient::findOrFail($id);
        $patient->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
