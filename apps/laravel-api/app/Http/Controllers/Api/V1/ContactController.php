<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Mail\ContactFormMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Handle contact form submission.
     */
    public function store(ContactFormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Send email to contact@go-adventure.net
        Mail::to('contact@go-adventure.net')
            ->queue(new ContactFormMail(
                name: $validated['name'],
                email: $validated['email'],
                message: $validated['message'],
            ));

        return response()->json([
            'success' => true,
            'message' => __('Your message has been sent successfully. We will get back to you within 24 hours.'),
        ]);
    }
}
