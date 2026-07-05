<?php

/**
 * Template Name: 帮助中心
 *
 * @package onedown
 */

// 添加自定义 body class
add_filter('body_class', function ($classes) {
    $classes[] = 'page-help-center';
    return $classes;
});

get_header();

// 获取所有分类（作为帮助分类）
$categories = get_terms(array(
    'taxonomy'   => 'category',
    'hide_empty' => false,
    'orderby'    => 'count',
    'order'      => 'DESC',
));

// 常见问题（可后台通过页面编辑自定义字段扩展）
$faq_items = array(
    array(
        'q' => '如何注册账号？',
        'a' => '点击右上角"登录/注册"按钮，填写邮箱和密码即可完成注册。注册后您可以使用下载、收藏、评论等完整功能。',
    ),
    array(
        'q' => '如何下载资源？',
        'a' => '在资源详情页点击"下载"按钮，根据提示完成支付或积分兑换后即可获取下载链接。',
    ),
    array(
        'q' => '支付后没有收到下载链接怎么办？',
        'a' => '支付成功后页面会自动刷新显示下载链接。如未显示，请检查订单状态或联系客服处理。',
    ),
    array(
        'q' => '如何查看我的下载记录？',
        'a' => '登录后进入"用户中心" → "我的下载"，即可查看所有下载记录。',
    ),
    array(
        'q' => 'VIP 会员有哪些权益？',
        'a' => 'VIP 会员可享受无限下载、专属资源、去广告等特权。具体权益请在 VIP 页面查看。',
    ),
    array(
        'q' => '如何联系管理员？',
        'a' => '您可以通过"联系我们"页面提交留言，或发送邮件至网站管理员邮箱。',
    ),
);
?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span>帮助中心</span>
    </nav>

    <section class="page-hero help-page-hero">
        <div class="page-hero-copy">
            <h1>帮助中心</h1>
            <p>在这里找到关于网站使用的常见问题解答和使用指南。</p>
            <div class="page-hero-actions">
                <a class="primary" href="#faqSection"><i class="fa fa-question-circle"></i> 常见问题</a>
                <a class="ghost" href="#categorySection"><i class="fa fa-folder"></i> 分类浏览</a>
            </div>
        </div>
        <div class="page-hero-stats">
            <span><strong><?php echo esc_html(count($faq_items)); ?></strong>个常见问题</span>
        </div>
    </section>

    <!-- 常见问题 -->
    <section class="content-shell" id="faqSection">
        <div class="main-column">
            <div class="help-quick-nav">
                <a href="#faqSection" class="help-quick-item">
                    <i class="fa fa-question-circle"></i>
                    <span>常见问题</span>
                </a>
                <a href="#categorySection" class="help-quick-item">
                    <i class="fa fa-folder"></i>
                    <span>分类浏览</span>
                </a>
                <a href="<?php echo esc_url(home_url('/contact.html')); ?>" class="help-quick-item">
                    <i class="fa fa-envelope"></i>
                    <span>联系我们</span>
                </a>
                <a href="<?php echo esc_url(home_url('/user-center/')); ?>" class="help-quick-item">
                    <i class="fa fa-user"></i>
                    <span>用户中心</span>
                </a>
            </div>
            <div class="section-card">
                <div class="section-head">
                    <h2 class="section-title"><i class="fa fa-question-circle"></i> 常见问题</h2>
                </div>
                <div class="faq-list">
                    <?php foreach ($faq_items as $index => $item) : ?>
                        <details class="faq-item" <?php echo $index === 0 ? 'open' : ''; ?>>
                            <summary class="faq-question">
                                <span><?php echo esc_html($item['q']); ?></span>
                                <i class="fa fa-chevron-down"></i>
                            </summary>
                            <div class="faq-answer">
                                <p><?php echo esc_html($item['a']); ?></p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php get_sidebar(); ?>
    </section>

    <!-- 分类浏览 -->
    <section class="content-shell" id="categorySection">
        <div class="main-column">
            <div class="section-card">
                <div class="section-head">
                    <h2 class="section-title"><i class="fa fa-folder"></i> 按分类浏览</h2>
                </div>
                <?php if (! empty($categories) && ! is_wp_error($categories)) : ?>
                    <div class="help-cates-grid">
                        <?php foreach ($categories as $category) :
                            $count = $category->count;
                            $link  = get_category_link($category);
                            $desc  = category_description($category->term_id);
                        ?>
                            <a class="help-cate-item" href="<?php echo esc_url($link); ?>">
                                <div class="help-cate-icon"><i class="fa fa-folder-open"></i></div>
                                <strong class="help-cate-name"><?php echo esc_html($category->name); ?></strong>
                                <span class="help-cate-count"><?php echo esc_html($count); ?> 篇内容</span>
                                <?php if ($desc) : ?>
                                    <span class="help-cate-desc"><?php echo esc_html(wp_trim_words($desc, 10, '...')); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty-state">
                        <i class="fa fa-inbox"></i>
                        <p>暂无分类</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php get_sidebar(); ?>
    </section>
</main>
<?php get_footer(); ?>