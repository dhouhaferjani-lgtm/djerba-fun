<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            imageCount: 5,

            init() {
                const images = $wire.get('data.gallery_images') || [];
                if (Array.isArray(images) && images.length > 0) {
                    this.imageCount = Math.min(images.length, 5);
                }

                // Watch for changes to gallery_images
                $watch('$wire.data.gallery_images', (value) => {
                    if (Array.isArray(value) && value.length > 0) {
                        this.imageCount = Math.min(value.length, 5);
                    }
                });
            },

            getGridStyle() {
                const count = parseInt(this.imageCount);
                const base = 'display: grid; gap: 8px;';
                if (count === 1) return base + 'grid-template-columns: 1fr; height: 250px;';
                if (count === 2) return base + 'grid-template-columns: repeat(2, 1fr); height: 250px;';
                if (count === 3) return base + 'grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr); height: 350px;';
                if (count === 4) return base + 'grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr); height: 350px;';
                return base + 'grid-template-columns: repeat(4, 1fr); grid-template-rows: repeat(2, 1fr); height: 350px;';
            },

            getSlotStyle(index) {
                const count = parseInt(this.imageCount);
                if (count === 3 && index === 0) return 'grid-row: span 2;';
                if (count === 5 && index === 0) return 'grid-column: span 2; grid-row: span 2;';
                return '';
            }
        }"
        class="space-y-4"
    >
        <!-- Layout Selector -->
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                How many photos will you upload?
            </label>
            <select
                x-model="imageCount"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="1">1 photo</option>
                <option value="2">2 photos</option>
                <option value="3">3 photos</option>
                <option value="4">4 photos</option>
                <option value="5">5 photos</option>
            </select>
        </div>

        <!-- Layout Preview Grid -->
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Layout Preview
                </h4>
                <span class="text-xs text-gray-500 dark:text-gray-400">This is how your gallery will appear</span>
            </div>

            <div :style="getGridStyle()">
                <template x-for="i in parseInt(imageCount)" :key="i">
                    <div
                        :style="getSlotStyle(i - 1)"
                        class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center transition-all"
                    >
                        <div class="text-center">
                            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span
                                class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                x-text="i === 1 ? 'Cover Photo' : 'Photo ' + i"
                            ></span>
                        </div>
                        <!-- Cover badge -->
                        <span
                            x-show="i === 1"
                            class="absolute top-2 left-2 bg-primary-600 text-white text-xs font-medium px-2 py-1 rounded"
                        >
                            COVER
                        </span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Instructions -->
        <div class="text-sm text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
            <p class="font-medium text-blue-700 dark:text-blue-300 mb-1">How to upload:</p>
            <ol class="list-decimal list-inside text-blue-600 dark:text-blue-400 space-y-1">
                <li>Select the number of photos above</li>
                <li>Use the upload area below to add your photos</li>
                <li>Drag photos to reorder - first photo is your cover image</li>
            </ol>
        </div>
    </div>
</x-dynamic-component>
