<?php
require_once __DIR__ . '/environment.php';
require_once dirname(__DIR__) . '/proxy/config.php';
$GLOBALS['school_assets_css_version'] = '0.51';
require dirname(__DIR__) . '/shared/includes/school-head-assets.php';
$apsCssBase = $GLOBALS['apsCssBase'] ?? site_asset_url('');
?>
<link rel="icon" type="image/x-icon"
    href="https://myschool-assets.s3.ap-south-1.amazonaws.com/uploads/ZORSdJXNGlZAVQJ4ZNWZsMEVZ1thgvBkL8JeOSXH.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"></noscript>
<meta name="robots" content="index, follow" />
<meta name="google-site-verification" content="EmdUK3Oq1YzEFjtrqR08m0VdNnd4uM5wGvpuj_JbDiU" />
<?php require dirname(__DIR__) . '/shared/includes/school-google-fonts.php'; ?>

<?php if (!empty($GLOBALS['khalasiline_hero_lcp_preload']) && is_array($GLOBALS['khalasiline_hero_lcp_preload'])): ?>
<?php foreach ($GLOBALS['khalasiline_hero_lcp_preload'] as $heroPreload): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars((string) ($heroPreload['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" media="<?= htmlspecialchars((string) ($heroPreload['media'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" fetchpriority="high">
<?php endforeach; ?>
<?php endif; ?>


<?php if (!empty($GLOBALS['khalasiline_approach_bg_preload'])): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars((string) $GLOBALS['khalasiline_approach_bg_preload'], ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>


<?php require dirname(__DIR__) . '/shared/includes/cms-editor-styles.php'; ?>

<script>
var baseUrl = "<?= rtrim(API_BASE_URL, '/') ?>";
var branchId = "<?= BRANCH_ID ?>";
var cmsBranchId = <?= (int) BRANCH_ID ?>;

function fixCmsAssetUrl(url) {
    if (!url) return '';
    url = String(url).trim();
    if (url.indexOf('https://cms.allenhouseschools.com/https://') === 0) {
        return url.replace('https://cms.allenhouseschools.com/', '');
    }
    if (/^https?:\/\//i.test(url)) return url;
    if (url.indexOf('//') === 0) return 'https:' + url;
    if (url.charAt(0) === '/') return baseUrl + url;
    return baseUrl + '/' + url.replace(/^\/+/, '');
}

function cmsGalleryMediaRawUrl(media) {
    return fixCmsAssetUrl(
        (media && (media.media_url || media.media_file || media.url || media.image_url)) || ''
    );
}

function extractImageUrlFromHtml(html) {
    if (!html) return '';
    var match = String(html).match(/<img[^>]+src=["']([^"']+)["']/i);
    if (!match) return '';
    var url = fixCmsAssetUrl(match[1]);
    if (/emoji\.php|facebook\.com\/images\/emoji/i.test(url)) return '';
    return url;
}

function pickGalleryCoverUrl(item) {
    if (item.coverUrl) return item.coverUrl;
    var medias = (item.medias || []).filter(function(m) { return m.urls; });
    var markedCover = medias.find(function(m) { return m.pivot && m.pivot.is_cover === '1'; });
    if (markedCover && markedCover.urls) return markedCover.urls;
    for (var j = 0; j < medias.length; j++) {
        if (medias[j].urls && !/emoji\.php/i.test(medias[j].urls)) return medias[j].urls;
    }
    return extractImageUrlFromHtml(item.description || item.achivementdescription || '');
}

function normalizeCmsGalleryItem(item) {
    var medias = [];
    (item.media || []).forEach(function(m, i) {
        var url = cmsGalleryMediaRawUrl(m);
        if (!url) return;
        medias.push({
            urls: url,
            pivot: { is_cover: (m.sort_order === 1 || i === 0) ? '1' : '0' }
        });
    });
    if (medias.length && !medias.some(function(m) { return m.pivot.is_cover === '1'; })) {
        medias[0].pivot.is_cover = '1';
    }
    var subType = (item.gallery_sub_type && item.gallery_sub_type.sub_type_name) || item.gallery_type || '';
    var videoMedia = (item.media || []).map(function(m) {
        return { url: cmsGalleryMediaRawUrl(m) };
    }).filter(function(m) { return m.url; });
    var description = item.content || item.description || '';
    var coverUrl = pickGalleryCoverUrl({ medias: medias, description: description });
    return {
        id: item.id,
        title: item.heading || item.gallery_title || '',
        achievementtitle: item.heading || item.gallery_title || '',
        achivementdescription: description,
        description: description,
        achevementdate: item.date,
        date: item.date,
        created_at: item.created_at || item.date,
        achivementtype: subType,
        type: subType,
        subTypeId: (item.gallery_sub_type && item.gallery_sub_type.id) || null,
        subTypeName: subType,
        galleryType: item.gallery_type || '',
        coverUrl: coverUrl,
        medias: medias,
        videogallerymedia: videoMedia
    };
}

var CMS_GALLERY_SUBTYPE = { achievements: 1, awards: 2, x_toppers: 3, xii_toppers: 4, photo: 5, photo_gallery: 5, video: 6, video_gallery: 6, print: 7, print_media: 7, events: 8 };

function isBoardTopperAlbum(item) {
    return /topper/i.test(item.achievementtitle || item.title || '');
}

function isClassXTopperAlbum(item) {
    var title = (item.achievementtitle || item.title || '').toLowerCase();
    if (/xii|xll|class\s*12|\b12th\b/.test(title)) return false;
    if (/\bclass\s*x\b/.test(title)) return true;
    if (/topper/i.test(title) && /\bx\b/.test(title)) return true;
    return false;
}

function isClassXiiTopperAlbum(item) {
    var title = (item.achievementtitle || item.title || '').toLowerCase();
    return /xii|xll|class\s*12|\b12th\b/.test(title);
}

window.onerror = function(msg, url, line, col, error) {
    console.error("GLOBAL ERROR:", {
        msg: msg,
        url: url,
        line: line,
        col: col,
        error: error
    });
};

window.onunhandledrejection = function(event) {
    console.error("PROMISE REJECTION:", event.reason);
};

</script>
<?php require_once dirname(__DIR__) . '/shared/includes/school-head-perf.php'; ?>
<?php if (schoolNeedsGalleryScripts()): ?>
<?php require dirname(__DIR__) . '/shared/includes/cms-gallery-client-init.php'; ?>
<script defer src="<?= htmlspecialchars(site_asset_url('assets/js/gallery-year-api.js'), ENT_QUOTES, 'UTF-8') ?>?v=19"></script>
<?php endif; ?>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-PC497XLW');</script>
<!-- End Google Tag Manager -->
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PC497XLW"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "@id": "https://allenhousekhalasiline.com/#website",
  "url": "https://allenhousekhalasiline.com/",
  "name": "Allenhouse Khalasi Line",
  "alternateName": "Allenhouse School Khalasi Line",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://allenhousekhalasiline.com/?s={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "School",
  "@id": "https://allenhousekhalasiline.com/#school",
  "name": "Allenhouse Khalasi Line",
  "alternateName": "Allenhouse School Khalasi Line",
  "url": "https://allenhousekhalasiline.com/",
  "logo": "https://allenhousekhalasiline.com/assets/images/logo.jpg",
  "image": "https://lh3.googleusercontent.com/p/AF1QipMDQ5GNdoVoCpRC6dj8kRqFDNBTS4A_v-zlVTmi=w408-h271-k-no",
  "telephone": "+91-7311132932",
  "email": "contact@allenhousekhalasiline.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "10/447, Sir Allen Ave, Gwai, Khalasi Line",
    "addressLocality": "Kanpur",
    "addressRegion": "Uttar Pradesh",
    "postalCode": "208002",
    "addressCountry": "IN"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "26.4867531",
    "longitude": "80.3299478"
  },
  "openingHoursSpecification": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": [
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday"
    ],
    "opens": "07:30",
    "closes": "14:00"
  },
  "sameAs": [
    "https://www.facebook.com/allenhousepublicschoolkhl/",
    "https://www.instagram.com/allenhousepublicschoolkhl/",
    "https://in.linkedin.com/school/allenhousepublicschoolkhl/",
    "https://www.youtube.com/@allenhousepublicschoolkhl"
  ]
}
</script>
<?php
require_once dirname(__DIR__) . '/shared/includes/cms-breadcrumb-schema.php';
cmsRenderBreadcrumbSchemaJsonLd();
$requestPath = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
$basePath = rtrim(site_base_url(), '/');
$homePaths = array_filter([
    '',
    '/',
    $basePath,
    $basePath . '/',
    $basePath . '/index.php',
]);
$isKhalasilineHomeRequest = in_array($requestPath, $homePaths, true);

$GLOBALS['cms_og_site_name'] = 'Allenhouse Khalasi Line';
$GLOBALS['cms_og_domain'] = 'allenhousekhalasiline.com';
$GLOBALS['cms_og_image_default'] = 'https://lh3.googleusercontent.com/p/AF1QipMDQ5GNdoVoCpRC6dj8kRqFDNBTS4A_v-zlVTmi=w408-h271-k-no';
if ($isKhalasilineHomeRequest) {
    $homeSeoDefaults = [
        'canonical_url' => 'https://allenhousekhalasiline.com/',
        'meta_title' => 'Best CBSE Board School in Kanpur | Allenhouse Khalasi Line',
        'document_title' => 'Best CBSE Board School in Kanpur | Allenhouse Khalasi Line',
        'meta_description' => 'Searching for a Best CBSE school in Kanpur? APS Khalasi Line delivers quality CBSE education, smart classrooms & student-focused learning. Book a seat now',
        'og_image' => $GLOBALS['cms_og_image_default'],
    ];
    $GLOBALS['cms_seo'] = array_merge($homeSeoDefaults, is_array($GLOBALS['cms_seo'] ?? null) ? $GLOBALS['cms_seo'] : []);
}
require dirname(__DIR__) . '/shared/includes/cms-head-og-dynamic.php';
?>
