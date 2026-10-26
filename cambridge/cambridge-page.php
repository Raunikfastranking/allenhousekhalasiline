<?php

declare(strict_types=1);

/**
 * Cambridge Curriculum landing page — custom CMS renderer.
 *
 * Renders the CMS page sections (columns, cards, carousel, accordion,
 * video cards) into the approved Cambridge design. Content stays dynamic:
 * everything inside each section comes from the CMS API; only the layout,
 * the pathway stage tabs and the enquiry form shell live in this template.
 *
 * Wire-up (per project): see README.md in this folder.
 */

if (!function_exists('cmsCambridgeBrochureUrl')) {
    /**
     * "Download Brochure" target. Set $GLOBALS['cambridge_brochure_url']
     * in the page wire-up to a real PDF/page URL; default scrolls to the
     * enquiry section like the reference design.
     */
    function cmsCambridgeBrochureUrl(): string
    {
        $url = trim((string) ($GLOBALS['cambridge_brochure_url'] ?? ''));

        return $url !== '' ? $url : '#cb-contact';
    }
}

if (!function_exists('cmsCambridgeBrochureLinkAttrs')) {
    /**
     * Brochure anchor attributes. Renders a real link only when a brochure
     * URL is configured; otherwise the button stays inert (not clickable).
     */
    function cmsCambridgeBrochureLinkAttrs(): string
    {
        $url = cmsCambridgeBrochureUrl();
        if (str_starts_with($url, '#')) {
            return ' aria-disabled="true" tabindex="-1"';
        }

        return ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener"';
    }
}

if (!function_exists('cmsCambridgeBrochureDisabledClass')) {
    function cmsCambridgeBrochureDisabledClass(): string
    {
        return str_starts_with(cmsCambridgeBrochureUrl(), '#') ? ' cb-disabled' : '';
    }
}

if (!function_exists('cmsCambridgeIsActive')) {
    function cmsCambridgeIsActive(array $item): bool
    {
        if (array_key_exists('is_active', $item) && empty($item['is_active'])) {
            return false;
        }
        $status = strtolower(trim((string) ($item['status'] ?? 'active')));
        $publish = strtolower(trim((string) ($item['publish_status'] ?? 'published')));
        if ($status !== '' && !in_array($status, ['active', '1', 'enabled'], true)) {
            return false;
        }
        if ($publish !== '' && !in_array($publish, ['published', 'publish', 'active', '1', ''], true)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('cmsCambridgePrepareHtml')) {
    /** Clean CMS editor HTML (Quill wrappers, empty blocks) before output. */
    function cmsCambridgePrepareHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (function_exists('cmsPrepareCmsHtml')) {
            $html = cmsPrepareCmsHtml($html);
        }

        $html = preg_replace('/<span[^>]*\bclass="[^"]*\bql-ui\b[^"]*"[^>]*>\s*<\/span>/i', '', $html) ?? $html;

        // Strip empty paragraphs and empty divs (CMS columns often carry
        // copied markup whose inner content was cleared in the editor).
        for ($i = 0; $i < 3; $i++) {
            $next = preg_replace('/<p\b[^>]*>\s*(?:&nbsp;|\xC2\xA0|<br\s*\/?>)?\s*<\/p>/i', '', $html) ?? $html;
            $next = preg_replace('/<div\b[^>]*>\s*<\/div>/i', '', $next) ?? $next;
            if ($next === $html) {
                break;
            }
            $html = $next;
        }

        return trim($html);
    }
}

if (!function_exists('cmsCambridgeMediaUrl')) {
    function cmsCambridgeMediaUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (function_exists('cmsResolveMediaUrl')) {
            return cmsResolveMediaUrl($url);
        }
        if (function_exists('cmsFixMediaUrl')) {
            return cmsFixMediaUrl($url);
        }

        return $url;
    }
}

if (!function_exists('cmsCambridgeYoutubeId')) {
    /** YouTube video id from any common URL shape (youtu.be, watch?v=, embed, shorts). */
    function cmsCambridgeYoutubeId(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (function_exists('cmsYoutubeExtractVideoId')) {
            return cmsYoutubeExtractVideoId($url);
        }
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/|youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})#i', $url, $m)) {
            return $m[1];
        }
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if (preg_match('#^[A-Za-z0-9_-]{11}$#', $path)) {
            return $path;
        }

        return '';
    }
}

