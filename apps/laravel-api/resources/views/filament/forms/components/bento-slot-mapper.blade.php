<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            imageCount: 5,
            images: {},
            files: {},
            uploading: {},
            progress: {},

            init() {
                // Load existing images from Livewire state
                this.loadExistingImages();

                // Watch for external changes
                $watch('$wire.data.gallery_images', () => this.loadExistingImages());
            },

            loadExistingImages() {
                const galleryImages = $wire.get('data.gallery_images') || [];
                if (Array.isArray(galleryImages)) {
                    galleryImages.forEach((img, i) => {
                        if (img && typeof img === 'string') {
                            // Build URL for existing images
                            this.images[i] = img.startsWith('http') ? img : '/storage/' + img;
                        }
                    });
                    if (galleryImages.length > 0) {
                        this.imageCount = Math.max(this.imageCount, galleryImages.length);
                    }
                }
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
            },

            triggerUpload(index) {
                if (!this.uploading[index]) {
                    document.getElementById('file-input-' + index).click();
                }
            },

            handleFileSelect(event, index) {
                const file = event.target.files[0];
                if (!file) return;

                // Store file reference to prevent garbage collection of blob URL
                this.files[index] = file;

                // Show local preview immediately
                this.images[index] = URL.createObjectURL(file);
                this.uploading[index] = true;
                this.progress[index] = 0;

                // Upload using Livewire
                $wire.upload(
                    'data.gallery_images.' + index,
                    file,
                    (uploadedFilename) => {
                        // Success - blob URL remains valid because we kept file reference
                        this.uploading[index] = false;
                        delete this.progress[index];
                        console.log('Upload success for slot ' + index);
                    },
                    () => {
                        // Error - clean up
                        this.uploading[index] = false;
                        delete this.images[index];
                        delete this.files[index];
                        alert('Upload failed. Please try again.');
                    },
                    (event) => {
                        // Progress
                        this.progress[index] = event.detail.progress;
                    }
                );
            },

            removeImage(index) {
                // Clean up blob URL and file reference
                if (this.images[index] && this.images[index].startsWith('blob:')) {
                    URL.revokeObjectURL(this.images[index]);
                }
                delete this.images[index];
                delete this.files[index];
                $wire.set('data.gallery_images.' + index, null);
            }
        }"
        class="space-y-4"
    >
        <!-- Layout Selector -->
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                How many photos?
            </label>
            <select x-model="imageCount" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="1">1 photo</option>
                <option value="2">2 photos</option>
                <option value="3">3 photos</option>
                <option value="4">4 photos</option>
                <option value="5">5 photos</option>
            </select>
        </div>

        <!-- Clickable Bento Grid -->
        <div :style="getGridStyle()">
            <template x-for="i in parseInt(imageCount)" :key="i">
                <div
                    :style="getSlotStyle(i - 1)"
                    @click="triggerUpload(i - 1)"
                    class="relative overflow-hidden rounded-lg cursor-pointer transition-all hover:ring-2 hover:ring-primary-500"
                    :class="images[i - 1] ? 'bg-gray-200' : 'border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700'"
                >
                    <!-- Hidden file input -->
                    <input
                        type="file"
                        accept="image/*"
                        class="hidden"
                        :id="'file-input-' + (i - 1)"
                        @change="handleFileSelect($event, i - 1)"
                    />

                    <!-- Image preview -->
                    <template x-if="images[i - 1]">
                        <div class="absolute inset-0">
                            <img :src="images[i - 1]" class="w-full h-full object-cover" />
                            <button
                                @click.stop="removeImage(i - 1)"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 hover:bg-red-600 shadow-lg"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>

                    <!-- Upload placeholder -->
                    <template x-if="!images[i - 1] && !uploading[i - 1]">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                <span class="text-sm text-gray-500 dark:text-gray-400" x-text="i === 1 ? 'Cover Photo' : 'Photo ' + i"></span>
                                <p class="text-xs text-gray-400 mt-1">Click to upload</p>
                            </div>
                        </div>
                    </template>

                    <!-- Upload progress -->
                    <template x-if="uploading[i - 1]">
                        <div class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                            <div class="text-center">
                                <div class="w-12 h-12 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                                <span class="text-sm text-gray-500 mt-2 block" x-text="(progress[i - 1] || 0) + '%'"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Cover badge -->
                    <span
                        x-show="i === 1"
                        class="absolute top-2 left-2 bg-primary-600 text-white text-xs font-medium px-2 py-1 rounded z-10"
                    >
                        COVER
                    </span>
                </div>
            </template>
        </div>

        <!-- Helper text -->
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
            Click each slot to upload. First slot is your cover photo.
        </p>
    </div>
</x-dynamic-component>
