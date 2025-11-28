<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGeminiAttachment;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttachmentController extends Controller
{
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'file' => ['required', 'file'],
        ])->validate();

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('attachments');

        $attachment = Attachment::create([
            'user_id' => $request->user()->id,
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'source' => 'web',
            'raw_payload' => [
                'request_ip' => $request->ip(),
            ],
        ]);

        ProcessGeminiAttachment::dispatch($attachment);

        return response()->json($attachment, 201);
    }

    public function process(Attachment $attachment)
    {
        ProcessGeminiAttachment::dispatch($attachment);

        return response()->json([
            'message' => 'Processamento re-enfileirado.',
        ]);
    }
}
