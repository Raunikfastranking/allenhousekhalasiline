<?php

declare(strict_types=1);

require_once __DIR__ . '/environment.php';

if (!defined('BRANCH_ID')) {
    require_once dirname(__DIR__) . '/proxy/config.php';
}

if (!function_exists('cmsAssetUrl')) {
    require_once __DIR__ . '/api-adapters.php';
}

if (!function_exists('cmsLayoutItems')) {
    function cmsLayoutItems($layoutData): array
    {
        if (!is_array($layoutData)) {
            return [];
        }

        if (isset($layoutData['data']) && is_array($layoutData['data'])) {
            return $layoutData['data'];
        }

        if (isset($layoutData[0]) && is_array($layoutData[0])) {
            return $layoutData;
        }

        return [];
    }
}

if (!function_exists('cmsLayoutPart')) {
    function cmsLayoutPart($layoutData, string $slug): ?array
    {
        if ($slug === '') {
            return null;
        }

        foreach (cmsLayoutItems($layoutData) as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (($item['slug'] ?? null) === $slug || ($item['layout_slug'] ?? null) === $slug) {
                return $item;
            }
        }

        return null;
    }
}

if (!function_exists('cmsLayoutPartByType')) {
    function cmsLayoutPartByType($layoutData, string $type): ?array
    {
        if ($type === '') {
            return null;
        }

        foreach (cmsLayoutItems($layoutData) as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (($item['type'] ?? null) === $type || ($item['layout_type'] ?? null) === $type) {
                return $item;
            }
        }

        return null;
    }
}

if (!function_exists('cmsLayoutMeta')) {
    function cmsLayoutMeta($layoutData, string $slug): array
    {
        $part = cmsLayoutPart($layoutData, $slug);

        return is_array($part['meta_data'] ?? null) ? $part['meta_data'] : [];
    }
}

if (!function_exists('cmsFooterLinkGroup')) {
    function cmsFooterLinkGroup($linkData, string $groupSlug): array
    {
        if ($groupSlug === '') {
            return [];
        }

        $groups = [];
        if (is_array($linkData)) {
            $groups = isset($linkData['data']) && is_array($linkData['data']) ? $linkData['data'] : $linkData;
        }

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            if (($group['slug'] ?? null) === $groupSlug || ($group['group_slug'] ?? null) === $groupSlug) {
                return $group;
            }
        }

        return [];
    }
}

if (!function_exists('cmsKhalasilineHierarchicalFooterGroups')) {
  /**
   * @return list<array<string, mixed>>
   */
  function cmsKhalasilineHierarchicalFooterGroups(): array
  {
    static $groups = null;
    if ($groups !== null) {
      return $groups;
    }

    $groups = [];
    if (!defined('BRANCH_ID') || !function_exists('cms_curl_get_json') || !defined('CMS_API_URL')) {
      return $groups;
    }

    $response = cms_curl_get_json(
      rtrim(CMS_API_URL, '/') . '/public/link-groups/position/footer/hierarchical?branch_id=' . BRANCH_ID
    );
    if (is_array($response['data'] ?? null)) {
      $groups = $response['data'];
    }

    return $groups;
  }
}

if (!function_exists('cmsKhalasilineFooterLinkGroupBySlug')) {
  /**
   * Load a footer link group from /link-groups/{slug}, with branch-filtered tree from CMS.
   *
   * @return array<string, mixed>
   */
  function cmsKhalasilineFooterLinkGroupBySlug(string $slug): array
  {
    if ($slug === '' || !function_exists('cms_curl_get_json') || !defined('CMS_API_URL')) {
      return [];
    }

    $response = cms_curl_get_json(rtrim(CMS_API_URL, '/') . '/link-groups/' . rawurlencode($slug));
    $group = is_array($response['data'] ?? null) ? $response['data'] : [];

    foreach (cmsKhalasilineHierarchicalFooterGroups() as $branchGroup) {
      if (!is_array($branchGroup) || ($branchGroup['slug'] ?? '') !== $slug) {
        continue;
      }
      if (is_array($branchGroup['links'] ?? null)) {
        $group['links'] = $branchGroup['links'];
      }
      break;
    }

    return $group;
  }
}

