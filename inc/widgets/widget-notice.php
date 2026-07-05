<?php
if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Notice');
}, 1);

class OD_Widget_Notice extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_notice',
            'description' => '显示顶部滚动公告条（公告弹窗已移至主题设置）',
        );
        parent::__construct('od-notice', __('OD 滚动公告', 'onedown'), $widget_ops);
    }

    /**
     * 生成自动标识（后台用）
     */
    private function get_auto_title($instance)
    {
        if (! empty($instance['title'])) {
            return $instance['title'];
        }
        // 根据 widget 数量生成序号
        $settings  = $this->get_settings();
        $widget_nr = 1;
        if (! empty($settings)) {
            $ids = array_keys($settings);
            $idx = array_search($this->number, $ids);
            if ($idx !== false) {
                $widget_nr = $idx + 1;
            }
        }
        return sprintf('滚动公告 #%d', $widget_nr);
    }

    public function widget($args, $instance)
    {
        $auto_title = $this->get_auto_title($instance);

        // ── 兼容旧数据：textares 格式 → 转为 items ──
        if (! empty($instance['notices']) && empty($instance['items'])) {
            $lines  = explode("\n", $instance['notices']);
            $lines  = array_map('trim', $lines);
            $lines  = array_filter($lines);
            $items  = array();
            foreach ($lines as $line) {
                $parts = explode('||', $line);
                $items[] = array(
                    'text' => ! empty($parts[1]) ? trim($parts[1]) : $line,
                    'url'  => ! empty($parts[1]) ? trim($parts[0]) : '',
                );
            }
            $instance['items'] = $items;
        }

        $items     = ! empty($instance['items']) ? $instance['items'] : array();
        $more_url  = ! empty($instance['more_url']) ? $instance['more_url'] : '#';
        $more_text = ! empty($instance['more_text']) ? $instance['more_text'] : '更多';

        $uid = $this->id;

        if (! empty($items)) :
            $item_count  = count($items);
            $track_items = array_merge($items, $items);
            $uid_attr    = esc_attr('od-notice-' . $uid);
            $style_uid   = 'od-notice-style-' . $uid;
            $duration    = ! empty($instance['scroll_duration']) ? absint($instance['scroll_duration']) : max(6, $item_count * 2);
        ?>
            <style id="<?php echo $style_uid; ?>">
                #<?php echo $uid_attr; ?>.owl-dynamic-track {
                    animation: owlDynamicScroll_<?php echo $uid; ?> <?php echo $duration; ?>s linear infinite;
                }
                @keyframes owlDynamicScroll_<?php echo $uid; ?> {
                    0% { transform: translateY(0); }
                    100% { transform: translateY(calc(-50% - 0px)); }
                }
            </style>
            <section class="owl-dynamic" id="<?php echo $uid_attr; ?>" aria-label="<?php echo esc_attr($auto_title); ?>">
                <div class="owl-dynamic-label">
                    <i class="fa fa-volume-up"></i>
                </div>
                <div class="owl-dynamic-viewport">
                    <div class="owl-dynamic-track">
                        <?php foreach ($track_items as $item) :
                            $link = ! empty($item['url']) ? $item['url'] : '';
                            $text = ! empty($item['text']) ? $item['text'] : '';
                            if (! $text) continue;
                        ?>
                            <a href="<?php echo $link ? esc_url($link) : '#'; ?>"><?php echo esc_html($text); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a class="owl-dynamic-more" href="<?php echo esc_url($more_url); ?>"><?php echo esc_html($more_text); ?> <i
                        class="fa fa-angle-right"></i></a>
            </section>
        <?php endif;
    }

    public function form($instance)
    {
        $items     = ! empty($instance['items']) ? $instance['items'] : array();
        $more_url  = ! empty($instance['more_url']) ? $instance['more_url'] : '';
        $more_text = ! empty($instance['more_text']) ? $instance['more_text'] : '更多';
        $scroll_duration = ! empty($instance['scroll_duration']) ? absint($instance['scroll_duration']) : '';

        if (empty($items)) {
            $items = array(array('text' => '', 'url' => ''));
        }

        $auto_title = $this->get_auto_title($instance);
        $widget_id  = $this->id;
        ?>
        <p style="margin-bottom:8px;">
            <span
                style="display:inline-block;background:#e8f0fe;color:#1967d2;font-size:11px;padding:2px 8px;border-radius:3px;font-weight:500;">
                <?php echo esc_html($auto_title); ?>
            </span>
            <span style="font-size:11px;color:#999;margin-left:4px;">（自动标识）</span>
            <input type="hidden" name="<?php echo $this->get_field_name('title'); ?>"
                value="<?php echo esc_attr($auto_title); ?>">
        </p>

        <div class="od-notice-items" style="margin-top:8px;">
            <p style="font-weight:600;margin-bottom:6px;">
                公告条目
                <span style="font-weight:400;font-size:11px;color:#999;">（点击条目展开编辑）</span>
            </p>
            <?php foreach ($items as $i => $item) :
                $text = ! empty($item['text']) ? $item['text'] : '';
                $url  = ! empty($item['url']) ? $item['url'] : '';
                $summary = $text ?: '（空）';
                $has_text = ! empty($text);
            ?>
                <div class="od-notice-item" data-index="<?php echo $i; ?>"
                    style="background:#fff;margin-bottom:6px;border-radius:4px;border:1px solid #ddd;overflow:hidden;">
                    <div class="od-notice-item-header"
                        style="display:flex;align-items:center;padding:8px 10px;cursor:pointer;user-select:none;gap:8px;background:#fafafa;">
                        <span class="od-notice-item-toggle"
                            style="font-size:10px;color:#999;transition:transform .2s;">&#9654;</span>
                        <span style="font-weight:500;font-size:12px;color:#666;flex-shrink:0;">#<?php echo $i + 1; ?></span>
                        <span class="od-notice-item-summary"
                            style="font-size:12px;color:<?php echo $has_text ? '#333' : '#bbb'; ?>;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html($summary); ?></span>
                        <?php if ($i > 0) : ?>
                            <button type="button" class="button button-small od-notice-remove-item"
                                style="color:#a00;flex-shrink:0;font-size:11px;line-height:1.4;min-height:0;padding:2px 6px;">删除</button>
                        <?php endif; ?>
                    </div>
                    <div class="od-notice-item-body" style="display:none;padding:8px 10px;border-top:1px solid #eee;">
                        <p style="margin:0 0 6px;">
                            <label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">公告文字：</label>
                            <input type="text" class="widefat od-notice-text-input" style="font-size:13px;"
                                name="<?php echo $this->get_field_name('items'); ?>[<?php echo $i; ?>][text]"
                                value="<?php echo esc_attr($text); ?>" placeholder="输入公告文字内容">
                        </p>
                        <p style="margin:0;">
                            <label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">跳转链接（可选）：</label>
                            <input type="url" class="widefat" style="font-size:13px;"
                                name="<?php echo $this->get_field_name('items'); ?>[<?php echo $i; ?>][url]"
                                value="<?php echo esc_attr($url); ?>" placeholder="https://... 或留空">
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p style="margin-top:8px;">
            <button type="button" class="button od-notice-add-item"
                data-prefix="<?php echo esc_attr($this->get_field_name('items')); ?>">+ 添加公告</button>
        </p>

        <hr style="border:none;border-top:1px solid #e5e5e5;margin:12px 0;">

        <p style="font-weight:600;color:#555;">页脚区域</p>
        <p>
            <label>「更多」链接：<input type="text" class="widefat" name="<?php echo $this->get_field_name('more_url'); ?>"
                    value="<?php echo esc_attr($more_url); ?>" placeholder="#"></label>
        </p>
        <p>
            <label>「更多」文字：<input type="text" class="widefat" name="<?php echo $this->get_field_name('more_text'); ?>"
                    value="<?php echo esc_attr($more_text); ?>" placeholder="更多"></label>
        </p>

        <hr style="border:none;border-top:1px solid #e5e5e5;margin:12px 0;">

        <p style="font-weight:600;color:#555;">外观与行为</p>
        <p>
            <label>滚动速度（秒/周期）：<input type="number" class="widefat"
                    name="<?php echo $this->get_field_name('scroll_duration'); ?>"
                    value="<?php echo esc_attr($scroll_duration); ?>" placeholder="自动" min="3" step="1"></label>
            <span class="description" style="color:#999;font-size:12px;">单个滚动周期时长（秒），留空则根据公告条数自动计算</span>
        </p>

        <script type="text/javascript">
            jQuery(function($) {
                // ── 折叠/展开（全局委托，每个组件只绑一次） ──
                $(document).off('click.odnotice', '.od-notice-item-header').on('click.odnotice', '.od-notice-item-header',
                    function(e) {
                        if ($(e.target).closest('.od-notice-remove-item').length) return;
                        var $body = $(this).closest('.od-notice-item').find('.od-notice-item-body');
                        var $toggle = $(this).find('.od-notice-item-toggle');
                        var isOpen = $body.is(':visible');
                        $body.stop(true, true).slideToggle(150);
                        $toggle.css('transform', isOpen ? 'rotate(0deg)' : 'rotate(90deg)');
                    });

                // ── 输入时实时更新摘要 ──
                $(document).off('input.odnotice', '.od-notice-text-input').on('input.odnotice', '.od-notice-text-input',
                    function() {
                        var val = $(this).val() || '（空）';
                        var $item = $(this).closest('.od-notice-item');
                        $item.find('.od-notice-item-summary').text(val).css('color', val === '（空）' ? '#bbb' : '#333');
                    });

                // ── 添加公告 ──
                $(document).off('click.odnotice', '.od-notice-add-item').on('click.odnotice', '.od-notice-add-item',
                    function() {
                        var namePrefix = $(this).data('prefix');
                        if (!namePrefix) return;
                        var $ctx = $(this).closest('.widget-content, .widget-inside');
                        if (!$ctx.length) return;
                        var $items = $ctx.find('.od-notice-items');
                        var count = $items.find('.od-notice-item').length;

                        var html =
                            '<div class="od-notice-item" data-index="' + count +
                            '" style="background:#fff;margin-bottom:6px;border-radius:4px;border:1px solid #ddd;overflow:hidden;">' +
                            '<div class="od-notice-item-header" style="display:flex;align-items:center;padding:8px 10px;cursor:pointer;user-select:none;gap:8px;background:#fafafa;">' +
                            '<span class="od-notice-item-toggle" style="font-size:10px;color:#999;transition:transform .2s;">&#9654;</span>' +
                            '<span style="font-weight:500;font-size:12px;color:#666;flex-shrink:0;">#' + (count + 1) +
                            '</span>' +
                            '<span class="od-notice-item-summary" style="font-size:12px;color:#bbb;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">（空）</span>' +
                            '<button type="button" class="button button-small od-notice-remove-item" style="color:#a00;flex-shrink:0;font-size:11px;line-height:1.4;min-height:0;padding:2px 6px;">删除</button>' +
                            '</div>' +
                            '<div class="od-notice-item-body" style="display:none;padding:8px 10px;border-top:1px solid #eee;">' +
                            '<p style="margin:0 0 6px;">' +
                            '<label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">公告文字：</label>' +
                            '<input type="text" class="widefat od-notice-text-input" style="font-size:13px;" name="' +
                            namePrefix + '[' + count + '][text]" value="" placeholder="输入公告文字内容">' +
                            '</p>' +
                            '<p style="margin:0;">' +
                            '<label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">跳转链接（可选）：</label>' +
                            '<input type="url" class="widefat" style="font-size:13px;" name="' + namePrefix + '[' +
                            count + '][url]" value="" placeholder="https://... 或留空">' +
                            '</p>' +
                            '</div>' +
                            '</div>';

                        var $newItem = $(html);
                        $items.append($newItem);
                        $newItem.find('.od-notice-item-header').trigger('click');
                    });

                // ── 删除公告 ──
                $(document).off('click.odnotice', '.od-notice-remove-item').on('click.odnotice',
                    '.od-notice-remove-item',
                    function() {
                        var $ctx = $(this).closest('.widget-content, .widget-inside');
                        if (!$ctx.length) return;
                        $(this).closest('.od-notice-item').remove();
                        $ctx.find('.od-notice-item').each(function(idx) {
                            $(this).find('> .od-notice-item-header > span:first').text('#' + (idx + 1));
                            $(this).attr('data-index', idx);
                            $(this).find('input').each(function() {
                                var name = $(this).attr('name');
                                if (name) {
                                    name = name.replace(/\[\d+\]/, '[' + idx + ']');
                                    $(this).attr('name', name);
                                }
                            });
                        });
                    });
            });
        </script>
    <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        // 自动生成 title（不依赖用户输入）
        $instance['title'] = ! empty($new_instance['title']) ? wp_kses_post($new_instance['title']) : '';

        // 处理 items 数组
        $items = array();
        if (! empty($new_instance['items']) && is_array($new_instance['items'])) {
            foreach ($new_instance['items'] as $item) {
                $text = ! empty($item['text']) ? sanitize_text_field($item['text']) : '';
                $url  = ! empty($item['url']) ? esc_url_raw($item['url']) : '';
                // 至少要有文字才保存
                if ($text) {
                    $items[] = array(
                        'text' => $text,
                        'url'  => $url,
                    );
                }
            }
        }
        $instance['items'] = $items;

        // 清理旧数据
        unset($instance['notices']);

        $instance['more_url']   = esc_url_raw($new_instance['more_url']);
        $instance['more_text']  = sanitize_text_field($new_instance['more_text']);
        $instance['scroll_duration'] = ! empty($new_instance['scroll_duration']) ? absint($new_instance['scroll_duration']) : '';
        return $instance;
    }
}

