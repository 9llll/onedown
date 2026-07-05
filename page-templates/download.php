<?php
/**
 * Template Name: 下载页面
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$page_download_html = '';

// 如果有 post_id 参数，渲染对应文章的付费下载资源
if ($post_id) {
    $post = get_post($post_id);
    if ($post && function_exists('onedown_pay_download_box')) {
        $page_download_html = onedown_pay_download_box($post_id, false);
    }
} else {
    // 使用当前页面自身的下载数据
    global $post;
    if ($post && function_exists('onedown_render_page_downloads')) {
        $page_download_html = onedown_render_page_downloads($post->ID);
    }
}

// 获取页面副标题
$dl_data    = $post_id ? array() : (get_post_meta(get_the_ID(), '_onedown_page_downloads', true) ?: array());
$subtitle   = ! $post_id && isset($dl_data['subtitle']) ? $dl_data['subtitle'] : '';
?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span><?php echo $post_id ? esc_html(get_the_title($post_id)) : esc_html(get_the_title()); ?></span>
    </nav>

    <section class="content-shell detail-shell">
        <article class="article-card" id="download-page">
            <?php if ($post_id) : ?>
                <header class="article-head">
                    <h1><?php echo esc_html(get_the_title($post_id)); ?> - 资源下载</h1>
                </header>
                <?php if ($page_download_html) : ?>
                    <?php echo $page_download_html; ?>
                <?php else : ?>
                    <div style="padding: 60px 20px; text-align: center; color: var(--od-muted);">
                        <i class="fa fa-download" style="font-size: 48px; display: block; margin-bottom: 16px; opacity: 0.3;"></i>
                        <p style="margin: 0; font-size: 15px;">暂无下载资源</p>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <header class="article-head">
                    <h1><?php the_title(); ?></h1>
                    <?php if ($subtitle) : ?>
                        <p style="margin: 8px 0 0; color: var(--od-muted); font-size: 14px;"><?php echo esc_html($subtitle); ?></p>
                    <?php endif; ?>
                </header>
                <div class="article-content">
                    <?php the_content(); ?>
                </div>
                <?php if ($page_download_html) : ?>
                    <?php echo $page_download_html; ?>
                <?php endif; ?>
            <?php endif; ?>
        </article>
        <?php get_sidebar(); ?>
    </section>
</main>
<?php get_footer(); ?>
