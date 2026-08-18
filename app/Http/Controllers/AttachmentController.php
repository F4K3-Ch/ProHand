<?php

namespace App\Http\Controllers;

use App\Models\ErrandAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(Request $request, $id)
    {
        $attachment = ErrandAttachment::with('errand')->find($id);

        if (!$attachment || !$attachment->errand) {
            return response()->json([
                'error' => 'Attachment not found.'
            ], 404);
        }

        $user = $request->user();
        $errand = $attachment->errand;

        $hasAccess = $user->role === 'admin'
            || ($user->role === 'client' && $errand->client_id === $user->id)
            || ($user->role === 'tasker' && $errand->tasker_id === $user->id);

        if (!$hasAccess) {
            return response()->json([
                'error' => 'You are not authorized to download this attachment.'
            ], 403);
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        return response()->download(
            Storage::disk('public')->path($attachment->file_path),
            $attachment->original_name
        );
    }
}