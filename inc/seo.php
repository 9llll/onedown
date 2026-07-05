<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * ═══════════════════════════════════════════════════════════════
 * Onedown SEO 模块
 *
 * 功能：
 * - 自定义 <title>、meta description/keywords
 * - Canonical URL & 分页 rel prev/next
 * - Open Graph / Twitter Card 标签
 * - JSON-LD 结构化数据（Organization、WebSite、Article、BreadcrumbList）
 * - 文章/页面 SEO Meta Box（自定义标题、描述、关键词、noindex）
 * - XML Sitemap 生成
 * - robots.txt 自定义
 * - 分类/标签页 SEO 描述
 * ═══════════════════════════════════════════════════════════════
 */

// ──────────────────────────────────────────────
// 初始化：注册钩子
// ──────────────────────────────────────────────
add_action('init', 'onedown_seo_init');

function onedown_seo_init()
{
    // 自定义 robots.txt
    if (_pz('seo_robots_txt_enabled', false)) {
        add_filter('robots_txt', 'onedown_seo_robots_txt', 10, 2);
    }

    // 如果没有启用主题的 title-tag，不需要额外处理
    if (! current_theme_supports('title-tag')) {
        return;
    }

    // 注册 SEO Meta Box（在后台）
    if (is_admin() && _pz('seo_meta_box', true)) {
        add_action('add_meta_boxes', 'onedown_seo_add_meta_box');
        add_action('save_post', 'onedown_seo_save_meta_box', 10, 2);
    }

    // ═══ 仅在启用对应功能时注册 wp_head 钩子 ═══

    // Canonical / Description / Keywords / Robots（至少启用一个才注册）
    if (_pz('seo_canonical', true) || _pz('seo_pagination', true) || _pz('seo_description', '') || _pz('seo_keywords', '')) {
        add_action('wp_head', 'onedown_seo_output_head', 1);
    }

    // Open Graph / Twitter Card
    if (_pz('seo_og_enabled', true)) {
        add_action('wp_head', 'onedown_seo_output_social', 2);
    }

    // JSON-LD 结构化数据
    if (_pz('seo_jsonld_enabled', true)) {
        add_action('wp_head', 'onedown_seo_output_jsonld', 3);
    }
}

// ──────────────────────────────────────────────
// wp_head 输出（优先级 1，早于其他标签）
// ──────────────────────────────────────────────
// 钩子注册已移至 onedown_seo_init()，仅在功能启用时注册

function onedown_seo_output_head()
{
    // ── 网站图标 Favicon ──
    $favicon = _pz('favicon');
    if ($favicon && !empty($favicon['url'])) {
        echo '<link rel="icon" href="' . esc_url($favicon['url']) . '" sizes="32x32">' . "\n";
        echo '<link rel="apple-touch-icon" href="' . esc_url($favicon['url']) . '">' . "\n";
    }

    // ── 规范 URL ──
    if (_pz('seo_canonical', true)) {
        $canonical = onedown_seo_get_canonical_url();
        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
        }
    }

    // ── 分页 rel prev/next ──
    if (_pz('seo_pagination', true)) {
        onedown_seo_pagination_rel();
    }

    // ── robots meta ──
    $robots = onedown_seo_get_robots();
    if ($robots) {
        echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";
    }

    // ── Meta Description ──
    $description = onedown_seo_get_description();
    if ($description) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    // ── Meta Keywords ──
    $keywords = onedown_seo_get_keywords();
    if ($keywords) {
        echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
    }
}

// ──────────────────────────────────────────────
// wp_head 输出（优先级 2 — Open Graph / Twitter）
// ──────────────────────────────────────────────
// 钩子注册已移至 onedown_seo_init()

function onedown_seo_output_social()
{
    if (! _pz('seo_og_enabled', true)) {
        return;
    }

    $data = onedown_seo_get_og_data();

    // Open Graph
    foreach ($data as $tag) {
        printf(
            '<meta property="%s" content="%s">' . "\n",
            esc_attr($tag['property']),
            esc_attr($tag['content'])
        );
    }

    // Twitter Card
    $twitter_card = _pz('seo_twitter_card', 'summary_large_image');
    echo '<meta name="twitter:card" content="' . esc_attr($twitter_card) . '">' . "\n";

    if (! empty($data['og:title'])) {
        echo '<meta name="twitter:title" content="' . esc_attr($data['og:title']['content']) . '">' . "\n";
    }
    if (! empty($data['og:description'])) {
        echo '<meta name="twitter:description" content="' . esc_attr($data['og:description']['content']) . '">' . "\n";
    }
    if (! empty($data['og:image'])) {
        echo '<meta name="twitter:image" content="' . esc_attr($data['og:image']['content']) . '">' . "\n";
    }

    $twitter_username = _pz('seo_twitter_username', '');
    if ($twitter_username) {
        echo '<meta name="twitter:site" content="@' . esc_attr($twitter_username) . '">' . "\n";
    }
}

// ──────────────────────────────────────────────
// wp_head 输出（优先级 3 — JSON-LD 结构化数据）
// ──────────────────────────────────────────────
// 钩子注册已移至 onedown_seo_init()

function onedown_seo_output_jsonld()
{
    if (! _pz('seo_jsonld_enabled', true)) {
        return;
    }

    $jsonld = array();

    // ── Organization ──
    $org = onedown_seo_get_organization_jsonld();
    if ($org) {
        $jsonld[] = $org;
    }

    // ── WebSite + SearchBox ──
    $website = onedown_seo_get_website_jsonld();
    if ($website) {
        $jsonld[] = $website;
    }

    // ── BreadcrumbList ──
    if (_pz('seo_jsonld_breadcrumb', true)) {
        $breadcrumb = onedown_seo_get_breadcrumb_jsonld();
        if ($breadcrumb) {
            $jsonld[] = $breadcrumb;
        }
    }

    // ── Article / NewsArticle ──
    if (is_singular() && _pz('seo_jsonld_article', true)) {
        $article = onedown_seo_get_article_jsonld();
        if ($article) {
            $jsonld[] = $article;
        }
    }

    if (empty($jsonld)) {
        return;
    }

    // 输出所有 JSON-LD
    foreach ($jsonld as $data) {
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
        echo '</script>' . "\n";
    }
}

