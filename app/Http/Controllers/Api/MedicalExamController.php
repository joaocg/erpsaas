<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalExamController extends Controller
{
    public function index(Request $request)
    {
        $exams = MedicalExam::with(['category', 'attachment'])
            ->where('user_id', $request->user()->id)
            ->latest('occurred_on')
            ->paginate(20);

        return response()->json($exams);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'exam_type' => ['required', 'string', 'max:255'],
            'lab_name' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'attachment_id' => ['nullable', 'exists:attachments,id'],
            'notes' => ['nullable', 'string'],
            'results_json' => ['nullable', 'array'],
        ])->validate();

        $exam = MedicalExam::create([
            'user_id' => $request->user()->id,
            ...$data,
        ]);

        return response()->json($exam->load(['category', 'attachment']), 201);
    }
}
