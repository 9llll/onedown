<?php

/**
 * Onedown 主题 - 小组件初始化
 *
 * 加载所有自定义小组件，注册侧边栏位置
 */

if (! defined('ABSPATH')) {
    exit;
}

// 注册侧边栏位置
if (! function_exists('onedown_register_sidebars')) :
    function onedown_register_sidebars()
    {
        // 全局侧边栏（默认兜底）
        register_sidebar(array(
            'name'          => __('全局侧边栏', 'onedown'),
            'id'            => 'sidebar_global',
            'description'   => __('全局默认侧边栏，当其他侧边栏未设置时显示此内容', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 首页侧边栏
        register_sidebar(array(
            'name'          => __('首页侧边栏', 'onedown'),
            'id'            => 'sidebar_home',
            'description'   => __('仅在首页显示的侧边栏，优先级高于全局侧边栏', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 分类/归档页侧边栏
        register_sidebar(array(
            'name'          => __('分类页侧边栏', 'onedown'),
            'id'            => 'sidebar_archive',
            'description'   => __('分类、标签、归档及搜索页显示的侧边栏', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 详情页侧边栏
        register_sidebar(array(
            'name'          => __('详情页侧边栏', 'onedown'),
            'id'            => 'sidebar_single',
            'description'   => __('文章详情页和页面显示的侧边栏', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 首页 - 顶部全宽度
        register_sidebar(array(
            'name'          => __('首页-顶部全宽度', 'onedown'),
            'id'            => 'home_top_fluid',
            'description'   => __('显示在首页顶部全宽度位置', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 首页 - 底部全宽度
        register_sidebar(array(
            'name'          => __('首页-底部全宽度', 'onedown'),
            'id'            => 'home_bottom_fluid',
            'description'   => __('显示在首页底部全宽度位置', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 首页 - 最新内容上方
        register_sidebar(array(
            'name'          => __('首页-最新内容上方', 'onedown'),
            'id'            => 'home_before_latest',
            'description'   => __('显示在首页"最新内容"上方的右侧小工具区', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 首页 - 最新内容下方
        register_sidebar(array(
            'name'          => __('首页-最新内容下方', 'onedown'),
            'id'            => 'home_after_latest',
            'description'   => __('显示在首页"最新内容"下方的右侧小工具区', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 文章页 - 顶部全宽度
        register_sidebar(array(
            'name'          => __('文章页-顶部全宽度', 'onedown'),
            'id'            => 'single_top_fluid',
            'description'   => __('显示在文章页顶部全宽度位置', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 文章页 - 底部全宽度
        register_sidebar(array(
            'name'          => __('文章页-底部全宽度', 'onedown'),
            'id'            => 'single_bottom_fluid',
            'description'   => __('显示在文章页底部全宽度位置', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));

        // 页脚区域
        register_sidebar(array(
            'name'          => __('页脚区', 'onedown'),
            'id'            => 'footer_widgets',
            'description'   => __('显示在页脚区域', 'onedown'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ));
    }
    add_action('widgets_init', 'onedown_register_sidebars', 5);
endif;

// 移除默认侧边栏注册（已在 setup.php 中注册，这里用优先级覆盖）
// 实际上 onedown_register_sidebars 在 widgets_init 优先级 5 执行，
// 而 setup.php 中的 onedown_widgets_init 使用默认优先级 10，
// 且它注册的是 sidebar-1，不会重复，无需移除。

// 加载小组件文件
$onedown_widgets = array(
    'profile',          // 用户资料卡片
    'posts',            // 文章列表 & 热榜文章
    'tags',             // 标签云
    'search',           // 搜索框
    'notice',           // 滚动公告
    'comments',         // 最新评论
    'featured',         // 推荐图文
    'text-rank',        // 文字排行（Tab切换）
    'toc',              // 文章目录
    'links',            // 友情链接
    'carousel',         // 图文轮播
    'hero',             // 首页 Hero 全宽轮播
    'docs',             // 文档列表
    'category-posts',   // 分类文章网格
    'text-ad',          // 文字广告
    'ad-carousel',      // 图文轮播广告
    'division',         // 分类分区导航网格
    'dynamic',          // 最近动态滚动列表
);

foreach ($onedown_widgets as $widget) {
    $file = get_theme_file_path('inc/widgets/widget-' . $widget . '.php');
    if (file_exists($file)) {
        require_once $file;
    }
}