// ──────────────────────────────────────────────
// 标题过滤器
// ──────────────────────────────────────────────
add_filter('pre_get_document_title', 'onedown_seo_document_title', 10);

function onedown_seo_document_title($title)
{
    // 首页
    if (is_home() || is_front_page()) {
        $seo_title = _pz('seo_title', '');
        if ($seo_title) {
            return $seo_title;
        }
        return $title;
    }

    // 文章/页面：优先使用自定义 SEO 标题
    if (is_singular()) {
        $post_id  = get_queried_object_id();
        $seo_title = get_post_meta($post_id, '_onedown_seo_title', true);
        if ($seo_title) {
            return $seo_title;
        }

        return $title;
    }

    // 分类/标签/归档
    if (is_category() || is_tag() || is_tax()) {
        $queried = get_queried_object();
        $prefix  = _pz('seo_archive_title_prefix', '');
        $name    = '';
        if ($queried && isset($queried->name)) {
            $name = $queried->name;
        }
        // 检查是否有自定义 SEO 标题
        if ($queried && isset($queried->term_id)) {
            $seo_title = get_term_meta($queried->term_id, '_onedown_seo_title', true);
            if ($seo_title) {
                return $seo_title;
            }
        }
        if ($prefix && $name) {
            return $prefix . $name;
        }
        return $title;
    }

    // 搜索页
    if (is_search()) {
        return sprintf(__('搜索：%s', 'onedown'), get_search_query()) . ' - ' . get_bloginfo('name');
    }

    // 404
    if (is_404()) {
        return __('页面未找到', 'onedown') . ' - ' . get_bloginfo('name');
    }

    // 授权管理页面
    if (get_query_var('od_license')) {
        $seo_title = _pz('seo_license_title', '');
        if ($seo_title) {
            return $seo_title;
        }
        return __('授权管理', 'onedown') . ' - ' . get_bloginfo('name');
    }

    // 用户中心页面
    if (get_query_var('user_center')) {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
        if (function_exists('onedown_get_tab_title')) {
            return onedown_get_tab_title($tab) . ' - ' . get_bloginfo('name');
        }
    }

    return $title;
}

// ──────────────────────────────────────────────
// 核心函数：获取 Meta Description
// ──────────────────────────────────────────────
function onedown_seo_get_description()
{
    // 首页
    if (is_home() || is_front_page()) {
        $desc = _pz('seo_description', '');
        if ($desc) {
            return $desc;
        }
        return get_bloginfo('description');
    }

    // 文章/页面
    if (is_singular()) {
        $post_id = get_queried_object_id();
        $seo_desc = get_post_meta($post_id, '_onedown_seo_description', true);
        if ($seo_desc) {
            return $seo_desc;
        }
        // 回退到摘要
        $post = get_post($post_id);
        if ($post && ! empty($post->post_excerpt)) {
            return wp_trim_words(strip_tags($post->post_excerpt), 160, '...');
        }
        // 回退到内容前 160 字
        if ($post && ! empty($post->post_content)) {
            return wp_trim_words(strip_tags($post->post_content), 160, '...');
        }
        return '';
    }

    // 分类/标签/归档
    if (is_category() || is_tag() || is_tax()) {
        if (! _pz('seo_archive_desc', true)) {
            return '';
        }
        $queried = get_queried_object();
        if ($queried && isset($queried->description) && $queried->description) {
            return wp_trim_words(strip_tags($queried->description), 160, '...');
        }
        return '';
    }

    // 授权管理页面
    if (get_query_var('od_license')) {
        $seo_desc = _pz('seo_license_description', '');
        if ($seo_desc) {
            return $seo_desc;
        }
        return __('查询授权状态、更换授权域名、自助购买授权码 - OneDown主题授权管理系统', 'onedown');
    }

    return '';
}

// ──────────────────────────────────────────────
// 核心函数：获取 Meta Keywords
// ──────────────────────────────────────────────
function onedown_seo_get_keywords()
{
    // 首页
    if (is_home() || is_front_page()) {
        $keywords = _pz('seo_keywords', '');
        if ($keywords) {
            return $keywords;
        }
        return '';
    }

    // 文章/页面
    if (is_singular()) {
        $post_id = get_queried_object_id();
        $seo_keywords = get_post_meta($post_id, '_onedown_seo_keywords', true);
        if ($seo_keywords) {
            return $seo_keywords;
        }
        // 回退到文章标签
        $tags = get_the_tags($post_id);
        if ($tags) {
            $tag_names = wp_list_pluck($tags, 'name');
            return implode(', ', $tag_names);
        }
        return '';
    }

    // 分类/标签页
    if (is_category() || is_tag() || is_tax()) {
        $queried = get_queried_object();
        if ($queried && isset($queried->name)) {
            return $queried->name;
        }
        return '';
    }

    // 授权管理页面
    if (get_query_var('od_license')) {
        $seo_keywords = _pz('seo_license_keywords', '');
        if ($seo_keywords) {
            return $seo_keywords;
        }
        return __('授权管理,主题授权,授权码查询,域名更换,自助购买', 'onedown');
    }

    return '';
}

// ──────────────────────────────────────────────
// 核心函数：获取 Canonical URL
// ──────────────────────────────────────────────
function onedown_seo_get_canonical_url()
{
    // 授权管理页面
    if (get_query_var('od_license')) {
        return home_url('/od-license.html');
    }

    // 文章/页面：优先使用自定义 Canonical
    if (is_singular()) {
        $post_id  = get_queried_object_id();
        $canonical = get_post_meta($post_id, '_onedown_seo_canonical', true);
        if ($canonical) {
            return $canonical;
        }
        return get_permalink($post_id);
    }

    if (is_home() || is_front_page()) {
        return home_url('/');
    }

    if (is_category() || is_tag() || is_tax()) {
        $queried = get_queried_object();
        if ($queried && isset($queried->term_id)) {
            $canonical = get_term_meta($queried->term_id, '_onedown_seo_canonical', true);
            if ($canonical) {
                return $canonical;
            }
            return get_term_link($queried);
        }
    }

    // 分页
    $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 0);
    if ($paged > 1) {
        // 让 WordPress 自己生成分页链接
        global $wp;
        $current_url = home_url(add_query_arg(array(), $wp->request));
        $canonical   = $current_url;
        // 移除 /page/N
        $canonical = preg_replace('/\/page\/\d+$/', '', $canonical);
        $canonical = preg_replace('/\/page\/\d+\/$/', '', $canonical);
        return trailingslashit($canonical);
    }

    // 搜索页不要 canonical
    if (is_search() || is_404()) {
        return '';
    }

    // 默认
    global $wp;
    $current_url = home_url(add_query_arg(array(), $wp->request));
    return $current_url ? trailingslashit($current_url) : '';
}

