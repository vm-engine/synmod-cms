<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsTag;

class TagArticles extends Component
{
    /** @var Collection<int, CmsTag> */
    public Collection $tagModels;

    /** @var Collection<int, CmsArticle> */
    public Collection $articles;

    public function __construct(
        public string $tags = '',
        public string $match = 'any',
        public int $limit = 6,
        public string $sort = 'latest',
        public string $layout = 'grid',
        public bool $showSection = true,
        public bool $showExcerpt = true,
        public bool $showCover = true,
        public bool $showDate = true,
    ) {
        $slugs = array_filter(array_map('trim', explode(',', $this->tags)));
        $this->tagModels = CmsTag::whereIn('slug', $slugs)->get();
        $tagIds = $this->tagModels->pluck('id');

        $query = CmsArticle::with(['section', 'category'])->published();

        if ($tagIds->isNotEmpty()) {
            if ($this->match === 'all') {
                foreach ($tagIds as $tagId) {
                    $query->whereHas('tags', fn ($q) => $q->where('cms_tags.id', $tagId));
                }
            } else {
                $query->whereHas('tags', fn ($q) => $q->whereIn('cms_tags.id', $tagIds));
            }
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
        return view('cms::components.widget.tag-articles.tag-articles');
    }
}