if (!function_exists('cmsLoadJhansiLayoutData')) {
    function cmsLoadJhansiLayoutData(): array
    {
        static $loaded = false;
        static $layoutData = [];
        static $linkData = [];

        if ($loaded) {
            return ['layout' => $layoutData, 'links' => $linkData];
        }

        if (!defined('BRANCH_ID') || !function_exists('cms_curl_get_json') || !defined('CMS_API_URL')) {
            return ['layout' => [], 'links' => []];
        }

        $loaded = true;

        require_once dirname(__DIR__) . '/shared/includes/cms-layout-bootstrap.php';
        $boot = cmsLoadSiteLayoutBootstrap();
        $layoutData = $boot['layout'];
        $linkData = $boot['links'];

        return ['layout' => $layoutData, 'links' => $linkData];
    }
}

if (!function_exists('cmsKhalasilineSanitizeMenuName')) {
    function cmsKhalasilineSanitizeMenuName(string $name): string
    {
        $name = preg_replace('/\s*Page$/i', '', $name);
        $name = preg_replace('/\s*\(English\)\s*$/i', '', $name);
        $name = preg_replace('/\s+Paeg\s+(Jhansi|Khalasiline|Khalasi Line).*$/i', '', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);

        if (strcasecmp($name, 'About Us') === 0) {
            return 'About';
        }

        return $name;
    }
}

if (!function_exists('cmsKhalasilineNormalizeMenuChildren')) {
    /**
     * Branch menu API nests via "children" (max 2 levels). Full menu uses "descendants" for deeper trees.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    function cmsKhalasilineNormalizeMenuChildren(array $item): array
    {
        $children = $item['children'] ?? [];
        $descendants = $item['descendants'] ?? [];
        if ((!is_array($children) || $children === []) && is_array($descendants) && $descendants !== []) {
            $item['children'] = $descendants;
        }
        unset($item['descendants']);

        $normalized = [];
        foreach ($item['children'] ?? [] as $child) {
            if (is_array($child)) {
                $normalized[] = cmsKhalasilineNormalizeMenuChildren($child);
            }
        }
        if ($normalized !== []) {
            $item['children'] = $normalized;
        }

        return $item;
    }
}

if (!function_exists('cmsKhalasilineSanitizeMenuItem')) {
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    function cmsKhalasilineSanitizeMenuItem(array $item): array
    {
        $item['name'] = cmsKhalasilineSanitizeMenuName((string) ($item['name'] ?? ''));

        $children = $item['children'] ?? [];
        if (is_array($children) && $children !== []) {
            $sanitized = [];
            foreach ($children as $child) {
                if (is_array($child)) {
                    $sanitized[] = cmsKhalasilineSanitizeMenuItem($child);
                }
            }
            $item['children'] = $sanitized;
        }

        return $item;
    }
}

if (!function_exists('cmsKhalasilineNavItems')) {
    /**
     * @return list<array<string, mixed>>
     */
    function cmsKhalasilineNavItems(): array
    {
        static $items = null;
        if ($items !== null) {
            return $items;
        }

        if (!function_exists('cmsNavItems')) {
            require_once __DIR__ . '/cms-bootstrap.php';
        }

        $items = cmsNavItems();
        if ($items !== []) {
            foreach ($items as $i => $item) {
                $items[$i] = cmsKhalasilineSanitizeMenuItem($item);
            }
            return $items;
        }

        return [];
    }
}

