<?php
/**
 * 全部分类列表页模板
 *
 * 对应 /cates/ 路由，展示所有分类的卡片列表
 *
 * @package onedown
 */

get_header();

$categories = get_terms(array(
    'taxonomy'   => 'category',
    'hide_empty' => false,
    'orderby'    => 'count',
    'order'      => 'DESC',
));
?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span>全部分类</span>
    </nav>

    <section class="page-hero category-page-hero">
        <div class="page-hero-copy">
            <h1>全部分类</h1>
            <p>按分类浏览内容，快速定位你需要的教程和资源。</p>
            <div class="page-hero-actions"><a class="primary" href="#catesGrid"><i class="fa fa-folder"></i> 浏览分类</a></div>
        </div>
        <div class="page-hero-stats">
            <span><strong><?php echo esc_html(is_array($categories) ? count($categories) : 0); ?></strong>个分类</span>
        </div>
    </section>

    <section class="content-shell">
        <div class="main-column" id="catesGrid">
            <div class="section-card">
                <div class="section-head"><h2 class="section-title"><i class="fa fa-folder"></i> 全部分类</h2></div>
                <?php if (! empty($categories) && ! is_wp_error($categories)) : ?>
                    <div class="cates-grid">
                        <?php foreach ($categories as $category) :
                            $count     = $category->count;
                            $link      = get_category_link($category);
                            $desc      = category_description($category->term_id);
                            $desc_trim = $desc ? wp_trim_words($desc, 20, '...') : '暂无描述';
                        ?>
                            <a class="cate-item" href="<?php echo esc_url($link); ?>">
                                <div class="cate-item-top">
                                    <img class="cate-item-img" src="https://picsum.photos/seed/cat-<?php echo esc_attr($category->term_id); ?>/400/225" alt="<?php echo esc_attr($category->name); ?>" loading="lazy">
                                    <span class="cate-item-count"><?php echo esc_html($count); ?> 篇</span>
                                </div>
                                <div class="cate-item-body">
                                    <strong class="cate-item-name"><?php echo esc_html($category->name); ?></strong>
                                    <span class="cate-item-desc"><?php echo esc_html($desc_trim); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div style="padding:40px;text-align:center;color:var(--od-muted);">
                        <i class="fa fa-inbox" style="font-size:48px;display:block;margin-bottom:16px;"></i>
                        <p>还没有分类。</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php get_sidebar(); ?>
    </section>
</main>

<style>
.cates-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 20px;
}

.cate-item {
    display: flex;
    flex-direction: column;
    border-radius: 10px;
    background: linear-gradient(180deg, #fff, rgba(245, 247, 252, .86));
    border: 1px solid rgba(45, 55, 76, .06);
    overflow: hidden;
    transition: transform .25s ease, box-shadow .25s ease;
    text-decoration: none;
    color: inherit;
}

.cate-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 34px rgba(33, 40, 66, .08);
}

.cate-item-top {
    position: relative;
    height: 130px;
    overflow: hidden;
    background: linear-gradient(135deg, #e9ecf5, #dde2ef);
}

.cate-item-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.cate-item-count {
    position: absolute;
    right: 10px;
    bottom: 10px;
    padding: 4px 10px;
    border-radius: 999px;
    color: #fff;
    background: rgba(20, 26, 40, .68);
    backdrop-filter: blur(8px);
    font-size: 12px;
    font-weight: 700;
}

.cate-item-body {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 14px 16px 16px;
    flex: 1;
}

.cate-item-name {
    font-size: 15px;
    font-weight: 800;
    color: #303746;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cate-item-desc {
    font-size: 13px;
    color: var(--od-muted);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (max-width: 992px) {
    .cates-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .cates-grid {
        grid-template-columns: repeat(2, 1fr);
        padding: 12px;
        gap: 12px;
    }
    .cate-item-top {
        height: 100px;
    }
}

@media (max-width: 480px) {
    .cates-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>
