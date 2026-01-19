<x-mail::message>
# New Contact Form Submission

You have received a new message from the Go Adventure website contact form.

**From:** {{ $name }}

**Email:** {{ $email }}

---

## Message:

{{ $contactMessage }}

---

<x-mail::button :url="'mailto:' . $email">
Reply to {{ $name }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