// ──────────────────────────────────────────────
// 核心函数：获取 robots 指令
// ──────────────────────────────────────────────
function onedown_seo_get_robots()
{
    // 搜索页禁止索引
    if (is_search()) {
        return 'noindex,follow';
    }

    // 404 禁止索引
    if (is_404()) {
        return 'noindex,nofollow';
    }

    // 文章/页面：优先使用自定义 noindex
    if (is_singular()) {
        $post_id = get_queried_object_id();
        $noindex = get_post_meta($post_id, '_onedown_seo_noindex', true);
        if ($noindex) {
            return 'noindex,' . (_pz('seo_robots', 'index,follow') === 'noindex,nofollow' ? 'nofollow' : 'follow');
        }
    }

    // 分类/标签：如果主题启用了 noindex
    if (is_category() || is_tag() || is_tax()) {
        $queried = get_queried_object();
        if ($queried && isset($queried->term_id)) {
            $noindex = get_term_meta($queried->term_id, '_onedown_seo_noindex', true);
            if ($noindex) {
                return 'noindex,follow';
            }
        }
    }

    // 分页页面（第2页+）
    $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 0);
    if ($paged > 1) {
        $global_robots = _pz('seo_robots', 'index,follow');
        if (strpos($global_robots, 'noindex') !== false) {
            return $global_robots;
        }
        // 默认分页页面建议 noindex
        return 'noindex,follow';
    }

    return _pz('seo_robots', 'index,follow');
}

// ──────────────────────────────────────────────
// 分页 rel prev/next
// ──────────────────────────────────────────────
function onedown_seo_pagination_rel()
{
    $paged = get_query_var('paged') ? (int) get_query_var('paged') : (get_query_var('page') ? (int) get_query_var('page') : 1);
    $numpages = 0;

    if (is_singular()) {
        // 文章分页
        global $post, $page, $numpages;
        $numpages = isset($numpages) ? $numpages : substr_count($post->post_content, '<!--nextpage-->') + 1;
        if ($numpages <= 1) {
            return;
        }
        $permalink = get_permalink();
        if ($page > 1) {
            $previous = $permalink . ($page - 1) . '/';
            echo '<link rel="prev" href="' . esc_url($previous) . '">' . "\n";
        }
        if ($page < $numpages) {
            $next = $permalink . ($page + 1) . '/';
            echo '<link rel="next" href="' . esc_url($next) . '">' . "\n";
        }
        return;
    }

    if (is_home() || is_archive() || is_search()) {
        global $wp_query;
        $max = $wp_query->max_num_pages;
        if ($max < 2) {
            return;
        }

        $current_url = onedown_seo_get_canonical_url();
        if (! $current_url) {
            return;
        }

        if ($paged > 1) {
            $prev_url = $paged === 2 ? $current_url : trailingslashit($current_url) . 'page/' . ($paged - 1) . '/';
            echo '<link rel="prev" href="' . esc_url($prev_url) . '">' . "\n";
        }
        if ($paged < $max) {
            $next_url = trailingslashit($current_url) . 'page/' . ($paged + 1) . '/';
            echo '<link rel="next" href="' . esc_url($next_url) . '">' . "\n";
        }
    }
}

// ──────────────────────────────────────────────
// 获取 Open Graph 数据
// ──────────────────────────────────────────────
function onedown_seo_get_og_data()
{
    $data = array();
    $site_name = get_bloginfo('name');

    if (is_singular()) {
        global $post;
        setup_postdata($post);

        $title       = get_the_title();
        $description = onedown_seo_get_description() ?: wp_trim_words(strip_tags($post->post_content), 80, '...');
        $url         = get_permalink();
        $image       = '';

        if (has_post_thumbnail()) {
            $image = get_the_post_thumbnail_url(null, 'large');
        } else {
            // 默认 OG 图片
            $og_image = _pz('seo_og_image', '');
            if (is_array($og_image) && ! empty($og_image['url'])) {
                $image = $og_image['url'];
            } elseif (is_string($og_image) && $og_image) {
                $image = $og_image;
            }
            // 仍然没有则从内容中提取
            if (! $image) {
                preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches);
                $image = $matches[1] ?? '';
            }
        }

        $data[] = array('property' => 'og:type', 'content' => 'article');
        $data[] = array('property' => 'og:title', 'content' => $title);
        $data[] = array('property' => 'og:description', 'content' => $description);
        $data[] = array('property' => 'og:url', 'content' => $url);
        $data[] = array('property' => 'og:site_name', 'content' => $site_name);
        $data[] = array('property' => 'og:locale', 'content' => get_locale());

        if ($image) {
            $data[] = array('property' => 'og:image', 'content' => $image);
            $img_width  = _pz('seo_og_image_width', '1200');
            $img_height = _pz('seo_og_image_height', '630');
            $data[] = array('property' => 'og:image:width', 'content' => $img_width);
            $data[] = array('property' => 'og:image:height', 'content' => $img_height);
        }

        // article:published_time / modified_time
        $data[] = array('property' => 'article:published_time', 'content' => get_the_date('c'));
        $data[] = array('property' => 'article:modified_time', 'content' => get_the_modified_date('c'));

        // article:author
        $author_id = $post->post_author;
        $author_display = get_the_author_meta('display_name', $author_id);
        $data[] = array('property' => 'article:author', 'content' => $author_display);

        // publisher
        $fb_url = _pz('seo_facebook_page_url', '');
        if ($fb_url) {
            $data[] = array('property' => 'article:publisher', 'content' => $fb_url);
        }

        wp_reset_postdata();
    } elseif (is_home() || is_front_page()) {
        $title       = _pz('seo_title', '') ?: $site_name;
        $description = onedown_seo_get_description() ?: get_bloginfo('description');
        $url         = home_url('/');
        $image       = '';

        $og_image = _pz('seo_og_image', '');
        if (is_array($og_image) && ! empty($og_image['url'])) {
            $image = $og_image['url'];
        } elseif (is_string($og_image) && $og_image) {
            $image = $og_image;
        }

        $data[] = array('property' => 'og:type', 'content' => 'website');
        $data[] = array('property' => 'og:title', 'content' => $title);
        $data[] = array('property' => 'og:description', 'content' => $description);
        $data[] = array('property' => 'og:url', 'content' => $url);
        $data[] = array('property' => 'og:site_name', 'content' => $site_name);
        $data[] = array('property' => 'og:locale', 'content' => get_locale());

        if ($image) {
            $data[] = array('property' => 'og:image', 'content' => $image);
            $data[] = array('property' => 'og:image:width', 'content' => _pz('seo_og_image_width', '1200'));
            $data[] = array('property' => 'og:image:height', 'content' => _pz('seo_og_image_height', '630'));
        }
    } else {
        // 归档/其他页面
        $title       = wp_get_document_title();
        $description = onedown_seo_get_description() ?: get_bloginfo('description');
        $url         = home_url(add_query_arg(array()));
        $image       = '';

        $og_image = _pz('seo_og_image', '');
        if (is_array($og_image) && ! empty($og_image['url'])) {
            $image = $og_image['url'];
        } elseif (is_string($og_image) && $og_image) {
            $image = $og_image;
        }

        $data[] = array('property' => 'og:type', 'content' => 'website');
        $data[] = array('property' => 'og:title', 'content' => $title);
        $data[] = array('property' => 'og:description', 'content' => $description);
        $data[] = array('property' => 'og:url', 'content' => $url);
        $data[] = array('property' => 'og:site_name', 'content' => $site_name);
        $data[] = array('property' => 'og:locale', 'content' => get_locale());

        if ($image) {
            $data[] = array('property' => 'og:image', 'content' => $image);
        }
    }

    return $data;
}

