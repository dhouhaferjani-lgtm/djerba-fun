<x-filament-panels::page>
    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach($this->getStats() as $stat)
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="fi-wi-stats-overview-stat-icon flex h-12 w-12 items-center justify-center rounded-lg bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-400/10">
                        <x-dynamic-component
                            :component="$stat['icon']"
                            class="h-6 w-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400"
                        />
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $stat['label'] }}
                        </span>
                        <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $stat['value'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Deletion Requests --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-trash class="h-5 w-5 text-gray-400" />
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Recent Deletion Requests
                    </h3>
                </div>
            </div>
            <div class="fi-section-content px-6 pb-6">
                @php $requests = $this->getRecentDeletionRequests(); @endphp
                @if(count($requests) > 0)
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($requests as $request)
                            <li class="py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $request['email'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $request['requested_at'] }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $request['status_color'] }}-50 text-{{ $request['status_color'] }}-700 dark:bg-{{ $request['status_color'] }}-400/10 dark:text-{{ $request['status_color'] }}-400">
                                        {{ ucfirst($request['status']) }}
                                    </span>
                                </div>
                                @if($request['reason'])
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $request['reason'] }}
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4">
                        <a href="{{ route('filament.admin.resources.data-deletion-requests.index') }}"
                           class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                            View all requests &rarr;
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No deletion requests yet.
                    </p>
                @endif
            </div>
        </div>

        {{-- Consent Breakdown --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-hand-thumb-up class="h-5 w-5 text-gray-400" />
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Active Consent Breakdown
                    </h3>
                </div>
            </div>
            <div class="fi-section-content px-6 pb-6">
                @php $breakdown = $this->getConsentBreakdown(); @endphp
                @if(count($breakdown) > 0)
                    <dl class="space-y-3">
                        @foreach($breakdown as $type => $count)
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400 capitalize">
                                    {{ str_replace('_', ' ', $type) }}
                                </dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($count) }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No consent data recorded yet.
                    </p>
                @endif
            </div>
        </div>

        {{-- Data Retention Status --}}
        <div class="lg:col-span-2 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-clock class="h-5 w-5 text-gray-400" />
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Data Retention Status
                    </h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Data that may need to be cleaned up based on retention policies.
                </p>
            </div>
            <div class="fi-section-content px-6 pb-6">
                @php $retention = $this->getDataRetentionStatus(); @endphp
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Category
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Count
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Recommended Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($retention as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        {{ $item['label'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($item['count'] > 0)
                                            <span class="inline-flex items-center rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">
                                                {{ number_format($item['count']) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-400/10 dark:text-success-400">
                                                0
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item['action'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Note:</strong> Run <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">php artisan gdpr:apply-retention</code>
                        to apply data retention policies. This command is typically scheduled to run daily.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
