{{-- Page builder template "full": receives $layout, $regions (key => label) and $editing. --}}
<div class="cms-builder cms-builder--full space-y-8">
    <x-cms::builder.region
        name="hero"
        :$layout
        :$regions
        :$editing
    />
    <x-cms::builder.region
        name="main"
        :$layout
        :$regions
        :$editing
    />
</div>
