<?php

namespace App\Http\Controllers;

use App\Models\Errand;
use App\Models\History;
use App\Models\ErrandAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ErrandController extends Controller
{
    public function show($id)
    {
        $userId = Auth::id();

        $errand = Errand::with('attachment')
            ->where('id', $id)
            ->where('client_id', $userId)
            ->first();

        $isFinished = false;

        if (!$errand) {
            $errand = History::with('attachment')
                ->where('errand_id', $id)
                ->where('client_id', $userId)
                ->firstOrFail();
            
            $isFinished = true;
        }

        return view('client.view_errand_info', compact('errand', 'isFinished'));
    }

    public function download(ErrandAttachment $attachment)
    {
        $path = storage_path('app/public/' . $attachment->file_path);

        if (!file_exists($path)) {
            abort(404, 'File not found on server.');
        }

        $response = response()->download($path, $attachment->original_name);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}