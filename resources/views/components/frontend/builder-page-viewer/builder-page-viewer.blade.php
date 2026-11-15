<div class="cms-builder-page [html:has(&)]:overflow-x-clip [body:has(&)]:overflow-x-clip">
    @include($this->template['view'], [
        'layout' => $page->layout ?? [],
        'regions' => $this->template['regions'],
        'editing' => false,
    ])
</div>
