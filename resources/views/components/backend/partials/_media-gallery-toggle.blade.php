<div class="card mt-6">
    <div class="card-body flex items-start gap-3">
        <x-synapse-toggler
            id="media_gallery"
            wire:model="form.mediaGallery"
            :inline="true"
            :checked="$form->mediaGallery"
            activeColor="green"
        />
        <div>
            <span class="text-sm">{{ __('cms::labels.media_gallery_enable') }}</span>
            <p class="form-help">{{ $help }}</p>
        </div>
    </div>
</div>
