<x-filament-panels::page>
    <div
        x-data="checkInScanner()"
        x-init="init()"
        class="space-y-4"
    >
        {{-- Section A: Filters --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-4 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    {{-- Listing filter --}}
                    <div class="flex-1">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Activity / Event</label>
                        <select
                            wire:model.live="selectedListingId"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        >
                            <option value="">All my activities</option>
                            @foreach ($this->vendorListings as $listing)
                                <option value="{{ $listing['id'] }}">{{ $listing['title'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date filter --}}
                    @if ($this->selectedListingId && count($this->upcomingDates) > 0)
                        <div class="flex-1">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Date / Time Slot</label>
                            <select
                                wire:model.live="selectedDate"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                            >
                                <option value="">All dates</option>
                                @foreach ($this->upcomingDates as $date)
                                    <option value="{{ $date['value'] }}">{{ $date['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Stats badge --}}
                    <div class="flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2.5 dark:bg-gray-800">
                        <svg class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <span class="text-primary-600 dark:text-primary-400">{{ $checkInStats['checkedIn'] }}</span>
                            / {{ $checkInStats['total'] }} checked in
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section B: Scanner --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-4 sm:p-6">
                {{-- Camera area --}}
                <div class="mx-auto max-w-md">
                    <div
                        x-show="!scannerActive"
                        class="flex flex-col items-center gap-4"
                    >
                        <div class="flex h-48 w-full items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Camera is off</p>
                            </div>
                        </div>
                        <button
                            @click="startScanner()"
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                            </svg>
                            Start Scanner
                        </button>
                    </div>

                    <div x-show="scannerActive" x-cloak>
                        <div id="qr-reader" class="overflow-hidden rounded-xl"></div>
                        <div class="mt-3 flex gap-2">
                            <button
                                @click="stopScanner()"
                                type="button"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Stop Camera
                            </button>
                            <button
                                x-show="scanPaused"
                                @click="resumeScanner()"
                                type="button"
                                class="flex-1 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                            >
                                Scan Next
                            </button>
                        </div>
                    </div>

                    {{-- Manual input fallback --}}
                    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Or enter code manually</label>
                        <div class="mt-1 flex gap-2">
                            <input
                                wire:model="scannedCode"
                                wire:keydown.enter="manualLookup"
                                type="text"
                                placeholder="VOC-XXXXXXXXXX"
                                class="block flex-1 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                            />
                            <button
                                wire:click="manualLookup"
                                type="button"
                                class="inline-flex items-center rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                            >
                                Lookup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section C: Scan Result --}}
        @if ($scanResult)
            <div class="fi-section rounded-xl shadow-sm ring-1
                @if (in_array($scanResult['status'], ['VALID']))
                    bg-green-50 ring-green-300 dark:bg-green-950 dark:ring-green-800
                @elseif ($scanResult['status'] === 'CHECKED_IN_SUCCESS')
                    bg-green-50 ring-green-300 dark:bg-green-950 dark:ring-green-800
                @elseif ($scanResult['status'] === 'ALREADY_CHECKED_IN')
                    bg-amber-50 ring-amber-300 dark:bg-amber-950 dark:ring-amber-800
                @elseif ($scanResult['status'] === 'WRONG_DATE')
                    bg-orange-50 ring-orange-300 dark:bg-orange-950 dark:ring-orange-800
                @else
                    bg-red-50 ring-red-300 dark:bg-red-950 dark:ring-red-800
                @endif
            ">
                <div class="p-4 sm:p-6">
                    {{-- Status header --}}
                    <div class="flex items-start gap-3">
                        @if (in_array($scanResult['status'], ['VALID', 'CHECKED_IN_SUCCESS']))
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @elseif ($scanResult['status'] === 'ALREADY_CHECKED_IN')
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900">
                                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @elseif ($scanResult['status'] === 'WRONG_DATE')
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900">
                                <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                </svg>
                            </div>
                        @else
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="flex-1">
                            <p class="text-lg font-semibold
                                @if (in_array($scanResult['status'], ['VALID', 'CHECKED_IN_SUCCESS'])) text-green-800 dark:text-green-300
                                @elseif ($scanResult['status'] === 'ALREADY_CHECKED_IN') text-amber-800 dark:text-amber-300
                                @elseif ($scanResult['status'] === 'WRONG_DATE') text-orange-800 dark:text-orange-300
                                @else text-red-800 dark:text-red-300
                                @endif
                            ">
                                {{ $scanResult['message'] }}
                            </p>

                            @if (isset($scanResult['code']))
                                <p class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $scanResult['code'] }}</p>
                            @endif
                        </div>

                        <button wire:click="clearResult" type="button" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Participant details (shown for valid/checked-in) --}}
                    @if (isset($scanResult['participantName']) && in_array($scanResult['status'], ['VALID', 'CHECKED_IN_SUCCESS', 'ALREADY_CHECKED_IN']))
                        <div class="mt-4 grid grid-cols-2 gap-3 rounded-lg bg-white/60 p-3 dark:bg-gray-800/60 sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $scanResult['participantName'] }}</p>
                            </div>
                            @if (isset($scanResult['personType']))
                                <div>
                                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Type</p>
                                    <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                                        {{ ucfirst($scanResult['personType'] ?? '-') }}
                                    </span>
                                </div>
                            @endif
                            @if (isset($scanResult['badgeNumber']))
                                <div>
                                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Badge</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $scanResult['badgeNumber'] }}</p>
                                </div>
                            @endif
                            @if (isset($scanResult['bookingNumber']))
                                <div>
                                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Booking</p>
                                    <p class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $scanResult['bookingNumber'] }}</p>
                                </div>
                            @endif
                            @if (isset($scanResult['listingTitle']))
                                <div class="col-span-2">
                                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Activity</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $scanResult['listingTitle'] }}</p>
                                </div>
                            @endif
                            @if (isset($scanResult['eventDate']))
                                <div>
                                    <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $scanResult['eventDate'] }}
                                        @if (isset($scanResult['eventTime'])) at {{ $scanResult['eventTime'] }} @endif
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Extras --}}
                        @if (! empty($scanResult['extras'] ?? []))
                            <div class="mt-3 rounded-lg bg-white/60 p-3 dark:bg-gray-800/60">
                                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Extras / Add-ons</p>
                                <ul class="mt-1 space-y-1">
                                    @foreach ($scanResult['extras'] as $extra)
                                        <li class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $extra['name'] }} &times; {{ $extra['quantity'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    {{-- Action buttons --}}
                    <div class="mt-4">
                        @if ($scanResult['status'] === 'VALID')
                            <button
                                wire:click="performCheckIn('{{ $scanResult['participantId'] }}')"
                                wire:loading.attr="disabled"
                                type="button"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-green-600 px-6 py-4 text-lg font-bold text-white shadow-lg hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span wire:loading.remove wire:target="performCheckIn">CHECK IN</span>
                                <span wire:loading wire:target="performCheckIn">Checking in...</span>
                            </button>
                        @elseif ($scanResult['status'] === 'CHECKED_IN_SUCCESS')
                            <div class="text-center">
                                <p class="text-lg font-bold text-green-700 dark:text-green-400">Checked in at {{ $scanResult['checkedInAt'] ?? '-' }}</p>
                            </div>
                        @elseif ($scanResult['status'] === 'ALREADY_CHECKED_IN')
                            <button
                                wire:click="undoCheckIn('{{ $scanResult['participantId'] }}')"
                                wire:loading.attr="disabled"
                                type="button"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border-2 border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-300 dark:hover:bg-amber-900"
                            >
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                                </svg>
                                Undo Check-In
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Section D: Recent Scans --}}
        @if (count($recentScans) > 0)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="p-4 sm:p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Scans</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-2 pr-4 text-left text-xs font-medium uppercase text-gray-500">Time</th>
                                    <th class="pb-2 pr-4 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                                    <th class="pb-2 pr-4 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th class="pb-2 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($recentScans as $scan)
                                    <tr>
                                        <td class="whitespace-nowrap py-2 pr-4 font-mono text-xs text-gray-500">{{ $scan['time'] }}</td>
                                        <td class="whitespace-nowrap py-2 pr-4 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $scan['code'] }}</td>
                                        <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ $scan['name'] }}</td>
                                        <td class="py-2">
                                            @php
                                                $badgeColor = match ($scan['status']) {
                                                    'VALID' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                    'CHECKED_IN' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                    'ALREADY_CHECKED_IN' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
                                                    'WRONG_EVENT' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                    'WRONG_DATE' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                                                    'NOT_CONFIRMED' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                    'UNDO' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                                    default => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                };
                                                $badgeLabel = match ($scan['status']) {
                                                    'VALID' => 'Ready',
                                                    'CHECKED_IN' => 'Checked In',
                                                    'ALREADY_CHECKED_IN' => 'Already In',
                                                    'WRONG_EVENT' => 'Wrong Event',
                                                    'WRONG_DATE' => 'Wrong Date',
                                                    'NOT_CONFIRMED' => 'Not Confirmed',
                                                    'UNDO' => 'Undone',
                                                    default => 'Invalid',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeColor }}">
                                                {{ $badgeLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- html5-qrcode library --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
        function checkInScanner() {
            return {
                scannerActive: false,
                scanPaused: false,
                scanner: null,

                init() {
                    // Listen for Livewire events
                    Livewire.on('voucher-scanned', (data) => {
                        // Handled by Livewire
                    });
                },

                async startScanner() {
                    this.scannerActive = true;
                    this.scanPaused = false;

                    await this.$nextTick();

                    const readerEl = document.getElementById('qr-reader');
                    if (!readerEl) return;

                    this.scanner = new Html5Qrcode('qr-reader');

                    try {
                        await this.scanner.start(
                            { facingMode: 'environment' },
                            {
                                fps: 10,
                                qrbox: { width: 250, height: 250 },
                                aspectRatio: 1.0,
                            },
                            (decodedText) => {
                                this.onScanSuccess(decodedText);
                            },
                            (errorMessage) => {
                                // Ignore scan errors (no QR found in frame)
                            }
                        );
                    } catch (err) {
                        console.error('Camera start failed:', err);
                        this.scannerActive = false;
                        alert('Could not access camera. Please check permissions or use manual input.');
                    }
                },

                onScanSuccess(decodedText) {
                    // Pause scanning (manual mode)
                    if (this.scanner) {
                        this.scanner.pause(true);
                    }
                    this.scanPaused = true;

                    // Play a beep sound
                    this.playBeep();

                    // Send to Livewire
                    @this.lookupVoucher(decodedText);
                },

                resumeScanner() {
                    if (this.scanner) {
                        this.scanner.resume();
                    }
                    this.scanPaused = false;

                    // Clear previous result
                    @this.clearResult();
                },

                async stopScanner() {
                    if (this.scanner) {
                        try {
                            await this.scanner.stop();
                        } catch (e) {
                            // Ignore stop errors
                        }
                        this.scanner = null;
                    }
                    this.scannerActive = false;
                    this.scanPaused = false;
                },

                playBeep() {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const oscillator = ctx.createOscillator();
                        const gain = ctx.createGain();
                        oscillator.connect(gain);
                        gain.connect(ctx.destination);
                        oscillator.frequency.value = 880;
                        oscillator.type = 'sine';
                        gain.gain.value = 0.3;
                        oscillator.start();
                        oscillator.stop(ctx.currentTime + 0.15);
                    } catch (e) {
                        // Audio not supported, silently ignore
                    }
                },
            };
        }
    </script>

    <style>
        /* Ensure QR reader fits mobile screens */
        #qr-reader {
            width: 100% !important;
            border: none !important;
        }
        #qr-reader video {
            border-radius: 0.75rem;
        }
        /* Hide the html5-qrcode built-in UI elements we don't need */
        #qr-reader__dashboard {
            display: none !important;
        }
        #qr-reader__status_span {
            display: none !important;
        }
        #qr-reader__header_message {
            display: none !important;
        }
        /* Hide x-cloak elements until Alpine initializes */
        [x-cloak] {
            display: none !important;
        }
    </style>
</x-filament-panels::page>
