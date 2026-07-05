<?php

if (! defined('ABSPATH')) {
    exit;
}

$prefix = '_onedown_options';

CSF::createOptions($prefix, array(
    'menu_title'       => __('OD主题设置', 'onedown'),
    'menu_slug'        => 'onedown-options',
    'framework_title'  => __('Onedown 主题设置', 'onedown'),
    'theme'            => 'dark',
    'menu_icon'        => 'dashicons-layout',
    'menu_position'    => 81,
    'show_bar_menu'    => true,
    'show_sub_menu'    => true,
    'show_reset_section' => true,
));

// =====================
// 全局配置
// =====================
CSF::createSection($prefix, array(
    'id'    => 'global',
    'title' => __('全局&功能', 'onedown'),
    'icon'  => 'fas fa-cog',
));

// -- LOGO 设置
CSF::createSection($prefix, array(
    'id'     => 'global-logo',
    'parent' => 'global',
    'title'  => __('LOGO 设置', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'logo_show',
            'type'    => 'switcher',
            'title'   => __('显示 LOGO', 'onedown'),
            'subtitle' => __('关闭后隐藏 LOGO 图片，前台显示网站名称文字', 'onedown'),
            'default' => true,
        ),
        array(
            'id'       => 'logo',
            'type'     => 'media',
            'title'    => __('网站 LOGO', 'onedown'),
            'subtitle' => __('上传网站 LOGO 图片', 'onedown'),
            'library'  => 'image',
            'preview'  => true,
        ),
        array(
            'id'      => 'logo_width',
            'type'    => 'slider',
            'title'   => __('LOGO 宽度', 'onedown'),
            'default' => 150,
            'min'     => 50,
            'max'     => 300,
            'step'    => 1,
            'unit'    => 'px',
        ),
        array(
            'id'       => 'favicon',
            'type'     => 'media',
            'title'    => __('网站图标', 'onedown'),
            'subtitle' => __('上传网站 favicon 图标，建议尺寸 16x16 或 32x32', 'onedown'),
            'library'  => 'image',
            'preview'  => true,
        ),
    ),
));