if (!function_exists('cmsCambridgePlainText')) {
    function cmsCambridgePlainText(string $html): string
    {
        if (function_exists('cmsPrepareCmsPlainText')) {
            return trim(cmsPrepareCmsPlainText($html));
        }

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}

if (!function_exists('cmsCambridgeSectionKey')) {
    /**
     * Map a CMS section to its design slot. Matching is title-based so the
     * same template works on sister projects whose section ids differ.
     */
    function cmsCambridgeSectionKey(array $section): string
    {
        if (function_exists('cmsSectionIsHero') && cmsSectionIsHero($section)) {
            return 'banner';
        }

        $title = strtolower(trim((string) ($section['title'] ?? '')));

        if (str_contains($title, 'global learning')) {
            return 'hero';
        }
        if (str_contains($title, 'about cambridge')) {
            return 'about';
        }
        if (str_contains($title, 'our approach')) {
            return 'approach';
        }
        if (str_contains($title, 'key benefit')) {
            return 'benefits';
        }
        if (str_contains($title, 'for parents')) {
            return 'parents';
        }
        if (str_contains($title, 'beyond the classroom')) {
            return 'campus';
        }
        if (str_contains($title, 'admission')) {
            return 'admissions';
        }
        if (str_contains($title, 'our community')) {
            return 'community';
        }
        if (str_contains($title, 'get started')) {
            return 'contact';
        }
        if (str_contains($title, 'faq')) {
            return 'faqs';
        }
        if (str_contains($title, 'accordion') || str_contains($title, 'pathway')) {
            return 'pathway';
        }

        return 'content';
    }
}

if (!function_exists('cmsCambridgeSplitColumns')) {
    /**
     * @return array{text: list<array<string, mixed>>, images: list<array<string, mixed>>}
     */
    function cmsCambridgeSplitColumns(array $section): array
    {
        $text = [];
        $images = [];
        $columns = $section['columns'] ?? [];
        if (!is_array($columns)) {
            return ['text' => [], 'images' => []];
        }

        $columns = array_values($columns);
        usort($columns, static function (array $a, array $b): int {
            return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
        });

        foreach ($columns as $column) {
            if (!is_array($column) || !cmsCambridgeIsActive($column)) {
                continue;
            }
            $type = strtolower((string) ($column['content_type'] ?? ''));
            $img = cmsCambridgeMediaUrl((string) ($column['image_url'] ?? $column['image_path'] ?? ''));
            if ($type === 'image' || ($type !== 'text' && $type !== 'video' && $img !== '')) {
                $images[] = $column;
            } else {
                $text[] = $column;
            }
        }

        return ['text' => $text, 'images' => $images];
    }
}

if (!function_exists('cmsCambridgeColumnHtml')) {
    function cmsCambridgeColumnHtml(array $column): string
    {
        foreach (['content', 'content_heading', 'description'] as $key) {
            $html = cmsCambridgePrepareHtml((string) ($column[$key] ?? ''));
            if ($html !== '') {
                return $html;
            }
        }

        return '';
    }
}

if (!function_exists('cmsCambridgeColumnImage')) {
    function cmsCambridgeColumnImage(array $column): string
    {
        return cmsCambridgeMediaUrl(
            (string) ($column['image_url'] ?? $column['image_path'] ?? $column['media_url'] ?? '')
        );
    }
}

if (!function_exists('cmsCambridgeSectionHeadHtml')) {
    /** Heading block of a section (content field or content_heading). */
    function cmsCambridgeSectionHeadHtml(array $section): string
    {
        $html = cmsCambridgePrepareHtml((string) ($section['content'] ?? ''));
        if ($html === '') {
            $html = cmsCambridgePrepareHtml((string) ($section['content_heading'] ?? ''));
        }

        // CMS copy-paste carries inline background-color; head should stay transparent.
        $html = (string) preg_replace_callback(
            '/\bstyle\s*=\s*("[^"]*"|\'[^\']*\')/i',
            static function (array $m): string {
                $styles = (string) preg_replace('/background-color\s*:\s*[^;"\']+;?/i', '', substr($m[1], 1, -1));
                return 'style=' . $m[1][0] . trim($styles) . $m[1][0];
            },
            $html
        );

        return $html;
    }
}

if (!function_exists('cmsCambridgeAssetsUrl')) {
    function cmsCambridgeAssetsUrl(string $file): string
    {
        $rel = 'cambridge/assets/' . ltrim($file, '/');
        $url = function_exists('site_asset_url') ? site_asset_url($rel) : '/' . $rel;

        $path = __DIR__ . '/assets/' . ltrim($file, '/');
        $mtime = is_file($path) ? (int) filemtime($path) : 0;
        if ($mtime > 0) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $mtime;
        }

        return $url;
    }
}

/* ------------------------------------------------------------------ */
/* Section renderers                                                   */
/* ------------------------------------------------------------------ */