// ──────────────────────────────────────────────
// JSON-LD: Organization
// ──────────────────────────────────────────────
function onedown_seo_get_organization_jsonld()
{
    $org_name = _pz('seo_jsonld_org_name', '');
    if (! $org_name) {
        $org_name = get_bloginfo('name');
    }
    if (! $org_name) {
        return null;
    }

    $org = array(
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => $org_name,
        'url'      => home_url('/'),
    );

    $logo = _pz('seo_jsonld_org_logo', '');
    if (is_array($logo) && ! empty($logo['url'])) {
        $org['logo'] = $logo['url'];
    } elseif (is_string($logo) && $logo) {
        $org['logo'] = $logo;
    }

    $social_text = _pz('seo_jsonld_org_social', '');
    if ($social_text) {
        $social_lines = preg_split("/\r\n|\n|\r/", $social_text);
        $social_links = array();
        foreach ($social_lines as $line) {
            $line = trim($line);
            if ($line && filter_var($line, FILTER_VALIDATE_URL)) {
                $social_links[] = $line;
            }
        }
        if (! empty($social_links)) {
            $org['sameAs'] = $social_links;
        }
    }

    return $org;
}

// ──────────────────────────────────────────────
// JSON-LD: WebSite + Sitelinks Search Box
// ──────────────────────────────────────────────
function onedown_seo_get_website_jsonld()
{
    $org_name = _pz('seo_jsonld_org_name', '') ?: get_bloginfo('name');
    if (! $org_name) {
        return null;
    }

    $website = array(
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => $org_name,
        'url'      => home_url('/'),
    );

    if (_pz('seo_jsonld_search_enabled', true)) {
        $website['potentialAction'] = array(
            '@type'       => 'SearchAction',
            'target'      => home_url('/?s={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        );
    }

    return $website;
}

// ──────────────────────────────────────────────
// JSON-LD: BreadcrumbList
// ──────────────────────────────────────────────
function onedown_seo_get_breadcrumb_jsonld()
{
    if (is_front_page() || is_home()) {
        return null;
    }

    $items  = array();
    $crumbs = array();

    // 首页
    $crumbs[] = array(
        'name' => __('首页', 'onedown'),
        'url'  => home_url('/'),
    );

    if (is_singular()) {
        // 分类
        $categories = get_the_category();
        if (! empty($categories)) {
            $cat = $categories[0];
            $crumbs[] = array(
                'name' => $cat->name,
                'url'  => get_category_link($cat->term_id),
            );
        }
        // 文章本身
        $crumbs[] = array(
            'name' => get_the_title(),
            'url'  => get_permalink(),
        );
    } elseif (is_category()) {
        $queried = get_queried_object();
        $crumbs[] = array(
            'name' => $queried->name,
            'url'  => get_category_link($queried->term_id),
        );
    } elseif (is_tag()) {
        $queried = get_queried_object();
        $crumbs[] = array(
            'name' => $queried->name,
            'url'  => get_tag_link($queried->term_id),
        );
    } elseif (is_search()) {
        $crumbs[] = array(
            'name' => sprintf(__('搜索：%s', 'onedown'), get_search_query()),
            'url'  => home_url('/?s=' . urlencode(get_search_query())),
        );
    } elseif (is_404()) {
        $crumbs[] = array(
            'name' => __('页面未找到', 'onedown'),
            'url'  => home_url('/'),
        );
    } elseif (is_page()) {
        $crumbs[] = array(
            'name' => get_the_title(),
            'url'  => get_permalink(),
        );
    } elseif (is_archive()) {
        if (is_day()) {
            $crumbs[] = array(
                'name' => get_the_date(_x('F j, Y', 'daily archives date format', 'onedown')),
                'url'  => get_day_link(get_the_time('Y'), get_the_time('m'), get_the_time('d')),
            );
        } elseif (is_month()) {
            $crumbs[] = array(
                'name' => get_the_date(_x('F Y', 'monthly archives date format', 'onedown')),
                'url'  => get_month_link(get_the_time('Y'), get_the_time('m')),
            );
        } elseif (is_year()) {
            $crumbs[] = array(
                'name' => get_the_date(_x('Y', 'yearly archives date format', 'onedown')),
                'url'  => get_year_link(get_the_time('Y')),
            );
        } elseif (is_author()) {
            $crumbs[] = array(
                'name' => get_the_author(),
                'url'  => get_author_posts_url(get_the_author_meta('ID')),
            );
        } else {
            $crumbs[] = array(
                'name' => __('归档', 'onedown'),
                'url'  => home_url(add_query_arg(array())),
            );
        }
    }

    foreach ($crumbs as $i => $crumb) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $crumb['name'],
            'item'     => $crumb['url'],
        );
    }

    if (count($items) < 2) {
        return null;
    }

    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    );
}