if (!function_exists('cmsKhalasilineMenuDropdownSvg')) {
    function cmsKhalasilineMenuDropdownSvg(string $extraClass = ''): string
    {
        $class = trim('transition fill-white sm:fill-black group-hover:fill-white ' . $extraClass);

        return '<svg width="8" height="5" viewBox="0 0 8 5" xmlns="http://www.w3.org/2000/svg" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">'
            . '<path fill-rule="evenodd" clip-rule="evenodd" d="M0.699203 0.281451C0.825931 0.154761 0.997788 0.0835904 1.17698 0.0835904C1.35617 0.0835904 1.52803 0.154761 1.65476 0.281451L3.88011 2.5068L6.10546 0.281451C6.1678 0.216906 6.24237 0.165424 6.32482 0.130007C6.40727 0.0945895 6.49594 0.0759472 6.58567 0.0751674C6.6754 0.0743877 6.76439 0.091486 6.84744 0.125465C6.93049 0.159444 7.00595 0.209623 7.0694 0.273074C7.13285 0.336525 7.18303 0.411978 7.21701 0.495029C7.25099 0.57808 7.26808 0.667067 7.2673 0.756797C7.26652 0.846527 7.24788 0.935203 7.21246 1.01765C7.17705 1.1001 7.12557 1.17467 7.06102 1.23701L4.35789 3.94014C4.23116 4.06683 4.0593 4.138 3.88011 4.138C3.70092 4.138 3.52906 4.06683 3.40233 3.94014L0.699203 1.23701C0.572513 1.11028 0.501343 0.938422 0.501343 0.759229C0.501343 0.580035 0.572513 0.408179 0.699203 0.281451Z"/>'
            . '</svg>';
    }
}

if (!function_exists('renderJhansiMenu')) {
    /**
     * @param list<array<string, mixed>> $items
     */
    function renderJhansiMenu(array $items, string $variant = 'desktop', bool $isSub = false, bool $appendEnquiry = false): void
    {
        if ($items === [] && !($appendEnquiry && $variant === 'desktop' && !$isSub)) {
            return;
        }

        $isMobile = $variant === 'mobile';
        $linkClass = 'hover:text-white hover:bg-[#002a5b] p-2 transition-all';
        $parentLinkClass = $isMobile
            ? 'group hover:text-white hover:bg-[#002a5b] p-2 transition-all flex items-center gap-1'
            : 'group hover:text-white hover:bg-[#002a5b] p-2 transition-all flex items-center gap-1';

        if (!$isSub) {
            echo $isMobile ? '<ul class="mobile-nav">' : '<ul>';
        } elseif ($isMobile) {
            echo '<ul class="mobile-submenu">';
        } else {
            echo '<ul>';
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $children = $item['children'] ?? [];
            if (!is_array($children)) {
                $children = [];
            }
            $hasChildren = $children !== [];
            $name = (string) ($item['name'] ?? '');
            $target = htmlspecialchars((string) ($item['target'] ?? '_self'), ENT_QUOTES, 'UTF-8');
            $href = cmsMenuUrl($item['url'] ?? '#');
            if ($hasChildren) {
                $href = '#';
            }
            $hrefAttr = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');

            if ($isMobile) {
                if ($hasChildren) {
                    echo '<li class="mobile-nav-item has-submenu">';
                    echo '<a href="' . $hrefAttr . '" class="' . $parentLinkClass . ' nav-parent-link">';
                    echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    echo cmsKhalasilineMenuDropdownSvg('arrow group-hover:fill-white');
                    echo '</a>';
                    renderJhansiMenu($children, 'mobile', true);
                    echo '</li>';
                } else {
                    echo '<li><a href="' . $hrefAttr . '" target="' . $target . '" class="' . $linkClass . '">';
                    echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    echo '</a></li>';
                }
                continue;
            }

            echo $hasChildren ? '<li class="nav-has-children">' : '<li>';
            if ($hasChildren) {
                echo '<a href="' . $hrefAttr . '" class="' . $parentLinkClass . ' nav-parent-link" aria-haspopup="true" aria-expanded="false">';
                echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                echo cmsKhalasilineMenuDropdownSvg();
                echo '</a>';
                renderJhansiMenu($children, 'desktop', true);
            } else {
                echo '<a href="' . $hrefAttr . '" target="' . $target . '" class="' . $linkClass . '">';
                echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                echo '</a>';
            }
            echo '</li>';
        }

        if ($appendEnquiry && $variant === 'desktop' && !$isSub) {
            echo '<li class="desktop-enquiry-item desktopEnquiry shrink-0 ml-12 -mt-px list-none">';
            echo '<button type="button" onclick="openPopup()" class="px-4 py-2 bg-blue-main text-white whitespace-nowrap">Enquiry Form</button>';
            echo '</li>';
        }

        echo '</ul>';
    }
}

