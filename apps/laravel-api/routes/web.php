<?php

use App\Http\Controllers\Api\V1\ClictopayCallbackController;
use App\Http\Controllers\Filament\LocaleSwitchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/filament/locale/{locale}', LocaleSwitchController::class)
    ->name('filament.locale.switch')
    ->middleware(['web']);

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