// ──────────────────────────────────────────────
// JSON-LD: Article
// ──────────────────────────────────────────────
function onedown_seo_get_article_jsonld()
{
    if (! is_singular('post')) {
        return null;
    }

    $post = get_queried_object();
    if (! $post || ! isset($post->ID)) {
        return null;
    }

    $author_id   = $post->post_author;
    $author_name = get_the_author_meta('display_name', $author_id);
    $image       = '';

    if (has_post_thumbnail($post->ID)) {
        $image = get_the_post_thumbnail_url($post->ID, 'full');
    }

    $article = array(
        '@context'         => 'https://schema.org',
        '@type'            => 'Article',
        'headline'         => get_the_title($post),
        'datePublished'    => get_the_date('c', $post),
        'dateModified'     => get_the_modified_date('c', $post),
        'author'           => array(
            '@type' => 'Person',
            'name'  => $author_name,
        ),
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id'   => get_permalink($post),
        ),
        'publisher'       => array(
            '@type' => 'Organization',
            'name'  => _pz('seo_jsonld_org_name', '') ?: get_bloginfo('name'),
        ),
    );

    if ($image) {
        $article['image'] = array(
            '@type' => 'ImageObject',
            'url'   => $image,
        );
    }

    $description = onedown_seo_get_description();
    if ($description) {
        $article['description'] = $description;
    }

    return $article;
}

// ──────────────────────────────────────────────
// robots.txt 自定义
// ──────────────────────────────────────────────
function onedown_seo_robots_txt($output, $public)
{
    if (! $public) {
        return $output;
    }

    $custom = _pz('seo_robots_txt_content', '');
    if ($custom) {
        return $custom . "\n";
    }

    return $output;
}

// ──────────────────────────────────────────────
// 文章/页面 SEO Meta Box
// ──────────────────────────────────────────────
function onedown_seo_add_meta_box()
{
    $post_types = _pz('seo_meta_box_post_types', array('post' => true, 'page' => true));
    if (! is_array($post_types)) {
        $post_types = array('post' => true, 'page' => true);
    }

    $screen = array();
    foreach ($post_types as $pt => $enabled) {
        if ($enabled) {
            $screen[] = $pt;
        }
    }

    if (empty($screen)) {
        return;
    }

    add_meta_box(
        'onedown-seo-meta-box',
        __('SEO 设置', 'onedown'),
        'onedown_seo_render_meta_box',
        $screen,
        'normal',
        'high'
    );
}

function onedown_seo_render_meta_box($post)
{
    wp_nonce_field('onedown_seo_meta_box', 'onedown_seo_meta_box_nonce');

    $seo_title       = get_post_meta($post->ID, '_onedown_seo_title', true);
    $seo_description = get_post_meta($post->ID, '_onedown_seo_description', true);
    $seo_keywords    = get_post_meta($post->ID, '_onedown_seo_keywords', true);
    $seo_canonical   = get_post_meta($post->ID, '_onedown_seo_canonical', true);
    $seo_noindex     = get_post_meta($post->ID, '_onedown_seo_noindex', true);
?>
    <style>
        #onedown-seo-meta-box .seo-field { margin-bottom: 14px; }
        #onedown-seo-meta-box .seo-field label { display: block; font-weight: 600; margin-bottom: 4px; }
        #onedown-seo-meta-box .seo-field .description { color: #666; font-size: 12px; margin-top: 3px; }
        #onedown-seo-meta-box .seo-field input[type="text"],
        #onedown-seo-meta-box .seo-field textarea { width: 100%; }
        #onedown-seo-meta-box .seo-field textarea { height: 60px; }
        #onedown-seo-meta-box .seo-field .char-count { float: right; color: #999; font-size: 12px; }
        #onedown-seo-meta-box .seo-field.checkbox-field label { display: inline; font-weight: 400; margin-left: 4px; }
    </style>
    <div class="seo-field">
        <label for="onedown-seo-title"><?php _e('SEO 标题', 'onedown'); ?></label>
        <input type="text" id="onedown-seo-title" name="onedown_seo_title" value="<?php echo esc_attr($seo_title); ?>" placeholder="<?php echo esc_attr(get_the_title($post)); ?>">
        <div class="description"><?php _e('自定义此页面的 SEO 标题，留空则使用默认标题。', 'onedown'); ?></div>
    </div>
    <div class="seo-field">
        <label for="onedown-seo-description"><?php _e('SEO 描述', 'onedown'); ?></label>
        <textarea id="onedown-seo-description" name="onedown_seo_description" maxlength="320" oninput="document.getElementById('onedown-seo-desc-count').textContent=this.value.length"><?php echo esc_textarea($seo_description); ?></textarea>
        <div class="description">
            <?php _e('自定义此页面的 meta description，建议 50-160 个字符。', 'onedown'); ?>
            <span class="char-count"><span id="onedown-seo-desc-count"><?php echo strlen($seo_description); ?></span>/320</span>
        </div>
    </div>
    <div class="seo-field">
        <label for="onedown-seo-keywords"><?php _e('SEO 关键词', 'onedown'); ?></label>
        <input type="text" id="onedown-seo-keywords" name="onedown_seo_keywords" value="<?php echo esc_attr($seo_keywords); ?>" placeholder="<?php _e('多个关键词用英文逗号分隔', 'onedown'); ?>">
        <div class="description"><?php _e('自定义此页面的 meta keywords，多个关键词用英文逗号分隔。', 'onedown'); ?></div>
    </div>
    <div class="seo-field">
        <label for="onedown-seo-canonical"><?php _e('规范 URL (Canonical)', 'onedown'); ?></label>
        <input type="text" id="onedown-seo-canonical" name="onedown_seo_canonical" value="<?php echo esc_attr($seo_canonical); ?>" placeholder="<?php echo esc_attr(get_permalink($post)); ?>">
        <div class="description"><?php _e('自定义此页面的规范 URL，留空则自动生成。', 'onedown'); ?></div>
    </div>
    <div class="seo-field checkbox-field">
        <input type="checkbox" id="onedown-seo-noindex" name="onedown_seo_noindex" value="1" <?php checked($seo_noindex, '1'); ?>>
        <label for="onedown-seo-noindex"><?php _e('禁止搜索引擎索引此页面 (noindex)', 'onedown'); ?></label>
    </div>
<?php
}

