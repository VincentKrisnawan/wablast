<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Models\MessageSession;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function store(Request $request, $session_id)
    {
        $request->validate([
            'template' => 'required|string',
        ]);

        $session = MessageSession::findOrFail($session_id);
        $batch_id = $session->batch_id;

        $template = MessageTemplate::updateOrCreate(
            ['batch_id' => $batch_id],
            ['template' => $request->template]
        );

        return response()->json(['message' => 'Template disimpan', 'data' => $template]);
    }

    public function show($session_id)
    {
        $session = MessageSession::findOrFail($session_id);
        $template = MessageTemplate::where('batch_id', $session->batch_id)->first();

        if (!$template) {
            return response()->json(['message' => 'Template belum dibuat'], 404);
        }

        return response()->json($template);
    }
}
