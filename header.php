<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Open Graph / Twitter Card — 由 SEO 模块统一管理（inc/seo.php）
    // 仅当 SEO 模块未启用 OG 时使用旧的内联输出
    if (! function_exists('_pz') || ! _pz('seo_og_enabled', true)) :
        if (is_singular('post')) {
            global $post;
            setup_postdata($post);
            $og_title       = get_the_title();
            $og_description = wp_trim_words(get_the_excerpt() ?: get_the_title(), 80, '...');
            $og_url         = get_permalink();
            $og_image       = '';
            if (has_post_thumbnail()) {
                $og_image = get_the_post_thumbnail_url(null, 'large');
            } else {
                preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches);
                $og_image = $matches[1] ?? '';
            }
            $og_site_name = get_bloginfo('name');
            wp_reset_postdata();
        } elseif (is_home() || is_front_page()) {
            $og_title       = get_bloginfo('name');
            $og_description = get_bloginfo('description');
            $og_url         = home_url('/');
            $og_image       = onedown_fallback_thumb_url(0, 1200, 630);
            $og_site_name   = get_bloginfo('name');
        } else {
            $og_title       = wp_get_document_title();
            $og_description = get_bloginfo('description');
            $og_url         = home_url(add_query_arg(array()));
            $og_image       = onedown_fallback_thumb_url(0, 1200, 630);
            $og_site_name   = get_bloginfo('name');
        }
    ?>
        <meta property="og:title" content="<?php echo esc_attr($og_title); ?>">
        <meta property="og:description" content="<?php echo esc_attr($og_description); ?>">
        <meta property="og:url" content="<?php echo esc_url($og_url); ?>">
        <meta property="og:image" content="<?php echo esc_url($og_image); ?>">
        <meta property="og:site_name" content="<?php echo esc_attr($og_site_name); ?>">
        <meta property="og:type" content="<?php echo is_singular('post') ? 'article' : 'website'; ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo esc_attr($og_title); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr($og_description); ?>">
        <meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
    <?php endif; ?>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('onedown-theme');
                var theme = stored || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="page-wrap">
    <?php get_template_part( 'template-parts/header-markup' ); ?>