add_action('wp_footer', function () {
    if (! function_exists('onedown_get_option')) return;

    $modal_enabled = (bool) onedown_get_option('notice_modal_enabled', false);
    if (! $modal_enabled) return;

    $modal_title   = onedown_get_option('notice_modal_title', '欢迎访问本站');
    $modal_kicker  = onedown_get_option('notice_modal_kicker', '站点公告');
    $modal_content = onedown_get_option('notice_modal_content', '');

    $buttons = onedown_get_option('notice_modal_buttons', array());
    if (empty($buttons) || ! is_array($buttons)) {
        $buttons = array(
            array('text' => '30天内不再提示', 'action' => 'today', 'link' => ''),
            array('text' => '我知道了', 'action' => 'close', 'link' => ''),
        );
    }

    static $rendered = false;
    if ($rendered) return;
    $rendered = true;

    $uid         = 'global';
    $modal_id    = 'notice-modal-' . $uid;
    $title_id    = 'notice-title-' . $uid;
    $storage_key = 'od_notice_hide_' . md5($uid);
    $btn_count   = count($buttons);
    ?>
    <div class="notice-modal" id="<?php echo esc_attr($modal_id); ?>" aria-hidden="true" data-notice-modal>
        <div class="notice-mask"></div>
        <div class="notice-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($title_id); ?>">
            <button class="notice-close" type="button" aria-label="关闭公告" data-notice-close>
                <i class="fa fa-times"></i>
            </button>
            <div class="notice-dialog-head">
                <span class="notice-dialog-icon"><i class="fa fa-bullhorn"></i></span>
                <div>
                    <span class="notice-dialog-kicker"><?php echo esc_html($modal_kicker); ?></span>
                    <h2 id="<?php echo esc_attr($title_id); ?>"><?php echo esc_html($modal_title); ?></h2>
                </div>
            </div>
            <div class="notice-dialog-body">
                <?php if ($modal_content) : ?>
                <?php echo wp_kses_post($modal_content); ?>
                <?php else : ?>
                <p style="text-align:center;color:#666;padding:20px 0;">欢迎访问本站，请前往后台设置公告内容。</p>
                <?php endif; ?>
            </div>
            <div class="notice-dialog-actions">
                <?php foreach ($buttons as $i => $btn) :
                    $btn_text   = ! empty($btn['text']) ? $btn['text'] : '按钮';
                    $btn_action = ! empty($btn['action']) ? $btn['action'] : 'close';
                    $btn_link   = ! empty($btn['link']) ? $btn['link'] : '';
                    $is_last    = ($i === $btn_count - 1);
                    $btn_class  = $is_last ? 'notice-primary' : 'notice-secondary';
                    $link_attr  = ($btn_action === 'link' && $btn_link) ? ' data-notice-link="' . esc_url($btn_link) . '"' : '';
                    if ($btn_action === 'today' && in_array($btn_text, array('今天不再提示', '今日不再提示'), true)) {
                        $btn_text = '30天内不再提示';
                    }
                    $today_attr = $btn_action === 'today' ? ' data-notice-today' : '';
                ?>
                <button class="<?php echo $btn_class; ?>" type="button" data-notice-action="<?php echo esc_attr($btn_action); ?>"<?php echo $link_attr . $today_attr; ?>><?php echo esc_html($btn_text); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var modal = document.getElementById('<?php echo esc_js($modal_id); ?>');
            if (!modal) return;
            var actionBtns = modal.querySelectorAll('[data-notice-action]');
            var storageKey = '<?php echo esc_js($storage_key); ?>';
            var oneDay = 24 * 60 * 60 * 1000;
            var thirtyDays = 30 * oneDay;

            function showModal() {
                modal.classList.add('is-show');
                modal.setAttribute('aria-hidden', 'false');
                modal.removeAttribute('inert');
                // 聚焦到弹窗内的第一个按钮
                var firstBtn = modal.querySelector('.notice-primary, .notice-secondary, [data-notice-action]');
                if (firstBtn) firstBtn.focus();
            }

            function hideModal() {
                // 先将焦点移出弹窗，避免 aria-hidden 冲突
                if (modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                modal.classList.remove('is-show');
                modal.setAttribute('aria-hidden', 'true');
                modal.setAttribute('inert', '');
            }

            function shouldShowModal() {
                var hiddenUntil = parseInt(localStorage.getItem(storageKey), 10);
                return !hiddenUntil || hiddenUntil <= Date.now();
            }

            function rememberHidden(duration) {
                localStorage.setItem(storageKey, String(Date.now() + duration));
            }

            if (shouldShowModal()) {
                setTimeout(showModal, 800);
            }

            modal.addEventListener('click', function(e) {
                if (e.target.closest('[data-notice-close]')) {
                    hideModal();
                }
            });

            actionBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var action = this.getAttribute('data-notice-action');
                    if (action === 'link') {
                        var link = this.getAttribute('data-notice-link');
                        if (link) {
                            window.open(link, '_blank');
                        }
                    }
                    if (this.hasAttribute('data-notice-today')) {
                        rememberHidden(thirtyDays);
                    } else if (action === 'close') {
                        rememberHidden(oneDay);
                    }
                    hideModal();
                });
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('is-show')) {
                    hideModal();
                }
            });
        })();
    </script>
<?php
}, 99);
