<x-filament-panels::page>
    {{-- Help Banner --}}
    <div class="rounded-xl bg-primary-50 dark:bg-primary-400/10 p-4 mb-6">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="h-5 w-5 text-primary-600 dark:text-primary-400 mt-0.5 flex-shrink-0" />
            <div class="text-sm text-primary-700 dark:text-primary-300">
                <p class="font-medium mb-1">How to use the Homepage Builder</p>
                <ul class="list-disc list-inside space-y-1 text-primary-600 dark:text-primary-400">
                    <li><strong>Drag and drop</strong> sections to reorder them on the homepage</li>
                    <li>Use the <strong>toggle switch</strong> to show or hide sections</li>
                    <li>Click <strong>Edit</strong> to modify the content of each section</li>
                    <li>Changes are saved automatically</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Section Cards --}}
    <div
        x-data="{
            init() {
                new Sortable(this.$refs.sortableList, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    onEnd: (evt) => {
                        const orderedIds = Array.from(evt.target.children).map(el => el.dataset.sectionId);
                        $wire.updateSectionOrder(orderedIds);
                    }
                });
            }
        }"
        class="space-y-3"
    >
        <div x-ref="sortableList" class="space-y-3">
            @foreach($sections as $section)
                <div
                    data-section-id="{{ $section['id'] }}"
                    class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 {{ !($section['enabled'] ?? true) ? 'opacity-60' : '' }}"
                >
                    <div class="flex items-center gap-4 p-4">
                        {{-- Drag Handle --}}
                        <div class="drag-handle cursor-grab active:cursor-grabbing p-2 -m-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                            </svg>
                        </div>

                        {{-- Icon --}}
                        <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                            <x-dynamic-component
                                :component="$section['icon'] ?? 'heroicon-o-square-3-stack-3d'"
                                class="h-5 w-5 text-gray-600 dark:text-gray-400"
                            />
                        </div>

                        {{-- Section Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white truncate">
                                    {{ $section['label'] ?? $section['id'] }}
                                </h3>
                                @if($section['required'] ?? false)
                                    <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">
                                        Required
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $section['description'] ?? '' }}
                            </p>
                        </div>

                        {{-- Toggle --}}
                        <div class="flex-shrink-0">
                            @if($section['required'] ?? false)
                                <div class="w-11 h-6 flex items-center justify-center">
                                    <span class="text-xs text-gray-400">Always on</span>
                                </div>
                            @else
                                <button
                                    type="button"
                                    wire:click="toggleSection('{{ $section['id'] }}')"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900 {{ ($section['enabled'] ?? true) ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                    role="switch"
                                    aria-checked="{{ ($section['enabled'] ?? true) ? 'true' : 'false' }}"
                                >
                                    <span
                                        aria-hidden="true"
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ ($section['enabled'] ?? true) ? 'translate-x-5' : 'translate-x-0' }}"
                                    ></span>
                                </button>
                            @endif
                        </div>

                        {{-- Edit Button --}}
                        <div class="flex-shrink-0">
                            @php $editUrl = $this->getEditUrl($section['id']); @endphp
                            @if($editUrl)
                                <a
                                    href="{{ $editUrl }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    <x-heroicon-m-pencil-square class="h-4 w-4" />
                                    Edit
                                </a>
                            @elseif($section['editHint'] ?? null)
                                <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500" title="{{ $section['editHint'] }}">
                                    <x-heroicon-o-information-circle class="h-4 w-4" />
                                    {{ \Illuminate\Support\Str::limit($section['editHint'], 30) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Load SortableJS from CDN --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @endpush
</x-filament-panels::page>