function onedown_seo_save_meta_box($post_id, $post)
{
    // 安全检查
    if (! isset($_POST['onedown_seo_meta_box_nonce']) || ! wp_verify_nonce($_POST['onedown_seo_meta_box_nonce'], 'onedown_seo_meta_box')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array(
        '_onedown_seo_title'       => 'sanitize_text_field',
        '_onedown_seo_description' => 'sanitize_textarea_field',
        '_onedown_seo_keywords'    => 'sanitize_text_field',
        '_onedown_seo_canonical'   => 'esc_url_raw',
    );

    foreach ($fields as $meta_key => $sanitize_cb) {
        $value = isset($_POST[ 'onedown_' . str_replace('_onedown_', '', $meta_key) ])
            ? call_user_func($sanitize_cb, $_POST[ 'onedown_' . str_replace('_onedown_', '', $meta_key) ])
            : '';

        if ($value) {
            update_post_meta($post_id, $meta_key, $value);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }

    // noindex checkbox
    $noindex = isset($_POST['onedown_seo_noindex']) ? '1' : '';
    if ($noindex) {
        update_post_meta($post_id, '_onedown_seo_noindex', $noindex);
    } else {
        delete_post_meta($post_id, '_onedown_seo_noindex');
    }
}

// ──────────────────────────────────────────────
// XML Sitemap
// ──────────────────────────────────────────────
add_action('init', 'onedown_seo_sitemap_init');

function onedown_seo_sitemap_init()
{
    if (! _pz('seo_sitemap_enabled', true)) {
        return;
    }

    add_feed('sitemap', 'onedown_seo_sitemap_feed');
    add_action('generate_rewrite_rules', 'onedown_seo_sitemap_rewrite_rules');

    // 文章发布/更新时清除 sitemap 缓存
    add_action('save_post', 'onedown_seo_clear_sitemap_cache');
    add_action('delete_post', 'onedown_seo_clear_sitemap_cache');

    // 添加 sitemap 索引
    add_action('wp_head', 'onedown_seo_sitemap_index_link', 1);
}

function onedown_seo_sitemap_rewrite_rules($wp_rewrite)
{
    if (get_option('permalink_structure')) {
        $new_rules['sitemap\.xml$'] = 'index.php?feed=sitemap';
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }
}

function onedown_seo_sitemap_index_link()
{
    echo '<link rel="alternate" type="application/rss+xml" title="' . esc_attr(__('站点地图', 'onedown')) . '" href="' . esc_url(home_url('/sitemap.xml')) . '">' . "\n";
}

function onedown_seo_clear_sitemap_cache()
{
    delete_transient('onedown_sitemap_cache');
}

function onedown_seo_sitemap_feed()
{
    // 尝试从缓存读取
    $cached = get_transient('onedown_sitemap_cache');
    if (false !== $cached) {
        header('Content-Type: application/xml; charset=' . get_option('blog_charset'), true);
        echo $cached;
        return;
    }

    $post_types   = _pz('seo_sitemap_post_types', array('post' => true));
    $taxonomies   = _pz('seo_sitemap_taxonomies', array('category' => true, 'post_tag' => true));
    $exclude_ids  = _pz('seo_sitemap_exclude_posts', '');
    $exclude_arr  = array();
    if ($exclude_ids) {
        $exclude_arr = array_map('intval', explode(',', str_replace('，', ',', $exclude_ids)));
    }

    $posts_per_page = 200;
    $blog_charset   = get_option('blog_charset');

    // 收集 URL
    $urls = array();

    // 首页
    $home_lastmod = '';
    $home_post = get_posts(array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ));
    if (! empty($home_post)) {
        $home_lastmod = get_the_modified_date('c', $home_post[0]->ID);
    }

    $urls[] = array(
        'loc'     => home_url('/'),
        'lastmod' => $home_lastmod,
        'changefreq' => 'daily',
        'priority'   => '1.0',
    );

    // 文章类型的 URL（分批查询，避免大站点一次性加载过多）
    if (is_array($post_types)) {
        foreach ($post_types as $pt => $enabled) {
            if (! $enabled) {
                continue;
            }

            $priority = ($pt === 'page') ? _pz('seo_sitemap_priority_page', '0.5') : _pz('seo_sitemap_priority_post', '0.7');

            $offset = 0;
            while (true) {
                $args = array(
                    'post_type'      => $pt,
                    'posts_per_page' => $posts_per_page,
                    'post_status'    => 'publish',
                    'orderby'        => 'modified',
                    'order'          => 'DESC',
                    'no_found_rows'  => true,
                    'offset'         => $offset,
                );

                if (! empty($exclude_arr)) {
                    $args['post__not_in'] = $exclude_arr;
                }

                $query = new WP_Query($args);
                if (! $query->have_posts()) {
                    break;
                }

                while ($query->have_posts()) {
                    $query->the_post();
                    $urls[] = array(
                        'loc'        => get_permalink(),
                        'lastmod'    => get_the_modified_date('c'),
                        'changefreq' => ($pt === 'page') ? 'monthly' : 'weekly',
                        'priority'   => $priority,
                    );
                }
                wp_reset_postdata();

                $found = $query->post_count;
                if ($found < $posts_per_page) {
                    break;
                }
                $offset += $posts_per_page;
            }
        }
    }

    // Taxonomy URL
    if (is_array($taxonomies)) {
        foreach ($taxonomies as $tax => $enabled) {
            if (! $enabled) {
                continue;
            }

            $terms = get_terms(array(
                'taxonomy'   => $tax,
                'hide_empty' => true,
                'fields'     => 'all',
            ));

            if (! is_wp_error($terms) && ! empty($terms)) {
                foreach ($terms as $term) {
                    $urls[] = array(
                        'loc'     => get_term_link($term),
                        'lastmod' => '',
                        'changefreq' => 'weekly',
                        'priority'   => '0.4',
                    );
                }
            }
        }
    }

    // 构建 XML
    $xml  = '<?xml version="1.0" encoding="' . $blog_charset . '"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($urls as $url) {
        $xml .= "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url($url['loc']) . "</loc>\n";
        if (! empty($url['lastmod'])) {
            $xml .= "\t\t<lastmod>" . esc_html($url['lastmod']) . "</lastmod>\n";
        }
        $xml .= "\t\t<changefreq>" . esc_html($url['changefreq']) . "</changefreq>\n";
        $xml .= "\t\t<priority>" . esc_html($url['priority']) . "</priority>\n";
        $xml .= "\t</url>\n";
    }

    $xml .= '</urlset>';

    // 缓存（1 小时）
    set_transient('onedown_sitemap_cache', $xml, HOUR_IN_SECONDS);

    header('Content-Type: application/xml; charset=' . $blog_charset, true);
    echo $xml;
    exit;
}

