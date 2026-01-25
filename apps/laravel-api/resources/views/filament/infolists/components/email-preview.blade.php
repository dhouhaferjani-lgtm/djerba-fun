<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    @if($getState())
        <div class="p-4">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Preview</span>
                <button
                    type="button"
                    onclick="toggleEmailFullscreen(this)"
                    class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400"
                >
                    <span class="expand-text">Expand</span>
                    <span class="collapse-text hidden">Collapse</span>
                </button>
            </div>
            <div class="email-preview-container relative overflow-hidden rounded border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-900" style="height: 400px;">
                <iframe
                    srcdoc="{{ $getState() }}"
                    class="h-full w-full border-0"
                    sandbox="allow-same-origin"
                    title="Email Preview"
                ></iframe>
            </div>
        </div>
    @else
        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
            <x-heroicon-o-envelope class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2">No email content available</p>
        </div>
    @endif
</div>

<style>
    .email-preview-container.fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        height: 100vh !important;
        border-radius: 0;
        border: none;
    }
</style>

<script>
    function toggleEmailFullscreen(button) {
        const container = button.closest('.rounded-lg').querySelector('.email-preview-container');
        const expandText = button.querySelector('.expand-text');
        const collapseText = button.querySelector('.collapse-text');

        container.classList.toggle('fullscreen');
        expandText.classList.toggle('hidden');
        collapseText.classList.toggle('hidden');
    }
</script>
