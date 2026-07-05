<?php
/**
 * 全部专题列表页模板
 *
 * 对应 /topic/ 路由，展示所有专题的卡片列表
 *
 * @package onedown
 */

get_header();

$topics = get_terms(array(
    'taxonomy'   => 'topic',
    'hide_empty' => false,
    'orderby'    => 'count',
    'order'      => 'DESC',
));
?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span>全部专题</span>
    </nav>

    <section class="page-hero category-page-hero">
        <div class="page-hero-copy">
            <h1>全部专题</h1>
            <p>精选内容，汇聚成专题。浏览不同专题，发现更多精彩。</p>
            <div class="page-hero-actions"><a class="primary" href="#topicGrid"><i class="fa fa-th-large"></i> 浏览专题</a></div>
        </div>
        <div class="page-hero-stats">
            <span><strong><?php echo esc_html(is_array($topics) ? count($topics) : 0); ?></strong>个专题</span>
        </div>
    </section>

    <section class="content-shell">
        <div class="main-column" id="topicGrid">
            <div class="section-card">
                <div class="section-head"><h2 class="section-title"><i class="fa fa-tags"></i> 全部专题</h2></div>
                <?php if (! empty($topics) && ! is_wp_error($topics)) : ?>
                    <div class="topic-grid">
                        <?php foreach ($topics as $topic) :
                            $image     = onedown_get_topic_image($topic->term_id);
                            $count     = $topic->count;
                            $link      = get_term_link($topic);
                            $desc      = term_description($topic->term_id, 'topic');
                            $desc_trim = $desc ? wp_trim_words($desc, 20, '...') : '暂无描述';
                        ?>
                            <a class="topic-item" href="<?php echo esc_url($link); ?>">
                                <div class="topic-item-top">
                                    <?php if ($image) : ?>
                                        <img class="topic-item-img" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($topic->name); ?>" loading="lazy">
                                    <?php else : ?>
                                        <div class="topic-item-placeholder"><i class="fa fa-folder-open"></i></div>
                                    <?php endif; ?>
                                    <span class="topic-item-count"><?php echo esc_html($count); ?> 篇</span>
                                </div>
                                <div class="topic-item-body">
                                    <strong class="topic-item-name"><?php echo esc_html($topic->name); ?></strong>
                                    <span class="topic-item-desc"><?php echo esc_html($desc_trim); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div style="padding:40px;text-align:center;color:var(--od-muted);">
                        <i class="fa fa-inbox" style="font-size:48px;display:block;margin-bottom:16px;"></i>
                        <p>还没有创建专题，请先在后台添加。</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php get_sidebar(); ?>
    </section>
</main>

<style>
.topic-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 20px;
}

.topic-item {
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

.topic-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 34px rgba(33, 40, 66, .08);
}

.topic-item-top {
    position: relative;
    height: 130px;
    overflow: hidden;
    background: #dde2ef;
}

.topic-item-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.topic-item-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: #b0b7c8;
    background: linear-gradient(135deg, #e9ecf5, #dde2ef);
}

.topic-item-count {
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

.topic-item-body {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 14px 16px 16px;
    flex: 1;
}

.topic-item-name {
    font-size: 15px;
    font-weight: 800;
    color: #303746;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.topic-item-desc {
    font-size: 13px;
    color: var(--od-muted);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (max-width: 992px) {
    .topic-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .topic-grid {
        grid-template-columns: repeat(2, 1fr);
        padding: 12px;
        gap: 12px;
    }
    .topic-item-top {
        height: 100px;
    }
}

@media (max-width: 480px) {
    .topic-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>