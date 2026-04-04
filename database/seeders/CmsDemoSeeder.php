<?php

declare(strict_types=1);

namespace VmEngine\Cms\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Enums\ArticleStatus;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Models\CmsTag;
use VmEngine\Fm\Models\FmFile;

class CmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTags();
        $this->seedPages();
        $this->seedSections();
        $this->seedAlbums();
    }

    // -------------------------------------------------------------------------
    // Tags
    // -------------------------------------------------------------------------

    private function seedTags(): void
    {
        $tags = [
            ['name' => 'Technology', 'slug' => 'technology'],
            ['name' => 'Business',   'slug' => 'business'],
            ['name' => 'Design',     'slug' => 'design'],
            ['name' => 'Tutorial',   'slug' => 'tutorial'],
            ['name' => 'Laravel',    'slug' => 'laravel'],
            ['name' => 'PHP',        'slug' => 'php'],
            ['name' => 'News',       'slug' => 'news'],
            ['name' => 'Tips',       'slug' => 'tips'],
        ];

        foreach ($tags as $tag) {
            CmsTag::firstOrCreate(['slug' => $tag['slug']], ['name' => $tag['name']]);
        }
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    private function seedPages(): void
    {
        $about = $this->page('about-us', 'About Us', null, [
            'excerpt' => 'Learn more about who we are, our mission, and the team behind our work.',
            'body' => $this->pageBody('About Us', [
                'We are a passionate team of developers and designers committed to building exceptional digital experiences.',
                'Founded in 2018, we have grown from a small studio to a full-service digital agency working with clients across the globe.',
                'Our approach combines strategic thinking with technical excellence to deliver solutions that truly make a difference.',
            ]),
            'cover_image' => 'https://picsum.photos/seed/page-about/1200/400',
            'sort_order' => 1,
            'config' => ['show_title' => true, 'show_breadcrumb' => true, 'show_children' => true, 'children_layout' => 'grid'],
            'published_at' => now()->subDays(60),
        ]);

        $this->page('our-team', 'Our Team', $about, [
            'excerpt' => 'Meet the talented people who make our work possible.',
            'body' => $this->pageBody('Our Team', [
                'Our team is made up of experienced professionals from diverse backgrounds, all united by a shared passion for great work.',
                'From front-end developers to back-end engineers, UX designers to project managers — every member brings unique expertise to the table.',
            ]),
            'cover_image' => 'https://picsum.photos/seed/page-team/1200/400',
            'sort_order' => 1,
            'published_at' => now()->subDays(58),
        ]);

        $this->page('our-history', 'Our History', $about, [
            'excerpt' => 'A brief history of how we got to where we are today.',
            'body' => $this->pageBody('Our History', [
                '2018 — Founded as a small freelance collective focused on web development.',
                '2019 — Expanded to include design services and onboarded our first long-term clients.',
                '2021 — Moved into a dedicated office and grew the team to 12 full-time members.',
                '2023 — Launched our proprietary platform and expanded into international markets.',
            ]),
            'cover_image' => 'https://picsum.photos/seed/page-history/1200/400',
            'sort_order' => 2,
            'published_at' => now()->subDays(57),
        ]);

        $services = $this->page('services', 'Services', null, [
            'excerpt' => 'We offer a wide range of digital services to help your business grow.',
            'body' => $this->pageBody('Our Services', [
                'From web development and design to consulting and support — we provide end-to-end digital solutions tailored to your needs.',
                'Every project starts with a deep understanding of your goals. We work closely with our clients to ensure results that exceed expectations.',
            ]),
            'cover_image' => 'https://picsum.photos/seed/page-services/1200/400',
            'sort_order' => 2,
            'config' => ['show_title' => true, 'show_breadcrumb' => true, 'show_children' => true, 'children_layout' => 'grid'],
            'published_at' => now()->subDays(55),
        ]);

        $serviceChildren = [
            ['slug' => 'web-development', 'title' => 'Web Development', 'excerpt' => 'Custom web applications built with modern technologies.', 'days' => 50],
            ['slug' => 'ui-ux-design',    'title' => 'UI/UX Design',    'excerpt' => 'Beautiful, intuitive interfaces that delight your users.',    'days' => 49],
            ['slug' => 'consulting',      'title' => 'Consulting',      'excerpt' => 'Strategic guidance to help your business make smart technology decisions.', 'days' => 48],
        ];

        foreach ($serviceChildren as $i => $child) {
            $this->page($child['slug'], $child['title'], $services, [
                'excerpt' => $child['excerpt'],
                'body' => $this->pageBody($child['title'], [
                    $child['excerpt'],
                    'Our experienced team delivers high-quality work on time and within budget.',
                    'Contact us today to discuss how we can help you achieve your goals.',
                ]),
                'cover_image' => "https://picsum.photos/seed/page-{$child['slug']}/1200/400",
                'sort_order' => $i + 1,
                'published_at' => now()->subDays($child['days']),
            ]);
        }

        $this->page('contact', 'Contact', null, [
            'excerpt' => 'Get in touch with our team.',
            'body' => $this->pageBody('Get in Touch', [
                'We would love to hear from you. Whether you have a project in mind or just want to say hello, our team is ready to help.',
                '<strong>Email:</strong> hello@example.com<br><strong>Phone:</strong> +1 (555) 000-0000<br><strong>Address:</strong> 123 Main Street, Suite 400, New York, NY 10001',
            ]),
            'cover_image' => 'https://picsum.photos/seed/page-contact/1200/400',
            'sort_order' => 3,
            'config' => ['show_title' => true, 'show_breadcrumb' => false, 'show_children' => false, 'children_layout' => 'grid'],
            'published_at' => now()->subDays(45),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function page(string $slug, string $title, ?CmsPage $parent, array $attributes = []): CmsPage
    {
        $fullSlug = $parent ? $parent->full_slug.'/'.$slug : $slug;

        return CmsPage::firstOrCreate(
            ['slug' => $slug, 'parent_id' => $parent?->id],
            array_merge([
                'title' => $title,
                'full_slug' => $fullSlug,
                'status' => 'published',
                'published_at' => now(),
                'sort_order' => 0,
                'config' => ['show_title' => true, 'show_breadcrumb' => true, 'show_children' => false, 'children_layout' => 'grid'],
            ], $attributes)
        );
    }

    // -------------------------------------------------------------------------
    // Sections → Categories → Articles
    // -------------------------------------------------------------------------

    private function seedSections(): void
    {
        $tagMap = CmsTag::all()->keyBy('slug');

        // News section
        $news = CmsSection::firstOrCreate(
            ['slug' => 'news'],
            [
                'title' => 'News',
                'description' => '<p>Stay up to date with the latest news and announcements from our team and industry.</p>',
                'cover_image' => 'https://picsum.photos/seed/section-news/1200/400',
                'status' => 'published',
                'sort_order' => 1,
                'config' => ['order_by' => 'published_at', 'order_dir' => 'desc', 'show_date' => true, 'show_author' => false, 'show_thumbnail' => true, 'items_per_page' => 9],
            ]
        );

        $techCat = CmsCategory::firstOrCreate(
            ['slug' => 'technology', 'section_id' => $news->id],
            [
                'title' => 'Technology',
                'description' => 'Tech news and updates.',
                'status' => 'published',
                'sort_order' => 1,
                'inherit_config' => true,
            ]
        );

        $bizCat = CmsCategory::firstOrCreate(
            ['slug' => 'business', 'section_id' => $news->id],
            [
                'title' => 'Business',
                'description' => 'Business and industry news.',
                'status' => 'published',
                'sort_order' => 2,
                'inherit_config' => true,
            ]
        );

        $newsArticles = [
            [
                'title' => 'Laravel 12 Released With Major Performance Improvements',
                'slug' => 'laravel-12-released',
                'excerpt' => 'The Laravel team has announced the release of version 12, bringing significant performance gains and new developer features.',
                'category_id' => $techCat->id,
                'is_featured' => true,
                'tags' => ['technology', 'laravel', 'php'],
                'days_ago' => 3,
            ],
            [
                'title' => 'The Future of Web Development in 2025',
                'slug' => 'future-web-development-2025',
                'excerpt' => 'An in-depth look at emerging trends shaping the web development landscape this year.',
                'category_id' => $techCat->id,
                'is_featured' => true,
                'tags' => ['technology', 'news'],
                'days_ago' => 7,
            ],
            [
                'title' => 'Open Source Contributions Hit Record High',
                'slug' => 'open-source-record-high',
                'excerpt' => 'GitHub reports a record number of open source contributions in the past year, driven by AI-assisted tooling.',
                'category_id' => $techCat->id,
                'is_featured' => false,
                'tags' => ['technology', 'news'],
                'days_ago' => 14,
            ],
            [
                'title' => 'Digital Transformation Trends for SMEs',
                'slug' => 'digital-transformation-smes',
                'excerpt' => 'Small and medium enterprises are accelerating their digital adoption — here is what is driving the shift.',
                'category_id' => $bizCat->id,
                'is_featured' => true,
                'tags' => ['business', 'technology'],
                'days_ago' => 5,
            ],
            [
                'title' => 'Remote Work Reshapes Office Real Estate Market',
                'slug' => 'remote-work-real-estate',
                'excerpt' => 'The sustained shift to remote and hybrid work continues to have profound effects on commercial real estate.',
                'category_id' => $bizCat->id,
                'is_featured' => false,
                'tags' => ['business', 'news'],
                'days_ago' => 20,
            ],
        ];

        $this->createArticles($news, $newsArticles, $tagMap);

        // Blog section
        $blog = CmsSection::firstOrCreate(
            ['slug' => 'blog'],
            [
                'title' => 'Blog',
                'description' => '<p>Insights, tutorials, and tips from our development and design team.</p>',
                'cover_image' => 'https://picsum.photos/seed/section-blog/1200/400',
                'status' => 'published',
                'sort_order' => 2,
                'config' => ['order_by' => 'published_at', 'order_dir' => 'desc', 'show_date' => true, 'show_author' => false, 'show_thumbnail' => true, 'items_per_page' => 9],
            ]
        );

        $tipsCat = CmsCategory::firstOrCreate(
            ['slug' => 'tips', 'section_id' => $blog->id],
            [
                'title' => 'Tips & Tricks',
                'description' => 'Quick tips to improve your workflow.',
                'status' => 'published',
                'sort_order' => 1,
                'inherit_config' => true,
            ]
        );

        $tutCat = CmsCategory::firstOrCreate(
            ['slug' => 'tutorials', 'section_id' => $blog->id],
            [
                'title' => 'Tutorials',
                'description' => 'Step-by-step guides for developers and designers.',
                'status' => 'published',
                'sort_order' => 2,
                'inherit_config' => true,
            ]
        );

        $blogArticles = [
            [
                'title' => '10 Tips for Writing Cleaner PHP Code',
                'slug' => '10-tips-cleaner-php-code',
                'excerpt' => 'Practical advice to improve readability, maintainability, and performance of your PHP projects.',
                'category_id' => $tipsCat->id,
                'is_featured' => true,
                'tags' => ['php', 'tips', 'tutorial'],
                'days_ago' => 2,
            ],
            [
                'title' => 'How to Optimize Tailwind CSS for Production',
                'slug' => 'optimize-tailwind-css-production',
                'excerpt' => 'Reduce your CSS bundle size significantly with these Tailwind optimization techniques.',
                'category_id' => $tipsCat->id,
                'is_featured' => false,
                'tags' => ['design', 'tips'],
                'days_ago' => 10,
            ],
            [
                'title' => 'Building a REST API with Laravel 12 — Complete Guide',
                'slug' => 'rest-api-laravel-12-guide',
                'excerpt' => 'A comprehensive walkthrough of building, securing, and documenting a REST API using Laravel 12.',
                'category_id' => $tutCat->id,
                'is_featured' => true,
                'tags' => ['laravel', 'php', 'tutorial'],
                'days_ago' => 4,
            ],
            [
                'title' => 'Getting Started with Livewire v4',
                'slug' => 'getting-started-livewire-v4',
                'excerpt' => 'Everything you need to know to start building reactive UIs with Livewire v4 and Alpine.js.',
                'category_id' => $tutCat->id,
                'is_featured' => false,
                'tags' => ['laravel', 'tutorial'],
                'days_ago' => 18,
            ],
            [
                'title' => 'Mastering Tailwind v4 with the New CSS-First Configuration',
                'slug' => 'mastering-tailwind-v4',
                'excerpt' => 'Tailwind v4 introduces a radical new configuration approach — here is how to make the most of it.',
                'category_id' => $tutCat->id,
                'is_featured' => true,
                'tags' => ['design', 'tutorial'],
                'days_ago' => 25,
            ],
        ];

        $this->createArticles($blog, $blogArticles, $tagMap);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, CmsTag>  $tagMap
     * @param  array<int, array{title: string, slug: string, excerpt: string, category_id: int, is_featured: bool, tags: array<string>, days_ago: int}>  $articles
     */
    private function createArticles(CmsSection $section, array $articles, Collection $tagMap): void
    {
        foreach ($articles as $data) {
            $body = $this->articleBody($data['title'], $data['excerpt']);

            $article = CmsArticle::firstOrCreate(
                ['slug' => $data['slug'], 'section_id' => $section->id],
                [
                    'category_id' => $data['category_id'],
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'body' => $body,
                    'cover_image' => "https://picsum.photos/seed/art-{$data['slug']}/800/450",
                    'status' => ArticleStatus::Published,
                    'published_at' => now()->subDays($data['days_ago']),
                    'is_featured' => $data['is_featured'],
                    'sort_order' => 0,
                    'read_time' => CmsArticle::computeReadTime($body),
                ]
            );

            $tagIds = collect($data['tags'])
                ->map(fn ($slug) => $tagMap->get($slug)?->id)
                ->filter()
                ->all();

            if ($tagIds) {
                $article->tags()->syncWithoutDetaching($tagIds);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Albums & Images
    // -------------------------------------------------------------------------

    private function seedAlbums(): void
    {
        // Hero banner album
        $hero = CmsAlbum::firstOrCreate(
            ['slug' => 'hero'],
            [
                'title' => 'Hero Banner',
                'description' => 'Main homepage banner slides.',
                'type' => AlbumType::Banner,
                'status' => 'active',
                'sort_order' => 0,
            ]
        );

        $heroSlides = [
            ['title' => 'Welcome to Our World',       'caption' => 'Crafting digital experiences that inspire and engage.', 'color' => [30, 58, 138]],
            ['title' => 'Innovation Meets Design',    'caption' => 'We blend creativity with technology to build the future.', 'color' => [6, 78, 59]],
            ['title' => 'Your Vision, Our Expertise', 'caption' => 'Partnering with ambitious teams to create lasting impact.', 'color' => [67, 20, 7]],
            ['title' => 'Built for Performance',      'caption' => 'Fast, reliable, and scalable solutions for every challenge.', 'color' => [26, 32, 44]],
        ];

        foreach ($heroSlides as $i => $slide) {
            if ($hero->images()->count() > $i) {
                continue;
            }

            $file = $this->createImageFile("hero-slide-{$i}", 1200, 480, $slide['color'], $slide['title']);

            if ($file) {
                CmsAlbumImage::create([
                    'album_id' => $hero->id,
                    'file_id' => $file->id,
                    'title' => $slide['title'],
                    'caption' => $slide['caption'],
                    'sort_order' => $i,
                ]);
            }
        }

        // Company gallery album
        $gallery = CmsAlbum::firstOrCreate(
            ['slug' => 'company-gallery'],
            [
                'title' => 'Company Gallery',
                'description' => 'Photos from our office, events, and team activities.',
                'cover_image' => 'https://picsum.photos/seed/album-gallery/800/450',
                'type' => AlbumType::Normal,
                'status' => 'active',
                'sort_order' => 1,
            ]
        );

        $galleryImages = [
            ['title' => 'Office Space',        'caption' => 'Our open and collaborative workspace.'],
            ['title' => 'Team Standup',         'caption' => 'Daily morning standup session.'],
            ['title' => 'Product Launch Event', 'caption' => 'Celebrating our latest product launch.'],
            ['title' => 'Design Workshop',      'caption' => 'Interactive design thinking workshop.'],
            ['title' => 'Hackathon 2024',       'caption' => '48 hours of non-stop building.'],
            ['title' => 'End of Year Party',    'caption' => 'Team celebration and awards night.'],
        ];

        $galleryColors = [[99, 102, 241], [16, 185, 129], [245, 158, 11], [239, 68, 68], [14, 165, 233], [168, 85, 247]];

        foreach ($galleryImages as $i => $img) {
            if ($gallery->images()->count() > $i) {
                continue;
            }

            $file = $this->createImageFile("gallery-{$i}", 800, 600, $galleryColors[$i % count($galleryColors)], $img['title']);

            if ($file) {
                CmsAlbumImage::create([
                    'album_id' => $gallery->id,
                    'file_id' => $file->id,
                    'title' => $img['title'],
                    'caption' => $img['caption'],
                    'sort_order' => $i,
                ]);
            }
        }

        // Projects showcase album
        $projects = CmsAlbum::firstOrCreate(
            ['slug' => 'projects'],
            [
                'title' => 'Projects',
                'description' => 'A showcase of selected client projects and case studies.',
                'cover_image' => 'https://picsum.photos/seed/album-projects/800/450',
                'type' => AlbumType::Normal,
                'status' => 'active',
                'sort_order' => 2,
            ]
        );

        $projectImages = [
            ['title' => 'E-Commerce Platform',   'caption' => 'Full-stack solution for a retail client.'],
            ['title' => 'SaaS Dashboard',         'caption' => 'Analytics dashboard for a B2B software company.'],
            ['title' => 'Mobile Banking App',     'caption' => 'Secure and intuitive banking experience.'],
            ['title' => 'Healthcare Portal',      'caption' => 'Patient management system redesign.'],
        ];

        $projectColors = [[15, 23, 42], [21, 128, 61], [30, 64, 175], [126, 34, 206]];

        foreach ($projectImages as $i => $img) {
            if ($projects->images()->count() > $i) {
                continue;
            }

            $file = $this->createImageFile("project-{$i}", 900, 600, $projectColors[$i % count($projectColors)], $img['title']);

            if ($file) {
                CmsAlbumImage::create([
                    'album_id' => $projects->id,
                    'file_id' => $file->id,
                    'title' => $img['title'],
                    'caption' => $img['caption'],
                    'sort_order' => $i,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function createImageFile(string $name, int $width, int $height, array $rgb, string $label = ''): ?FmFile
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $relativePath = "dummy/{$name}.jpg";
        $storagePath = "fm/public/{$relativePath}";

        // Skip if file already exists in DB
        $existing = FmFile::where('folder_path', 'fm/public')
            ->where('relative_path', $relativePath)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Generate image
        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            return null;
        }

        $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        $text = imagecolorallocate($img, 255, 255, 255);

        imagefilledrectangle($img, 0, 0, $width, $height, (int) $bg);

        // Overlay gradient-like darker strip at bottom
        $strip = imagecolorallocatealpha($img, 0, 0, 0, 60);
        imagefilledrectangle($img, 0, (int) ($height * 0.7), $width, $height, (int) $strip);

        // Label text
        if ($label && $text !== false) {
            $fontSize = 5;
            $textX = 20;
            $textY = $height - 30;
            imagestring($img, $fontSize, $textX, $textY, $label, $text);
        }

        // Capture JPEG output
        ob_start();
        imagejpeg($img, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($img);

        if (! $jpeg) {
            return null;
        }

        Storage::disk('public')->put($storagePath, $jpeg);

        return FmFile::create([
            'disk' => 'public',
            'folder_path' => 'fm/public',
            'relative_path' => $relativePath,
            'filename' => "{$name}.jpg",
            'original_name' => "{$name}.jpg",
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size' => strlen($jpeg),
            'has_thumbnail' => false,
            'is_trashed' => false,
        ]);
    }

    /** @param string[] $paragraphs */
    private function pageBody(string $heading, array $paragraphs): string
    {
        $html = "<h2>{$heading}</h2>\n";
        foreach ($paragraphs as $p) {
            $html .= "<p>{$p}</p>\n";
        }

        return $html;
    }

    private function articleBody(string $title, string $excerpt): string
    {
        return <<<HTML
        <p class="lead">{$excerpt}</p>

        <h2>Introduction</h2>
        <p>This topic has become increasingly important in the industry over the past few years. Understanding the key concepts is essential for any professional looking to stay ahead of the curve.</p>

        <h2>Key Points</h2>
        <p>There are several important aspects to consider when approaching this subject. First, it is crucial to establish a solid foundation before diving into more advanced topics.</p>
        <ul>
            <li>Understanding the core principles and how they apply to real-world scenarios</li>
            <li>Recognising common patterns and best practices used by industry leaders</li>
            <li>Applying knowledge incrementally to build confidence and competence</li>
            <li>Staying current with evolving standards and community conventions</li>
        </ul>

        <h2>Practical Application</h2>
        <p>Theory is only valuable when it can be applied in practice. In real-world projects, the principles discussed here translate directly into measurable improvements in quality, performance, and maintainability.</p>
        <p>Teams that adopt these practices consistently report faster delivery cycles, fewer production issues, and greater developer satisfaction.</p>

        <h2>Conclusion</h2>
        <p>As the landscape continues to evolve, staying informed and adaptable remains the most valuable skill. We encourage you to explore further, experiment freely, and share what you learn with the community.</p>
        HTML;
    }
}
