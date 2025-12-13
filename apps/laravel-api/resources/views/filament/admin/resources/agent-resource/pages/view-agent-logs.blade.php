<x-filament-panels::page>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-2">Agent Information</h3>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="mt-1 text-sm">
                        @if($record->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rate Limit</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->rate_limit }} requests/minute</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Used</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->last_used_at?->diffForHumans() ?? 'Never' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Permissions</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        @if($record->permissions)
                            @foreach($record->permissions as $permission)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 mr-2 mb-2">{{ $permission }}</span>
                            @endforeach
                        @else
                            <span class="text-gray-400">No permissions set</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
