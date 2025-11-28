<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = MedicalAppointment::with(['category', 'attachment'])
            ->where('user_id', $request->user()->id)
            ->latest('occurred_on')
            ->paginate(20);

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'provider_name' => ['required', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'attachment_id' => ['nullable', 'exists:attachments,id'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $appointment = MedicalAppointment::create([
            'user_id' => $request->user()->id,
            ...$data,
        ]);

        return response()->json($appointment->load(['category', 'attachment']), 201);
    }
}
