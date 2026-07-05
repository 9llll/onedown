<?php
/**
 * 搜索框小组件
 *
 * 严格对标静态页面结构：
 * - 横幅模式：search-bg 区块（首页全宽搜索横幅）
 * - 侧边栏模式：搜索表单 + 热门搜索词 + 推荐标签
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Search');
}, 1);

class OD_Widget_Search extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_search',
            'description' => '搜索模块：首页搜索横幅 / 侧边栏搜索 + 热门搜索 + 推荐标签',
        );
        parent::__construct('od-search', __('OD 搜索模块', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        $display = ! empty($instance['display']) ? $instance['display'] : 'sidebar';
        $keyword = get_search_query();

        if ($display === 'banner') {
            // === 横幅模式：首页全宽 search-bg ===
            $kicker = ! empty($instance['kicker']) ? $instance['kicker'] : '资源检索';
            $heading = ! empty($instance['heading']) ? $instance['heading'] : '查找主题教程、社区问题与商城资源';
            $desc    = ! empty($instance['desc']) ? $instance['desc'] : '输入关键词快速定位文档、帖子、资源和更新记录。';
            $placeholder = ! empty($instance['placeholder']) ? $instance['placeholder'] : '搜索 WordPress、Onedown、会员、商城...';
            ?>
            <section class="search-bg lazyloaded" aria-label="资源搜索">
                <div class="search-panel">
                    <div class="search-copy">
                        <span class="section-kicker"><i class="fa fa-search"></i> <?php echo esc_html($kicker); ?></span>
                        <h2><?php echo esc_html($heading); ?></h2>
                        <p><?php echo esc_html($desc); ?></p>
                    </div>
                    <form class="search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <i class="fa fa-search"></i>
                        <input type="search" name="s" value="<?php echo esc_attr($keyword); ?>" placeholder="<?php echo esc_attr($placeholder); ?>">
                        <button type="submit">搜索</button>
                    </form>
                </div>
            </section>
            <?php
        } else {
            // === 侧边栏模式 ===
            echo $args['before_widget'];

            $title = ! empty($instance['title']) ? $instance['title'] : '';
            if ($title) {
                echo $args['before_title'] . $title . $args['after_title'];
            }

            $show_hot      = ! empty($instance['show_hot']);
            $show_tags     = ! empty($instance['show_tags']);
            $hot_keywords  = ! empty($instance['hot_keywords']) ? explode("\n", $instance['hot_keywords']) : array();
            $hot_keywords  = array_map('trim', $hot_keywords);
            $hot_keywords  = array_filter($hot_keywords);
            $tags_number   = ! empty($instance['tags_number']) ? (int) $instance['tags_number'] : 8;
            ?>
            <form class="search-page-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <i class="fa fa-search"></i>
                <input type="search" name="s" value="<?php echo esc_attr($keyword); ?>" placeholder="输入关键词搜索内容">
                <button type="submit">搜索</button>
            </form>

            <?php if ($show_hot && ! empty($hot_keywords)) : ?>
                <div class="search-hot-words" aria-label="热门搜索">
                    <?php foreach ($hot_keywords as $kw) : ?>
                        <a href="<?php echo esc_url(home_url('/?s=' . urlencode($kw))); ?>"><?php echo esc_html($kw); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($show_tags) :
                $tags = get_terms(array(
                    'taxonomy'   => 'post_tag',
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'number'     => $tags_number,
                    'hide_empty' => false,
                ));
                if (! empty($tags) && ! is_wp_error($tags)) : ?>
                <div class="tag-cloud" style="margin-top:12px;">
                    <?php foreach ($tags as $tag) :
                        $tag_link = get_term_link($tag);
                        if (is_wp_error($tag_link)) continue;
                    ?>
                        <a href="<?php echo esc_url($tag_link); ?>"><?php echo esc_html($tag->name); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif;
            endif;

            echo $args['after_widget'];
        }
    }

    public function form($instance)
    {
        $display      = ! empty($instance['display']) ? $instance['display'] : 'sidebar';
        $title        = ! empty($instance['title']) ? $instance['title'] : '';
        $kicker       = ! empty($instance['kicker']) ? $instance['kicker'] : '资源检索';
        $heading      = ! empty($instance['heading']) ? $instance['heading'] : '';
        $desc         = ! empty($instance['desc']) ? $instance['desc'] : '';
        $placeholder  = ! empty($instance['placeholder']) ? $instance['placeholder'] : '';
        $show_hot     = ! empty($instance['show_hot']);
        $show_tags    = ! empty($instance['show_tags']);
        $hot_keywords = ! empty($instance['hot_keywords']) ? $instance['hot_keywords'] : '';
        $tags_number  = ! empty($instance['tags_number']) ? (int) $instance['tags_number'] : 8;
        ?>
        <p>
            <label>显示模式：
                <select class="widefat" name="<?php echo $this->get_field_name('display'); ?>">
                    <option value="sidebar" <?php selected($display, 'sidebar'); ?>>侧边栏模式</option>
                    <option value="banner" <?php selected($display, 'banner'); ?>>横幅模式（首页全宽）</option>
                </select>
            </label>
        </p>
        <hr>
        <p><strong>通用设置</strong></p>
        <p>
            <label>标题（仅侧边栏模式）：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="搜索"></label>
        </p>
        <hr>
        <p><strong>横幅模式设置</strong></p>
        <p>
            <label>标签文字：<input type="text" class="widefat" name="<?php echo $this->get_field_name('kicker'); ?>" value="<?php echo esc_attr($kicker); ?>" placeholder="资源检索"></label>
        </p>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('heading'); ?>" value="<?php echo esc_attr($heading); ?>" placeholder="查找主题教程、社区问题与商城资源"></label>
        </p>
        <p>
            <label>描述：<input type="text" class="widefat" name="<?php echo $this->get_field_name('desc'); ?>" value="<?php echo esc_attr($desc); ?>" placeholder="输入关键词快速定位文档、帖子、资源和更新记录。"></label>
        </p>
        <p>
            <label>搜索框占位符：<input type="text" class="widefat" name="<?php echo $this->get_field_name('placeholder'); ?>" value="<?php echo esc_attr($placeholder); ?>" placeholder="搜索 WordPress、Onedown、会员、商城..."></label>
        </p>
        <hr>
        <p><strong>侧边栏模式设置</strong></p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_hot); ?> name="<?php echo $this->get_field_name('show_hot'); ?>"> 显示热门搜索词</label>
        </p>
        <p>
            <label>热门搜索词（每行一个）：<br>
                <textarea class="widefat" rows="4" name="<?php echo $this->get_field_name('hot_keywords'); ?>" placeholder="WordPress"><?php echo esc_textarea($hot_keywords); ?></textarea>
            </label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_tags); ?> name="<?php echo $this->get_field_name('show_tags'); ?>"> 显示推荐标签</label>
        </p>
        <p>
            <label>标签数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('tags_number'); ?>" value="<?php echo esc_attr($tags_number); ?>" min="1" max="30">
            </label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance                  = $old_instance;
        $instance['display']       = $new_instance['display'];
        $instance['title']         = wp_kses_post($new_instance['title']);
        $instance['kicker']        = sanitize_text_field($new_instance['kicker']);
        $instance['heading']       = sanitize_text_field($new_instance['heading']);
        $instance['desc']          = sanitize_text_field($new_instance['desc']);
        $instance['placeholder']   = sanitize_text_field($new_instance['placeholder']);
        $instance['show_hot']      = ! empty($new_instance['show_hot']) ? 1 : 0;
        $instance['show_tags']     = ! empty($new_instance['show_tags']) ? 1 : 0;
        $instance['hot_keywords']  = sanitize_textarea_field($new_instance['hot_keywords']);
        $instance['tags_number']   = (int) $new_instance['tags_number'];
        return $instance;
    }
}
