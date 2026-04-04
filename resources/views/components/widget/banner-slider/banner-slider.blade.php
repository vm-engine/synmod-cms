@if(!empty($slides))
<div
    class="cms-banner-slider"
    style="--slider-height: {{ $height }}"
    x-data="{
        current: 0,
        slides: @js($slides),
        autoplay: {{ $autoplay ? 'true' : 'false' }},
        interval: {{ $interval }},
        timer: null,
        init() {
            if (this.autoplay && this.slides.length > 1) {
                this.startTimer();
            }
        },
        startTimer() {
            this.timer = setInterval(() => this.next(), this.interval);
        },
        stopTimer() {
            if (this.timer) { clearInterval(this.timer); this.timer = null; }
        },
        go(index) {
            this.stopTimer();
            this.current = index;
            if (this.autoplay) this.startTimer();
        },
        prev() {
            this.stopTimer();
            this.current = (this.current - 1 + this.slides.length) % this.slides.length;
            if (this.autoplay) this.startTimer();
        },
        next() {
            this.current = (this.current + 1) % this.slides.length;
        },
    }"
    @mouseenter="stopTimer()"
    @mouseleave="autoplay && slides.length > 1 && startTimer()"
>
    {{-- Slides --}}
    <div class="cms-banner-slider__track">
        <template x-for="(slide, idx) in slides" :key="idx">
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
                    :alt="slide.title || ''"
                    loading="lazy"
                />

                @if($showCaption)
                <div
                    class="cms-banner-slider__caption"
                    x-show="slide.title || slide.caption"
                >
                    <p class="cms-banner-slider__caption-title" x-text="slide.title" x-show="slide.title"></p>
                    <p class="cms-banner-slider__caption-text" x-text="slide.caption" x-show="slide.caption"></p>
                </div>
                @endif
            </div>
        </template>
    </div>

    @if($showControls)
    {{-- Prev button --}}
    <button
        class="cms-banner-slider__control cms-banner-slider__control--prev"
        type="button"
        @click="prev()"
        x-show="slides.length > 1"
        aria-label="Previous"
    >
        <span class="fa-solid fa-chevron-left"></span>
    </button>

    {{-- Next button --}}
    <button
        class="cms-banner-slider__control cms-banner-slider__control--next"
        type="button"
        @click="next()"
        x-show="slides.length > 1"
        aria-label="Next"
    >
        <span class="fa-solid fa-chevron-right"></span>
    </button>
    @endif

    @if($showIndicators)
    {{-- Dot indicators --}}
    <div class="cms-banner-slider__indicators" x-show="slides.length > 1">
        <template x-for="(slide, idx) in slides" :key="idx">
            <button
                class="cms-banner-slider__dot"
                :class="{ 'cms-banner-slider__dot--active': idx === current }"
                type="button"
                @click="go(idx)"
                :aria-label="'Go to slide ' + (idx + 1)"
            ></button>
        </template>
    </div>
    @endif
</div>
@endif
