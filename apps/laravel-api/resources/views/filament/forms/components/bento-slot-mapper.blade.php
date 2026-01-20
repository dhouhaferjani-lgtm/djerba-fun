<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="{
            images: [],
            slots: [],
            selectedSlot: null,

            init() {
                // Get images from the gallery_images field
                const galleryImages = $wire.get('data.gallery_images') || [];
                // Convert to array of paths (handle both string paths and temp upload objects)
                this.images = this.normalizeImages(galleryImages);
                this.slots = [...this.images];

                // Watch for changes to gallery_images from FileUpload
                this.$watch('$wire.data.gallery_images', (value) => {
                    const normalized = this.normalizeImages(value || []);
                    // Only update if images actually changed (not just reorder)
                    if (JSON.stringify(normalized.sort()) !== JSON.stringify(this.images.sort())) {
                        this.images = normalized;
                        this.slots = [...this.images];
                    }
                });

                // Update gallery_images when slots change
                this.$watch('slots', (value) => {
                    const filtered = value.filter(img => img !== null && img !== undefined);
                    $wire.set('data.gallery_images', filtered);
                });
            },

            normalizeImages(images) {
                if (!images || !Array.isArray(images)) return [];
                return images.map(img => {
                    // If it's a string, use as-is
                    if (typeof img === 'string') return img;
                    // If it's an object with a path, use the path
                    if (img && img.path) return img.path;
                    // Otherwise return the value
                    return img;
                }).filter(Boolean);
            },

            get visibleSlotCount() {
                return Math.min(Math.max(this.images.length, 1), 5);
            },

            get unassignedImages() {
                return this.images.filter(img => !this.slots.slice(0, 5).includes(img));
            },

            get additionalImages() {
                return this.slots.slice(5);
            },

            selectSlot(index) {
                this.selectedSlot = this.selectedSlot === index ? null : index;
            },

            assignImage(image) {
                if (this.selectedSlot !== null) {
                    // If slot already has an image, swap or move
                    const currentImage = this.slots[this.selectedSlot];
                    const imageCurrentIndex = this.slots.indexOf(image);

                    if (imageCurrentIndex !== -1) {
                        // Image is already in a slot, swap
                        this.slots[imageCurrentIndex] = currentImage;
                    }

                    this.slots[this.selectedSlot] = image;
                    this.selectedSlot = null;
                }
            },

            clearSlot(index) {
                // Move image to end of array (becomes additional image)
                const image = this.slots[index];
                this.slots.splice(index, 1);
                this.slots.push(image);
            },

            setAsCover(image) {
                const currentIndex = this.slots.indexOf(image);
                if (currentIndex > 0) {
                    // Swap with current cover
                    const currentCover = this.slots[0];
                    this.slots[0] = image;
                    this.slots[currentIndex] = currentCover;
                }
            },

            getImageUrl(path) {
                if (!path) return '';
                if (path.startsWith('http')) return path;
                return '/storage/' + path;
            }
        }"
        class="space-y-4"
    >
        <!-- Instructions -->
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            <p><strong>How to use:</strong> Click a slot, then click an image below to assign it. The first slot is your cover photo.</p>
        </div>

        <!-- Bento Grid Preview -->
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Gallery Preview</h4>

            <div
                class="grid gap-2"
                :class="{
                    'grid-cols-1': visibleSlotCount === 1,
                    'grid-cols-2': visibleSlotCount === 2,
                    'grid-cols-3 grid-rows-2': visibleSlotCount >= 3
                }"
                style="max-width: 500px;"
            >
                <!-- Cover Slot (always first, larger) -->
                <div
                    @click="selectSlot(0)"
                    :class="{
                        'col-span-1 row-span-2': visibleSlotCount >= 3,
                        'col-span-1': visibleSlotCount < 3,
                        'ring-2 ring-primary-500': selectedSlot === 0
                    }"
                    class="relative aspect-square bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden cursor-pointer transition-all hover:ring-2 hover:ring-primary-300"
                >
                    <template x-if="slots[0]">
                        <div class="relative w-full h-full">
                            <img :src="getImageUrl(slots[0])" class="w-full h-full object-cover" alt="Cover">
                            <button
                                @click.stop="clearSlot(0)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600"
                            >×</button>
                            <span class="absolute bottom-1 left-1 bg-primary-500 text-white text-xs px-2 py-1 rounded">COVER</span>
                        </div>
                    </template>
                    <template x-if="!slots[0]">
                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-xs font-medium">COVER PHOTO</span>
                            <span class="text-xs">Click to select</span>
                        </div>
                    </template>
                </div>

                <!-- Gallery Slots 2-5 -->
                <template x-for="i in [1, 2, 3, 4]" :key="i">
                    <div
                        x-show="visibleSlotCount > i"
                        @click="selectSlot(i)"
                        :class="{ 'ring-2 ring-primary-500': selectedSlot === i }"
                        class="relative aspect-square bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden cursor-pointer transition-all hover:ring-2 hover:ring-primary-300"
                    >
                        <template x-if="slots[i]">
                            <div class="relative w-full h-full">
                                <img :src="getImageUrl(slots[i])" class="w-full h-full object-cover" :alt="'Gallery ' + i">
                                <button
                                    @click.stop="clearSlot(i)"
                                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
                                >×</button>
                                <button
                                    @click.stop="setAsCover(slots[i])"
                                    class="absolute bottom-1 left-1 bg-gray-800/70 text-white text-xs px-1.5 py-0.5 rounded hover:bg-gray-800"
                                    title="Set as cover"
                                >★</button>
                            </div>
                        </template>
                        <template x-if="!slots[i]">
                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span class="text-xs">Slot <span x-text="i + 1"></span></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Image Pool (unassigned images) -->
        <div x-show="unassignedImages.length > 0" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Available Images
                <span class="text-gray-400" x-show="selectedSlot !== null">(Click to assign to selected slot)</span>
            </h4>
            <div class="flex flex-wrap gap-2">
                <template x-for="(img, index) in unassignedImages" :key="index">
                    <div
                        @click="assignImage(img)"
                        :class="{ 'ring-2 ring-primary-500 scale-105': selectedSlot !== null }"
                        class="relative w-20 h-20 rounded-lg overflow-hidden cursor-pointer transition-all hover:scale-105"
                    >
                        <img :src="getImageUrl(img)" class="w-full h-full object-cover" alt="Available image">
                    </div>
                </template>
            </div>
        </div>

        <!-- Additional Images (6-10) -->
        <div x-show="additionalImages.length > 0" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">
                Additional Photos (<span x-text="additionalImages.length"></span>) - Shown in lightbox only
            </h4>
            <p class="text-xs text-blue-600 dark:text-blue-400 mb-3">These images appear when visitors open the full gallery.</p>
            <div class="flex flex-wrap gap-2">
                <template x-for="(img, index) in additionalImages" :key="index">
                    <div class="relative w-16 h-16 rounded-lg overflow-hidden">
                        <img :src="getImageUrl(img)" class="w-full h-full object-cover" alt="Additional image">
                        <button
                            @click="setAsCover(img)"
                            class="absolute bottom-0.5 left-0.5 bg-gray-800/70 text-white text-xs px-1 py-0.5 rounded hover:bg-gray-800"
                            title="Move to bento grid"
                        >↑</button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="images.length === 0" class="text-center py-8 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p>Upload images above to see the gallery preview</p>
        </div>

        <!-- Image Count -->
        <div class="text-sm text-gray-500 dark:text-gray-400 text-right">
            <span x-text="images.length"></span> / 10 photos
        </div>
    </div>
</x-dynamic-component>
