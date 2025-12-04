<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Appointment::query();

        if ($user->role !== 'admin') {
            $query->where('patient_id', $user->id);
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->role !== 'admin' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($appointment);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_time' => 'required|date',
            'status' => 'required|string',
        ]);

        // Csak admin hozhat létre időpontot
        if(auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $appointment = Appointment::create($request->all());

        return response()->json($appointment, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->role !== 'admin' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'doctor_id' => 'sometimes|integer',
            'appointment_time' => 'sometimes|date',
            'status' => 'sometimes|string'
        ]);

        $appointment->update($data);
        return response()->json($appointment);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->role !== 'admin' && $appointment->patient_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $appointment->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
