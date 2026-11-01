<?php

namespace App\Http\Controllers;

use App\Support\TimingReports;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TimingsController extends Controller
{
    /** Receives a report from a running server and answers with where to read it. */
    public function store(Request $request): JsonResponse
    {
        $body = $request->getContent();

        if (($reason = TimingReports::rejection($body)) !== null) {
            return response()->json(['error' => $reason], 422);
        }

        $key = TimingReports::store($body);

        return response()->json([
            'key' => $key,
            // From APP_URL, not the request: behind a TLS-terminating proxy the request scheme reads as http.
            'url' => rtrim(config('app.url'), '/').route('timings.show', ['key' => $key], false),
        ], 201);
    }

    /** The report as the server sent it, as a file. */
    public function download(string $key): Response
    {
        $raw = TimingReports::raw($key);

        abort_if($raw === null, 404);

        return response($raw, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="basalt-timings-'.$key.'.json"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