// -- SEO 优化
CSF::createSection($prefix, array(
    'id'     => 'global-seo',
    'parent' => 'global',
    'title'  => __('SEO 优化', 'onedown'),
    'fields' => array(

        // 基础 Meta
        array(
            'type'    => 'subheading',
            'content' => __('基础 Meta 标签', 'onedown'),
        ),
        array(
            'id'      => 'seo_title',
            'type'    => 'text',
            'title'   => __('首页 SEO 标题', 'onedown'),
            'subtitle' => __('自定义首页标题，留空则使用站点名称', 'onedown'),
            'default' => get_bloginfo('name'),
        ),
        array(
            'id'      => 'seo_description',
            'type'    => 'textarea',
            'title'   => __('首页 SEO 描述', 'onedown'),
            'subtitle' => __('首页 meta description 内容', 'onedown'),
            'default' => get_bloginfo('description'),
        ),
        array(
            'id'    => 'seo_keywords',
            'type'  => 'textarea',
            'title' => __('首页 SEO 关键词', 'onedown'),
            'subtitle' => __('首页 meta keywords 内容', 'onedown'),
            'desc'  => __('多个关键词用英文逗号分隔', 'onedown'),
        ),
        array(
            'id'      => 'seo_robots',
            'type'    => 'select',
            'title'   => __('全局搜索引擎索引', 'onedown'),
            'subtitle' => __('设置站点默认的 robots 指令', 'onedown'),
            'options' => array(
                'index,follow'       => __('允许索引，允许跟踪', 'onedown'),
                'noindex,nofollow'   => __('禁止索引，禁止跟踪', 'onedown'),
                'index,nofollow'     => __('允许索引，禁止跟踪', 'onedown'),
                'noindex,follow'     => __('禁止索引，允许跟踪', 'onedown'),
            ),
            'default' => 'index,follow',
        ),
        array(
            'id'      => 'seo_canonical',
            'type'    => 'switcher',
            'title'   => __('启用规范 URL（Canonical）', 'onedown'),
            'subtitle' => __('开启后自动输出 canonical 标签，减少重复内容影响', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'seo_pagination',
            'type'    => 'switcher',
            'title'   => __('分页 rel 标签', 'onedown'),
            'subtitle' => __('在分页页面添加 rel="prev" 和 rel="next" 标签', 'onedown'),
            'default' => true,
        ),

        // 分类/标签 SEO
        array(
            'type'    => 'subheading',
            'content' => __('分类与标签页 SEO', 'onedown'),
        ),
        array(
            'id'      => 'seo_archive_desc',
            'type'    => 'switcher',
            'title'   => __('启用摘要作为 Meta Description', 'onedown'),
            'subtitle' => __('开启后分类和标签页的 meta description 自动使用描述内容', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'seo_archive_title_prefix',
            'type'    => 'text',
            'title'   => __('分类页标题前缀', 'onedown'),
            'subtitle' => __('例如“分类：”“归档：”等，留空则不显示前缀', 'onedown'),
            'default' => '',
        ),

        // 文章/页面 SEO Meta Box
        array(
            'type'    => 'subheading',
            'content' => __('文章/页面 SEO 设置', 'onedown'),
        ),
        array(
            'id'      => 'seo_meta_box',
            'type'    => 'switcher',
            'title'   => __('启用文章 SEO 设置面板', 'onedown'),
            'subtitle' => __('在文章和页面编辑页显示独立 SEO 配置面板', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'seo_meta_box_post_types',
            'type'    => 'checkbox',
            'title'   => __('SEO Meta 面板启用文章类型', 'onedown'),
            'subtitle' => __('选择需要显示 SEO 设置面板的文章类型', 'onedown'),
            'options' => 'post_types',
            'default' => array('post' => true, 'page' => true),
            'dependency' => array('seo_meta_box', '==', 'true'),
        ),

        // Open Graph / Twitter Card
        array(
            'type'    => 'subheading',
            'content' => __('Open Graph & Twitter Card', 'onedown'),
        ),
        array(
            'id'      => 'seo_og_enabled',
            'type'    => 'switcher',
            'title'   => __('启用 Open Graph 标签', 'onedown'),
            'subtitle' => __('开启后自动在页面头部添加 og:meta 标签，优化社交分享效果', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'seo_twitter_card',
            'type'    => 'select',
            'title'   => __('Twitter Card 类型', 'onedown'),
            'subtitle' => __('选择分享到 Twitter 时的卡片样式', 'onedown'),
            'options' => array(
                'summary'             => __('Summary Card', 'onedown'),
                'summary_large_image' => __('Summary Large Image', 'onedown'),
            ),
            'default' => 'summary_large_image',
            'dependency' => array('seo_og_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'seo_facebook_page_url',
            'type'    => 'text',
            'title'   => __('Facebook 主页 URL', 'onedown'),
            'subtitle' => __('用于 og:article:publisher 标签', 'onedown'),
            'default' => '',
        ),
        array(
            'id'      => 'seo_twitter_username',
            'type'    => 'text',
            'title'   => __('Twitter 用户名', 'onedown'),
            'subtitle' => __('用于 twitter:site 标签，填写时无需包含 @ 符号', 'onedown'),
            'default' => '',
        ),
        array(
            'id'      => 'seo_og_image',
            'type'    => 'media',
            'title'   => __('默认 OG 图片', 'onedown'),
            'subtitle' => __('文章没有缩略图时，分享卡片默认使用这张图片', 'onedown'),
            'library'  => 'image',
            'preview'  => true,
        ),
        array(
            'id'      => 'seo_og_image_width',
            'type'    => 'text',
            'title'   => __('OG 图片宽度', 'onedown'),
            'default' => '1200',
        ),
        array(
            'id'      => 'seo_og_image_height',
            'type'    => 'text',
            'title'   => __('OG 图片高度', 'onedown'),
            'default' => '630',
        ),

        // JSON-LD 结构化数据
        array(
            'type'    => 'subheading',
            'content' => __('JSON-LD 结构化数据', 'onedown'),
        ),
        array(
            'id'      => 'seo_jsonld_enabled',
            'type'    => 'switcher',
            'title'   => __('启用 JSON-LD 结构化数据', 'onedown'),
            'subtitle' => __('开启后自动输出网站、文章、面包屑等结构化数据', 'onedown'),
            'default' => true,
        ),
        array(
            'id'         => 'seo_jsonld_org_name',
            'type'       => 'text',
            'title'      => __('组织名称', 'onedown'),
            'subtitle'   => __('结构化数据中展示的组织或企业名称', 'onedown'),
            'default'    => get_bloginfo('name'),
            'dependency' => array('seo_jsonld_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_jsonld_org_logo',
            'type'       => 'media',
            'title'      => __('组织 Logo', 'onedown'),
            'subtitle'   => __('建议使用 512x512 及以上的方形 Logo', 'onedown'),
            'library'    => 'image',
            'preview'    => true,
            'dependency' => array('seo_jsonld_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_jsonld_org_social',
            'type'       => 'textarea',
            'title'      => __('社交媒体链接', 'onedown'),
            'subtitle'   => __('每行填写一个完整的社交媒体 URL', 'onedown'),
            'desc'       => __('例如：https://weibo.com/yourpage', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_jsonld_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_jsonld_search_enabled',
            'type'       => 'switcher',
            'title'      => __('启用站内搜索结构化数据', 'onedown'),
            'subtitle'   => __('为搜索引擎提供站内搜索入口结构化数据', 'onedown'),
            'default'    => true,
            'dependency' => array('seo_jsonld_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_jsonld_breadcrumb',
            'type'       => 'switcher',
            'title'      => __('启用面包屑结构化数据', 'onedown'),
            'subtitle'   => __('为页面输出 BreadcrumbList 结构化数据', 'onedown'),
            'default'    => true,
            'dependency' => array('seo_jsonld_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_jsonld_article',
            'type'       => 'switcher',
            'title'      => __('启用文章结构化数据', 'onedown'),
            'subtitle'   => __('为文章页面输出 Article 结构化数据', 'onedown'),
            'default'    => true,
            'dependency' => array('seo_jsonld_enabled', '==', 'true'),
        ),

        // XML 站点地图
        array(
            'type'    => 'subheading',
            'content' => __('XML 站点地图', 'onedown'),
        ),
        array(
            'id'      => 'seo_sitemap_enabled',
            'type'    => 'switcher',
            'title'   => __('启用 XML 站点地图', 'onedown'),
            'subtitle' => __('自动生成 sitemap.xml，方便搜索引擎抓取和收录', 'onedown'),
            'default' => true,
        ),
        array(
            'id'         => 'seo_sitemap_post_types',
            'type'       => 'checkbox',
            'title'      => __('包含的文章类型', 'onedown'),
            'subtitle'   => __('选择站点地图中需要包含的文章类型', 'onedown'),
            'options'    => 'post_types',
            'default'    => array('post' => true),
            'dependency' => array('seo_sitemap_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_sitemap_taxonomies',
            'type'       => 'checkbox',
            'title'      => __('包含的分类法', 'onedown'),
            'subtitle'   => __('选择站点地图中需要包含的分类法', 'onedown'),
            'options'    => 'taxonomies',
            'default'    => array('category' => true, 'post_tag' => true),
            'dependency' => array('seo_sitemap_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_sitemap_exclude_posts',
            'type'       => 'textarea',
            'title'      => __('排除的文章 ID', 'onedown'),
            'subtitle'   => __('这些文章或页面 ID 不会出现在站点地图中，多个 ID 用英文逗号分隔', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_sitemap_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_sitemap_priority_post',
            'type'       => 'select',
            'title'      => __('文章默认优先级', 'onedown'),
            'options'    => array(
                '0.1' => '0.1',
                '0.2' => '0.2',
                '0.3' => '0.3',
                '0.4' => '0.4',
                '0.5' => '0.5',
                '0.6' => '0.6',
                '0.7' => '0.7',
                '0.8' => '0.8',
                '0.9' => '0.9',
                '1.0' => '1.0',
            ),
            'default'    => '0.7',
            'dependency' => array('seo_sitemap_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_sitemap_priority_page',
            'type'       => 'select',
            'title'      => __('页面默认优先级', 'onedown'),
            'options'    => array(
                '0.1' => '0.1',
                '0.2' => '0.2',
                '0.3' => '0.3',
                '0.4' => '0.4',
                '0.5' => '0.5',
                '0.6' => '0.6',
                '0.7' => '0.7',
                '0.8' => '0.8',
                '0.9' => '0.9',
                '1.0' => '1.0',
            ),
            'default'    => '0.5',
            'dependency' => array('seo_sitemap_enabled', '==', 'true'),
        ),

        // robots.txt
        array(
            'type'    => 'subheading',
            'content' => __('robots.txt 增强', 'onedown'),
        ),
        array(
            'id'      => 'seo_robots_txt_enabled',
            'type'    => 'switcher',
            'title'   => __('自定义 robots.txt', 'onedown'),
            'subtitle' => __('启用后将使用你配置的 robots.txt 内容替代 WordPress 默认输出', 'onedown'),
            'default' => false,
        ),
        array(
            'id'         => 'seo_robots_txt_content',
            'type'       => 'textarea',
            'title'      => __('robots.txt 内容', 'onedown'),
            'subtitle'   => __('自定义 robots.txt 规则内容', 'onedown'),
            'desc'       => __('留意规则格式，错误配置可能影响搜索引擎抓取', 'onedown'),
            'default'    => "User-agent: *\nAllow: /\n\nSitemap: " . home_url('/sitemap.xml'),
            'dependency' => array('seo_robots_txt_enabled', '==', 'true'),
        ),

        // URL 主动提交到搜索引擎
        array(
            'type'    => 'subheading',
            'content' => __('URL 主动提交', 'onedown'),
        ),
        array(
            'id'      => 'seo_url_submit_enabled',
            'type'    => 'switcher',
            'title'   => __('启用 URL 自动提交', 'onedown'),
            'subtitle' => __('发布或更新文章时，自动向已启用的搜索引擎提交 URL', 'onedown'),
            'default' => false,
        ),
        array(
            'id'      => 'seo_url_submit_on',
            'type'    => 'select',
            'title'   => __('提交时机', 'onedown'),
            'subtitle' => __('选择自动提交 URL 的触发时机', 'onedown'),
            'options' => array(
                'publish' => __('仅发布时提交', 'onedown'),
                'all'     => __('发布和更新时都提交', 'onedown'),
            ),
            'default' => 'publish',
            'dependency' => array('seo_url_submit_enabled', '==', 'true'),
        ),

        // 鈹€鈹€ 百度 鈹€鈹€
        array(
            'type'    => 'subheading',
            'content' => __('百度搜索', 'onedown'),
        ),
        array(
            'id'         => 'seo_url_submit_baidu_enabled',
            'type'       => 'switcher',
            'title'      => __('启用百度主动提交', 'onedown'),
            'default'    => true,
            'dependency' => array('seo_url_submit_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_baidu_token',
            'type'       => 'text',
            'title'      => __('百度 Token', 'onedown'),
            'subtitle'   => __('填写百度搜索资源平台提供的推送 Token', 'onedown'),
            'desc'       => __('接口示例：http://data.zz.baidu.com/urls?site=你的站点&token=此处Token', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_baidu_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_baidu_site',
            'type'       => 'text',
            'title'      => __('百度站点域名', 'onedown'),
            'subtitle'   => __('填写在百度搜索资源平台验证的站点域名，例如 www.example.com', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_baidu_enabled', '==', 'true'),
        ),

        // 鈹€鈹€ 360 鈹€鈹€
        array(
            'type'    => 'subheading',
            'content' => __('360 搜索', 'onedown'),
        ),
        array(
            'id'         => 'seo_url_submit_360_enabled',
            'type'       => 'switcher',
            'title'      => __('启用 360 主动提交', 'onedown'),
            'default'    => false,
            'dependency' => array('seo_url_submit_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_360_token',
            'type'       => 'text',
            'title'      => __('360 Token', 'onedown'),
            'subtitle'   => __('填写 360 站长平台提供的推送 Token', 'onedown'),
            'desc'       => __('接口示例：http://data.360.cn/urls?site=你的站点&token=此处Token', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_360_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_360_site',
            'type'       => 'text',
            'title'      => __('360 站点域名', 'onedown'),
            'subtitle'   => __('填写在 360 站长平台验证的站点域名，例如 www.example.com', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_360_enabled', '==', 'true'),
        ),

        // 鈹€鈹€ Bing 鈹€鈹€
        array(
            'type'    => 'subheading',
            'content' => __('Bing 搜索', 'onedown'),
        ),
        array(
            'id'         => 'seo_url_submit_bing_enabled',
            'type'       => 'switcher',
            'title'      => __('启用 Bing 提交', 'onedown'),
            'subtitle'   => __('Bing 支持通过 sitemap ping 提交，也可配置 API Key', 'onedown'),
            'default'    => false,
            'dependency' => array('seo_url_submit_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_bing_api_key',
            'type'       => 'text',
            'title'      => __('Bing API Key（可选）', 'onedown'),
            'subtitle'   => __('可在 Bing Webmaster Tools 的 API 设置中获取，不填则使用 sitemap ping 方式', 'onedown'),
            'desc'       => __('配置 API Key 后可提交单个 URL，否则仅提交 sitemap', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_bing_enabled', '==', 'true'),
        ),

        // 鈹€鈹€ Google 鈹€鈹€
        array(
            'type'    => 'subheading',
            'content' => __('Google 搜索', 'onedown'),
        ),
        array(
            'id'         => 'seo_url_submit_google_enabled',
            'type'       => 'switcher',
            'title'      => __('启用 Google 提交', 'onedown'),
            'subtitle'   => __('Google 主要通过 sitemap ping 方式通知抓取更新', 'onedown'),
            'default'    => false,
            'dependency' => array('seo_url_submit_enabled', '==', 'true'),
        ),

        // 鈹€鈹€ 搜狗 鈹€鈹€
        array(
            'type'    => 'subheading',
            'content' => __('搜狗搜索', 'onedown'),
        ),
        array(
            'id'         => 'seo_url_submit_sogou_enabled',
            'type'       => 'switcher',
            'title'      => __('启用搜狗主动提交', 'onedown'),
            'default'    => false,
            'dependency' => array('seo_url_submit_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_sogou_token',
            'type'       => 'text',
            'title'      => __('搜狗 Token', 'onedown'),
            'subtitle'   => __('填写搜狗站长平台提供的推送 Token', 'onedown'),
            'desc'       => __('接口示例：http://data.sogou.com/urls?site=你的站点&token=此处Token', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_sogou_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_sogou_site',
            'type'       => 'text',
            'title'      => __('搜狗站点域名', 'onedown'),
            'subtitle'   => __('填写在搜狗站长平台验证的站点域名', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_sogou_enabled', '==', 'true'),
        ),

        // 神马
        array(
            'type'    => 'subheading',
            'content' => __('神马搜索', 'onedown'),
        ),
        array(
            'id'         => 'seo_url_submit_shenma_enabled',
            'type'       => 'switcher',
            'title'      => __('启用神马主动提交', 'onedown'),
            'default'    => false,
            'dependency' => array('seo_url_submit_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_shenma_token',
            'type'       => 'text',
            'title'      => __('神马 Token', 'onedown'),
            'subtitle'   => __('填写神马站长平台提供的推送 Token', 'onedown'),
            'desc'       => __('接口示例：http://data.sm.cn/urls?site=你的站点&token=此处Token', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_shenma_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_shenma_site',
            'type'       => 'text',
            'title'      => __('神马站点域名', 'onedown'),
            'subtitle'   => __('填写在神马站长平台验证的站点域名', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_shenma_enabled', '==', 'true'),
        ),

        // 鈹€鈹€ 夸克 鈹€鈹€
        array(
            'type'    => 'subheading',
            'content' => __('夸克搜索', 'onedown'),
        ),
        array(
            'id'         => 'seo_url_submit_quark_enabled',
            'type'       => 'switcher',
            'title'      => __('启用夸克提交', 'onedown'),
            'subtitle'   => __('夸克搜索统一使用 UC 站长平台接口', 'onedown'),
            'default'    => false,
            'dependency' => array('seo_url_submit_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_quark_token',
            'type'       => 'text',
            'title'      => __('夸克/UC Token', 'onedown'),
            'subtitle'   => __('填写 UC 站长平台提供的推送 Token', 'onedown'),
            'desc'       => __('接口示例：http://data.uc.cn/urls?site=你的站点&token=此处Token', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_quark_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'seo_url_submit_quark_site',
            'type'       => 'text',
            'title'      => __('夸克/UC 站点域名', 'onedown'),
            'subtitle'   => __('填写在 UC 站长平台验证的站点域名', 'onedown'),
            'default'    => '',
            'dependency' => array('seo_url_submit_quark_enabled', '==', 'true'),
        ),

        // 批量主动推送
        array(
            'type'    => 'subheading',
            'content' => __('批量主动推送', 'onedown'),
        ),
        array(
            'id'      => 'seo_active_push_enabled',
            'type'    => 'switcher',
            'title'   => __('启用批量推送工具', 'onedown'),
            'subtitle' => __('开启后会在后台菜单新增“批量推送”页面，可手动批量提交历史内容', 'onedown'),
            'default' => false,
        ),

        // 授权管理页面 SEO
        array(
            'type'    => 'subheading',
            'content' => __('授权管理页面 SEO', 'onedown'),
        ),
        array(
            'id'      => 'seo_license_title',
            'type'    => 'text',
            'title'   => __('授权管理页 SEO 标题', 'onedown'),
            'subtitle' => __('自定义 /od-license.html 页面的 SEO 标题', 'onedown'),
            'default' => '',
        ),
        array(
            'id'      => 'seo_license_description',
            'type'    => 'textarea',
            'title'   => __('授权管理页 SEO 描述', 'onedown'),
            'subtitle' => __('自定义 /od-license.html 页面的 meta description', 'onedown'),
            'default' => '',
        ),
        array(
            'id'    => 'seo_license_keywords',
            'type'  => 'textarea',
            'title' => __('授权管理页 SEO 关键词', 'onedown'),
            'subtitle' => __('自定义 /od-license.html 页面的 meta keywords', 'onedown'),
            'desc'  => __('多个关键词用英文逗号分隔', 'onedown'),
            'default' => '',
        ),

        // 自动内链
        array(
            'type'    => 'subheading',
            'content' => __('自动内链', 'onedown'),
        ),
        array(
            'id'      => 'seo_auto_internal_link',
            'type'    => 'switcher',
            'title'   => __('启用自动内链', 'onedown'),
            'subtitle' => __('开启后会在文章标签和正文中自动为匹配关键词添加链接，并跳过图片链接', 'onedown'),
            'default' => false,
        ),
    ),
));

// -- 常用功能
CSF::createSection($prefix, array(
    'id'     => 'global-functions',
    'parent' => 'global',
    'title'  => __('常用功能', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'use_hbd_font',
            'type'    => 'switcher',
            'title'   => __('启用 HBD 字体', 'onedown'),
            'subtitle' => __('开启或关闭前台内置 HBD 字体', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'frontend_performance_optimize',
            'type'    => 'switcher',
            'title'   => __('前台性能优化', 'onedown'),
            'subtitle' => __('开启后自动启用主题内置的前台性能优化', 'onedown'),
            'default' => true,
        ),
        array(
            'type'    => 'subheading',
            'content' => __('验证码设置', 'onedown'),
        ),
        array(
            'id'      => 'captcha_switch',
            'type'    => 'content',
            'content' => __('以下开关用于控制各类表单是否启用图形验证码', 'onedown'),
        ),
        array(
            'id'      => 'captcha_login',
            'type'    => 'switcher',
            'title'   => __('登录启用验证码', 'onedown'),
            'subtitle' => __('开启后登录表单需要输入图形验证码', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'captcha_register',
            'type'    => 'switcher',
            'title'   => __('注册启用验证码', 'onedown'),
            'subtitle' => __('开启后注册表单需要输入图形验证码', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'captcha_resetpwd',
            'type'    => 'switcher',
            'title'   => __('找回密码启用验证码', 'onedown'),
            'subtitle' => __('开启后找回密码表单需要输入图形验证码', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'captcha_comment',
            'type'    => 'switcher',
            'title'   => __('评论启用验证码', 'onedown'),
            'subtitle' => __('开启后评论表单需要输入图形验证码', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'function_breadcrumb',
            'type'    => 'switcher',
            'title'   => __('显示面包屑导航', 'onedown'),
            'subtitle' => __('在页面顶部显示面包屑导航', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'function_lazyload',
            'type'    => 'switcher',
            'title'   => __('启用图片懒加载', 'onedown'),
            'subtitle' => __('开启后延迟加载图片，提升页面加载速度', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'function_avatar',
            'type'    => 'subheading',
            'content' => __('头像设置', 'onedown'),
        ),
        array(
            'id'      => 'avatar_source',
            'type'    => 'select',
            'title'   => __('头像来源', 'onedown'),
            'subtitle' => __('选择头像获取来源，默认使用 Gravatar 官方', 'onedown'),
            'options' => array(
                'gravatar'   => __('Gravatar 官方（国际）', 'onedown'),
                'cravatar'   => __('Cravatar 镜像（国内推荐）', 'onedown'),
                'lolicdn'    => __('Gravatar.loli.net 镜像', 'onedown'),
                'sepcc'      => __('Sep.cc CDN（当前）', 'onedown'),
                'custom'     => __('自定义源', 'onedown'),
            ),
            'default' => 'cravatar',
        ),
        array(
            'id'         => 'avatar_custom_url',
            'type'       => 'text',
            'title'      => __('自定义头像源地址', 'onedown'),
            'subtitle'   => __('填写自定义 Gravatar 镜像地址，例如 https://your-mirror.com/avatar', 'onedown'),
            'desc'       => __('仅在选择“自定义源”时生效，地址需要兼容 Gravatar URL 格式', 'onedown'),
            'dependency' => array('avatar_source', '==', 'custom'),
            'default'    => '',
        ),
    ),
));

// -- 显示布局
CSF::createSection($prefix, array(
    'id'     => 'global-layout',
    'parent' => 'global',
    'title'  => __('显示布局', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'layout_style',
            'type'    => 'select',
            'title'   => __('布局风格', 'onedown'),
            'options' => array(
                'wide'  => __('宽屏布局', 'onedown'),
                'boxed' => __('盒子布局', 'onedown'),
            ),
            'default' => 'wide',
        ),
        array(
            'id'      => 'layout_sidebar',
            'type'    => 'select',
            'title'   => __('侧边栏位置', 'onedown'),
            'options' => array(
                'right' => __('右侧', 'onedown'),
                'left'  => __('左侧', 'onedown'),
            ),
            'default' => 'right',
        ),
        array(
            'id'      => 'layout_width',
            'type'    => 'slider',
            'title'   => __('内容宽度', 'onedown'),
            'default' => 1200,
            'min'     => 960,
            'max'     => 1600,
            'step'    => 10,
            'unit'    => 'px',
        ),
        array(
            'id'      => 'theme_primary_color',
            'type'    => 'color',
            'title'   => __('主题主色', 'onedown'),
            'subtitle' => __('自定义站点主色，用于按钮、链接、标签等主要元素', 'onedown'),
            'default' => '#f04494',
        ),
        array(
            'type'    => 'content',
            'content' => '<div class="csf-field csf-field-palette compact skin-color"><div class="csf-title"><h4>' . __('快捷色板', 'onedown') . '</h4></div><div class="csf-fieldset"><div class="csf-siblings csf--palettes"><div class="csf--sibling csf--palette" data-color="#fd2760"><span style="background: #fd2760;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#fd2760"></div><div class="csf--sibling csf--palette csf--active" data-color="#f04494"><span style="background: #f04494;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#f04494" checked></div><div class="csf--sibling csf--palette" data-color="#ae53f3"><span style="background: #ae53f3;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#ae53f3"></div><div class="csf--sibling csf--palette" data-color="#627bf5"><span style="background: #627bf5;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#627bf5"></div><div class="csf--sibling csf--palette" data-color="#00a2e3"><span style="background: #00a2e3;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#00a2e3"></div><div class="csf--sibling csf--palette" data-color="#16b597"><span style="background: #16b597;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#16b597"></div><div class="csf--sibling csf--palette" data-color="#36af18"><span style="background: #36af18;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#36af18"></div><div class="csf--sibling csf--palette" data-color="#8fb107"><span style="background: #8fb107;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#8fb107"></div><div class="csf--sibling csf--palette" data-color="#b18c07"><span style="background: #b18c07;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#b18c07"></div><div class="csf--sibling csf--palette" data-color="#e06711"><span style="background: #e06711;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#e06711"></div><div class="csf--sibling csf--palette" data-color="#f74735"><span style="background: #f74735;"></span><input type="radio" name="_onedown_options[theme_color_presets]" value="#f74735"></div></div></div><div class="clear"></div></div>',
        ),
        array(
            'id'      => 'close_signup',
            'type'    => 'switcher',
            'title'   => __('关闭用户注册', 'onedown'),
            'subtitle' => __('开启后前台将不再允许新用户注册账号', 'onedown'),
            'default' => false,
        ),
        array(
            'id'       => 'user_agreement_page',
            'type'     => 'select',
            'title'    => __('用户协议页面', 'onedown'),
            'subtitle' => __('选择前台用户协议页面', 'onedown'),
            'options'  => 'pages',
            'query_args' => array(
                'posts_per_page' => -1,
            ),
            'default' => '',
        ),
        array(
            'id'      => 'user_redirect_after_login',
            'type'    => 'select',
            'title'   => __('登录后跳转页面', 'onedown'),
            'options' => array(
                'user_center' => __('用户中心', 'onedown'),
                'home'        => __('网站首页', 'onedown'),
                'previous'    => __('之前页面', 'onedown'),
            ),
            'default' => 'user_center',
        ),
    ),
));

// -- 自定义代码
CSF::createSection($prefix, array(
    'id'     => 'global-custom-code',
    'parent' => 'global',
    'title'  => __('自定义代码', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'custom_css',
            'type'    => 'code_editor',
            'title'   => __('自定义 CSS', 'onedown'),
            'settings' => array(
                'mode'   => 'css',
                'theme'  => 'monokai',
                'height' => 200,
            ),
        ),
        array(
            'id'      => 'custom_js',
            'type'    => 'code_editor',
            'title'   => __('自定义 JavaScript', 'onedown'),
            'settings' => array(
                'mode'   => 'javascript',
                'theme'  => 'monokai',
                'height' => 200,
            ),
        ),
        array(
            'id'      => 'custom_header',
            'type'    => 'code_editor',
            'title'   => __('头部代码', 'onedown'),
            'subtitle' => __('输出到页面 head 区域，可放统计、验证或自定义脚本', 'onedown'),
            'settings' => array(
                'mode'   => 'html',
                'theme'  => 'monokai',
                'height' => 150,
            ),
        ),
        array(
            'id'      => 'custom_footer',
            'type'    => 'code_editor',
            'title'   => __('底部代码', 'onedown'),
            'subtitle' => __('输出到页面底部的代码，例如统计代码或附加脚本', 'onedown'),
            'settings' => array(
                'mode'   => 'html',
                'theme'  => 'monokai',
                'height' => 150,
            ),
        ),
    ),
));

// -- 外链跳转
CSF::createSection($prefix, array(
    'id'     => 'global-go-link',
    'parent' => 'global',
    'title'  => __('外链跳转', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'go_link_s',
            'type'    => 'switcher',
            'title'   => __('启用外链跳转', 'onedown'),
            'subtitle' => __('开启后站内外部链接将先跳转到 go.php 过渡页，有利于 SEO 权重控制', 'onedown'),
            'default' => false,
        ),
        array(
            'id'         => 'go_link_post',
            'type'       => 'switcher',
            'title'      => __('文章内容外链跳转', 'onedown'),
            'subtitle'   => __('开启后文章正文中的外部链接会自动转换为跳转链接', 'onedown'),
            'default'    => true,
            'dependency' => array('go_link_s', '==', 'true'),
        ),
        array(
            'id'         => 'go_link_new_tab',
            'type'       => 'switcher',
            'title'      => __('新窗口打开 go 跳转', 'onedown'),
            'subtitle'   => __('开启后 go 跳转链接在新窗口打开，关闭后可返回当前页', 'onedown'),
            'default'    => false,
            'dependency' => array('go_link_s', '==', 'true'),
        ),
        array(
            'id'         => 'go_link_exclude_domain',
            'type'       => 'textarea',
            'title'      => __('排除域名', 'onedown'),
            'subtitle'   => __('这些域名不会进入跳转，每行一个或使用英文逗号分隔', 'onedown'),
            'desc'       => __('例如：baidu.com 或 www.baidu.com，支持不带 www 的纯域名', 'onedown'),
            'default'    => '',
            'dependency' => array('go_link_s', '==', 'true'),
        ),
    ),
));

// =====================
// 页面设置
// =====================
CSF::createSection($prefix, array(
    'id'    => 'page',
    'title' => __('页面&显示', 'onedown'),
    'icon'  => 'fas fa-file',
));

// -- 顶部导航
CSF::createSection($prefix, array(
    'id'     => 'page-header',
    'parent' => 'page',
    'title'  => __('顶部导航', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'header_fixed',
            'type'    => 'switcher',
            'title'   => __('固定顶部', 'onedown'),
            'subtitle' => __('开启后导航栏固定在页面顶部，滚动页面时保持可见', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'header_search',
            'type'    => 'switcher',
            'title'   => __('搜索功能', 'onedown'),
            'subtitle' => __('在导航栏中显示搜索按钮', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'header_theme_toggle',
            'type'    => 'switcher',
            'title'   => __('主题切换按钮', 'onedown'),
            'subtitle' => __('在导航栏中显示深色和浅色模式切换按钮', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'header_user_menu',
            'type'    => 'switcher',
            'title'   => __('用户菜单', 'onedown'),
            'subtitle' => __('在导航栏右侧显示用户头像或登录入口', 'onedown'),
            'default' => true,
        ),
    ),
));

// -- 移动端底部导航
CSF::createSection($prefix, array(
    'id'     => 'page-mobile-tabbar',
    'parent' => 'page',
    'title'  => __('移动底部导航', 'onedown'),
    'fields' => array(
        array(
            'id'       => 'mobile_tabbar_enabled',
            'type'     => 'switcher',
            'title'    => __('启用底部导航', 'onedown'),
            'subtitle' => __('在移动端底部显示固定 Tabbar 导航', 'onedown'),
            'default'  => true,
        ),
        array(
            'id'         => 'mobile_tabbar_show_label',
            'type'       => 'switcher',
            'title'      => __('显示文字', 'onedown'),
            'subtitle'   => __('关闭后仅显示图标，不显示导航文字', 'onedown'),
            'default'    => true,
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'type'       => 'subheading',
            'content'    => __('首页 Tab', 'onedown'),
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_home_title',
            'type'       => 'text',
            'title'      => __('首页名称', 'onedown'),
            'default'    => '首页',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_home_icon',
            'type'       => 'icon',
            'title'      => __('首页图标', 'onedown'),
            'default'    => 'fa fa-home',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_home_url',
            'type'       => 'text',
            'title'      => __('首页地址', 'onedown'),
            'default'    => home_url('/'),
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'type'       => 'subheading',
            'content'    => __('分类 Tab', 'onedown'),
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_category_title',
            'type'       => 'text',
            'title'      => __('分类名称', 'onedown'),
            'default'    => '分类',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_category_icon',
            'type'       => 'icon',
            'title'      => __('分类图标', 'onedown'),
            'default'    => 'fa fa-th-large',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_category_url',
            'type'       => 'text',
            'title'      => __('分类地址', 'onedown'),
            'default'    => home_url('/cates/'),
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'type'       => 'subheading',
            'content'    => __('VIP Tab', 'onedown'),
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_vip_title',
            'type'       => 'text',
            'title'      => __('VIP 名称', 'onedown'),
            'default'    => 'VIP',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_vip_icon',
            'type'       => 'icon',
            'title'      => __('VIP 图标', 'onedown'),
            'default'    => 'fa fa-diamond',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_vip_url',
            'type'       => 'text',
            'title'      => __('VIP 地址', 'onedown'),
            'default'    => function_exists('onedown_vip_page_url') ? onedown_vip_page_url() : home_url('/vip/'),
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'type'       => 'subheading',
            'content'    => __('我的 Tab', 'onedown'),
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_user_title',
            'type'       => 'text',
            'title'      => __('我的名称', 'onedown'),
            'default'    => '我的',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_user_icon',
            'type'       => 'icon',
            'title'      => __('我的图标', 'onedown'),
            'default'    => 'fa fa-user-o',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'mobile_tabbar_user_url',
            'type'       => 'text',
            'title'      => __('我的地址', 'onedown'),
            'subtitle'   => __('留空时，已登录跳转到用户中心，未登录跳转到登录页', 'onedown'),
            'default'    => '',
            'dependency' => array('mobile_tabbar_enabled', '==', 'true'),
        ),
    ),
));

// -- 搜索设置
CSF::createSection($prefix, array(
    'id'     => 'page-search',
    'parent' => 'page',
    'title'  => __('搜索设置', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'search_post_only',
            'type'    => 'switcher',
            'title'   => __('仅搜索文章', 'onedown'),
            'subtitle' => __('开启后前台站内搜索只查询文章 post，不显示页面和其他内容类型', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'search_spam_markers',
            'type'    => 'textarea',
            'title'   => __('垃圾搜索 Markers', 'onedown'),
            'subtitle' => __('每行一个 marker，命中 TG/Telegram/电报 等联系方式并同时命中这些 marker 时，将直接拦截搜索', 'onedown'),
            'desc'    => __("示例：\n引流\n获客\n咨询\n精准\n推广\n客源\n流量", 'onedown'),
            'default' => "引流\n获客\n咨询\n精准\n推广\n客源\n流量\n搜索引流\nyinliu\nhuoke\nzixun\njingzhun\ntuiguang\nkeyuan\nliuliang",
        ),
    ),
));

// -- 悬浮按钮
CSF::createSection($prefix, array(
    'id'     => 'page-float-buttons',
    'parent' => 'page',
    'title'  => __('悬浮按钮', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'float_btn_position',
            'type'    => 'button_set',
            'title'   => __('显示位置', 'onedown'),
            'options' => array(
                'right' => __('右侧', 'onedown'),
                'left'  => __('左侧', 'onedown'),
            ),
            'default' => 'right',
        ),
        array(
            'id'      => 'float_btn_filter',
            'type'    => 'checkbox',
            'title'   => __('显示设备', 'onedown'),
            'options' => array(
                'pc_s' => __('桌面端显示', 'onedown'),
                'm_s'  => __('移动端显示', 'onedown'),
            ),
            'default' => array('m_s'),
        ),
        array(
            'id'      => 'float_btn_scroll_hide',
            'type'    => 'switcher',
            'title'   => __('滚动时自动隐藏', 'onedown'),
            'subtitle' => __('开启后页面滚动过程中可临时隐藏悬浮按钮', 'onedown'),
            'default' => false,
        ),
        array(
            'id'      => 'float_btn',
            'type'    => 'group',
            'title'   => __('悬浮按钮列表', 'onedown'),
            'subtitle' => __('支持自定义按钮顺序、图标和显示终端', 'onedown'),
            'default' => array(
                array(
                    'type'  => 'theme_toggle',
                    'pc_s'  => true,
                    'm_s'   => true,
                    'title' => '切换模式',
                    'icon'  => 'fa fa-moon-o',
                ),
                array(
                    'type'  => 'pay_vip',
                    'pc_s'  => true,
                    'm_s'   => true,
                    'title' => '开通 VIP',
                    'icon'  => 'fa fa-diamond',
                ),
                array(
                    'type'               => 'build_similar',
                    'pc_s'               => true,
                    'm_s'                => true,
                    'title'              => '搭建同款',
                    'icon'               => 'fa fa-code',
                    'build_similar_desc' => '获取当前站点同款搭建方案',
                ),
                array(
                    'type'  => 'service_qq',
                    'pc_s'  => true,
                    'm_s'   => true,
                    'title' => 'QQ客服',
                    'icon'  => 'fa fa-qq',
                    'qq'    => '123456789',
                ),
                array(
                    'type'       => 'service_wechat',
                    'pc_s'       => true,
                    'm_s'        => true,
                    'title'      => '微信客服',
                    'icon'       => 'fa fa-wechat',
                ),
                array(
                    'type'            => 'custom_link',
                    'pc_s'            => true,
                    'm_s'             => true,
                    'title'           => '友情链接',
                    'icon'            => 'fa fa-link',
                    'custom_link_url' => 'https://example.com',
                ),
                array(
                    'type' => 'back_top',
                    'pc_s' => true,
                    'm_s'  => true,
                    'icon' => 'fa fa-angle-up',
                ),
            ),
            'fields' => array(
                array(
                    'id'      => 'type',
                    'type'    => 'select',
                    'title'   => __('按钮类型', 'onedown'),
                    'options' => array(
                        'back_top'       => __('返回顶部', 'onedown'),
                        'theme_toggle'   => __('切换深色/浅色模式', 'onedown'),
                        'pay_vip'        => __('开通 VIP', 'onedown'),
                        'edit_post'      => __('编辑文章', 'onedown'),
                        'service_qq'     => __('QQ客服', 'onedown'),
                        'service_wechat' => __('微信客服', 'onedown'),
                        'build_similar'  => __('搭建同款', 'onedown'),
                        'qq_group'       => __('QQ群', 'onedown'),
                        'wechat_group'   => __('微信群', 'onedown'),
                        'custom_link'    => __('自定义链接', 'onedown'),
                    ),
                ),
                array(
                    'id'      => 'pc_s',
                    'type'    => 'switcher',
                    'title'   => __('桌面端显示', 'onedown'),
                    'default' => true,
                ),
                array(
                    'id'      => 'm_s',
                    'type'    => 'switcher',
                    'title'  => __('移动端显示', 'onedown'),
                    'default' => true,
                ),
                array(
                    'id'      => 'title',
                    'type'    => 'text',
                    'title'   => __('按钮标题', 'onedown'),
                    'subtitle' => __('鼠标悬停时显示的提示文字', 'onedown'),
                    'default' => '',
                ),
                array(
                    'id'      => 'icon',
                    'type'    => 'icon',
                    'title'   => __('按钮图标', 'onedown'),
                    'subtitle' => __('选择按钮图标', 'onedown'),
                ),
                array(
                    'id'      => 'color',
                    'type'    => 'color',
                    'title'   => __('图标颜色', 'onedown'),
                    'subtitle' => __('设置当前按钮图标颜色', 'onedown'),
                ),
                array(
                    'id'      => 'qq',
                    'type'    => 'text',
                    'title'   => __('QQ号码', 'onedown'),
                    'subtitle' => __('QQ 客服类型：用于直接发起 QQ 聊天', 'onedown'),
                    'class'   => 'float-btn-svc service_qq',
                ),
                array(
                    'id'      => 'wechat_img',
                    'type'    => 'upload',
                    'title'   => __('微信二维码', 'onedown'),
                    'subtitle' => __('微信客服类型：上传弹窗展示的二维码图片', 'onedown'),
                    'library' => 'image',
                    'class'   => 'float-btn-svc service_wechat',
                ),
                array(
                    'id'      => 'build_similar_desc',
                    'type'    => 'textarea',
                    'title'   => __('搭建同款说明', 'onedown'),
                    'subtitle' => __('搭建同款类型：弹窗中展示的说明文案', 'onedown'),
                    'default' => '获取当前页面同款搭建方案',
                    'class'   => 'float-btn-svc build_similar',
                ),
                array(
                    'id'      => 'qq_group_number',
                    'type'    => 'text',
                    'title'   => __('QQ群号', 'onedown'),
                    'subtitle' => __('QQ群类型：填写群号用于展示或跳转', 'onedown'),
                    'class'   => 'float-btn-svc qq_group',
                ),
                array(
                    'id'      => 'qq_group_img',
                    'type'    => 'upload',
                    'title'   => __('QQ群二维码', 'onedown'),
                    'subtitle' => __('QQ群类型：弹窗中显示的群二维码，可选', 'onedown'),
                    'library' => 'image',
                    'class'   => 'float-btn-svc qq_group',
                ),
                array(
                    'id'      => 'wechat_group_img',
                    'type'    => 'upload',
                    'title'   => __('微信群二维码', 'onedown'),
                    'subtitle' => __('微信群类型：弹窗中显示的群二维码', 'onedown'),
                    'library' => 'image',
                    'class'   => 'float-btn-svc wechat_group',
                ),
                array(
                    'id'      => 'wechat_group_name',
                    'type'    => 'text',
                    'title'   => __('微信群名称', 'onedown'),
                    'subtitle' => __('微信群类型：弹窗中显示的群名称，可选', 'onedown'),
                    'class'   => 'float-btn-svc wechat_group',
                ),
                array(
                    'id'      => 'custom_link_url',
                    'type'    => 'text',
                    'title'   => __('链接地址', 'onedown'),
                    'subtitle' => __('自定义链接按钮的跳转地址', 'onedown'),
                    'default' => '',
                    'class'   => 'float-btn-svc custom_link',
                ),
            ),
        ),
    ),
));

// -- 底部页脚
CSF::createSection($prefix, array(
    'id'     => 'page-footer',
    'parent' => 'page',
    'title'  => __('底部页脚', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'footer_copyright',
            'type'    => 'text',
            'title'   => __('版权信息', 'onedown'),
            'default' => 'Copyright ' . date('Y') . ' ' . get_bloginfo('name') . '. All rights reserved.',
        ),
        array(
            'id'      => 'footer_powered',
            'type'    => 'switcher',
            'title'   => __('显示 Powered By', 'onedown'),
            'subtitle' => __('在版权区域显示 Powered By WordPress', 'onedown'),
            'default' => true,
        ),
        array(
            'type'    => 'subheading',
            'content' => __('页脚链接配置', 'onedown'),
        ),
        array(
            'id'      => 'footer_product_links',
            'type'    => 'repeater',
            'title'   => __('产品链接', 'onedown'),
            'subtitle' => __('页脚“产品”栏目下的链接列表', 'onedown'),
            'fields'  => array(
                array(
                    'id'    => 'text',
                    'type'  => 'text',
                    'title' => __('链接文字', 'onedown'),
                ),
                array(
                    'id'    => 'url',
                    'type'  => 'text',
                    'title' => __('链接地址', 'onedown'),
                ),
            ),
            'default' => array(
                array('text' => '主题市场', 'url' => home_url('/category/themes/')),
                array('text' => '插件推荐', 'url' => home_url('/category/plugins/')),
                array('text' => '教程中心', 'url' => home_url('/category/tutorials/')),
            ),
        ),

        array(
            'id'      => 'footer_support_links',
            'type'    => 'repeater',
            'title'   => __('支持链接', 'onedown'),
            'subtitle' => __('页脚“支持”栏目下的链接列表', 'onedown'),
            'fields'  => array(
                array(
                    'id'    => 'text',
                    'type'  => 'text',
                    'title' => __('链接文字', 'onedown'),
                ),
                array(
                    'id'    => 'url',
                    'type'  => 'text',
                    'title' => __('链接地址', 'onedown'),
                ),
            ),
            'default' => array(
                array('text' => '帮助中心', 'url' => home_url('/help/')),
                array('text' => '联系我们', 'url' => home_url('/contact/')),
                array('text' => '用户协议', 'url' => home_url('/agreement/')),
            ),
        ),
        array(
            'type'    => 'subheading',
            'content' => __('联系方式', 'onedown'),
        ),
        array(
            'id'      => 'footer_contact_phone',
            'type'    => 'text',
            'title'   => __('客服电话', 'onedown'),
            'default' => '400-123-4567',
        ),
        array(
            'id'      => 'footer_contact_email',
            'type'    => 'text',
            'title'   => __('客服邮箱', 'onedown'),
            'default' => 'support@example.com',
        ),
        array(
            'id'      => 'footer_contact_wechat',
            'type'    => 'text',
            'title'   => __('企业微信', 'onedown'),
            'default' => 'OneDown',
        ),
        array(
            'id'      => 'footer_contact_address',
            'type'    => 'text',
            'title'   => __('公司地址', 'onedown'),
            'default' => '待修复',
        ),
        array(
            'id'      => 'footer_business_hours',
            'type'    => 'text',
            'title'   => __('营业时间', 'onedown'),
            'default' => '周一至周五 9:00 - 18:00',
        ),
        array(
            'type'    => 'subheading',
            'content' => __('备案信息', 'onedown'),
        ),
        array(
            'id'      => 'footer_icp_beian',
            'type'    => 'text',
            'title'   => __('ICP 备案号', 'onedown'),
            'subtitle' => __('填写工信部 ICP 备案号，例如 京ICP备12345678号', 'onedown'),
            'default' => '待修复',
        ),
        array(
            'id'      => 'footer_icp_link',
            'type'    => 'switcher',
            'title'   => __('ICP 备案链接', 'onedown'),
            'subtitle' => __('开启后备案号将链接到工信部备案查询页面', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'footer_ps_beian',
            'type'    => 'text',
            'title'   => __('公安备案号', 'onedown'),
            'subtitle' => __('填写公安网备备案号，例如 京公网安备 11000000000000 号', 'onedown'),
            'default' => '待修复',
        ),
        array(
            'id'      => 'footer_ps_link',
            'type'    => 'text',
            'title'   => __('公安备案链接', 'onedown'),
            'subtitle' => __('默认使用公安备案查询地址，可按需替换', 'onedown'),
            'default' => 'https://www.beian.gov.cn/portal/registerSystemInfo',
        ),
    ),
));

// =====================
// 文章列表
// =====================
CSF::createSection($prefix, array(
    'id'    => 'post',
    'title' => __('文章&列表', 'onedown'),
    'icon'  => 'fas fa-list',
));

// -- 缩略图
CSF::createSection($prefix, array(
    'id'     => 'post-thumbnail',
    'parent' => 'post',
    'title'  => __('缩略图', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'thumbnail_enabled',
            'type'    => 'switcher',
            'title'   => __('启用缩略图', 'onedown'),
            'default' => true,
        ),
        array(
            'id'         => 'thumbnail_size',
            'type'       => 'select',
            'title'      => __('缩略图尺寸', 'onedown'),
            'options'     => array(
                'thumbnail' => __('缩略图 (150x150)', 'onedown'),
                'medium'    => __('中等 (300x300)', 'onedown'),
                'large'     => __('大尺寸 (1024x1024)', 'onedown'),
                'full'      => __('原图', 'onedown'),
            ),
            'default'     => 'medium',
            'dependency'  => array('thumbnail_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'thumbnail_random_fallback',
            'type'       => 'switcher',
            'title'      => __('启用随机默认缩略图', 'onedown'),
            'subtitle'   => __('开启后文章没有特色图或图片时随机显示默认图，关闭后使用下方指定缩略图', 'onedown'),
            'default'    => true,
            'dependency' => array('thumbnail_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'thumbnail_fallback',
            'type'       => 'media',
            'title'      => __('默认缩略图', 'onedown'),
            'subtitle'   => __('关闭随机默认图后，文章没有可用图片时使用这里设置的默认缩略图', 'onedown'),
            'library'    => 'image',
            'dependency' => array('thumbnail_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'auto_featured_image',
            'type'    => 'switcher',
            'title'   => __('自动获取特色图片', 'onedown'),
            'subtitle' => __('开启后可从文章首张图片中自动提取并设置为特色图', 'onedown'),
            'default' => false,
        ),
    ),
));

// -- 文章功能
CSF::createSection($prefix, array(
    'id'     => 'post-functions',
    'parent' => 'post',
    'title'  => __('文章功能', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'post_excerpt',
            'type'    => 'switcher',
            'title'   => __('显示摘要', 'onedown'),
            'default' => true,
        ),
        array(
            'id'         => 'post_excerpt_length',
            'type'       => 'slider',
            'title'      => __('摘要长度', 'onedown'),
            'default'    => 120,
            'min'        => 50,
            'max'        => 300,
            'step'       => 10,
            'dependency' => array('post_excerpt', '==', 'true'),
        ),
        array(
            'id'      => 'post_meta',
            'type'    => 'switcher',
            'title'   => __('显示文章信息', 'onedown'),
            'subtitle' => __('显示作者、日期、分类等文章信息', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'show_post_views',
            'type'    => 'switcher',
            'title'   => __('显示阅读量', 'onedown'),
            'subtitle' => __('在文章列表和详情页显示阅读量', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'show_post_likes',
            'type'    => 'switcher',
            'title'   => __('显示点赞按钮', 'onedown'),
            'subtitle' => __('在文章详情页显示点赞按钮', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'show_post_share',
            'type'    => 'switcher',
            'title'   => __('显示分享按钮', 'onedown'),
            'subtitle' => __('在文章详情页显示分享按钮', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'show_post_favorites',
            'type'    => 'switcher',
            'title'   => __('显示收藏按钮', 'onedown'),
            'subtitle' => __('在文章详情页显示收藏按钮', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'post_copyright_enabled',
            'type'    => 'switcher',
            'title'   => __('显示版权声明', 'onedown'),
            'subtitle' => __('在文章底部显示当前文章的版权声明内容', 'onedown'),
            'default' => true,
        ),
        array(
            'id'         => 'post_copyright_text',
            'type'       => 'textarea',
            'title'      => __('版权声明内容', 'onedown'),
            'subtitle'   => __('支持填写固定版权说明或转载声明', 'onedown'),
            'default' => '本文为原创内容，转载请注明出处。',
            'dependency' => array('post_copyright_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'show_post_sales',
            'type'    => 'switcher',
            'title'   => __('显示销量', 'onedown'),
            'subtitle' => __('在付费资源页面显示销量信息', 'onedown'),
            'default' => true,
        ),
    ),
));

// -- 评论设置
CSF::createSection($prefix, array(
    'id'     => 'post-comments',
    'parent' => 'post',
    'title'  => __('评论设置', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'comments_enabled',
            'type'    => 'switcher',
            'title'   => __('启用评论', 'onedown'),
            'subtitle' => __('关闭后前台隐藏文章评论区，并禁止提交评论', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'comments_avatar',
            'type'    => 'switcher',
            'title'   => __('显示头像', 'onedown'),
            'subtitle' => __('显示评论者的 Gravatar 头像', 'onedown'),
            'default' => true,
            'dependency' => array('comments_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'comments_order',
            'type'    => 'select',
            'title'   => __('评论排序', 'onedown'),
            'options'  => array(
                'asc'  => __('按时间升序，较早的评论在前', 'onedown'),
                'desc' => __('按时间降序，较新的评论在前', 'onedown'),
            ),
            'default' => 'desc',
            'dependency' => array('comments_enabled', '==', 'true'),
        ),
        array(
            'type'    => 'subheading',
            'content' => __('垃圾评论拦截', 'onedown'),
            'dependency' => array('comments_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'comment_spam_enabled',
            'type'    => 'switcher',
            'title'   => __('启用自动拦截', 'onedown'),
            'subtitle' => __('开启后根据下方规则自动拦截垃圾评论', 'onedown'),
            'default' => true,
            'dependency' => array('comments_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'comment_spam_keywords',
            'type'    => 'textarea',
            'title'   => __('关键词规则', 'onedown'),
            'subtitle' => __('每行一条规则，支持普通关键词、正则表达式和分隔符匹配', 'onedown'),
            'desc'    => __("示例：\n优惠\n|viagra|i\n/https?:\\/\\/[^\\s]+/i", 'onedown'),
            'default' => '',
            'dependency' => array('comment_spam_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'comment_spam_ips',
            'type'    => 'textarea',
            'title'   => __('IP 规则', 'onedown'),
            'subtitle' => __('每行一个 IP 或 IP 段，命中后直接拦截', 'onedown'),
            'desc'    => __('示例：192.168.1.10 或 192.168.1.*', 'onedown'),
            'default' => '',
            'dependency' => array('comment_spam_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'comment_spam_emails',
            'type'    => 'textarea',
            'title'   => __('邮箱规则', 'onedown'),
            'subtitle' => __('每行一个邮箱或邮箱域名，命中后直接拦截', 'onedown'),
            'desc'    => __('示例：spam@example.com 鎴?@example.cn', 'onedown'),
            'default' => '',
            'dependency' => array('comment_spam_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'comment_spam_urls',
            'type'    => 'textarea',
            'title'   => __('网址规则', 'onedown'),
            'subtitle' => __('每行一个网址片段或规则，用于拦截推广链接', 'onedown'),
            'desc'    => __("示例：cheap-loan\n/bit\\.ly/i", 'onedown'),
            'default' => '',
            'dependency' => array('comment_spam_enabled', '==', 'true'),
        ),
    ),
));


// -- 推广分成
CSF::createSection($prefix, array(
    'id'     => 'shop-referral',
    'parent' => 'shop',
    'title'  => __('推广分成', 'onedown'),
    'icon'   => '',
    'fields' => array(
        array(
            'id'      => 'referral_enabled',
            'type'    => 'switcher',
            'title'   => __('启用推广分成', 'onedown'),
            'subtitle' => __('开启后用户可通过推广链接邀请注册和购买会员，并获得佣金', 'onedown'),
            'default' => false,
        ),
        array(
            'id'      => 'referral_judgment',
            'type'    => 'select',
            'title'   => __('推广判定方式', 'onedown'),
            'subtitle' => __('选择如何识别推广关系', 'onedown'),
            'options' => array(
                'all'  => __('注册即绑定：通过推广进入站点并注册后即建立推广关系', 'onedown'),
                'link' => __('仅推广链接：仅通过推广链接直接购买的订单才结算佣金', 'onedown'),
                ''     => __('不启用判定', 'onedown'),
            ),
            'default' => 'all',
        ),
        array(
            'id'      => 'referral_withdraw_min',
            'type'    => 'text',
            'title'   => __('最低提现金额（元）', 'onedown'),
            'subtitle' => __('达到该金额后用户才可以申请提现', 'onedown'),
            'default' => '50',
            'dependency' => array('referral_withdraw_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'referral_withdraw_enabled',
            'type'    => 'switcher',
            'title'   => __('启用提现', 'onedown'),
            'subtitle' => __('开启后用户可提交佣金提现吗申请', 'onedown'),
            'default' => true,
        ),
        array(
            'id'      => 'referral_withdraw_fee',
            'type'    => 'text',
            'title'   => __('提现手续费', 'onedown'),
            'subtitle' => __('填写百分比或固定金额前请结合业务逻辑确认', 'onedown'),
            'default' => '0',
            'dependency' => array('referral_withdraw_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'referral_desc',
            'type'    => 'textarea',
            'title'   => __('推广说明', 'onedown'),
            'subtitle' => __('显示在推广页面的规则说明文案', 'onedown'),
            'default' => '分享你的推广链接，用户通过链接注册或购买后，你将获得对应佣金。',
        ),
    ),
));

// =====================
// 商城&付费
// =====================
CSF::createSection($prefix, array(
    'id'    => 'shop',
    'title' => __('商城&付费', 'onedown'),
    'icon'  => 'fas fa-shopping-cart',
));

// -- 商城配置
CSF::createSection($prefix, array(
    'id'     => 'shop-config',
    'parent' => 'shop',
    'title'  => __('商城配置', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'guest_purchase_enabled',
            'type'    => 'switcher',
            'title'   => __('启用免登录购买', 'onedown'),
            'subtitle' => __('开启后未登录用户可直接购买资源，购买记录通过浏览器缓存临时保存', 'onedown'),
            'default' => false,
        ),
        array(
            'id'         => 'guest_purchase_expire_days',
            'type'       => 'text',
            'title'      => __('免登录购买记录保留天数', 'onedown'),
            'subtitle'   => __('超过该天数后购买记录自动失效，填 0 表示永久保留', 'onedown'),
            'default'    => '30',
            'dependency' => array('guest_purchase_enabled', '==', 'true'),
        ),
        array(
            'id'      => 'guest_purchase_reminder',
            'type'    => 'textarea',
            'title'   => __('免登录购买提示', 'onedown'),
            'subtitle' => __('未登录用户在购买页面显示的说明文字', 'onedown'),
            'default' => '未登录购买记录将保存在当前浏览器中，建议注册账号以免丢失。',
        ),
        array(
            'id'      => 'currency_symbol',
            'type'    => 'text',
            'title'   => __('货币符号', 'onedown'),
            'subtitle' => __('用于前台价格显示', 'onedown'),
            'default'  => '楼',
            'desc'     => __('例如：¥、元、积分、楼币等', 'onedown'),
        ),
        array(
            'id'      => 'free_resource_require_login',
            'type'    => 'switcher',
            'title'   => __('免费资源需登录', 'onedown'),
            'subtitle' => __('开启后即使是免费资源，也需要登录后才能下载', 'onedown'),
            'default' => true,
        ),
    ),
));

    // -- 广告自助投放
    CSF::createSection($prefix, array(
        'id'     => 'shop-ad',
        'parent' => 'shop',
        'title'  => __('广告自助投放', 'onedown'),
        'fields' => array(
            array(
                'id'      => 'ad_self_service_enabled',
                'type'    => 'switcher',
                'title'   => __('启用自助投放', 'onedown'),
                'subtitle' => __('开启后用户可在前台自助提交广告投放申请', 'onedown'),
                'default' => false,
            ),
            array(
                'id'         => 'ad_price',
                'type'       => 'text',
                'title'      => __('广告价格', 'onedown'),
                'subtitle'   => __('单次广告投放费用', 'onedown'),
                'default'    => '19.99',
                'dependency' => array('ad_self_service_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'ad_duration_days',
                'type'       => 'text',
                'title'      => __('广告有效天数', 'onedown'),
                'subtitle'   => __('支付成功后广告展示的有效天数', 'onedown'),
                'default'    => '30',
                'dependency' => array('ad_self_service_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'ad_max_per_user',
                'type'       => 'text',
                'title'      => __('每人最多投放数', 'onedown'),
                'subtitle'   => __('限制单个用户最多可投放的广告数量', 'onedown'),
                'default'    => '5',
                'dependency' => array('ad_self_service_enabled', '==', 'true'),
            ),
            array(
                'type'    => 'subheading',
                'content' => __('广告投放协议', 'onedown'),
            ),
            array(
                'id'         => 'ad_agreement_content',
                'type'       => 'wp_editor',
                'title'      => __('协议内容', 'onedown'),
                'subtitle'   => __('用户提交广告前需要阅读并同意的协议内容', 'onedown'),
                'desc'       => __('支持富文本编辑，可按业务要求自定义条款', 'onedown'),
                'default'    => '<p>广告投放协议</p><p>1. 禁止投放任何涉及违法、违规、低俗或侵权内容的广告。</p><p>2. 对违反规定的广告，站点有权拒绝、下架且不予退款。</p><p>3. 所有广告需经管理员审核通过后方可展示。</p><p>4. 本站保留对协议内容的最终解释权。</p>',
                'settings'   => array(
                    'textarea_rows' => 8,
                    'media_buttons' => false,
                ),
                'dependency' => array('ad_self_service_enabled', '==', 'true'),
            ),
        ),
    ));

    // -- VIP会员
    CSF::createSection($prefix, array(
        'id'     => 'shop-vip',
        'parent' => 'shop',
        'title'  => __('VIP会员', 'onedown'),
        'fields' => array(
            // 会员类型
            array(
                'id'      => 'vip_members',
                'type'    => 'group',
                'title'   => __('会员类型', 'onedown'),
                'subtitle' => __('自定义不同会员类型，每个会员可设置价格、时长、下载限制和推广分成比例', 'onedown'),
                'default' => array(
                    array(
                        'vip_id'          => 'monthly',
                        'vip_name'        => '月度会员',
                        'vip_price'       => '29',
                        'vip_show_price'  => '49',
                        'vip_months'      => '1',
                        'vip_download_limit' => '50',
                        'vip_commission_ratio' => '10',
                        'vip_desc'        => '适合轻度体验',
                        'vip_tag'         => '',
                    ),
                    array(
                        'vip_id'          => 'yearly',
                        'vip_name'        => '年度会员',
                        'vip_price'       => '199',
                        'vip_show_price'  => '399',
                        'vip_months'      => '12',
                        'vip_download_limit' => '200',
                        'vip_commission_ratio' => '20',
                        'vip_desc'        => '适合长期运营',
                        'vip_tag'         => '推荐',
                    ),
                    array(
                        'vip_id'          => 'forever',
                        'vip_name'        => '永久会员',
                        'vip_price'       => '399',
                        'vip_show_price'  => '999',
                        'vip_months'      => '0',
                        'vip_download_limit' => '999999',
                        'vip_commission_ratio' => '30',
                        'vip_desc'        => '一次性购买，永久有效',
                        'vip_tag'         => '超值',
                    ),
                ),
                'fields' => array(
                    array(
                        'id'      => 'vip_id',
                        'type'    => 'text',
                        'title'   => __('会员标识', 'onedown'),
                        'subtitle' => __('用于区分会员类型，建议使用唯一英文标识', 'onedown'),
                        'default' => '',
                    ),
                    array(
                        'id'      => 'vip_name',
                        'type'    => 'text',
                        'title'   => __('会员名称', 'onedown'),
                        'subtitle' => __('前台显示的会员名称', 'onedown'),
                        'default' => '',
                    ),
                    array(
                        'id'      => 'vip_price',
                        'type'    => 'text',
                        'title'   => __('会员价格', 'onedown'),
                        'subtitle' => __('单位：元', 'onedown'),
                        'default' => '',
                    ),
                    array(
                        'id'      => 'vip_show_price',
                        'type'    => 'text',
                        'title'   => __('划线价格', 'onedown'),
                        'subtitle' => __('用于前台展示原价或对比价', 'onedown'),
                        'default' => '',
                    ),
                    array(
                        'id'      => 'vip_months',
                        'type'    => 'text',
                        'title'   => __('有效期（月）', 'onedown'),
                        'subtitle' => __('填写数字，填 0 表示永久有效', 'onedown'),
                        'default' => '1',
                    ),
                    array(
                        'id'      => 'vip_download_limit',
                        'type'    => 'text',
                        'title'   => __('每日下载次数限制', 'onedown'),
                        'subtitle' => __('填 -1 表示不限制', 'onedown'),
                        'default' => '-1',
                    ),
                    array(
                        'id'      => 'vip_commission_ratio',
                        'type'    => 'text',
                        'title'   => __('推广分成比例', 'onedown'),
                        'subtitle' => __('该等级会员推广获得的佣金比例，例如 10 表示 10%', 'onedown'),
                        'default' => '0',
                    ),
                    array(
                        'id'      => 'vip_desc',
                        'type'    => 'text',
                        'title'   => __('会员描述', 'onedown'),
                        'subtitle' => __('显示在会员卡片或购买页面的简短说明', 'onedown'),
                        'default' => '',
                    ),
                    array(
                        'id'      => 'vip_tag',
                        'type'    => 'text',
                        'title'   => __('促销标签', 'onedown'),
                        'subtitle' => __('例如“推荐”“限时”“热卖”等标签', 'onedown'),
                        'default' => '',
                    ),
                ),
            ),
            // 会员权益
            array(
                'id'      => 'vip_benefits',
                'type'    => 'repeater',
                'title'   => __('会员权益', 'onedown'),
                'subtitle' => __('显示在会员开通页面的权益列表', 'onedown'),
                'default' => array(
                    array('text' => '每日免费下载资源'),
                    array('text' => '专属会员标识'),
                    array('text' => '专属会员折扣'),
                    array('text' => '推广赚取佣金'),
                ),
                'fields' => array(
                    array(
                        'id'      => 'text',
                        'type'    => 'text',
                        'title'   => __('权益描述', 'onedown'),
                        'default' => '',
                    ),
                ),
            ),
            // 会员参数
            array(
                'id'      => 'vip_download_unlimit',
                'type'    => 'text',
                'title'   => __('非会员每日下载次数', 'onedown'),
                'subtitle' => __('用于限制普通用户每天可下载的资源数量', 'onedown'),
                'default' => '5',
            ),
            // 会员介绍
            array(
                'id'      => 'vip_intro',
                'type'    => 'wp_editor',
                'title'   => __('会员介绍', 'onedown'),
                'subtitle' => __('显示在会员开通页面顶部的介绍内容，支持 HTML', 'onedown'),
                'default'  => '<p>开通会员，解锁更多专属权益。</p>',
                'settings' => array(
                    'textarea_rows' => 8,
                    'media_buttons' => false,
                ),
            ),

            // 文章付费默认价格
            array(
                'type'    => 'subheading',
                'content' => __('文章付费默认价格', 'onedown'),
            ),
            array(
                'id'       => 'pay_default_price',
                'type'     => 'number',
                'title'    => __('默认售价（元）', 'onedown'),
                'subtitle' => __('新建付费文章时默认使用的销售价格', 'onedown'),
                'default'  => '9.99',
                'min'      => 0,
                'step'     => 0.01,
            ),
            array(
                'id'       => 'pay_default_orig_price',
                'type'     => 'number',
                'title'    => __('默认划线价（元）', 'onedown'),
                'subtitle' => __('用于展示原价或优惠前价格', 'onedown'),
                'default'  => '19.99',
                'min'      => 0,
                'step'     => 0.01,
            ),
            array(
                'id'      => 'pay_unified_price',
                'type'    => 'switcher',
                'title'   => __('开启统一售价', 'onedown'),
                'subtitle' => __('开启后所有付费文章默认使用统一售价', 'onedown'),
                'default' => false,
            ),
            array(
                'id'       => 'pay_default_vip_prices',
                'type'     => 'textarea',
                'title'    => __('默认 VIP 会员价格', 'onedown'),
                'subtitle' => __('为不同会员类型设置默认专属价格', 'onedown'),
                'desc'     => __('每行一条，格式为 会员标识:价格，例如 monthly:19.99', 'onedown'),
                'default'  => "monthly:19.99\nyearly:99.99\nforever:199.99",
            ),

            // 下载页面跳转
            array(
                'type'    => 'subheading',
                'content' => __('下载页面跳转', 'onedown'),
            ),
            array(
                'id'      => 'download_redirect_enabled',
                'type'    => 'switcher',
                'title'   => __('启用下载页面跳转', 'onedown'),
                'subtitle' => __('开启后下载按钮先跳转到中间页，再进入真实下载地址', 'onedown'),
                'default' => false,
            ),
            array(
                'id'         => 'download_redirect_mode',
                'type'       => 'select',
                'title'      => __('下载跳转模式', 'onedown'),
                'subtitle'   => __('普通模式使用主题现有逻辑，二维码模式显示扫码页，自定义模式跳到你创建的下载页', 'onedown'),
                'options'    => array(
                    'normal'  => __('普通模式', 'onedown'),
                    'qrcode'  => __('二维码模式', 'onedown'),
                    'custom'  => __('自定义模式', 'onedown'),
                ),
                'default'    => 'normal',
                'dependency' => array('download_redirect_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'download_redirect_page',
                'type'       => 'select',
                'title'      => __('自定义下载页面', 'onedown'),
                'subtitle'   => __('选择你新建的“下载页面”模板页面，仅在自定义模式下生效', 'onedown'),
                'options'    => 'onedown_get_download_pages',
                'default'    => '',
                'dependency' => array('download_redirect_mode|download_redirect_enabled', '==|==', 'custom|true'),
            ),
            array(
                'id'         => 'download_box_simplified',
                'type'       => 'switcher',
                'title'      => __('文章详情页简化下载框', 'onedown'),
                'subtitle'   => __('开启后文章页下载区域使用简化展示样式', 'onedown'),
                'default'    => false,
                'dependency' => array('download_redirect_enabled', '==', 'true'),
            ),
        ),
    ));

    // -- 收款接口
    CSF::createSection($prefix, array(
        'id'     => 'shop-payment',
        'parent' => 'shop',
        'title'  => __('收款接口', 'onedown'),
        'fields' => array(

            // 微信收款接口
            array(
                'type'    => 'subheading',
                'content' => __('微信收款接口', 'onedown'),
            ),
            array(
                'id'      => 'wechat_pay_method',
                'type'    => 'select',
                'title'   => __('微信支付方式', 'onedown'),
                'subtitle' => __('选择微信支付的接入方式', 'onedown'),
                'options' => array(
                    'close'    => __('关闭微信收款', 'onedown'),
                    'official' => __('官方接口', 'onedown'),
                    'epay'     => __('易支付接口', 'onedown'),
                ),
                'default' => 'close',
            ),
            array(
                'id'         => 'pay_wechat_app_id',
                'type'       => 'text',
                'title'      => __('微信 APPID', 'onedown'),
                'subtitle'   => __('填写微信公众平台或开放平台的 AppID', 'onedown'),
                'default'    => '',
                'dependency' => array('wechat_pay_method', '==', 'official'),
            ),
            array(
                'id'         => 'pay_wechat_mch_id',
                'type'       => 'text',
                'title'      => __('微信商户号（MCHID）', 'onedown'),
                'default'    => '',
                'dependency' => array('wechat_pay_method', '==', 'official'),
            ),
            array(
                'id'         => 'pay_wechat_key',
                'type'       => 'text',
                'title'      => __('微信 API 密钥', 'onedown'),
                'subtitle'   => __('填写商户平台设置的 APIv3 密钥', 'onedown'),
                'default'    => '',
                'dependency' => array('wechat_pay_method', '==', 'official'),
            ),

            // 支付宝收款接口
            array(
                'type'    => 'subheading',
                'content' => __('支付宝收款接口', 'onedown'),
            ),
            array(
                'id'      => 'alipay_pay_method',
                'type'    => 'select',
                'title'   => __('支付宝支付方式', 'onedown'),
                'subtitle' => __('选择支付宝的接入方式', 'onedown'),
                'options' => array(
                    'close'    => __('关闭支付宝收款', 'onedown'),
                    'official' => __('官方接口', 'onedown'),
                    'epay'     => __('易支付接口', 'onedown'),
                ),
                'default' => 'close',
            ),
            array(
                'id'         => 'pay_alipay_app_id',
                'type'       => 'text',
                'title'      => __('支付宝 APPID', 'onedown'),
                'default'    => '',
                'dependency' => array('alipay_pay_method', '==', 'official'),
            ),
            array(
                'id'         => 'pay_alipay_private_key',
                'type'       => 'textarea',
                'title'      => __('应用私钥', 'onedown'),
                'subtitle'   => __('填写支付宝开放平台应用私钥内容', 'onedown'),
                'default'    => '',
                'dependency' => array('alipay_pay_method', '==', 'official'),
            ),
            array(
                'id'         => 'pay_alipay_public_key',
                'type'       => 'textarea',
                'title'      => __('支付宝公钥', 'onedown'),
                'subtitle'   => __('填写支付宝开放平台中的支付宝公钥', 'onedown'),
                'default'    => '',
                'dependency' => array('alipay_pay_method', '==', 'official'),
            ),

            // 易支付共享配置
            array(
                'type'    => 'subheading',
                'content' => __('易支付共享配置', 'onedown'),
            ),
            array(
                'id'         => 'pay_epay_api_url',
                'type'       => 'text',
                'title'      => __('易支付网关地址', 'onedown'),
                'subtitle'   => __('例如：https://your.epay.com/，注意保留末尾斜杠', 'onedown'),
                'default'    => '',
            ),
            array(
                'id'         => 'pay_epay_pid',
                'type'       => 'text',
                'title'      => __('商户ID (pid)', 'onedown'),
                'default'    => '',
            ),
            array(
                'id'         => 'pay_epay_key',
                'type'       => 'text',
                'title'      => __('商户密钥 (key)', 'onedown'),
                'default'    => '',
            ),

            // 其他支付方式
            array(
                'type'    => 'subheading',
                'content' => __('其他支付方式', 'onedown'),
            ),
            array(
                'id'      => 'pay_balance_enabled',
                'type'    => 'switcher',
                'title'   => __('启用余额支付', 'onedown'),
                'subtitle' => __('用户可在用户中心充值后使用余额支付', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'pay_offline_enabled',
                'type'    => 'switcher',
                'title'   => __('启用线下支付', 'onedown'),
                'subtitle' => __('显示线下支付说明，用户联系管理员完成付款', 'onedown'),
                'default' => false,
            ),
            array(
                'id'         => 'pay_offline_info',
                'type'       => 'textarea',
                'title'      => __('线下支付说明', 'onedown'),
                'subtitle'   => __('显示给用户的线下付款说明', 'onedown'),
                'default'    => '请联系管理员：QQ 123456789，微信 example',
                'dependency' => array('pay_offline_enabled', '==', 'true'),
            ),
        ),
    ));



    // =====================
    // 拓展增强
    // =====================
    CSF::createSection($prefix, array(
        'id'    => 'extension',
        'title' => __('拓展&增强', 'onedown'),
        'icon'  => 'fas fa-plug',
    ));

    CSF::createSection($prefix, array(
        'id'     => 'extension-tools',
        'parent' => 'extension',
        'title'  => __('系统工具', 'onedown'),
        'fields' => array(
            array(
                'id'      => 'classic_editor',
                'type'    => 'switcher',
                'title'   => __('启用经典编辑器', 'onedown'),
                'subtitle' => __('关闭 Gutenberg，改用 WordPress 经典编辑器', 'onedown'),
                'default' => true,
            ),
            array(
                'id'      => 'classic_widgets',
                'type'    => 'switcher',
                'title'   => __('启用经典小工具', 'onedown'),
                'subtitle' => __('使用传统小工具管理界面而不是区块小工具', 'onedown'),
                'default' => true,
            ),
            array(
                'id'      => 'remove_emoji',
                'type'    => 'switcher',
                'title'   => __('删除 Emoji 脚本', 'onedown'),
                'subtitle' => __('移除 WordPress 自带的 Emoji 样式和脚本，减少额外 HTTP 请求', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'remove_open_sans',
                'type'    => 'switcher',
                'title'   => __('删除 Google 字体', 'onedown'),
                'subtitle' => __('移除后台和前台加载的 Google Open Sans 字体', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'remove_more_wp_head',
                'type'    => 'switcher',
                'title'   => __('清理 wp_head 多余标签', 'onedown'),
                'subtitle' => __('移除 feed、RSD、WLW、版本号等不必要的头部输出标签', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'disable_wp_update',
                'type'    => 'switcher',
                'title'   => __('禁用 WordPress 更新检查', 'onedown'),
                'subtitle' => __('关闭 WordPress 后台的核心、主题和插件更新检查', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'disable_pingback',
                'type'    => 'switcher',
                'title'   => __('禁用文章 Pingback', 'onedown'),
                'subtitle' => __('阻止文章之间相互发送 pingback 通知', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'disable_trackback',
                'type'    => 'switcher',
                'title'   => __('禁用文章 Trackback', 'onedown'),
                'subtitle' => __('关闭并禁用 Trackback，前台和编辑页面不再显示相关入口，请求直接返回 403', 'onedown'),
                'default' => true,
            ),
            array(
                'id'      => 'disable_admin_for_non_admin',
                'type'    => 'switcher',
                'title'   => __('禁止非管理员登录后台', 'onedown'),
                'subtitle' => __('开启后仅管理员可访问后台，其他角色将被拦截', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'no_category_base',
                'type'    => 'switcher',
                'title'   => __('分类链接去除 category', 'onedown'),
                'subtitle' => __('优化分类链接结构，去掉 /category/ 前缀', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'cache_enabled',
                'type'    => 'switcher',
                'title'   => __('查询缓存', 'onedown'),
                'subtitle' => __('开启后缓存常用查询结果，减少数据库查询次数并提升页面响应速度', 'onedown'),
                'default' => false,
            ),
            array(
                'type'    => 'subheading',
                'content' => __('缓存工具', 'onedown'),
            ),
            array(
                'type'     => 'callback',
                'function' => 'onedown_render_force_clear_cache_action',
            ),
        ),
    ));

    CSF::createSection($prefix, array(
        'id'     => 'extension-security',
        'parent' => 'extension',
        'title'  => __('网站安全', 'onedown'),
        'fields' => array(
            array(
                'id'      => 'security_disable_xmlrpc',
                'type'    => 'switcher',
                'title'   => __('禁用 XML-RPC', 'onedown'),
                'subtitle' => __('为了提升站点安全性，建议关闭 XML-RPC 接口', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'security_disable_file_edit',
                'type'    => 'switcher',
                'title'   => __('禁用文件编辑', 'onedown'),
                'subtitle' => __('禁止在后台直接编辑主题和插件文件', 'onedown'),
                'default' => true,
            ),
            array(
                'id'      => 'security_https',
                'type'    => 'switcher',
                'title'   => __('强制 HTTPS', 'onedown'),
                'subtitle' => __('将所有 HTTP 请求重定向到 HTTPS', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'security_disable_devtools',
                'type'    => 'switcher',
                'title'   => __('禁用右键和调试快捷键', 'onedown'),
                'subtitle' => __('通过前端脚本限制右键、F12 等常见调试入口', 'onedown'),
                'default' => false,
            ),
        ),
    ));

    // -- AI 配置
    CSF::createSection($prefix, array(
        'id'     => 'extension-ai',
        'parent' => 'extension',
        'title'  => __('AI 配置', 'onedown'),
        'fields' => array(
            array(
                'id'      => 'ai_enabled',
                'type'    => 'switcher',
                'title'   => __('启用 AI 生成', 'onedown'),
                'subtitle' => __('开启后可在后台使用 AI 生成标题、内容、标签和图片', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'ai_enable_title',
                'type'    => 'switcher',
                'title'   => __('允许生成标题', 'onedown'),
                'default' => false,
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_enable_content',
                'type'    => 'switcher',
                'title'   => __('允许生成内容', 'onedown'),
                'default' => false,
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_enable_tags',
                'type'    => 'switcher',
                'title'   => __('允许生成标签', 'onedown'),
                'default' => false,
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_article_api',
                'type'    => 'text',
                'title'   => __('AI 文章接口地址', 'onedown'),
                'subtitle' => __('填写兼容 OpenAI 格式的 API 端点，例如 https://api.openai.com/v1/chat/completions', 'onedown'),
                'default' => 'https://api.openai.com/v1/chat/completions',
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_article_api_key',
                'type'    => 'text',
                'title'   => __('API Key', 'onedown'),
                'subtitle' => __('用于调用 AI 文章接口的密钥', 'onedown'),
                'default' => '',
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_article_model',
                'type'    => 'text',
                'title'   => __('模型名称', 'onedown'),
                'subtitle' => __('例如 gpt-4o-mini、gpt-4.1 等', 'onedown'),
                'default' => 'gpt-4o-mini',
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_article_prompt',
                'type'    => 'textarea',
                'title'   => __('文章生成提示词', 'onedown'),
                'subtitle' => __('用于约束 AI 生成文章内容的系统提示词', 'onedown'),
                'default' => '请根据给定主题生成结构清晰、适合中文站点发布的原创文章内容。',
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_title_prompt',
                'type'    => 'textarea',
                'title'   => __('标题生成提示词', 'onedown'),
                'subtitle' => __('用于约束 AI 生成标题的提示词', 'onedown'),
                'default' => '请生成简洁、有吸引力且适合 SEO 的中文标题。',
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_tags_prompt',
                'type'    => 'textarea',
                'title'   => __('标签生成提示词', 'onedown'),
                'subtitle' => __('用于约束 AI 生成文章标签的提示词', 'onedown'),
                'default' => '请为文章生成准确、简洁的中文标签，避免无关词。',
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_enable_image',
                'type'    => 'switcher',
                'title'   => __('允许生成特色图片', 'onedown'),
                'subtitle' => __('关闭后将禁用下方图片生成相关配置', 'onedown'),
                'default' => false,
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
            array(
                'id'      => 'ai_image_api',
                'type'    => 'text',
                'title'   => __('AI 图片接口地址', 'onedown'),
                'subtitle' => __('填写兼容 OpenAI 格式的图片生成 API 端点', 'onedown'),
                'default' => 'https://api.openai.com/v1/images/generations',
                'dependency' => array('ai_enable_image', '==', 'true'),
            ),
            array(
                'id'      => 'ai_image_api_key',
                'type'    => 'text',
                'title'   => __('图片 API Key', 'onedown'),
                'subtitle' => __('用于调用图片生成接口的密钥', 'onedown'),
                'default' => '',
                'dependency' => array('ai_enable_image', '==', 'true'),
            ),
            array(
                'id'      => 'ai_image_model',
                'type'    => 'text',
                'title'   => __('图片模型名称', 'onedown'),
                'subtitle' => __('例如 dall-e-3、gpt-image-1 等', 'onedown'),
                'default' => 'dall-e-3',
                'dependency' => array('ai_enable_image', '==', 'true'),
            ),
            array(
                'id'      => 'ai_image_prompt',
                'type'    => 'textarea',
                'title'   => __('图片生成提示词', 'onedown'),
                'subtitle' => __('用于约束 AI 生成特色图片的提示词模板', 'onedown'),
                'default' => '请生成一张适合作为中文博客封面的高质量横版配图。',
                'dependency' => array('ai_enable_image', '==', 'true'),
            ),
            array(
                'type'       => 'callback',
                'function'   => 'onedown_ai_test_button',
                'dependency' => array('ai_enabled', '==', 'true'),
            ),
        ),
    ));

    // -- 邮件配置
    CSF::createSection($prefix, array(
        'id'     => 'extension-mail',
        'parent' => 'extension',
        'title'  => __('邮件配置', 'onedown'),
        'fields' => array(

            // 鈹€鈹€ SMTP 寮€鍏?鈹€鈹€
            array(
                'id'      => 'mail_smtp_enabled',
                'type'    => 'switcher',
                'title'   => __('启用 SMTP 邮件发送', 'onedown'),
                'subtitle' => __('开启后站点邮件将通过 SMTP 服务发送', 'onedown'),
                'default' => false,
            ),

            // 发件人设置
            array(
                'type'    => 'subheading',
                'content' => __('发件人设置', 'onedown'),
            ),
            array(
                'id'         => 'mail_from_name',
                'type'       => 'text',
                'title'      => __('发件人名称', 'onedown'),
                'subtitle'   => __('自定义邮件发件人名称，不填则使用站点名称', 'onedown'),
                'default'    => '',
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'mail_from_email',
                'type'       => 'text',
                'title'      => __('发件人邮箱', 'onedown'),
                'subtitle'   => __('填写实际发送邮件使用的邮箱地址', 'onedown'),
                'default'    => '',
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),

            // SMTP 服务配置
            array(
                'type'    => 'subheading',
                'content' => __('SMTP 服务配置', 'onedown'),
            ),
            array(
                'id'         => 'mail_smtp_host',
                'type'       => 'text',
                'title'      => __('SMTP 服务器地址', 'onedown'),
                'subtitle'   => __('例如 smtp.qq.com、smtp.exmail.qq.com 等', 'onedown'),
                'default'    => '',
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'mail_smtp_port',
                'type'       => 'text',
                'title'      => __('SMTP 端口', 'onedown'),
                'subtitle'   => __('例如 465（SSL）或 587（TLS）', 'onedown'),
                'default'    => '465',
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'mail_smtp_secure',
                'type'       => 'select',
                'title'      => __('加密方式', 'onedown'),
                'options'    => array(
                    'ssl' => __('SSL', 'onedown'),
                    'tls' => __('TLS', 'onedown'),
                    ''    => __('无', 'onedown'),
                ),
                'default'    => 'ssl',
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'mail_smtp_auth',
                'type'       => 'switcher',
                'title'      => __('SMTPAuth 身份验证', 'onedown'),
                'subtitle'   => __('大多数 SMTP 服务需要开启身份验证', 'onedown'),
                'default'    => true,
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'mail_smtp_username',
                'type'       => 'text',
                'title'      => __('SMTP 用户名', 'onedown'),
                'subtitle'   => __('通常填写完整邮箱地址', 'onedown'),
                'default'    => '',
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'mail_smtp_password',
                'type'       => 'text',
                'title'      => __('SMTP 密码', 'onedown'),
                'subtitle'   => __('例如 QQ 邮箱请填写授权码，而不是登录密码', 'onedown'),
                'default'    => '',
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),

            // 管理员通知
            array(
                'type'    => 'subheading',
                'content' => __('管理员邮件通知', 'onedown'),
            ),
            array(
                'id'      => 'mail_admin_new_order',
                'type'    => 'switcher',
                'title'   => __('新订单通知', 'onedown'),
                'subtitle' => __('有新订单时向管理员发送邮件通知', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'mail_admin_new_comment',
                'type'    => 'switcher',
                'title'   => __('用户评论通知', 'onedown'),
                'subtitle' => __('有用户发表评论时向管理员发送邮件通知', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'mail_admin_content_review',
                'type'    => 'switcher',
                'title'   => __('内容审核通知', 'onedown'),
                'subtitle' => __('有待审核的文章或内容时向管理员发送邮件通知', 'onedown'),
                'default' => false,
            ),

            // 用户通知
            array(
                'type'    => 'subheading',
                'content' => __('用户邮件通知', 'onedown'),
            ),
            array(
                'id'      => 'mail_user_comment_review',
                'type'    => 'switcher',
                'title'   => __('评论审核通知', 'onedown'),
                'subtitle' => __('用户评论审核状态发生变化时邮件通知用户', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'mail_user_comment_reply',
                'type'    => 'switcher',
                'title'   => __('评论回复通知', 'onedown'),
                'subtitle' => __('用户收到评论回复时发送邮件通知', 'onedown'),
                'default' => false,
            ),
            array(
                'id'      => 'mail_user_content_review',
                'type'    => 'switcher',
                'title'   => __('内容审核通知', 'onedown'),
                'subtitle' => __('用户提交的内容审核状态变化时邮件通知用户', 'onedown'),
                'default' => false,
            ),

            // 邮件内容模板
            array(
                'type'    => 'subheading',
                'content' => __('邮件内容模板', 'onedown'),
            ),
            array(
                'id'         => 'mail_template_new_order',
                'type'       => 'textarea',
                'title'      => __('新订单通知模板', 'onedown'),
                'subtitle'   => __('可使用变量 {order_id} {order_title} {order_price} {user_name} {site_name} {site_url}', 'onedown'),
                'default' => '你有一笔新订单：{order_title}，订单号 {order_id}，金额 {order_price}。',
                'dependency' => array('mail_admin_new_order', '==', 'true'),
            ),
            array(
                'id'         => 'mail_template_comment_approve',
                'type'       => 'textarea',
                'title'      => __('评论审核通知模板', 'onedown'),
                'subtitle'   => __('可使用变量 {comment_author} {comment_content} {post_title} {status} {site_name} {site_url}', 'onedown'),
                'default' => '你的评论《{comment_content}》在《{post_title}》下的审核状态已更新：{status}。',
                'dependency' => array('mail_user_comment_review', '==', 'true'),
            ),
            array(
                'id'         => 'mail_template_comment_reply',
                'type'       => 'textarea',
                'title'      => __('评论回复通知模板', 'onedown'),
                'subtitle'   => __('可使用变量 {comment_author} {reply_author} {reply_content} {post_title} {site_name} {site_url}', 'onedown'),
                'default' => '{reply_author} 回复了你在《{post_title}》下的评论：{reply_content}',
                'dependency' => array('mail_user_comment_reply', '==', 'true'),
            ),
            array(
                'id'         => 'mail_template_admin_comment',
                'type'       => 'textarea',
                'title'      => __('管理员评论通知模板', 'onedown'),
                'subtitle'   => __('可使用变量 {comment_author} {comment_content} {post_title} {site_name} {site_url}', 'onedown'),
                'default'    => '用户 {comment_author} 在文章《{post_title}》下发表了评论：{comment_content}',
                'dependency' => array('mail_admin_new_comment', '==', 'true'),
            ),
            array(
                'id'         => 'mail_template_admin_review',
                'type'       => 'textarea',
                'title'      => __('管理员审核通知模板', 'onedown'),
                'subtitle'   => __('可使用变量 {post_title} {author_name} {post_type} {site_name} {site_url}', 'onedown'),
                'default' => '有新的 {post_type} 内容待审核：{post_title}，提交者：{author_name}。',
                'dependency' => array('mail_admin_content_review', '==', 'true'),
            ),

            // 测试邮件
            array(
                'type'    => 'subheading',
                'content' => __('测试邮件', 'onedown'),
            ),
            array(
                'id'      => 'mail_test_email',
                'type'    => 'text',
                'title'   => __('测试邮箱地址', 'onedown'),
                'subtitle' => __('输入用于接收测试邮件的邮箱地址', 'onedown'),
                'default' => '',
            ),
            array(
                'type'       => 'callback',
                'function'   => 'onedown_mail_test_button',
                'dependency' => array('mail_smtp_enabled', '==', 'true'),
            ),
        ),
    ));

    // 远程发布
    CSF::createSection($prefix, array(
        'id'     => 'extension-remote-pub',
        'parent' => 'extension',
        'title'  => __('远程发布', 'onedown'),
        'fields' => array(
            array(
                'id'       => 'remote_pub_enabled',
                'type'     => 'switcher',
                'title'    => __('启用远程发布接口', 'onedown'),
                'subtitle' => __('开启后允许外部程序通过接口向站点发布文章', 'onedown'),
                'default'  => false,
            ),

            // 鈹€鈹€ 鎺ュ彛淇℃伅 鈹€鈹€
            array(
                'type'    => 'subheading',
                'content' => __('接口信息', 'onedown'),
            ),

            // 鈹€鈹€ 鉴权 鈹€鈹€
            array(
                'type'    => 'subheading',
                'content' => __('鉴权设置', 'onedown'),
            ),
            array(
                'id'       => 'remote_pub_apitype',
                'type'     => 'select',
                'title'    => __('鉴权方式', 'onedown'),
                'options'  => array(
                    'normal' => __('普通鉴权', 'onedown'),
                    'safe'   => __('安全鉴权', 'onedown'),
                ),
                'default'  => 'normal',
                'dependency' => array('remote_pub_enabled', '==', 'true'),
            ),
            array(
                'id'       => 'remote_pub_apikey',
                'type'     => 'text',
                'title'    => __('API 密钥', 'onedown'),
                'subtitle' => __('调用远程发布接口时需要携带的 API 密钥', 'onedown'),
                'default'  => '',
                'dependency' => array('remote_pub_enabled', '==', 'true'),
            ),

            // 文章默认值
            array(
                'type'    => 'subheading',
                'content' => __('文章默认值', 'onedown'),
            ),
            array(
                'id'       => 'remote_pub_author',
                'type'     => 'textarea',
                'title'    => __('默认作者', 'onedown'),
                'subtitle' => __('填写默认作者 ID，多个作者可按需扩展逻辑', 'onedown'),
                'default'  => '',
                'dependency' => array('remote_pub_enabled', '==', 'true'),
            ),
            array(
                'id'       => 'remote_pub_pay_type',
                'type'     => 'select',
                'title'    => __('默认付费类型', 'onedown'),
                'subtitle' => __('远程发布文章时默认使用的付费方式', 'onedown'),
                'options'  => array(
                    'no'       => __('免费', 'onedown'),
                    'read'     => __('付费阅读', 'onedown'),
                    'download' => __('付费下载', 'onedown'),
                ),
                'default'  => 'no',
                'dependency' => array('remote_pub_enabled', '==', 'true'),
            ),
            array(
                'id'       => 'remote_pub_pay_price',
                'type'     => 'text',
                'title'    => __('默认价格', 'onedown'),
                'subtitle' => __('远程发布付费文章时默认使用的售价', 'onedown'),
                'default'  => '0',
                'dependency' => array('remote_pub_enabled', '==', 'true'),
            ),
            array(
                'id'       => 'remote_pub_pay_original_price',
                'type'     => 'text',
                'title'    => __('默认原价', 'onedown'),
                'subtitle' => __('远程发布付费文章时默认使用的原价', 'onedown'),
                'default'  => '0',
                'dependency' => array('remote_pub_enabled', '==', 'true'),
            ),
            array(
                'id'       => 'remote_pub_buy_permission',
                'type'     => 'select',
                'title'    => __('默认购买权限', 'onedown'),
                'options'  => array(
                    'all'       => __('所有用户', 'onedown'),
                    'logged_in' => __('仅登录用户', 'onedown'),
                    'vip_only'  => __('仅 VIP 用户', 'onedown'),
                ),
                'default'  => 'all',
                'dependency' => array('remote_pub_enabled', '==', 'true'),
            ),
            array(
                'id'       => 'remote_pub_check_link_valid',
                'type'     => 'switcher',
                'title'    => __('检查下载链接有效性', 'onedown'),
                'subtitle' => __('发布前尝试检测远程下载地址是否有效，可能增加请求时间', 'onedown'),
                'default'  => false,
                'dependency' => array('remote_pub_enabled', '==', 'true'),
            ),
        ),
    ));

    // 微信工具
    CSF::createSection($prefix, array(
        'id'     => 'extension-wechat',
        'parent' => 'extension',
        'title'  => __('微信工具', 'onedown'),
        'fields' => array(

            // 基础配置
            array(
                'type'    => 'subheading',
                'content' => __('公众号基础配置', 'onedown'),
            ),
            array(
                'id'      => 'wechat_qrcode',
                'type'    => 'media',
                'title'   => __('公众号二维码图片', 'onedown'),
                'subtitle' => __('用于前台展示的公众号关注二维码，建议尺寸 300×300px', 'onedown'),
                'library' => 'image',
                'preview' => true,
            ),
            array(
                'id'      => 'wechat_account_type',
                'type'    => 'select',
                'title'   => __('公众号类型', 'onedown'),
                'subtitle' => __('根据你的公众号类型进行选择', 'onedown'),
                'options' => array(
                    'service'      => __('服务号', 'onedown'),
                    'subscription' => __('订阅号', 'onedown'),
                ),
                'default' => 'subscription',
            ),
            array(
                'id'       => 'wechat_app_id',
                'type'     => 'text',
                'title'    => __('公众号 AppID', 'onedown'),
                'subtitle' => __('填写微信公众号后台提供的 AppID', 'onedown'),
                'default'  => '',
            ),
            array(
                'id'       => 'wechat_app_secret',
                'type'     => 'text',
                'title'    => __('公众号 AppSecret', 'onedown'),
                'subtitle' => __('填写微信公众号后台提供的 AppSecret', 'onedown'),
                'default'  => '',
            ),
            array(
                'id'       => 'wechat_token',
                'type'     => 'text',
                'title'    => __('接口验证 Token', 'onedown'),
                'subtitle' => __('用于微信服务器回调接口校验', 'onedown'),
                'default'  => '',
            ),
            array(
                'id'       => 'wechat_encoding_aes_key',
                'type'     => 'text',
                'title'    => __('消息加解密密钥（EncodingAESKey）', 'onedown'),
                'subtitle' => __('开启安全模式时需要填写', 'onedown'),
                'default'  => '',
            ),

            // 新关注回复
            array(
                'type'    => 'subheading',
                'content' => __('新关注回复', 'onedown'),
            ),
            array(
                'id'      => 'wechat_subscribe_scan_reply',
                'type'    => 'textarea',
                'title'   => __('扫码关注回复', 'onedown'),
                'subtitle' => __('用户通过扫码关注公众号时自动回复的消息内容', 'onedown'),
                'default' => '欢迎关注，感谢你的支持。',
                'height'  => '120',
            ),
            array(
                'id'      => 'wechat_subscribe_normal_reply',
                'type'    => 'textarea',
                'title'   => __('非扫码新关注回复', 'onedown'),
                'subtitle' => __('用户直接搜索关注公众号时自动回复的消息内容', 'onedown'),
                'default' => '欢迎关注我们的公众号。',
                'height'  => '120',
            ),
            array(
                'id'      => 'wechat_default_reply',
                'type'    => 'textarea',
                'title'   => __('默认回复', 'onedown'),
                'subtitle' => __('未命中任何规则时回复给用户的默认消息', 'onedown'),
                'default' => '已收到你的消息，我们会尽快回复。',
                'height'  => '120',
            ),

            // 微信登录
            array(
                'type'    => 'subheading',
                'content' => __('微信登录配置', 'onedown'),
            ),
            array(
                'id'      => 'wechat_login_enabled',
                'type'    => 'switcher',
                'title'   => __('启用微信登录', 'onedown'),
                'subtitle' => __('开启后用户可通过微信扫码登录站点', 'onedown'),
                'default' => false,
            ),
            array(
                'id'         => 'wechat_scan_login_reply',
                'type'       => 'textarea',
                'title'      => __('扫码登录提示信息', 'onedown'),
                'subtitle'   => __('用户扫码登录时向公众号推送的提示消息，可使用 {site_name}、{login_url}', 'onedown'),
                'default' => '你正在登录 {site_name}，点击下方链接继续：{login_url}',
                'height'     => '120',
                'dependency' => array('wechat_login_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'wechat_login_redirect',
                'type'       => 'text',
                'title'      => __('登录成功跳转地址', 'onedown'),
                'subtitle'   => __('微信扫码登录成功后的跳转页面，留空则跳转到首页', 'onedown'),
                'default'    => '',
                'dependency' => array('wechat_login_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'wechat_bind_after_login',
                'type'       => 'switcher',
                'title'      => __('登录后自动绑定账号', 'onedown'),
                'subtitle'   => __('开启后扫码登录时若无站点账号则自动创建并绑定', 'onedown'),
                'default'    => true,
                'dependency' => array('wechat_login_enabled', '==', 'true'),
            ),

            // 关键词自动回复
            array(
                'type'    => 'subheading',
                'content' => __('关键词自动回复', 'onedown'),
            ),
            array(
                'id'      => 'wechat_keyword_reply_enabled',
                'type'    => 'switcher',
                'title'   => __('启用关键词回复', 'onedown'),
                'subtitle' => __('开启后按关键词规则自动回复用户消息', 'onedown'),
                'default' => false,
            ),
            array(
                'id'         => 'wechat_keyword_replies',
                'type'       => 'repeater',
                'title'      => __('关键词回复列表', 'onedown'),
                'subtitle'   => __('添加关键词及对应回复内容，多个关键词可用 "|" 分隔', 'onedown'),
                'fields'     => array(
                    array(
                        'id'    => 'keyword',
                        'type'  => 'text',
                        'title' => __('关键词', 'onedown'),
                    ),
                    array(
                        'id'      => 'reply_type',
                        'type'    => 'select',
                        'title'   => __('回复类型', 'onedown'),
                        'options' => array(
                            'text' => __('文本', 'onedown'),
                            'news' => __('图文', 'onedown'),
                        ),
                        'default' => 'text',
                    ),
                    array(
                        'id'         => 'reply_content',
                        'type'       => 'textarea',
                        'title'      => __('回复内容（文本）', 'onedown'),
                        'height'     => '100',
                        'dependency' => array('reply_type', '==', 'text'),
                    ),
                    array(
                        'id'         => 'reply_news_title',
                        'type'       => 'text',
                        'title'      => __('图文标题', 'onedown'),
                        'dependency' => array('reply_type', '==', 'news'),
                    ),
                    array(
                        'id'         => 'reply_news_desc',
                        'type'       => 'textarea',
                        'title'      => __('图文描述', 'onedown'),
                        'height'     => '60',
                        'dependency' => array('reply_type', '==', 'news'),
                    ),
                    array(
                        'id'         => 'reply_news_url',
                        'type'       => 'text',
                        'title'      => __('图文链接', 'onedown'),
                        'dependency' => array('reply_type', '==', 'news'),
                    ),
                    array(
                        'id'         => 'reply_news_pic',
                        'type'       => 'text',
                        'title'      => __('图文图片URL', 'onedown'),
                        'subtitle'   => __('建议尺寸 300×200px', 'onedown'),
                        'dependency' => array('reply_type', '==', 'news'),
                    ),
                ),
                'default'    => array(),
                'dependency' => array('wechat_keyword_reply_enabled', '==', 'true'),
            ),

            // 自定义菜单
            array(
                'type'    => 'subheading',
                'content' => __('自定义菜单', 'onedown'),
            ),
            array(
                'id'      => 'wechat_menu_enabled',
                'type'    => 'switcher',
                'title'   => __('启用微信菜单同步', 'onedown'),
                'subtitle' => __('开启后可将这里配置的菜单同步到微信公众号', 'onedown'),
                'default' => false,
            ),
            array(
                'id'         => 'wechat_menu',
                'type'       => 'repeater',
                'title'      => __('菜单项（最多 3 个一级菜单）', 'onedown'),
                'subtitle'   => __('每个一级菜单最多可配置 5 个子菜单', 'onedown'),
                'fields'     => array(
                    array(
                        'id'    => 'name',
                        'type'  => 'text',
                        'title' => __('菜单名称', 'onedown'),
                    ),
                    array(
                        'id'      => 'type',
                        'type'    => 'select',
                        'title'   => __('菜单类型', 'onedown'),
                        'options' => array(
                            'click'       => __('点击事件 (click)', 'onedown'),
                            'view'        => __('跳转URL (view)', 'onedown'),
                            'miniprogram' => __('跳转小程序 (miniprogram)', 'onedown'),
                        ),
                        'default' => 'view',
                    ),
                    array(
                        'id'       => 'key',
                        'type'     => 'text',
                        'title'    => __('菜单 KEY', 'onedown'),
                        'subtitle' => __('点击事件类型菜单需要填写唯一 KEY', 'onedown'),
                        'desc'     => __('系统预设 KEY：subscribe_scan（扫码关注）、subscribe_normal（普通关注）、scan_login（扫码登录）、help（帮助）', 'onedown'),
                    ),
                    array(
                        'id'       => 'url',
                        'type'     => 'text',
                        'title'    => __('跳转链接', 'onedown'),
                        'subtitle' => __('当菜单类型为 view 时填写跳转链接', 'onedown'),
                    ),
                    array(
                        'id'       => 'appid',
                        'type'     => 'text',
                        'title'    => __('小程序 AppID', 'onedown'),
                        'subtitle' => __('当菜单类型为 miniprogram 时填写', 'onedown'),
                    ),
                    array(
                        'id'       => 'pagepath',
                        'type'     => 'text',
                        'title'    => __('小程序页面路径', 'onedown'),
                        'subtitle' => __('当菜单类型为 miniprogram 时填写 pagepath', 'onedown'),
                    ),
                    array(
                        'id'       => 'sub_button',
                        'type'     => 'repeater',
                        'title'    => __('子菜单（最多 5 个）', 'onedown'),
                        'fields'   => array(
                            array(
                                'id'    => 'name',
                                'type'  => 'text',
                                'title' => __('子菜单名称', 'onedown'),
                            ),
                            array(
                                'id'      => 'type',
                                'type'    => 'select',
                                'title'   => __('子菜单类型', 'onedown'),
                                'options' => array(
                                    'click'       => __('点击事件 (click)', 'onedown'),
                                    'view'        => __('跳转URL (view)', 'onedown'),
                                    'miniprogram' => __('跳转小程序 (miniprogram)', 'onedown'),
                                ),
                                'default' => 'view',
                            ),
                            array(
                                'id'    => 'key',
                                'type'  => 'text',
                                'title' => __('子菜单 KEY', 'onedown'),
                                'subtitle' => __('点击事件类型子菜单需要填写唯一 KEY', 'onedown'),
                            ),
                            array(
                                'id'    => 'url',
                                'type'  => 'text',
                                'title' => __('子菜单链接', 'onedown'),
                                'subtitle' => __('当子菜单类型为 view 时填写', 'onedown'),
                            ),
                            array(
                                'id'    => 'appid',
                                'type'  => 'text',
                                'title' => __('子菜单小程序AppID', 'onedown'),
                                'subtitle' => __('当子菜单类型为 miniprogram 时填写', 'onedown'),
                            ),
                            array(
                                'id'    => 'pagepath',
                                'type'  => 'text',
                                'title' => __('子菜单小程序页面路径', 'onedown'),
                                'subtitle' => __('当子菜单类型为 miniprogram 时填写 pagepath', 'onedown'),
                            ),
                        ),
                        'default' => array(),
                    ),
                ),
                'default'    => array(
                    array(
                        'name' => __('网站首页', 'onedown'),
                        'type' => 'view',
                        'url'  => home_url(),
                    ),
                    array(
                        'name' => __('用户中心', 'onedown'),
                        'type' => 'view',
                        'url'  => function_exists('onedown_user_center_url') ? onedown_user_center_url() : home_url('/user-center/'),
                    ),
                    array(
                        'name' => __('联系我们', 'onedown'),
                        'type' => 'click',
                        'key'  => 'help',
                    ),
                ),
                'dependency' => array('wechat_menu_enabled', '==', 'true'),
            ),
            array(
                'type'    => 'callback',
                'function' => 'onedown_wechat_menu_sync_button',
                'dependency' => array('wechat_menu_enabled', '==', 'true'),
            ),
        ),
    ));

    // =====================
    // 主题同质化
    // =====================
    CSF::createSection($prefix, array(
        'id'     => 'extension-homogenization',
        'parent' => 'extension',
        'title'  => __('主题同质化', 'onedown'),
        'fields' => array(
            array(
                'id'      => 'homogenization_enabled',
                'type'    => 'switcher',
                'title'   => __('启用主题同质化参数', 'onedown'),
                'subtitle' => __('开启后会在前端 HTML 输出中加入随机参数，降低模板特征被自动化工具识别的概率', 'onedown'),
                'default' => false,
            ),
            array(
                'id'         => 'homogenization_params',
                'type'       => 'textarea',
                'title'      => __('参数名称列表', 'onedown'),
                'subtitle'   => __('每行填写一个参数名，用于生成随机类名片段', 'onedown'),
                'desc'       => __('例如：theme、skin、layout、color、style。渲染示例：&lt;html lang="zh-CN" class="theme-a3k8 layout-x7b2"&gt;', 'onedown'),
                'default'    => "theme\nskin\nlayout\ncolor\nstyle",
                'dependency' => array('homogenization_enabled', '==', 'true'),
            ),
            array(
                'id'         => 'homogenization_count',
                'type'       => 'slider',
                'title'      => __('每次注入参数数量', 'onedown'),
                'subtitle'   => __('控制每次在页面中注入多少个随机参数', 'onedown'),
                'default'    => 2,
                'min'        => 1,
                'max'        => 10,
                'step'       => 1,
                'dependency' => array('homogenization_enabled', '==', 'true'),
            ),
        ),
    ));
// =====================

if (file_exists(trailingslashit(get_template_directory()) . 'version.json')) {
CSF::createSection($prefix, array(
    'id'     => 'license-update-manifest',
    'parent' => 'license-update',
    'title'  => __('版本配置', 'onedown'),
    'fields' => array(
        array(
            'id'       => 'version_manifest_panel',
            'type'     => 'callback',
            'function' => 'onedown_render_version_manifest_panel',
        ),
    ),
));
}

CSF::createSection($prefix, array(
    'id'     => 'license-update-updater',
    'parent' => 'license-update',
    'title'  => __('主题更新', 'onedown'),
    'fields' => array(
        array(
            'id'       => 'theme_update_enabled',
            'type'     => 'switcher',
            'class'    => 'onedown-field-hidden',
            'title'    => __('启用主题在线更新', 'onedown'),
            'subtitle' => __('开启后会从远程接口检查主题版本并在后台显示更新提示', 'onedown'),
            'default'  => false,
        ),
        array(
            'id'       => 'theme_update_endpoint',
            'type'     => 'text',
            'class'    => 'onedown-field-hidden',
            'title'    => __('更新接口地址', 'onedown'),
            'subtitle' => __('返回 JSON 的远程版本检测地址', 'onedown'),
            'default'  => function_exists('onedown_get_default_update_manifest_url') ? onedown_get_default_update_manifest_url() : '',
        ),
        array(
            'id'         => 'theme_update_backup_enabled',
            'type'       => 'switcher',
            'title'      => __('更新前自动备份变更文件', 'onedown'),
            'subtitle'   => __('开启后，仅在覆盖本地已存在且发生变化的文件前先备份一份。', 'onedown'),
            'default'    => true,
        ),
        array(
            'id'         => 'theme_update_ignore_paths',
            'type'       => 'textarea',
            'class'      => 'onedown-field-hidden',
            'title'      => __('忽略更新路径', 'onedown'),
            'subtitle'   => __('每行一条。支持目录后缀 /，也支持 * 通配符。例如 assets/css/custom.css', 'onedown'),
            'default'    => '',
        ),
        array(
            'type'       => 'callback',
            'function'   => 'onedown_render_theme_update_panel',
        ),
    ),
));

// =====================
// 备份与恢复
// =====================
CSF::createSection($prefix, array(
    'id'     => 'backup',
    'title'  => __('备份&恢复', 'onedown'),
    'icon'   => 'fas fa-download',
    'fields' => array(
        array(
            'type'  => 'backup',
            'title' => __('备份/恢复设置', 'onedown'),
        ),
    ),
));

CSF::createSection($prefix, array(
    'id'     => 'global-notice',
    'parent' => 'global',
    'title'  => __('网站公告', 'onedown'),
    'fields' => array(
        array(
            'id'      => 'notice_modal_enabled',
            'type'    => 'switcher',
            'title'   => __('启用公告弹窗', 'onedown'),
            'subtitle' => __('访客首次访问时自动弹出的公告窗口，支持“我知道了”“24小时内不再提示”“30天内不再提示”', 'onedown'),
            'default' => false,
        ),
        array(
            'id'         => 'notice_modal_title',
            'type'       => 'text',
            'title'      => __('弹窗标题', 'onedown'),
            'subtitle'   => __('弹窗顶部的标题文字', 'onedown'),
            'default'    => '欢迎访问本站',
            'dependency' => array('notice_modal_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'notice_modal_kicker',
            'type'       => 'text',
            'title'      => __('弹窗标签', 'onedown'),
            'subtitle'   => __('标题上方的小标签文字', 'onedown'),
            'default'    => '站点公告',
            'dependency' => array('notice_modal_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'notice_modal_content',
            'type'       => 'wp_editor',
            'title'      => __('弹窗内容', 'onedown'),
            'subtitle'   => __('弹窗正文内容，支持图文、列表、链接等富文本', 'onedown'),
            'settings'   => array(
                'textarea_rows' => 8,
                'media_buttons'  => true,
                'teeny'          => true,
            ),
            'dependency' => array('notice_modal_enabled', '==', 'true'),
        ),
        array(
            'id'         => 'notice_modal_buttons',
            'type'       => 'repeater',
            'title'      => __('底部按钮', 'onedown'),
            'subtitle'   => __('自定义底部按钮，支持添加多个。点击“跳转链接”时在新标签页打开', 'onedown'),
            'fields'     => array(
                array(
                    'id'      => 'text',
                    'type'    => 'text',
                    'title'   => __('按钮文字', 'onedown'),
                ),
                array(
                    'id'      => 'action',
                    'type'    => 'select',
                    'title'   => __('行为', 'onedown'),
                    'options' => array(
                        'close' => __('关闭弹窗', 'onedown'),
                        'today' => __('关闭30天内不再提示', 'onedown'),
                        'link'  => __('跳转链接', 'onedown'),
                    ),
                    'default' => 'close',
                ),
                array(
                    'id'         => 'link',
                    'type'       => 'text',
                    'title'      => __('链接地址', 'onedown'),
                    'dependency' => array('action', '==', 'link'),
                ),
            ),
            'default'    => array(
                array('text' => '30天内不再提示', 'action' => 'today', 'link' => ''),
                array('text' => '我知道了', 'action' => 'close', 'link' => ''),
            ),
            'dependency' => array('notice_modal_enabled', '==', 'true'),
        ),
    ),
));

/**
 * CSF Callback: 渲染 OOP 授权面板
 */
if (! function_exists('onedown_render_license_activator_panel')) :
    function onedown_render_license_activator_panel($field)
    {
        $nonce = wp_create_nonce('onedown_load_admin_tab_panel');
        echo '<div class="onedown-lazy-admin-panel" data-panel="license" data-nonce="' . esc_attr($nonce) . '">';
        echo '<p style="color:#999;">' . __('进入当前标签后加载授权面板。', 'onedown') . '</p>';
        echo '</div>';
    }
endif;

if (! function_exists('onedown_render_license_activator_panel_html')) :
    function onedown_render_license_activator_panel_html(): void
    {
        if (class_exists('Onedown_License_Activator', false)) {
            Onedown_License_Activator::instance()->render_panel();
            return;
        }

        echo '<p style="color:#999;">' . __('授权模块未加载，请检查 class-license-activator.php 是否已加载。', 'onedown') . '</p>';
    }
endif;

if (! function_exists('onedown_render_lazy_admin_panel_loader')) :
    function onedown_render_lazy_admin_panel_loader(): void
    {
        if (! is_admin() || empty($_GET['page']) || $_GET['page'] !== 'onedown-options') {
            return;
        }
        ?>
        <script>
            (function() {
                if (window.onedownLazyAdminPanelBooted) return;
                window.onedownLazyAdminPanelBooted = true;

                var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;

                function loadPanel(panelName) {
                    var container = document.querySelector('.onedown-lazy-admin-panel[data-panel="' + panelName + '"]');
                    if (!container || container.dataset.loaded === '1' || container.dataset.loading === '1') {
                        return;
                    }

                    container.dataset.loading = '1';
                    container.innerHTML = '<p style="color:#999;">加载中...</p>';

                    var formData = new FormData();
                    formData.append('action', 'onedown_load_admin_tab_panel');
                    formData.append('panel', panelName);
                    formData.append('_ajax_nonce', container.dataset.nonce || '');

                    fetch(ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    }).then(function(res) {
                        return res.json();
                    }).then(function(res) {
                        if (!res || !res.success || !res.data || typeof res.data.html !== 'string') {
                            throw new Error((res && res.data && res.data.message) || '加载失败');
                        }
                        container.innerHTML = res.data.html;
                        container.querySelectorAll('script').forEach(function(oldScript) {
                            var newScript = document.createElement('script');
                            if (oldScript.src) {
                                newScript.src = oldScript.src;
                            } else {
                                newScript.text = oldScript.textContent;
                            }
                            Array.prototype.slice.call(oldScript.attributes).forEach(function(attr) {
                                newScript.setAttribute(attr.name, attr.value);
                            });
                            oldScript.parentNode.replaceChild(newScript, oldScript);
                        });
                        container.dataset.loaded = '1';
                    }).catch(function(err) {
                        container.innerHTML = '<p style="color:#b91c1c;">' + (err && err.message ? err.message : '加载失败') + '</p>';
                    }).finally(function() {
                        container.dataset.loading = '0';
                    });
                }

                function syncVisiblePanels() {
                    document.querySelectorAll('.csf-section:not(.hidden) .onedown-lazy-admin-panel').forEach(function(panel) {
                        var panelName = panel.getAttribute('data-panel');
                        if (panelName) {
                            loadPanel(panelName);
                        }
                    });
                }

                window.addEventListener('hashchange', function() {
                    setTimeout(syncVisiblePanels, 30);
                });

                document.addEventListener('click', function(event) {
                    var trigger = event.target.closest('[data-tab-id]');
                    if (trigger) {
                        setTimeout(syncVisiblePanels, 30);
                    }
                });

                if (window.jQuery) {
                    window.jQuery(document).on('csf.hashchange', function() {
                        setTimeout(syncVisiblePanels, 30);
                    });
                }

                setTimeout(syncVisiblePanels, 30);
                setTimeout(syncVisiblePanels, 200);
                setTimeout(syncVisiblePanels, 800);
            })();
        </script>
        <?php
    }
    add_action('admin_footer', 'onedown_render_lazy_admin_panel_loader', 100);
endif;

add_action('wp_ajax_onedown_load_admin_tab_panel', static function (): void {
    check_ajax_referer('onedown_load_admin_tab_panel');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('无权限访问', 'onedown')));
    }

    $panel = isset($_POST['panel']) ? sanitize_key((string) $_POST['panel']) : '';
    ob_start();

    if ($panel === 'license') {
        if (function_exists('onedown_render_license_activator_panel_html')) {
            onedown_render_license_activator_panel_html();
        } else {
            echo '<p style="color:#999;">' . __('授权模块未加载，请检查 class-license-activator.php 是否已加载。', 'onedown') . '</p>';
        }
    } elseif ($panel === 'version-manifest') {
        if (function_exists('onedown_render_version_manifest_panel_html')) {
            onedown_render_version_manifest_panel_html();
        } else {
            echo '<p style="color:#999;">' . __('授权模块未加载，请检查 class-license-activator.php 是否已加载。', 'onedown') . '</p>';
        }
    } elseif ($panel === 'updater') {
        if (function_exists('onedown_render_theme_update_panel_html')) {
            onedown_render_theme_update_panel_html();
        } else {
            echo '<p style="color:#999;">' . __('更新模块未加载，请检查 theme-updater.php 是否已加载。', 'onedown') . '</p>';
        }
    } else {
        wp_send_json_error(array('message' => __('未知面板类型', 'onedown')));
    }

    wp_send_json_success(array('html' => ob_get_clean()));
});







