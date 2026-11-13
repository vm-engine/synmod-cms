<div x-data="{ pageName: '{{ $this->title() }}' }">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="flex justify-between">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ __('cms::labels.tag_list') }}
                    </h3>
                </div>
                <div class="my-auto flex gap-3 px-5">
                    @canAccess('cms.tag.create')
                    <button
                        class="btn primary"
                        type="button"
                        @click="$dispatch('open-modal-tag-form')"
                        wire:click="$dispatch('reset-tag-form')"
                    >
                        <span class="ph ph-plus"></span> {{ __('cms::labels.add') }}
                    </button>
                    @endcanAccess
                    <input
                        class="form-input p-2"
                        type="text"
                        placeholder="{{ __('cms::labels.search') }}"
                        wire:model.live.debounce="q"
                    >
                </div>
            </div>

            <div class="max-w-full overflow-x-auto border-t border-gray-100 dark:border-gray-800">
                <table class="datatable min-w-full">
                    <thead>
                        <tr>
                            <th>
                                <p>No.</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.name') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.slug') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.articles') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.actions') }}</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->tagList as $i => $tag)
                            <tr>
                                <td>{{ $this->tagList->firstItem() + $i }}</td>
                                <td>{{ $tag->name }}</td>
                                <td><code class="text-xs">{{ $tag->slug }}</code></td>
                                <td>{{ $tag->articles_count }}</td>
                                <td>
                                    <div class="text-center">
                                        @canAccess('cms.tag.update')
                                        <button
                                            class="btn-icon warning has-tooltip group"
                                            type="button"
                                            @click="$dispatch('open-modal-tag-form')"
                                            wire:click="$dispatch('load-tag', { id: {{ $tag->id }} })"
                                        >
                                            <span class="ph ph-pencil-simple"></span>
                                            <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                        </button>
                                        @endcanAccess
                                        @canAccess('cms.tag.delete')
                                        <button
                                            class="btn-icon danger has-tooltip group"
                                            type="button"
                                            data-token="{{ $tag->delete_token }}"
                                            @click="$dispatch('confirm-dialog', {
                                                    title: '{{ __('cms::labels.delete_tag_title') }}',
                                                    message: '{{ __('cms::labels.delete_tag_message', ['name' => addslashes($tag->name)]) }}',
                                                    confirmText: '{{ __('cms::labels.yes_delete') }}',
                                                    confirmColor: 'danger',
                                                    icon: 'ph ph-trash',
                                                    wireMethod: 'delete',
                                                    wireParams: [$el.dataset.token],
                                                    wireComponent: '{{ $this->getId() }}'
                                                })"
                                        >
                                            <span class="ph ph-trash"></span>
                                            <span class="tooltip">{{ __('cms::labels.delete') }}</span>
                                        </button>
                                        @endcanAccess
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    class="py-8 text-center text-gray-400"
                                    colspan="5"
                                >{{ __('cms::labels.no_data') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex gap-2 border-t border-gray-100 p-3 dark:border-gray-800">
                <div class="pagination-container flex-1">{{ $this->tagList->links() }}</div>
            </div>
        </div>
    </div>

    {{-- Tag Form Modal --}}
    <x-synapse-modal
        name="tag-form"
        maxWidth="md"
    >
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold">{{ $form->tag ? __('cms::labels.edit_tag') : __('cms::labels.add_tag') }}
            </h3>

            <div x-data="cmsSlugField({{ $form->tag ? 'false' : 'true' }})">
                <label
                    class="form-label required"
                    for="tag_name"
                >{{ __('cms::labels.name') }}</label>
                <input
                    class="form-input @error('form.name') has-error @enderror"
                    id="tag_name"
                    type="text"
                    wire:model.blur="form.name"
                    @input="handleNameInput($event.target.value)"
                />
                @error('form.name')
                    <p class="form-error">{{ $message }}</p>
                @enderror

                <label
                    class="form-label required mt-3"
                    for="tag_slug"
                >{{ __('cms::labels.slug') }}</label>
                <input
                    class="form-input @error('form.slug') has-error @enderror"
                    id="tag_slug"
                    type="text"
                    wire:model.live="form.slug"
                    @input="autoSlug = false"
                />
                @error('form.slug')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button
                    class="btn secondary"
                    type="button"
                    @click="$dispatch('close-modal-tag-form')"
                >{{ __('cms::labels.cancel') }}</button>
                @canAccess('cms.tag.create|cms.tag.update')
                <button
                    class="btn primary"
                    type="button"
                    wire:click="save"
                >
                    <span class="ph ph-floppy-disk"></span> {{ __('cms::labels.save') }}
                </button>
                @endcanAccess
            </div>
        </div>
    </x-synapse-modal>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
        Alpine.data('cmsSlugField', (autoSlug) => ({
            autoSlug,
            generateSlug(v) {
                return v.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(
                    /-+/g, '-').trim();
            },
            handleNameInput(value) {
                if (this.autoSlug) {
                    this.$wire.set('form.slug', this.generateSlug(value));
                }
            },
        }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
</script>
