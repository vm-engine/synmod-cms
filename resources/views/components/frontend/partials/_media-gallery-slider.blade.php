<div
    class="cms-banner-slider cms-media-gallery cms-media-gallery--slider"
    style="--slider-height: 28rem"
    x-data="cmsBannerSlider({{ json_encode($slides) }}, false, 5000, {{ json_encode(__('cms::labels.go_to_slide')) }})"
>
    <div class="cms-banner-slider__track">
        <template
            x-for="(slide, idx) in slides"
            :key="idx"
        >
            <div
                class="cms-banner-slider__slide"
                x-show="idx === current"
                x-transition:enter="cms-banner-slider__slide--enter"
                x-transition:enter-start="cms-banner-slider__slide--hidden"
                x-transition:enter-end="cms-banner-slider__slide--visible"
                x-transition:leave="cms-banner-slider__slide--enter"
                x-transition:leave-start="cms-banner-slider__slide--visible"
                x-transition:leave-end="cms-banner-slider__slide--hidden"
            >
                <img
                    class="cms-banner-slider__image"
                    :src="slide.url"
                    :alt="slide.alt"
                    loading="lazy"
                />
                <div
                    class="cms-banner-slider__caption"
                    x-show="slide.caption"
                >
                    <p
                        class="cms-banner-slider__caption-text"
                        x-text="slide.caption"
                    ></p>
                </div>
            </div>
        </template>
    </div>

    <button
        class="cms-banner-slider__control cms-banner-slider__control--prev"
        type="button"
        @click="prev()"
        x-show="slides.length > 1"
        aria-label="{{ __('cms::labels.previous') }}"
    >
        <span class="ph ph-caret-left"></span>
    </button>
    <button
        class="cms-banner-slider__control cms-banner-slider__control--next"
        type="button"
        @click="next()"
        x-show="slides.length > 1"
        aria-label="{{ __('cms::labels.next') }}"
    >
        <span class="ph ph-caret-right"></span>
    </button>

    <div
        class="cms-banner-slider__indicators"
        x-show="slides.length > 1"
    >
        <template
            x-for="(slide, idx) in slides"
            :key="idx"
        >
            <button
                class="cms-banner-slider__dot"
                :class="{ 'cms-banner-slider__dot--active': idx === current }"
                type="button"
                @click="go(idx)"
                :aria-label="dotLabel(idx)"
            ></button>
        </template>
    </div>
</div>
@once
    @include('cms::components.widget.banner-slider._script')
@endonce
