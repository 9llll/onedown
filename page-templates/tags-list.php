<?php
/**
 * 全部标签列表页模板
 *
 * 对应 /tags/ 路由，展示所有标签的列表
 *
 * @package onedown
 */

get_header();

$tags = get_terms(array(
    'taxonomy'   => 'post_tag',
    'hide_empty' => false,
    'orderby'    => 'count',
    'order'      => 'DESC',
));
?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span>全部标签</span>
    </nav>

    <section class="page-hero tag-page-hero">
        <div class="page-hero-copy">
            <h1>全部标签</h1>
            <p>按标签浏览内容，快速找到你感兴趣的主题。</p>
            <div class="page-hero-actions"><a class="primary" href="#tagsList"><i class="fa fa-tags"></i> 浏览标签</a></div>
        </div>
        <div class="page-hero-stats">
            <span><strong><?php echo esc_html(is_array($tags) ? count($tags) : 0); ?></strong>个标签</span>
        </div>
    </section>

    <section class="content-shell">
        <div class="main-column" id="tagsList">
            <div class="section-card">
                <div class="section-head"><h2 class="section-title"><i class="fa fa-tags"></i> 全部标签</h2></div>
                <?php if (! empty($tags) && ! is_wp_error($tags)) : ?>
                    <div class="tags-cloud">
                        <?php foreach ($tags as $tag) :
                            $count = $tag->count;
                            $link  = get_term_link($tag);
                            $desc  = tag_description($tag->term_id);
                        ?>
                            <a class="tag-item" href="<?php echo esc_url($link); ?>">
                                <span class="tag-item-name"><?php echo esc_html($tag->name); ?></span>
                                <span class="tag-item-count"><?php echo esc_html($count); ?> 篇</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div style="padding:40px;text-align:center;color:var(--od-muted);">
                        <i class="fa fa-inbox" style="font-size:48px;display:block;margin-bottom:16px;"></i>
                        <p>还没有标签。</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php get_sidebar(); ?>
    </section>
</main>

<style>
.tags-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 20px;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 999px;
    background: linear-gradient(180deg, #fff, rgba(245, 247, 252, .86));
    border: 1px solid rgba(45, 55, 76, .06);
    text-decoration: none;
    transition: transform .2s ease, box-shadow .2s ease;
    font-size: 14px;
}

.tag-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 40, 66, .08);
    background: var(--od-primary, #4f6ef7);
    border-color: var(--od-primary, #4f6ef7);
}

.tag-item:hover .tag-item-name,
.tag-item:hover .tag-item-count {
    color: #fff;
}

.tag-item-name {
    color: #303746;
    font-weight: 600;
}

.tag-item-count {
    color: var(--od-muted, #8a93a8);
    font-size: 12px;
    font-weight: 500;
}

@media (max-width: 768px) {
    .tags-cloud {
        gap: 8px;
        padding: 12px;
    }
    .tag-item {
        padding: 6px 12px;
        font-size: 13px;
    }
}
</style>

<?php get_footer(); ?>
