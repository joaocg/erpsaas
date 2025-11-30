<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialRecord;
use App\Services\FinancialRecordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialRecordController extends Controller
{
    public function __construct(private FinancialRecordService $financialRecordService)
    {
    }

    public function index(Request $request)
    {
        $records = FinancialRecord::with(['category', 'attachment', 'ledgers', 'transaction'])
            ->where('user_id', $request->user()->id)
            ->latest('occurred_on')
            ->paginate(20);

        return response()->json($records);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'type' => ['required', 'in:expense,income'],
            'amount' => ['required', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'occurred_on' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'attachment_id' => ['nullable', 'integer', 'exists:attachments,id'],
            'description' => ['nullable', 'string'],
        ])->validate();

        $record = $this->financialRecordService->createRecord($request->user(), $data);

        return response()->json($record, 201);
    }
}
