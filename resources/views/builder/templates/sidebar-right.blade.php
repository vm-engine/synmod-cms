{{-- Page builder template "sidebar-right": receives $layout, $regions (key => label) and $editing. --}}
<div class="cms-builder cms-builder--sidebar-right space-y-8">
    <x-cms::builder.region
        name="hero"
        :$layout
        :$regions
        :$editing
    />
    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-cms::builder.region
                name="main"
                :$layout
                :$regions
                :$editing
            />
        </div>
        <aside>
            <x-cms::builder.region
                name="sidebar"
                :$layout
                :$regions
                :$editing
            />
        </aside>
    </div>
    <x-cms::builder.region
        name="footer-top"
        :$layout
        :$regions
        :$editing
    />
</div>