if (!function_exists('cmsKhalasilineScrollingText')) {
    function cmsKhalasilineScrollingText(): string
    {
        static $text = null;
        if ($text !== null) {
            return $text;
        }

        $text = '';
        if (!defined('BRANCH_ID') || !function_exists('cms_curl_get_json') || !defined('CMS_API_URL')) {
            return $text;
        }

        $response = cms_curl_get_json(rtrim(CMS_API_URL, '/') . '/scrolling-texts/branch/' . BRANCH_ID);
        $items = [];
        if (is_array($response)) {
            $items = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $content = trim((string) ($item['content'] ?? ''));
            if ($content !== '') {
                $text = $content;
                break;
            }
        }

        return $text;
    }
}

if (!function_exists('cmsMenuUrl')) {
    function cmsMenuUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '#';
        }
        if (str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        if (preg_match('#^https?://#i', $url)) {
            if (function_exists('is_app_local') && is_app_local()) {
                $path = parse_url($url, PHP_URL_PATH) ?: '';
                $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
                if (
                    $path !== '' &&
                    (
                        $host === 'allenhousekhalasiline.com'
                        || $host === 'www.allenhousekhalasiline.com'
                        || $host === 'localhost'
                        || $host === '127.0.0.1'
                    )
                ) {
                    return site_base_url() . ltrim($path, '/');
                }
            }

            return $url;
        }

        if ($url === '/') {
            return site_base_url();
        }

        return site_base_url() . ltrim($url, '/');
    }
}

if (!function_exists('cmsKhalasilineFooterAddressText')) {
    /**
     * Footer address from layout meta, with contact page CMS fallback.
     */
    function cmsKhalasilineFooterAddressText(array $footerMeta, array $headerMeta = []): string
    {
        $about = trim((string) ($footerMeta['footer_about'] ?? ''));
        if ($about !== '') {
            return $about;
        }

        $lines = [];
        foreach (['address_line1', 'address_line2'] as $key) {
            $line = trim((string) ($footerMeta[$key] ?? $headerMeta[$key] ?? ''));
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        $postal = trim((string) ($footerMeta['postal_code'] ?? $headerMeta['postal_code'] ?? ''));
        if ($lines !== [] && $postal !== '' && !str_contains(end($lines), $postal)) {
            $lines[count($lines) - 1] .= ($lines !== [] ? ' - ' : '') . $postal;
        }

        if ($lines !== []) {
            return implode("\n", $lines);
        }

        return cmsKhalasilineContactPageAddress();
    }
}

if (!function_exists('cmsKhalasilineContactPageAddress')) {
    function cmsKhalasilineContactPageAddress(): string
    {
        static $address = null;
        if ($address !== null) {
            return $address;
        }

        $address = '';
        if (!function_exists('cmsFetchKhalasilinePage')) {
            require_once __DIR__ . '/cms-page-helpers.php';
        }

        $page = cmsFetchKhalasilinePage('contact-us-page-khalasiline');
        foreach ($page['data']['sections'] ?? [] as $section) {
            if (!is_array($section)) {
                continue;
            }
            foreach ($section['columns'] ?? [] as $column) {
                if (!is_array($column)) {
                    continue;
                }
                $html = (string) ($column['content'] ?? '');
                if ($html === '') {
                    continue;
                }

                $text = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
                    $line = trim(preg_replace('/\s+/', ' ', $line) ?? $line);
                    if ($line === '' || stripos($line, "let's talk") !== false || stripos($line, 'lets talk') !== false) {
                        continue;
                    }
                    if (stripos($line, 'fill out the form') !== false) {
                        continue;
                    }
                    if (preg_match('/^\+?\d/', $line) || str_contains($line, '@')) {
                        continue;
                    }
                    if (preg_match('/khalasi|kanpur|20800[128]|swaroop|allenganj/i', $line)) {
                        $address = $line;
                        break 3;
                    }
                }
            }
        }

        return $address;
    }
}