// ──────────────────────────────────────────────
// URL 自动提交到搜索引擎
// ──────────────────────────────────────────────
add_action('save_post', 'onedown_seo_auto_submit_url', 20, 3);

function onedown_seo_auto_submit_url($post_id, $post, $update)
{
    // 是否启用自动提交
    if (! _pz('seo_url_submit_enabled', false)) {
        return;
    }

    // 自动保存/修订版本跳过
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    // 仅处理公开的文章类型（post/page）
    if (! in_array($post->post_type, array('post', 'page'), true)) {
        return;
    }

    // 仅处理已发布的文章
    if ($post->post_status !== 'publish') {
        return;
    }

    // 提交时机：仅发布时提交（更新时不提交）
    $submit_on = _pz('seo_url_submit_on', 'publish');
    if ($submit_on === 'publish' && $update) {
        return;
    }

    $permalink = get_permalink($post_id);
    if (! $permalink) {
        return;
    }

    // 逐个引擎提交
    $results = array();

    // ── 百度 ──
    if (_pz('seo_url_submit_baidu_enabled', true)) {
        $token = _pz('seo_url_submit_baidu_token', '');
        $site  = _pz('seo_url_submit_baidu_site', '');
        $res   = onedown_seo_submit_to_api('baidu', $permalink, $token, $site);
        if ($res) {
            $results[] = '百度:' . $res;
        }
    }

    // ── 360 ──
    if (_pz('seo_url_submit_360_enabled', false)) {
        $token = _pz('seo_url_submit_360_token', '');
        $site  = _pz('seo_url_submit_360_site', '');
        $res   = onedown_seo_submit_to_api('360', $permalink, $token, $site);
        if ($res) {
            $results[] = '360:' . $res;
        }
    }

    // ── 搜狗 ──
    if (_pz('seo_url_submit_sogou_enabled', false)) {
        $token = _pz('seo_url_submit_sogou_token', '');
        $site  = _pz('seo_url_submit_sogou_site', '');
        $res   = onedown_seo_submit_to_api('sogou', $permalink, $token, $site);
        if ($res) {
            $results[] = '搜狗:' . $res;
        }
    }

    // ── 神马 ──
    if (_pz('seo_url_submit_shenma_enabled', false)) {
        $token = _pz('seo_url_submit_shenma_token', '');
        $site  = _pz('seo_url_submit_shenma_site', '');
        $res   = onedown_seo_submit_to_api('shenma', $permalink, $token, $site);
        if ($res) {
            $results[] = '神马:' . $res;
        }
    }

    // ── 夸克/UC ──
    if (_pz('seo_url_submit_quark_enabled', false)) {
        $token = _pz('seo_url_submit_quark_token', '');
        $site  = _pz('seo_url_submit_quark_site', '');
        $res   = onedown_seo_submit_to_api('quark', $permalink, $token, $site);
        if ($res) {
            $results[] = '夸克:' . $res;
        }
    }

    // ── Bing ──
    if (_pz('seo_url_submit_bing_enabled', false)) {
        $api_key = _pz('seo_url_submit_bing_api_key', '');
        $res     = onedown_seo_submit_to_bing($permalink, $api_key);
        if ($res) {
            $results[] = 'Bing:' . $res;
        }
    }

    // ── Google ──
    if (_pz('seo_url_submit_google_enabled', false)) {
        $res = onedown_seo_submit_to_google($permalink);
        if ($res) {
            $results[] = 'Google:' . $res;
        }
    }

    // 记录提交结果（仅在有结果且有自定义日志时记录）
    if (! empty($results)) {
        $log = '[' . current_time('Y-m-d H:i:s') . '] URL提交: ' . $permalink . ' | ' . implode(' | ', $results);
        // 写入 PHP error log 方便调试
        error_log($log);
        // 保存到文章 meta 供后台查看
        update_post_meta($post_id, '_onedown_seo_submit_log', $log);
    }
}

/**
 * 向 API Push 类搜索引擎提交 URL
 *
 * @param string $engine   引擎标识: baidu, 360, sogou, shenma, quark
 * @param string $url      要提交的 URL
 * @param string $token    API Token
 * @param string $site     站点域名
 * @return string          结果描述，失败返回空字符串
 */
