@extends('admin.layouts.app')

@section('title', 'Carousel Settings')

@section('content')
    {{-- Settings Nav --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.settings.general') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">General</a>
        <a href="{{ route('admin.settings.monetization') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Monetization</a>
        <a href="{{ route('admin.settings.payment') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment</a>
        <a href="{{ route('admin.settings.seller') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Seller</a>
        <a href="{{ route('admin.settings.carousel') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Carousel</a>
        <a href="{{ route('admin.payment-methods.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment Methods</a>
    </div>

    <div class="mx-auto max-w-2xl" x-data="carouselManager()">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Carousel Slides</h2>

            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="group" value="carousel" />

                <div class="space-y-3 mb-4">
                    <template x-for="(slide, index) in slides" :key="index">
                        <div class="rounded-lg border p-4 relative">
                            <button type="button" @click="removeSlide(index)" class="absolute top-2 right-2 text-red-400 hover:text-red-600 text-xs">&times; Remove</button>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs text-gray-500">Title</label>
                                    <input type="text" :name="`slides[${index}][title]`" x-model="slide.title" class="w-full rounded-lg border px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-gray-500">Link URL</label>
                                    <input type="text" :name="`slides[${index}][link]`" x-model="slide.link" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="/products" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs text-gray-500">Description</label>
                                    <input type="text" :name="`slides[${index}][description]`" x-model="slide.description" class="w-full rounded-lg border px-3 py-2 text-sm" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs text-gray-500">Slide Image</label>
                                    <p class="mb-1 text-xs text-gray-400">Recommended: 1920×600px (landscape). Max 2MB. If the image contains text, leave Title & Description blank.</p>
                                    {{-- Current image preview --}}
                                    <div x-show="slide.image && !slide._preview" class="mb-2">
                                        <img :src="'/storage/' + slide.image" class="h-28 w-full rounded-lg border object-cover" />
                                    </div>
                                    {{-- New image preview --}}
                                    <div x-show="slide._preview" class="mb-2">
                                        <img :src="slide._preview" class="h-28 w-full rounded-lg border object-cover" />
                                    </div>
                                    <input
                                        type="file"
                                        :name="`slides[${index}][image_file]`"
                                        accept="image/*"
                                        @change="previewImage($event, index)"
                                        class="w-full rounded-lg border px-3 py-1.5 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand-primary file:px-3 file:py-1 file:text-xs file:text-white file:cursor-pointer"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mb-4">
                    <button type="button" @click="addSlide()" class="rounded-lg border border-dashed px-4 py-2 text-sm text-gray-500 hover:bg-gray-50 w-full">+ Add Slide</button>
                </div>

                <button type="submit" class="rounded-lg bg-brand-primary px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent">Save Settings</button>
            </form>
        </div>

        <script>
            function carouselManager() {
                let existing = [];
                try {
                    existing = JSON.parse(@json($settings['carousel_slides'] ?? '[]'));
                } catch (e) {
                    existing = [];
                }
                if (!Array.isArray(existing)) existing = [];

                // Add internal preview property
                existing = existing.map(s => ({ ...s, _preview: null }));

                return {
                    slides: existing,
                    addSlide() {
                        this.slides.push({ title: '', description: '', link: '', image: '', _preview: null });
                    },
                    removeSlide(index) {
                        this.slides.splice(index, 1);
                    },
                    previewImage(event, index) {
                        const file = event.target.files[0];
                        if (file) {
                            this.slides[index]._preview = URL.createObjectURL(file);
                        }
                    }
                };
            }
        </script>
    </div>
@endsection
