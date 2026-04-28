@extends('mail.layouts.base')

@section('content')
    <h1>{{ __('mail.slot_removed_heading') }}</h1>

    <p>{{ __('mail.slot_removed_intro', ['listing' => $listingTitle]) }}</p>

    <div class="details-card">
        <div class="detail-row">
            <strong>{{ __('mail.label_date') }}:</strong>
            <span>{{ $slotDate }}</span>
        </div>
        <div class="detail-row">
            <strong>{{ __('mail.label_time') }}:</strong>
            <span>{{ $slotStartTime }} – {{ $slotEndTime }}</span>
        </div>
    </div>

    <p>{{ __('mail.slot_removed_outro') }}</p>

    <p>
        <a href="{{ platform_url('/') }}" class="cta-button">
            {{ __('mail.slot_removed_cta') }}
        </a>
    </p>

    <p>{{ __('mail.signature') }}</p>
@endsection