if (!function_exists('cmsCambridgeRenderHero')) {
    function cmsCambridgeRenderHero(array $section): void
    {
        $parts = cmsCambridgeSplitColumns($section);
        $textHtml = '';
        foreach ($parts['text'] as $column) {
            $textHtml .= cmsCambridgeColumnHtml($column);
        }
        $imageUrl = '';
        $imageAlt = '';
        foreach ($parts['images'] as $column) {
            $imageUrl = cmsCambridgeColumnImage($column);
            if ($imageUrl !== '') {
                $imageAlt = cmsCambridgePlainText((string) ($column['image_alt'] ?? $column['title'] ?? ''));
                break;
            }
        }
        if ($textHtml === '' && $imageUrl === '') {
            return;
        }

        ?>
        <section class="hero hero-modern">
            <div class="hero-modern-copy">
                <?= $textHtml ?>
                <div class="hero-modern-actions">
                    <a class="hero-modern-primary<?= cmsCambridgeBrochureDisabledClass() ?>"<?= cmsCambridgeBrochureLinkAttrs() ?>>Download Brochure <span>&darr;</span></a>
                </div>
            </div>
            <?php if ($imageUrl !== ''): ?>
            <div class="hero-modern-visual" aria-hidden="true">
                <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8') ?>">
                <span class="hero-orbit orbit-one"></span>
                <span class="hero-orbit orbit-two"></span>
                <span class="hero-dot dot-one"></span><span class="hero-dot dot-two"></span>
            </div>
            <?php endif; ?>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderAbout')) {
    function cmsCambridgeRenderAbout(array $section): void
    {
        $parts = cmsCambridgeSplitColumns($section);
        $textHtml = '';
        foreach ($parts['text'] as $column) {
            $textHtml .= cmsCambridgeColumnHtml($column);
        }
        $imageUrl = '';
        $imageAlt = '';
        foreach ($parts['images'] as $column) {
            $imageUrl = cmsCambridgeColumnImage($column);
            if ($imageUrl !== '') {
                $imageAlt = cmsCambridgePlainText((string) ($column['image_alt'] ?? ''));
                break;
            }
        }
        if ($textHtml === '' && $imageUrl === '') {
            return;
        }
        ?>
        <section class="intro section cb-about">
            <div class="intro-top">
                <div class="intro-copy"><?= $textHtml ?></div>
                <?php if ($imageUrl !== ''): ?>
                <div class="intro-visual">
                    <img class="intro-img" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                    <div class="intro-badge"><strong>Cambridge</strong><span>Learning for the future</span></div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderPathway')) {
    /**
     * Pathway section: head copy from CMS; the four stage panels are part of
     * the approved design (static template content).
     */
    function cmsCambridgeRenderPathway(array $section): void
    {
        $headHtml = cmsCambridgeSectionHeadHtml($section);
        ?>
        <section class="pathway pathway-redesign section">
            <div class="pathway-head">
                <div class="pathway-head-copy"><?= $headHtml ?></div>
                <a class="pathway-brochure" href="https://myschool-assets.s3.ap-south-1.amazonaws.com/aps/pdf-details/pdfs/6adc8d7edc1cf_1792839038.pdf" target="_blank" rel="noopener" aria-label="Download fee structure">Download Fee Structure <span>&darr;</span></a>
            </div>

            <div class="pathway-tabs" role="tablist" aria-label="Cambridge stages">
                <button class="pathway-tab active" role="tab" aria-selected="true" aria-controls="cb-stage-early" data-stage-tab="early">Early Years</button>
                <button class="pathway-tab" role="tab" aria-selected="false" aria-controls="cb-stage-primary" data-stage-tab="primary">Primary</button>
                <button class="pathway-tab" role="tab" aria-selected="false" aria-controls="cb-stage-lower" data-stage-tab="lower">Lower Secondary</button>
                <button class="pathway-tab" role="tab" aria-selected="false" aria-controls="cb-stage-upper" data-stage-tab="upper">Upper Secondary &amp; Advanced</button>
            </div>

            <div class="pathway-panel active" id="cb-stage-early" role="tabpanel" data-stage-panel="early">
                <div class="pathway-main">
                    <span class="stage-label">AGES 3&ndash;5</span>
                    <h3>Cambridge Early Years</h3>
                    <p>A play-based start that builds language, early number sense, curiosity and social skills.</p>
                    <h4>Learning areas</h4>
                    <div class="subject-chips"><span>Communication &amp; language</span><span>Early mathematics</span><span>Personal &amp; social development</span><span>Physical development</span><span>Understanding the world</span><span>Creative expression</span></div>
                </div>
                <div class="pathway-side">
                    <span class="side-label">HOW PROGRESS IS SHARED</span>
                    <ul class="check-list"><li>Observation-based, with no formal exams</li><li>Focus on learning habits and confidence</li><li>Smooth transition into Stage 1</li></ul>
                </div>
            </div>

            <div class="pathway-panel" id="cb-stage-primary" role="tabpanel" data-stage-panel="primary" hidden>
                <div class="pathway-main">
                    <span class="stage-label">AGES 5&ndash;11 &middot; STAGES 1&ndash;6</span>
                    <h3>Cambridge Primary</h3>
                    <p>Strong foundations in language, number and scientific thinking, with plenty of hands-on learning.</p>
                    <h4>10 subjects</h4>
                    <div class="subject-chips"><span>English</span><span>English as a Second Language</span><span>Mathematics</span><span>Science</span><span>Global Perspectives</span><span>Computing</span><span>Digital Literacy</span><span>Art &amp; Design</span><span>Music</span><span>Physical Education</span></div>
                </div>
                <div class="pathway-side">
                    <span class="side-label">ASSESSMENT</span>
                    <ul class="check-list"><li>Ongoing classroom assessment against learning objectives</li><li>Progression Tests in Stages 3 to 6, marked by teachers in school</li><li>Primary Checkpoint at the end of Stage 6 for English, Mathematics and Science</li><li>Checkpoint provides a statement of achievement and detailed feedback</li></ul>
                </div>
            </div>

            <div class="pathway-panel" id="cb-stage-lower" role="tabpanel" data-stage-panel="lower" hidden>
                <div class="pathway-main">
                    <span class="stage-label">AGES 11&ndash;14 &middot; STAGES 7&ndash;9</span>
                    <h3>Cambridge Lower Secondary</h3>
                    <p>Deeper subject understanding and stronger independent study habits, preparing students for IGCSE.</p>
                    <h4>10 subjects</h4>
                    <div class="subject-chips"><span>English</span><span>English as a Second Language</span><span>Mathematics</span><span>Science</span><span>Global Perspectives</span><span>Computing</span><span>Digital Literacy</span><span>Art &amp; Design</span><span>Music</span><span>Physical Education</span></div>
                </div>
                <div class="pathway-side">
                    <span class="side-label">ASSESSMENT</span>
                    <ul class="check-list"><li>Progression Tests in Stages 7 to 9, marked by teachers</li><li>Lower Secondary Checkpoint at the end of Stage 9</li><li>Global Perspectives is assessed through a research report</li><li>Results can help guide subject choices for IGCSE</li></ul>
                </div>
            </div>

            <div class="pathway-panel" id="cb-stage-upper" role="tabpanel" data-stage-panel="upper" hidden>
                <div class="pathway-main">
                    <span class="stage-label">AGES 14&ndash;19</span>
                    <h3>Upper Secondary &amp; Advanced</h3>
                    <p>Cambridge IGCSE (ages 14&ndash;16) and Cambridge International AS &amp; A Level (ages 16&ndash;19) provide subject-focused qualifications for further study.</p>
                    <h4>Qualification pathway</h4>
                    <div class="qualification-cards"><div><strong>IGCSE</strong><small>Ages 14&ndash;16</small></div><div><strong>AS &amp; A Level</strong><small>Ages 16&ndash;19</small></div></div>
                </div>
                <div class="pathway-side">
                    <span class="side-label">AVAILABILITY</span>
                    <ul class="check-list"><li>Stages and subjects offered may differ by campus</li><li>Please contact admissions to confirm the current Cambridge stages available at Allenhouse</li></ul>
                </div>
            </div>

            <p class="small-note">Subject availability, stages and assessment arrangements may vary by campus and academic year. Please confirm current details with admissions.</p>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderApproach')) {
    function cmsCambridgeRenderApproach(array $section): void
    {
        $parts = cmsCambridgeSplitColumns($section);
        $textHtml = '';
        foreach ($parts['text'] as $column) {
            $textHtml .= cmsCambridgeColumnHtml($column);
        }
        $imageUrl = '';
        $imageAlt = '';
        foreach ($parts['images'] as $column) {
            $imageUrl = cmsCambridgeColumnImage($column);
            if ($imageUrl !== '') {
                $imageAlt = cmsCambridgePlainText((string) ($column['image_alt'] ?? ''));
                break;
            }
        }
        if ($textHtml === '' && $imageUrl === '') {
            return;
        }
        ?>
        <section class="approach section">
            <div class="approach-copy"><?= $textHtml ?></div>
            <?php if ($imageUrl !== ''): ?>
            <img class="approach-img" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
            <?php endif; ?>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderBenefits')) {
    function cmsCambridgeRenderBenefits(array $section): void
    {
        $headHtml = cmsCambridgeSectionHeadHtml($section);
        $media = $section['resolved_content']['media'] ?? [];
        if (!is_array($media)) {
            $media = [];
        }
        $media = array_values(array_filter($media, static function ($item): bool {
            return is_array($item) && cmsCambridgeIsActive($item);
        }));
        usort($media, static function (array $a, array $b): int {
            return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
        });
        if ($headHtml === '' && $media === []) {
            return;
        }
        ?>
        <section class="benefits section">
            <div class="benefits-head"><?= $headHtml ?></div>
            <?php if ($media !== []): ?>
            <div class="benefit-grid">
                <?php foreach ($media as $item):
                    $icon = cmsCambridgeMediaUrl((string) ($item['media_url'] ?? $item['media_file'] ?? ''));
                    $heading = cmsCambridgePrepareHtml((string) ($item['heading'] ?? ''));
                    $body = cmsCambridgePrepareHtml((string) ($item['content'] ?? ''));
                    $alt = cmsCambridgePlainText($heading !== '' ? $heading : (string) ($item['media_alt_text'] ?? ''));
                    ?>
                <article>
                    <?php if ($icon !== ''): ?>
                    <i class="benefit-ico"><img src="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>" loading="lazy"></i>
                    <?php else: ?>
                    <i class="benefit-ico">&#9678;</i>
                    <?php endif; ?>
                    <?= $heading !== '' ? $heading : '' ?>
                    <?= $body !== '' ? $body : '' ?>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderParents')) {
    function cmsCambridgeRenderParents(array $section): void
    {
        $columns = $section['columns'] ?? [];
        if (!is_array($columns)) {
            $columns = [];
        }
        usort($columns, static function (array $a, array $b): int {
            return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
        });

        $introHtml = '';
        $pointsHtml = '';
        foreach ($columns as $column) {
            if (!is_array($column) || !cmsCambridgeIsActive($column)) {
                continue;
            }
            $html = cmsCambridgeColumnHtml($column);
            if ($html === '') {
                continue;
            }
            if (str_contains($html, 'why-points')) {
                $pointsHtml .= $html;
            } else {
                $introHtml .= $html;
            }
        }
        if ($introHtml === '' && $pointsHtml === '') {
            return;
        }
        ?>
        <section class="why why-redesign section">
            <div class="why-editorial">
                <div class="why-intro"><?= $introHtml ?></div>
                <?= $pointsHtml ?>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderCampusCarousel')) {
    function cmsCambridgeRenderCampusCarousel(array $section): void
    {
        $headHtml = cmsCambridgeSectionHeadHtml($section);
        $items = $section['resolved_content']['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }
        $items = array_values(array_filter($items, static function ($item): bool {
            return is_array($item) && cmsCambridgeIsActive($item);
        }));
        usort($items, static function (array $a, array $b): int {
            return ((int) ($a['order_index'] ?? $a['order'] ?? 0)) <=> ((int) ($b['order_index'] ?? $b['order'] ?? 0));
        });
        if ($items === []) {
            return;
        }
        ?>
        <section class="campus-carousel section">
            <?php if ($headHtml !== ''): ?>
            <div class="carousel-head"><?= $headHtml ?></div>
            <?php endif; ?>
            <div class="campus-carousel-wrap">
                <button class="carousel-arrow prev" type="button" aria-label="Previous image">&lsaquo;</button>
                <div class="campus-track" aria-live="polite">
                    <?php foreach ($items as $item):
                        $img = cmsCambridgeMediaUrl((string) ($item['image_url'] ?? ''));
                        if ($img === '') {
                            continue;
                        }
                        $caption = cmsCambridgePrepareHtml((string) ($item['description'] ?? ''));
                        $alt = cmsCambridgePlainText((string) ($item['image_alt'] ?? $item['image_caption'] ?? $caption));
                        ?>
                    <article class="campus-slide">
                        <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                        <?php if ($caption !== ''): ?>
                        <div class="campus-caption"><?= $caption ?></div>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-arrow next" type="button" aria-label="Next image">&rsaquo;</button>
            </div>
            <div class="carousel-dots" aria-label="Carousel navigation"></div>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderAdmissions')) {
    function cmsCambridgeRenderAdmissions(array $section): void
    {
        $html = cmsCambridgeSectionHeadHtml($section);
        if ($html === '') {
            return;
        }
        ?>
        <section class="admissions section"><?= $html ?></section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderCommunity')) {
    /** Video testimonial reels — YouTube facade thumbnails that swap to the player on click. */
    function cmsCambridgeRenderCommunity(array $section): void
    {
        $headHtml = cmsCambridgeSectionHeadHtml($section);
        $media = $section['resolved_content']['media'] ?? [];
        if (!is_array($media)) {
            $media = [];
        }

        $videos = [];
        foreach ($media as $item) {
            if (!is_array($item) || !cmsCambridgeIsActive($item)) {
                continue;
            }
            $url = trim((string) ($item['media_url'] ?? $item['video_url'] ?? $item['media_file'] ?? ''));
            $videoId = cmsCambridgeYoutubeId($url);
            if ($videoId === '') {
                continue;
            }
            $videos[] = [
                'id' => $videoId,
                'badge' => cmsCambridgePlainText((string) ($item['media_alt_text'] ?? '')) ?: 'VIDEO',
                'heading' => cmsCambridgePrepareHtml((string) ($item['heading'] ?? '')),
                'body' => cmsCambridgePrepareHtml((string) ($item['content'] ?? '')),
            ];
        }

        if ($headHtml === '' && $videos === []) {
            return;
        }
        ?>
        <section class="testimonials section video-testimonials">
            <?php if ($headHtml !== ''): ?>
            <div class="video-testimonial-head"><div class="video-testimonial-copy"><?= $headHtml ?></div></div>
            <?php endif; ?>
            <?php if ($videos !== []): ?>
            <div class="reel-grid">
                <?php foreach ($videos as $video):
                    $idEsc = htmlspecialchars($video['id'], ENT_QUOTES, 'UTF-8');
                    $thumb = 'https://i.ytimg.com/vi/' . rawurlencode($video['id']) . '/hqdefault.jpg';
                    ?>
                <article class="reel-card">
                    <div class="reel-media" data-cb-yt="<?= $idEsc ?>"
                         style="background-image:linear-gradient(180deg,rgba(6,40,92,.05) 25%,rgba(6,40,92,.88) 100%),url('<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') ?>')">
                        <span class="reel-badge"><?= htmlspecialchars($video['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                        <button class="reel-play" type="button" aria-label="Play video">&#9654;</button>
                        <?php if ($video['heading'] !== '' || $video['body'] !== ''): ?>
                        <div class="reel-copy"><?= $video['heading'] ?><?= $video['body'] ?></div>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderContact')) {
    function cmsCambridgeRenderContact(array $section): void
    {
        $columns = $section['columns'] ?? [];
        if (!is_array($columns)) {
            $columns = [];
        }
        usort($columns, static function (array $a, array $b): int {
            return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
        });

        $copyHtml = '';
        foreach ($columns as $column) {
            if (!is_array($column) || !cmsCambridgeIsActive($column)) {
                continue;
            }
            $colTitle = strtolower((string) ($column['title'] ?? ''));
            if (str_contains($colTitle, 'enquiry') || str_contains($colTitle, 'form')) {
                continue;
            }
            $copyHtml .= cmsCambridgeColumnHtml($column);
        }

        ?>
        <section class="contact cb-contact" id="cb-contact">
            <div class="contact-copy">
                <?= $copyHtml ?>
                <div class="contact-actions">
                    <a class="button red" href="<?= htmlspecialchars((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/best-cambridge-school-in-lucknow'), ENT_QUOTES, 'UTF-8') ?>#AdmissionForm">Enquire About Admissions <span>&rarr;</span></a>
                    <a class="button outline<?= cmsCambridgeBrochureDisabledClass() ?>"<?= cmsCambridgeBrochureLinkAttrs() ?>>Download Brochure <span>&darr;</span></a>
                </div>
            </div>
            <div class="cb-enquiry-modal" id="cbEnquiryModal">
                <div class="cb-enquiry-dialog">
                    <button type="button" class="cb-enquiry-close" data-cb-enquiry-close aria-label="Close enquiry form">&times;</button>
                    <?php cmsCambridgeRenderEnquiryForm(); ?>
                </div>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderEnquiryForm')) {
    /**
     * The design's enquiry form, wired to the site's working admission flow.
     * Field ids match shared/includes/admission-procedure-form-init.php,
     * which foot.php already loads on every page.
     */
    function cmsCambridgeRenderEnquiryForm(): void
    {
        $includesDir = dirname(__DIR__) . '/includes';
        $includeBoard = !function_exists('cmsEnquiryFormIncludeBoard') || cmsEnquiryFormIncludeBoard();
        ?>
        <div id="admissionProcedureEnquiry" class="cb-enquiry">
            <div id="AdmissionFormPopup" class="cb-form-popup hidden" role="status">Form submitted successfully!</div>
            <form id="AdmissionForm" class="enquiry-form" method="post">
                <h3>Enquiry Form</h3>
                <div class="form-grid">
                    <label>Session*
                        <select name="session" id="asession" required>
                            <?php
                            $sessionFile = $includesDir . '/session-api.php';
                            $sessions = is_file($sessionFile) ? include $sessionFile : [];
                            if (is_array($sessions) && $sessions !== []) {
                                echo '<option value="" disabled selected>Select Session</option>';
                                foreach ($sessions as $item) {
                                    $sess = htmlspecialchars((string) (is_array($item) ? ($item['session'] ?? '') : ''), ENT_QUOTES, 'UTF-8');
                                    if ($sess !== '') {
                                        echo "<option value=\"{$sess}\">{$sess}</option>";
                                    }
                                }
                            } else {
                                echo '<option value="" disabled selected>Sessions unavailable</option>';
                            }
                            ?>
                        </select>
                    </label>
                    <?php if ($includeBoard): ?>
                    <label>Board / Curriculum*
                        <select name="board" id="aboard" required>
                            <?php
                            $boardFile = $includesDir . '/board-api.php';
                            if (is_file($boardFile)) {
                                include $boardFile;
                            } else {
                                echo '<option value="" disabled selected>Boards unavailable</option>';
                            }
                            ?>
                        </select>
                        <span id="aboard-error" class="cb-field-error hidden">Please select a board.</span>
                    </label>
                    <?php endif; ?>
                    <label>Grade*
                        <select name="grade" id="agrade" required>
                            <?php
                            $gradeFile = $includesDir . '/grade-api.php';
                            if (is_file($gradeFile)) {
                                include $gradeFile;
                            } else {
                                echo '<option value="" disabled selected>Grades unavailable</option>';
                            }
                            ?>
                        </select>
                        <span id="aclassError" class="cb-field-error hidden">Please select a class.</span>
                    </label>
                    <label>Student Name*
                        <input type="text" name="student-name" id="astudent_name" placeholder="Student Name" autocomplete="name" required>
                        <span id="astudent-error" class="cb-field-error hidden">Only letters and spaces allowed.</span>
                    </label>
                    <label>Parent's Name*
                        <input type="text" name="parent-name" id="aparent_name" placeholder="Parents Name" autocomplete="name">
                        <span id="aparent-error" class="cb-field-error hidden">Only letters and spaces allowed.</span>
                    </label>
                    <label>Mobile No.*
                        <input type="tel" name="mobile" id="amobile" placeholder="Mobile No." inputmode="tel" autocomplete="tel" required maxlength="10">
                        <span id="amobile-error" class="cb-field-error hidden">Please enter valid phone number</span>
                    </label>
                    <label>Email*
                        <input type="email" name="email" id="aemail" placeholder="E-mail" autocomplete="email">
                        <span id="aemail-error" class="cb-field-error hidden">Please enter a valid email address.</span>
                    </label>
                    <div class="cb-city-field">
                        <span class="cb-label">City*</span>
                        <div id="admissionCityWrapper" class="cb-city customSelect" data-cms-city-dropdown="1">
                            <select id="acity" name="city" class="cb-city-select hidden">
                                <option value="">Select City</option>
                                <?php
                                $cityFile = $includesDir . '/get-city.php';
                                $cityNames = [];
                                if (is_file($cityFile)) {
                                    $cmsGetCityMode = 'array';
                                    $cityNames = include $cityFile;
                                    if (!is_array($cityNames)) {
                                        $cityNames = [];
                                    }
                                }
                                foreach ($cityNames as $cityName) {
                                    $safe = htmlspecialchars((string) $cityName, ENT_QUOTES, 'UTF-8');
                                    echo "<option value=\"{$safe}\">{$safe}</option>";
                                }
                                if ($cityNames === []) {
                                    echo "<option value=''>Cities unavailable</option>";
                                }
                                ?>
                            </select>
                            <div id="admissionCityTrigger" class="cb-city-trigger">
                                <span class="selected-text">Select City</span>
                                <span class="cb-city-caret">&#9660;</span>
                            </div>
                            <div id="admissionCityDropdown" class="cb-city-dropdown hidden">
                                <input type="text" id="admissionCitySearch" placeholder="Search..." autocomplete="off">
                                <ul id="admissionCityList"></ul>
                            </div>
                        </div>
                        <span id="acity-error" class="cb-field-error hidden">Please select a city.</span>
                    </div>
                    <label>Pincode*
                        <input type="text" name="pincode" id="apincode" placeholder="Enter your Pincode" inputmode="numeric" maxlength="6" oninput="this.value=this.value.replace(/\D/g,'')" required>
                        <span id="apincode-error" class="cb-field-error hidden">Please enter a valid Pincode.</span>
                    </label>
                </div>
                <input type="hidden" id="source" name="source" value="">
                <label class="terms-check">
                    <input type="checkbox" id="admissionTerms" required>
                    <span>I agree to <a href="<?= htmlspecialchars(function_exists('site_base_url') ? site_base_url() . 'termsandconditions' : '/termsandconditions', ENT_QUOTES, 'UTF-8') ?>">Terms and Conditions</a>.</span>
                </label>
                <span id="admissionTermsError" class="cb-field-error hidden">You must agree to the terms and conditions.</span>
                <button class="submit" type="submit" id="AsubmitBtn">Submit Enquiry</button>
            </form>
        </div>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderFaqs')) {
    function cmsCambridgeRenderFaqs(array $section): void
    {
        $headHtml = cmsCambridgeSectionHeadHtml($section);
        $items = $section['resolved_content']['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }
        if ($headHtml === '' && $items === []) {
            return;
        }
        ?>
        <section class="faqs section">
            <?= $headHtml ?>
            <?php if ($items !== []): ?>
            <div class="faq-grid">
                <?php foreach ($items as $item):
                    if (!is_array($item)) {
                        continue;
                    }
                    $q = cmsCambridgePrepareHtml((string) ($item['title'] ?? ''));
                    $a = cmsCambridgePrepareHtml((string) ($item['content'] ?? ''));
                    if ($q === '') {
                        continue;
                    }
                    ?>
                <details>
                    <summary><?= $q ?></summary>
                    <?= $a !== '' ? $a : '' ?>
                </details>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php
    }
}

if (!function_exists('cmsCambridgeRenderContentSection')) {
    /** Fallback: any CMS section without a design slot renders as editor content. */
    function cmsCambridgeRenderContentSection(array $section): void
    {
        $html = function_exists('cmsSectionInnerHtml') ? cmsSectionInnerHtml($section) : '';
        $html = cmsCambridgePrepareHtml($html);
        if ($html === '') {
            return;
        }
        ?>
        <section class="cb-content section ql-snow">
            <div class="ql-editor cb-editor"><?= $html ?></div>
        </section>
        <?php
    }
}

/* ------------------------------------------------------------------ */
/* Page                                                                */
/* ------------------------------------------------------------------ */

if (!function_exists('cmsRenderCambridgePage')) {
    function cmsRenderCambridgePage(?array $page): void
    {
        if (!is_array($page)) {
            return;
        }

        cmsRenderPageBanner($page, []);
        cmsRenderPageBreadcrumbs($page, []);

        $sections = $page['data']['sections'] ?? [];
        if (!is_array($sections)) {
            $sections = [];
        }
        $sections = array_values(array_filter($sections, static function ($section): bool {
            return is_array($section) && cmsCambridgeIsActive($section);
        }));
        usort($sections, static function (array $a, array $b): int {
            return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
        });

        $cssUrl = htmlspecialchars(cmsCambridgeAssetsUrl('cambridge.css'), ENT_QUOTES, 'UTF-8');
        $jsUrl = htmlspecialchars(cmsCambridgeAssetsUrl('cambridge.js'), ENT_QUOTES, 'UTF-8');
        echo '<link rel="stylesheet" href="' . $cssUrl . '">';

        echo '<div class="cb-page">';

        $renderedIndices = [];

        // Render sections in CMS order — editors control placement.
        foreach ($sections as $index => $section) {
            $key = cmsCambridgeSectionKey($section);
            switch ($key) {
                case 'banner':
                    break;
                case 'hero':
                    cmsCambridgeRenderHero($section);
                    break;
                case 'about':
                    cmsCambridgeRenderAbout($section);
                    break;
                case 'pathway':
                    cmsCambridgeRenderPathway($section);
                    break;
                case 'approach':
                    cmsCambridgeRenderApproach($section);
                    break;
                case 'benefits':
                    cmsCambridgeRenderBenefits($section);
                    break;
                case 'parents':
                    cmsCambridgeRenderParents($section);
                    break;
                case 'campus':
                    cmsCambridgeRenderCampusCarousel($section);
                    break;
                case 'admissions':
                    cmsCambridgeRenderAdmissions($section);
                    break;
                case 'community':
                    cmsCambridgeRenderCommunity($section);
                    break;
                case 'contact':
                    cmsCambridgeRenderContact($section);
                    break;
                case 'faqs':
                    cmsCambridgeRenderFaqs($section);
                    break;
                default:
                    cmsCambridgeRenderContentSection($section);
                    break;
            }
            if ($key !== 'banner') {
                $renderedIndices[] = (int) $index;
            }
        }

        echo '</div>';
        echo '<script defer src="' . $jsUrl . '"></script>';

        if (!empty($renderedIndices) && function_exists('cmsSetRenderedSectionStartIndex')) {
            cmsSetRenderedSectionStartIndex(cmsRenderedStartIndexFromSectionIndices($renderedIndices));
        }
    }
}
