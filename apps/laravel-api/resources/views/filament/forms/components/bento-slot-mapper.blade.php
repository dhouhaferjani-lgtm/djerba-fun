<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="{
            imageCount: 5,
            images: [],
            uploading: {},

            init() {
                // Load existing images from gallery_images field
                const existingImages = $wire.get('data.gallery_images') || [];
                this.images = this.normalizeImages(existingImages);
                this.imageCount = Math.max(this.images.length, 1);

                // Pad array to match count
                while (this.images.length < this.imageCount) {
                    this.images.push(null);
                }
            },

            normalizeImages(images) {
                if (!images || !Array.isArray(images)) return [];
                return images.map(img => {
                    if (typeof img === 'string') return img;
                    if (img && img.path) return img.path;
                    return img;
                }).filter(Boolean);
            },

            onCountChange() {
                // Resize images array
                const newArray = [];
                for (let i = 0; i < this.imageCount; i++) {
                    newArray.push(this.images[i] || null);
                }
                this.images = newArray;
                this.syncToWire();
            },

            async uploadToSlot(index, event) {
                const file = event.target.files[0];
                if (!file) return;

                this.uploading[index] = true;

                // Create FormData for upload
                const formData = new FormData();
                formData.append('file', file);

                try {
                    // Use Livewire's upload mechanism
                    const tempPath = await new Promise((resolve, reject) => {
                        $wire.upload(
                            'gallery_upload_temp',
                            file,
                            (uploadedFilename) => {
                                resolve(uploadedFilename);
                            },
                            (error) => {
                                reject(error);
                            }
                        );
                    });

                    // Update images array
                    this.images[index] = 'listing-galleries/' + tempPath;
                    this.syncToWire();
                } catch (error) {
                    console.error('Upload failed:', error);
                    alert('Upload failed. Please try again.');
                } finally {
                    this.uploading[index] = false;
                }

                // Reset the input
                event.target.value = '';
            },

            removeFromSlot(index) {
                this.images[index] = null;
                this.syncToWire();
            },

            syncToWire() {
                const filtered = this.images.filter(img => img !== null && img !== undefined);
                $wire.set('data.gallery_images', filtered);
            },

            getImageUrl(path) {
                if (!path) return '';
                if (path.startsWith('http')) return path;
                if (path.startsWith('blob:')) return path;
                if (path.startsWith('data:')) return path;
                // For temporary uploads, use livewire temp URL
                if (path.includes('livewire-tmp')) {
                    return '/storage/' + path;
                }
                return '{{ config('app.url') }}/storage/' + path;
            },

            getGridClass() {
                const grids = {
                    1: 'grid-cols-1',
                    2: 'grid-cols-2',
                    3: 'grid-cols-2 grid-rows-2',
                    4: 'grid-cols-2 grid-rows-2',
                    5: 'grid-cols-4 grid-rows-2'
                };
                return grids[this.imageCount] || 'grid-cols-1';
            },

            getSlotClass(index) {
                const count = parseInt(this.imageCount);
                if (count === 1) return 'col-span-1 aspect-video';
                if (count === 2) return 'col-span-1 aspect-[4/3]';
                if (count === 3) {
                    return index === 0 ? 'col-span-1 row-span-2 aspect-auto h-full' : 'col-span-1 aspect-[4/3]';
                }
                if (count === 4) return 'col-span-1 aspect-square';
                if (count === 5) {
                    return index === 0 ? 'col-span-2 row-span-2 aspect-auto h-full' : 'col-span-1 aspect-square';
                }
                return 'col-span-1 aspect-square';
            },

            getSlotLabel(index) {
                if (index === 0) return 'Cover Photo';
                return 'Photo ' + (index + 1);
            }
        }"
        class="space-y-4"
    >
        <!-- Image Count Selector -->
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                How many photos do you want to upload?
            </label>
            <select
                x-model="imageCount"
                @change="onCountChange()"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="1">1 photo</option>
                <option value="2">2 photos</option>
                <option value="3">3 photos</option>
                <option value="4">4 photos</option>
                <option value="5">5 photos</option>
            </select>
        </div>

        <!-- Dynamic Grid -->
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Gallery Preview
                </h4>
                <span class="text-xs text-gray-500">This is exactly how it will appear on your listing</span>
            </div>

            <div
                :class="'grid gap-2 ' + getGridClass()"
                :style="imageCount >= 3 ? 'height: 400px;' : ''"
            >
                <template x-for="index in parseInt(imageCount)" :key="index - 1">
                    <div
                        :class="getSlotClass(index - 1)"
                        class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-700 transition-all hover:border-primary-400"
                    >
                        <!-- Has Image -->
                        <template x-if="images[index - 1]">
                            <div class="relative w-full h-full group">
                                <img
                                    :src="getImageUrl(images[index - 1])"
                                    class="w-full h-full object-cover"
                                    :alt="getSlotLabel(index - 1)"
                                >
                                <!-- Hover overlay with actions -->
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                                    <!-- Replace button -->
                                    <label class="cursor-pointer p-2 bg-white rounded-full hover:bg-gray-100 transition" title="Replace image">
                                        <input
                                            type="file"
                                            @change="uploadToSlot(index - 1, $event)"
                                            class="hidden"
                                            accept="image/*"
                                        >
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </label>
                                    <!-- Remove button -->
                                    <button
                                        @click="removeFromSlot(index - 1)"
                                        class="p-2 bg-white rounded-full hover:bg-red-100 transition"
                                        title="Remove image"
                                    >
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- Cover badge -->
                                <span
                                    x-show="index === 1"
                                    class="absolute top-2 left-2 bg-primary-600 text-white text-xs font-medium px-2 py-1 rounded"
                                >
                                    COVER
                                </span>
                            </div>
                        </template>

                        <!-- Empty Slot (upload area) -->
                        <template x-if="!images[index - 1]">
                            <label class="flex flex-col items-center justify-center w-full h-full min-h-[120px] cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                <input
                                    type="file"
                                    @change="uploadToSlot(index - 1, $event)"
                                    class="hidden"
                                    accept="image/*"
                                    :disabled="uploading[index - 1]"
                                >
                                <!-- Loading state -->
                                <template x-if="uploading[index - 1]">
                                    <div class="flex flex-col items-center">
                                        <svg class="animate-spin w-8 h-8 text-primary-500 mb-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-xs text-gray-500">Uploading...</span>
                                    </div>
                                </template>
                                <!-- Normal state -->
                                <template x-if="!uploading[index - 1]">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <span
                                            class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                            x-text="getSlotLabel(index - 1)"
                                        ></span>
                                        <span class="text-xs text-gray-400 mt-1">Click to upload</span>
                                    </div>
                                </template>
                            </label>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Help text -->
        <div class="text-sm text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
            <p class="font-medium text-blue-700 dark:text-blue-300 mb-1">Tips:</p>
            <ul class="list-disc list-inside text-blue-600 dark:text-blue-400 space-y-1">
                <li>The first photo is your cover image - shown in search results</li>
                <li>Hover over any image to replace or remove it</li>
                <li>Use high-quality images (recommended: 1920x1080 or larger)</li>
            </ul>
        </div>
    </div>
</x-dynamic-component>