if (!function_exists('renderKhalasilineFooterLinks')) {
    /**
     * @param list<array<string, mixed>> $links
     */
    function renderKhalasilineFooterLinks(array $links, string $linkClass, bool $isSub = false): void
    {
        if ($links === []) {
            return;
        }

        echo $isSub
            ? '<ul class="footer-submenu-list">'
            : '<ul class="footer-link-list grid grid-cols-1 gap-2 mt-2">';

        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }

            $title = trim((string) ($link['title'] ?? $link['name'] ?? ''));
            if ($title === '') {
                continue;
            }

            $url = cmsMenuUrl($link['url'] ?? '#');
            $target = htmlspecialchars((string) ($link['target'] ?? '_self'), ENT_QUOTES, 'UTF-8');
            $children = $link['children'] ?? $link['descendants'] ?? [];
            if (!is_array($children)) {
                $children = [];
            }
            $hasChildren = $children !== [];

            echo '<li class="footer-link-item">';

            if ($hasChildren) {
                echo '<button type="button" class="footer-submenu-toggle ' . htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8') . ' inline-flex items-center justify-center gap-1 max-w-full" aria-expanded="false" aria-label="Toggle ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' submenu">';
                echo '<span>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>';
                echo '<span class="footer-submenu-caret text-[10px] leading-none shrink-0" aria-hidden="true">▼</span>';
                echo '</button>';
                echo '<div class="footer-submenu hidden">';
                renderKhalasilineFooterLinks($children, $linkClass, true);
                echo '</div>';
            } else {
                echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="' . $target . '" class="' . htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8') . '">';
                echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                echo '</a>';
            }

            echo '</li>';
        }

        echo '</ul>';
    }
}

if (!function_exists('cmsKhalasilineUniqueQuickLinks')) {
    /**
     * @param list<array<string, mixed>> $links
     * @return list<array<string, mixed>>
     */
    function cmsKhalasilineUniqueQuickLinks(array $links): array
    {
        $byTitle = [];
        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }
            $title = strtolower(trim((string) ($link['title'] ?? $link['name'] ?? '')));
            if ($title === '') {
                continue;
            }
            if (!isset($byTitle[$title])) {
                $byTitle[$title] = $link;
                continue;
            }
            $newUrl = (string) ($link['url'] ?? '');
            $oldUrl = (string) ($byTitle[$title]['url'] ?? '');
            if (str_contains($newUrl, 'allenj') && !str_contains($oldUrl, 'allenj')) {
                $byTitle[$title] = $link;
            }
        }

        return array_values($byTitle);
    }
}

if (!function_exists('cmsKhalasilineHeaderFooterParts')) {
    /**
     * @return array{header: array, footer: array, headerMeta: array, footerMeta: array}
     */
    function cmsKhalasilineHeaderFooterParts(?array $layoutData = null): array
    {
        static $cache = null;
        if ($cache !== null && $layoutData === null && ($cache['headerMeta']['logo_url'] ?? '') !== '') {
            return $cache;
        }

        if ($layoutData === null) {
            $layoutData = cmsLoadJhansiLayoutData()['layout'];
        }

        $headerPart = cmsLayoutPart($layoutData, 'header-aps-khalasiline')
            ?? cmsLayoutPartByType($layoutData, 'header')
            ?? [];
        $footerPart = cmsLayoutPart($layoutData, 'footer-aps-khalasiline')
            ?? cmsLayoutPartByType($layoutData, 'footer')
            ?? [];

        $headerMeta = is_array($headerPart['meta_data'] ?? null)
            ? $headerPart['meta_data']
            : cmsLayoutMeta($layoutData, 'header-aps-khalasiline');
        $footerMeta = is_array($footerPart['meta_data'] ?? null)
            ? $footerPart['meta_data']
            : cmsLayoutMeta($layoutData, 'footer-aps-khalasiline');

        $cache = [
            'header' => is_array($headerPart) ? $headerPart : [],
            'footer' => is_array($footerPart) ? $footerPart : [],
            'headerMeta' => $headerMeta,
            'footerMeta' => $footerMeta,
        ];

        return $cache;
    }
}

require_once __DIR__ . '/cms-prefetch.php';
