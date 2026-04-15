<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsSection;

class SectionArticles extends Component
{
    public ?CmsSection $sectionModel;

    /** @var Collection<int, CmsArticle> */
    public Collection $articles;

    public function __construct(
        public string $section = '',
        public int $limit = 6,
        public string $sort = 'latest',
        public string $layout = 'grid',
        public bool $showCategory = true,
        public bool $showExcerpt = true,
        public bool $showCover = true,
        public bool $showDate = true,
        /** Custom Blade view rendered per article (receives $article + $section). Fallback to default card when empty. */
        public string $card = '',
        /** Extra classes applied to the outer <div>. Pass '' (default) to keep built-in classes. */
        public string $wrapperClass = '',
        /** Extra classes applied to the grid <div>. */
        public string $gridClass = '',
        /** Skip the default <div> wrappers entirely — caller provides its own container. */
        public bool $bare = false,
    ) {
        $this->sectionModel = CmsSection::where('slug', $this->section)
            ->published()
            ->first();

        $query = CmsArticle::with(['category'])->published();

        if ($this->sectionModel) {
            $query->where('section_id', $this->sectionModel->id);
        }

        match ($this->sort) {
            'featured' => $query->featured()->orderBy('published_at', 'desc'),
            'oldest' => $query->orderBy('published_at', 'asc'),
            'az' => $query->orderBy('title', 'asc'),
            'za' => $query->orderBy('title', 'desc'),
            default => $query->orderBy('published_at', 'desc'),
        };

        $this->articles = $query->limit($this->limit)->get();
    }

    public function render(): View
    {
        return view('cms::components.widget.section-articles.section-articles');
    }
}