function onedown_seo_submit_to_api($engine, $url, $token, $site)
{
    if (! $token || ! $site) {
        return '';
    }

    // API 端点映射
    $endpoints = array(
        'baidu'  => 'http://data.zz.baidu.com/urls',
        '360'    => 'http://data.360.cn/urls',
        'sogou'  => 'http://data.sogou.com/urls',
        'shenma' => 'http://data.sm.cn/urls',
        'quark'  => 'http://data.uc.cn/urls',
    );

    if (! isset($endpoints[$engine])) {
        return '';
    }

    $api_url = add_query_arg(array(
        'site'  => $site,
        'token' => $token,
    ), $endpoints[$engine]);

    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Content-Type' => 'text/plain',
        ),
        'body'    => $url,
        'timeout' => 10,
    ));

    if (is_wp_error($response)) {
        return '请求失败: ' . $response->get_error_message();
    }

    $body   = wp_remote_retrieve_body($response);
    $code   = wp_remote_retrieve_response_code($response);
    $result = json_decode($body, true);

    if ($code !== 200 || ! $result) {
        return 'HTTP ' . $code . ': ' . $body;
    }

    if (isset($result['success']) && $result['success'] > 0) {
        // 百度格式: {"success":1,"remain":xxx}
        return '成功提交 ' . ($result['success'] ?? 0) . ' 条';
    } elseif (isset($result['error'])) {
        return '错误: ' . $result['error'];
    } elseif (isset($result['remain'])) {
        // 部分引擎可能用 remain 表示成功
        return '提交成功, 剩余 ' . $result['remain'] . ' 条';
    }

    return '响应: ' . $body;
}

/**
 * 向 Bing 提交 URL
 *
 * 支持两种方式：
 * 1. IndexNow API Key 方式：单条 URL 提交
 * 2. Sitemap ping 方式：提交站点地图
 *
 * @param string $url     要提交的 URL
 * @param string $api_key Bing API Key（可选）
 * @return string         结果描述
 */
function onedown_seo_submit_to_bing($url, $api_key = '')
{
    if ($api_key) {
        // 使用 IndexNow API
        $indexnow_url = 'https://www.bing.com/indexnow?url=' . urlencode($url) . '&key=' . urlencode($api_key);

        $response = wp_remote_get($indexnow_url, array(
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            return 'IndexNow请求失败: ' . $response->get_error_message();
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            return 'IndexNow提交成功';
        }
        // 不阻塞，继续尝试 sitemap ping
    }

    // Sitemap Ping 方式
    $sitemap_url = home_url('/sitemap.xml');
    $ping_url    = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url);

    $response = wp_remote_get($ping_url, array(
        'timeout' => 10,
    ));

    if (is_wp_error($response)) {
        return 'Ping失败: ' . $response->get_error_message();
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        return 'Sitemap Ping成功';
    }

    return 'Ping响应码: ' . $code;
}

/**
 * 向 Google 提交 URL
 *
 * 使用 Sitemap Ping 方式
 *
 * @param string $url 要提交的 URL
 * @return string     结果描述
 */
function onedown_seo_submit_to_google($url)
{
    $sitemap_url = home_url('/sitemap.xml');
    $ping_url    = 'https://www.google.com/ping?sitemap=' . urlencode($sitemap_url);

    $response = wp_remote_get($ping_url, array(
        'timeout' => 10,
    ));

    if (is_wp_error($response)) {
        return 'Ping失败: ' . $response->get_error_message();
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        return 'Sitemap Ping成功';
    }

    return 'Ping响应码: ' . $code;
}

/**
 * 手动提交全部历史文章（批量提交用）
 * 通过 WP CLI 或定时任务调用
 */
function onedown_seo_batch_submit_all()
{
    if (! _pz('seo_url_submit_enabled', false)) {
        return;
    }

    // 获取最近的已发布文章
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    );

    $posts = get_posts($args);
    foreach ($posts as $post) {
        onedown_seo_auto_submit_url($post->ID, $post, true);
    }
}

/**
 * 获取 URL 提交记录
 *
 * @param int $post_id
 * @return string
 */
function onedown_seo_get_submit_log($post_id = 0)
{
    if (! $post_id) {
        $post_id = get_the_ID();
    }
    return get_post_meta($post_id, '_onedown_seo_submit_log', true);
}

// ──────────────────────────────────────────────
// 移除 header.php 中的旧 OG 标签（如果 SEO 模块启用 OG）
// ──────────────────────────────────────────────
// header.php 已有条件判断：除非 seo_og_enabled 关闭，否则不输出 OG 标签
// 因此无需额外的 output buffering 移除逻辑

// ──────────────────────────────────────────────
// 自动内链：根据文章标签自动在内容中添加内链
// ──────────────────────────────────────────────
add_filter('the_content', 'onedown_seo_auto_internal_links', 98);

function onedown_seo_auto_internal_links($content)
{
    // 仅在前端文章详情页且在主循环中处理
    if (! is_singular('post') || ! in_the_loop()) {
        return $content;
    }

    // 检查开关
    if (! _pz('seo_auto_internal_link', false)) {
        return $content;
    }

    // 获取文章标签
    $tags = get_the_tags();
    if (! $tags || is_wp_error($tags)) {
        return $content;
    }

    foreach ($tags as $tag) {
        $tag_name = $tag->name;
        $tag_url  = get_tag_link($tag->term_id);

        $content = onedown_seo_replace_first_text_occurrence($content, $tag_name, $tag_url);
    }

    return $content;
}

/**
 * 替换内容中第一个匹配的纯文本为内链
 * 跳过已存在于 <a> 或 <img> 标签中的文本
 *
 * @param string $content 文章内容
 * @param string $search  要搜索的文本（标签名）
 * @param string $url     内链 URL
 * @return string
 */
function onedown_seo_replace_first_text_occurrence($content, $search, $url)
{
    // 匹配 <a...>...</a>、<img.../> 或搜索文本
    $pattern = '/<a\b[^>]*>.*?<\/a>|<img\b[^>]*\/?>|' . preg_quote($search, '/') . '/iu';

    $replaced = false;

    return preg_replace_callback($pattern, function ($m) use ($url, $search, &$replaced) {
        // 如果匹配到的是 <a> 或 <img> 标签，原样返回
        if (strpos($m[0], '<') === 0) {
            return $m[0];
        }
        // 只替换第一个匹配到的纯文本
        if (! $replaced) {
            $replaced = true;
            return '<a href="' . esc_url($url) . '" title="' . esc_attr($search) . '">' . $m[0] . '</a>';
        }
        return $m[0];
    }, $content);
}
