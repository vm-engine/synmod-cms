    <script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('cmsBannerSlider', (slides, autoplay, interval, goToLabel) => ({
                current: 0,
                slides,
                autoplay,
                interval,
                goToLabel,
                timer: null,
                dotLabel(idx) {
                    return this.goToLabel.replace(':number', idx + 1);
                },
                init() {
                    if (this.autoplay && this.slides.length > 1) {
                        this.startTimer();
                    }
                },
                startTimer() {
                    this.timer = setInterval(() => this.next(), this.interval);
                },
                stopTimer() {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
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
            }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
    </script>
