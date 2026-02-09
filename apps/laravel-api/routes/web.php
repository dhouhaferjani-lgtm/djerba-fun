<?php

use App\Http\Controllers\Api\V1\ClictopayCallbackController;
use App\Http\Controllers\Filament\LocaleSwitchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/filament/locale/{locale}', LocaleSwitchController::class)
    ->name('filament.locale.switch')
    ->middleware(['web']);

/*
|--------------------------------------------------------------------------
| Admin Media Proxy Routes
|--------------------------------------------------------------------------
|
| Serve media files through Laravel to avoid CORS issues in Filament admin.
| FilePond (used by Filament) uses fetch() which is subject to CORS, while
| frontend <img> tags are not — so direct S3/MinIO URLs work on frontend
| but fail in admin panel previews.
|
*/

// Proxy for Spatie Media Library files (stored on minio/S3 disk)
Route::get('/admin/media-proxy/{media}', function (Media $media) {
    $disk = Storage::disk($media->disk);
    $path = $media->getPathRelativeToRoot();

    if (! $disk->exists($path)) {
        abort(404);
    }

    $stream = $disk->readStream($path);

    return response()->stream(
        function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        },
        200,
        [
            'Content-Type' => $media->mime_type,
            'Content-Length' => $media->size,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]
    );
})->middleware(['web', 'auth'])->name('admin.media.proxy');

// Proxy for disk files (public, minio) — listings media, blog images, destinations, etc.
Route::get('/admin/storage-proxy', function (Request $request) {
    $path = $request->query('path');
    $diskName = $request->query('disk', 'public');

    // Only allow safe disks
    if (! in_array($diskName, ['public', 'minio'], true)) {
        abort(400);
    }

    if (! $path || str_contains($path, '..')) {
        abort(400);
    }

    $disk = Storage::disk($diskName);
    if (! $disk->exists($path)) {
        abort(404);
    }

    $stream = $disk->readStream($path);

    return response()->stream(
        function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        },
        200,
        [
            'Content-Type' => $disk->mimeType($path),
            'Content-Length' => $disk->size($path),
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=3600',
        ]
    );
})->middleware(['web', 'auth'])->name('admin.storage.proxy');

/*
|--------------------------------------------------------------------------
| Payment Gateway Callbacks
|--------------------------------------------------------------------------
|
| These routes handle redirects from external payment gateways.
| They must be web routes (not API) because they receive browser redirects.
|
*/

// Clictopay SMT callback - handles redirect after payment on Clictopay page
Route::get('/payment/clictopay/callback/{intent}', [ClictopayCallbackController::class, 'callback'])
    ->name('payment.clictopay.callback');

/*
|--------------------------------------------------------------------------
| Temporary Email Diagnostic Route (remove after debugging)
|--------------------------------------------------------------------------
*/
Route::get('/debug/test-custom-trip-email', function () {
    // Security: only accessible with correct token
    if (request()->query('token') !== 'ga-debug-2025') {
        abort(404);
    }

    $results = [];

    // Step 1: Check config
    $results['config'] = [
        'mail_mailer' => config('mail.default'),
        'queue_connection' => config('queue.default'),
        'mail_from' => config('mail.from.address'),
    ];

    // Step 2: Find latest custom trip request
    $request = \App\Models\CustomTripRequest::latest()->first();
    if (! $request) {
        return response()->json(['error' => 'No custom trip requests found in database'], 404);
    }

    $results['custom_trip_request'] = [
        'id' => $request->id,
        'reference' => $request->reference,
        'contact_email' => $request->contact_email,
        'contact_name' => $request->contact_name,
        'created_at' => $request->created_at->toIso8601String(),
    ];

    // Step 3: Try to construct the mailable
    try {
        $mailable = new \App\Mail\CustomTripRequestConfirmationMail($request);
        $results['mailable_construct'] = 'OK';
    } catch (\Throwable $e) {
        $results['mailable_construct'] = 'FAILED: ' . $e->getMessage();
        return response()->json($results);
    }

    // Step 4: Try to render the email HTML
    try {
        $html = $mailable->render();
        $results['mailable_render'] = 'OK (' . strlen($html) . ' bytes)';
    } catch (\Throwable $e) {
        $results['mailable_render'] = 'FAILED: ' . $e->getMessage();
        return response()->json($results);
    }

    // Step 5: Try to serialize/deserialize (simulates queue)
    try {
        $serialized = serialize($mailable);
        $deserialized = unserialize($serialized);
        $results['serialization'] = 'OK';
    } catch (\Throwable $e) {
        $results['serialization'] = 'FAILED: ' . $e->getMessage();
    }

    // Step 6: Check email_logs for custom trip emails
    $logs = \App\Models\EmailLog::where('email_type', 'custom_trip_confirmation')
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get(['recipient_email', 'status', 'error_message', 'sent_at', 'created_at']);
    $results['recent_email_logs'] = $logs->toArray();

    // Step 7: Check failed_jobs
    $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
        ->where('payload', 'like', '%CustomTripRequest%')
        ->orderBy('failed_at', 'desc')
        ->take(3)
        ->get(['uuid', 'exception', 'failed_at']);
    $results['failed_jobs'] = $failedJobs->map(fn ($job) => [
        'uuid' => $job->uuid,
        'exception' => \Illuminate\Support\Str::limit($job->exception, 500),
        'failed_at' => $job->failed_at,
    ])->toArray();

    // Step 8: If ?send=1, actually send synchronously
    if (request()->query('send') === '1') {
        $overrideEmail = request()->query('email', $request->contact_email);
        try {
            \Illuminate\Support\Facades\Mail::to($overrideEmail)->send($mailable);
            $results['sync_send'] = 'SUCCESS - email sent to ' . $overrideEmail;
        } catch (\Throwable $e) {
            $results['sync_send'] = 'FAILED: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
        }
    } else {
        $results['sync_send'] = 'Skipped. Add &send=1 to URL to send a test email. Add &email=you@example.com to override recipient.';
    }

    return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});
