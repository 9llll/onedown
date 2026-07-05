<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * ═══════════════════════════════════════════════════════════════
 * Onedown SEO 批量主动推送模块
 *
 * 功能：
 * - 在 OD 主题设置下添加"主动推送"子菜单页面
 * - 支持手动批量将已发布文章推送到搜索引擎（百度/360/搜狗/神马/夸克等）
 * - 支持分批 AJAX 推送，避免超时
 * - 显示推送日志和结果统计
 * ═══════════════════════════════════════════════════════════════
 */

// ──────────────────────────────────────────────
// 初始化
// ──────────────────────────────────────────────
add_action('admin_menu', 'onedown_seo_push_admin_menu', 20);

function onedown_seo_push_admin_menu()
{
    // 仅当开关开启时显示子菜单
    if (! _pz('seo_active_push_enabled', false)) {
        return;
    }

    add_submenu_page(
        'onedown-options',
        __('SEO 主动推送', 'onedown'),
        __('主动推送', 'onedown'),
        'manage_options',
        'onedown-seo-push',
        'onedown_seo_push_render_page'
    );
}

// ──────────────────────────────────────────────
// 注册 AJAX 处理
// ──────────────────────────────────────────────
add_action('wp_ajax_onedown_seo_push_batch', 'onedown_seo_push_ajax_handler');
add_action('wp_ajax_onedown_seo_push_log', 'onedown_seo_push_log_ajax_handler');
add_action('wp_ajax_onedown_seo_push_stats', 'onedown_seo_push_stats_ajax_handler');

// ──────────────────────────────────────────────
// 渲染管理页面
// ──────────────────────────────────────────────
function onedown_seo_push_render_page()
{
    // 检查权限
    if (! current_user_can('manage_options')) {
        wp_die(__('权限不足', 'onedown'));
    }

    // 获取统计信息
    $total_posts = wp_count_posts('post')->publish;
    $pushed_count = get_option('onedown_seo_push_total_count', 0);
    $last_push = get_option('onedown_seo_push_last_time', '');

?>
    <div class="wrap">
        <h1><?php _e('SEO 主动推送', 'onedown'); ?></h1>
        <p><?php _e('手动将已发布的文章批量提交到搜索引擎，加快收录速度。推送功能依赖上方 SEO 设置中各搜索引擎的 Token 配置。', 'onedown'); ?></p>

        <div class="onedown-seo-push-container" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;">

            <!-- 推送控制面板 -->
            <div class="postbox" style="flex:1; min-width:400px;">
                <div class="postbox-header">
                    <h2 class="hndle" style="padding:12px; margin:0;"><?php _e('推送控制', 'onedown'); ?></h2>
                </div>
                <div class="inside" style="padding:12px;">
                    <!-- 统计概览 -->
                    <div style="display:flex; gap:20px; margin-bottom:20px; padding:15px; background:#f0f6fc; border-radius:4px;">
                        <div style="text-align:center; flex:1;">
                            <div style="font-size:28px; font-weight:700; color:#2271b1;"><?php echo esc_html($total_posts); ?></div>
                            <div style="color:#666; font-size:13px;"><?php _e('已发布文章', 'onedown'); ?></div>
                        </div>
                        <div style="text-align:center; flex:1;">
                            <div style="font-size:28px; font-weight:700; color:#46b450;"><?php echo esc_html($pushed_count); ?></div>
                            <div style="color:#666; font-size:13px;"><?php _e('累计推送次数', 'onedown'); ?></div>
                        </div>
                        <div style="text-align:center; flex:1;">
                            <div style="font-size:14px; font-weight:400; color:#999;"><?php echo $last_push ? esc_html($last_push) : '--'; ?></div>
                            <div style="color:#666; font-size:13px;"><?php _e('上次推送时间', 'onedown'); ?></div>
                        </div>
                    </div>

                    <!-- 推送选项 -->
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;"><?php _e('推送范围', 'onedown'); ?></label>
                        <select id="onedown-push-scope" class="regular-text" style="width:100%;">
                            <option value="all"><?php _e('全部已发布文章', 'onedown'); ?></option>
                            <option value="week"><?php _e('最近7天发布的文章', 'onedown'); ?></option>
                            <option value="month"><?php _e('最近30天发布的文章', 'onedown'); ?></option>
                        </select>
                    </div>

                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;"><?php _e('每批推送数量', 'onedown'); ?></label>
                        <select id="onedown-push-limit" style="width:100%;">
                            <option value="10"><?php _e('10 篇/批', 'onedown'); ?></option>
                            <option value="20" selected><?php _e('20 篇/批（推荐）', 'onedown'); ?></option>
                            <option value="50"><?php _e('50 篇/批', 'onedown'); ?></option>
                            <option value="100"><?php _e('100 篇/批', 'onedown'); ?></option>
                        </select>
                    </div>

                    <!-- 进度条 -->
                    <div id="onedown-push-progress-wrap" style="display:none; margin-bottom:15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                            <span id="onedown-push-progress-text"><?php _e('准备推送...', 'onedown'); ?></span>
                            <span id="onedown-push-progress-percent">0%</span>
                        </div>
                        <div style="background:#e5e5e5; border-radius:3px; height:24px; overflow:hidden;">
                            <div id="onedown-push-progress-bar" style="background:#2271b1; height:100%; width:0%; transition:width 0.3s;"></div>
                        </div>
                        <div id="onedown-push-result" style="margin-top:8px; color:#666; font-size:13px;"></div>
                    </div>

                    <!-- 按钮 -->
                    <div style="display:flex; gap:10px;">
                        <button id="onedown-push-start-btn" class="button button-primary button-large" style="flex:1;">
                            <span class="dashicons dashicons-update" style="vertical-align:middle; margin-right:5px;"></span>
                            <?php _e('开始推送', 'onedown'); ?>
                        </button>
                        <button id="onedown-push-stop-btn" class="button button-secondary button-large" style="display:none;">
                            <?php _e('停止推送', 'onedown'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 推送日志 -->
            <div class="postbox" style="flex:1; min-width:400px;">
                <div class="postbox-header">
                    <h2 class="hndle" style="padding:12px; margin:0;"><?php _e('推送日志', 'onedown'); ?></h2>
                </div>
                <div class="inside" style="padding:12px;">
                    <div id="onedown-push-log-list" style="max-height:400px; overflow-y:auto;">
                        <?php
                        $logs = get_option('onedown_seo_push_logs', array());
                        if (! empty($logs)) {
                            $logs = array_slice($logs, 0, 50);
                            foreach ($logs as $log) {
                                echo '<div style="padding:6px 0; border-bottom:1px solid #f0f0f0; font-size:12px; color:#555;">';
                                echo '<span style="color:#999;">' . esc_html($log['time']) . '</span> - ';
                                echo esc_html($log['message']);
                                echo '</div>';
                            }
                        } else {
                            echo '<p style="color:#999;">' . __('暂无推送记录', 'onedown') . '</p>';
                        }
                        ?>
                    </div>
                    <div style="margin-top:10px;">
                        <button id="onedown-push-clear-log-btn" class="button button-small"><?php _e('清空日志', 'onedown'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .onedown-seo-push-container .postbox { border-radius:6px; }
        #onedown-push-progress-bar { background: linear-gradient(90deg, #2271b1, #46b450); }
    </style>

    <script>
    jQuery(function($) {
        var isRunning = false;
        var shouldStop = false;
        var totalPosts = 0;
        var processedPosts = 0;

        $('#onedown-push-start-btn').on('click', function() {
            if (isRunning) return;

            isRunning = true;
            shouldStop = false;
            processedPosts = 0;

            $('#onedown-push-start-btn').prop('disabled', true).hide();
            $('#onedown-push-stop-btn').show();
            $('#onedown-push-progress-wrap').show();
            $('#onedown-push-result').html('');

            var scope = $('#onedown-push-scope').val();
            var limit = parseInt($('#onedown-push-limit').val());

            // 获取总文章数
            $.post(ajaxurl, {
                action: 'onedown_seo_push_stats',
                scope: scope,
                _ajax_nonce: '<?php echo wp_create_nonce('onedown_seo_push_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    totalPosts = response.data.total;
                    if (totalPosts === 0) {
                        $('#onedown-push-result').html('<span style="color:#dc3232;"><?php _e('没有符合条件的文章', 'onedown'); ?></span>');
                        resetPushState();
                        return;
                    }
                    $('#onedown-push-progress-text').text('<?php _e('共发现', 'onedown'); ?> ' + totalPosts + ' <?php _e('篇文章，开始推送...', 'onedown'); ?>');
                    processBatch(1, scope, limit);
                } else {
                    $('#onedown-push-result').html('<span style="color:#dc3232;">' + response.data.message + '</span>');
                    resetPushState();
                }
            });
        });

        $('#onedown-push-stop-btn').on('click', function() {
            shouldStop = true;
            $(this).prop('disabled', true).text('<?php _e('正在停止...', 'onedown'); ?>');
        });

        $('#onedown-push-clear-log-btn').on('click', function() {
            $.post(ajaxurl, {
                action: 'onedown_seo_push_log',
                clear: 1,
                _ajax_nonce: '<?php echo wp_create_nonce('onedown_seo_push_nonce'); ?>'
            }, function() {
                $('#onedown-push-log-list').html('<p style="color:#999;"><?php _e('暂无推送记录', 'onedown'); ?></p>');
            });
        });

        function processBatch(page, scope, limit) {
            if (shouldStop) {
                $('#onedown-push-result').append('<div style="color:#dc3232;"><?php _e('推送已手动停止', 'onedown'); ?></div>');
                resetPushState();
                return;
            }

            $.post(ajaxurl, {
                action: 'onedown_seo_push_batch',
                page: page,
                scope: scope,
                limit: limit,
                _ajax_nonce: '<?php echo wp_create_nonce('onedown_seo_push_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    var data = response.data;
                    processedPosts += data.count;

                    // 更新进度
                    var percent = Math.min(Math.round((processedPosts / totalPosts) * 100), 100);
                    $('#onedown-push-progress-bar').css('width', percent + '%');
                    $('#onedown-push-progress-percent').text(percent + '%');
                    $('#onedown-push-progress-text').text('<?php _e('正在推送', 'onedown'); ?>... (' + processedPosts + '/' + totalPosts + ')');

                    // 显示每批结果
                    if (data.results && data.results.length > 0) {
                        var resultHtml = '';
                        $.each(data.results, function(i, r) {
                            resultHtml += '<div style="font-size:12px; color:#555; padding:2px 0;">' +
                                '<span style="color:#2271b1;">[' + r.engine + ']</span> ' + r.message + '</div>';
                        });
                        $('#onedown-push-result').append(resultHtml);
                    }

                    // 刷新日志
                    refreshLogs();

                    // 继续下一批
                    if (data.has_more) {
                        processBatch(page + 1, scope, limit);
                    } else {
                        // 完成
                        $('#onedown-push-progress-bar').css('width', '100%');
                        $('#onedown-push-progress-percent').text('100%');
                        $('#onedown-push-progress-text').text('<?php _e('推送完成！', 'onedown'); ?> (' + processedPosts + '/' + totalPosts + ')');
                        $('#onedown-push-result').append('<div style="color:#46b450; font-weight:600; margin-top:8px;"><?php _e('全部推送完毕', 'onedown'); ?></div>');
                        resetPushState();
                        refreshLogs();
                    }
                } else {
                    $('#onedown-push-result').append('<div style="color:#dc3232;"><?php _e('推送出错', 'onedown'); ?>: ' + response.data.message + '</div>');
                    resetPushState();
                }
            }).fail(function() {
                $('#onedown-push-result').append('<div style="color:#dc3232;"><?php _e('网络请求失败，请重试', 'onedown'); ?></div>');
                resetPushState();
            });
        }

        function resetPushState() {
            isRunning = false;
            $('#onedown-push-start-btn').prop('disabled', false).show();
            $('#onedown-push-stop-btn').hide().prop('disabled', false).text('<?php _e('停止推送', 'onedown'); ?>');
        }

        function refreshLogs() {
            $.post(ajaxurl, {
                action: 'onedown_seo_push_log',
                _ajax_nonce: '<?php echo wp_create_nonce('onedown_seo_push_nonce'); ?>'
            }, function(response) {
                if (response.success && response.data.logs) {
                    var html = '';
                    $.each(response.data.logs, function(i, log) {
                        html += '<div style="padding:6px 0; border-bottom:1px solid #f0f0f0; font-size:12px; color:#555;">' +
                            '<span style="color:#999;">' + log.time + '</span> - ' + log.message + '</div>';
                    });
                    $('#onedown-push-log-list').html(html || '<p style="color:#999;"><?php _e('暂无推送记录', 'onedown'); ?></p>');
                }
            });
        }
    });
    </script>
<?php
}

// ──────────────────────────────────────────────
// AJAX 处理：获取推送统计
// ──────────────────────────────────────────────
function onedown_seo_push_stats_ajax_handler()
{
    check_ajax_referer('onedown_seo_push_nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('权限不足', 'onedown')));
    }

    $scope = isset($_POST['scope']) ? sanitize_text_field($_POST['scope']) : 'all';

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    );

    // 按时间范围筛选
    if ($scope === 'week') {
        $args['date_query'] = array(
            'after' => '-7 days',
        );
    } elseif ($scope === 'month') {
        $args['date_query'] = array(
            'after' => '-30 days',
        );
    }

    $query = new WP_Query($args);
    $total = $query->post_count;

    wp_send_json_success(array(
        'total' => $total,
    ));
}

// ──────────────────────────────────────────────
// AJAX 处理：批量推送
// ──────────────────────────────────────────────
function onedown_seo_push_ajax_handler()
{
    check_ajax_referer('onedown_seo_push_nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('权限不足', 'onedown')));
    }

    // 是否启用 URL 提交
    if (! _pz('seo_url_submit_enabled', false)) {
        wp_send_json_error(array('message' => __('请先在"SEO 优化 → URL 自动提交"中启用总开关并配置搜索引擎参数', 'onedown')));
    }

    $page    = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit   = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
    $scope   = isset($_POST['scope']) ? sanitize_text_field($_POST['scope']) : 'all';

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    );

    // 按时间范围筛选
    if ($scope === 'week') {
        $args['date_query'] = array(
            'after' => '-7 days',
        );
    } elseif ($scope === 'month') {
        $args['date_query'] = array(
            'after' => '-30 days',
        );
    }

    $query = new WP_Query($args);

    if (! $query->have_posts()) {
        wp_send_json_success(array(
            'count'    => 0,
            'results'  => array(),
            'has_more' => false,
        ));
    }

    $results   = array();
    $push_count = 0;

    while ($query->have_posts()) {
        $query->the_post();
        $post_id    = get_the_ID();
        $post_title = get_the_title();
        $permalink  = get_permalink();

        if (! $permalink) {
            continue;
        }

        // 逐个引擎提交
        $engine_results = array();

        // 百度
        if (_pz('seo_url_submit_baidu_enabled', true)) {
            $token = _pz('seo_url_submit_baidu_token', '');
            $site  = _pz('seo_url_submit_baidu_site', '');
            $res   = onedown_seo_submit_to_api('baidu', $permalink, $token, $site);
            if ($res) {
                $engine_results[] = array('engine' => '百度', 'message' => $res);
            }
        }

        // 360
        if (_pz('seo_url_submit_360_enabled', false)) {
            $token = _pz('seo_url_submit_360_token', '');
            $site  = _pz('seo_url_submit_360_site', '');
            $res   = onedown_seo_submit_to_api('360', $permalink, $token, $site);
            if ($res) {
                $engine_results[] = array('engine' => '360', 'message' => $res);
            }
        }

        // 搜狗
        if (_pz('seo_url_submit_sogou_enabled', false)) {
            $token = _pz('seo_url_submit_sogou_token', '');
            $site  = _pz('seo_url_submit_sogou_site', '');
            $res   = onedown_seo_submit_to_api('sogou', $permalink, $token, $site);
            if ($res) {
                $engine_results[] = array('engine' => '搜狗', 'message' => $res);
            }
        }

        // 神马
        if (_pz('seo_url_submit_shenma_enabled', false)) {
            $token = _pz('seo_url_submit_shenma_token', '');
            $site  = _pz('seo_url_submit_shenma_site', '');
            $res   = onedown_seo_submit_to_api('shenma', $permalink, $token, $site);
            if ($res) {
                $engine_results[] = array('engine' => '神马', 'message' => $res);
            }
        }

        // 夸克
        if (_pz('seo_url_submit_quark_enabled', false)) {
            $token = _pz('seo_url_submit_quark_token', '');
            $site  = _pz('seo_url_submit_quark_site', '');
            $res   = onedown_seo_submit_to_api('quark', $permalink, $token, $site);
            if ($res) {
                $engine_results[] = array('engine' => '夸克', 'message' => $res);
            }
        }

        // Bing
        if (_pz('seo_url_submit_bing_enabled', false)) {
            $api_key = _pz('seo_url_submit_bing_api_key', '');
            $res     = onedown_seo_submit_to_bing($permalink, $api_key);
            if ($res) {
                $engine_results[] = array('engine' => 'Bing', 'message' => $res);
            }
        }

        // Google
        if (_pz('seo_url_submit_google_enabled', false)) {
            $res = onedown_seo_submit_to_google($permalink);
            if ($res) {
                $engine_results[] = array('engine' => 'Google', 'message' => $res);
            }
        }

        if (! empty($engine_results)) {
            foreach ($engine_results as $er) {
                $results[] = array(
                    'post_id'    => $post_id,
                    'post_title' => $post_title,
                    'engine'     => $er['engine'],
                    'message'    => $er['message'],
                );
            }
            $push_count++;
        }

        // 记录当前文章推送日志到 post meta
        $log_message = '[' . current_time('Y-m-d H:i:s') . '] 批量推送: ' . implode(', ', array_map(function ($e) {
            return $e['engine'] . ':' . $e['message'];
        }, $engine_results));
        update_post_meta($post_id, '_onedown_seo_submit_log', $log_message);
    }

    wp_reset_postdata();

    // 检查是否还有更多
    $has_more = $query->max_num_pages > $page;

    // 更新全局统计
    if ($push_count > 0) {
        $total = intval(get_option('onedown_seo_push_total_count', 0)) + $push_count;
        update_option('onedown_seo_push_total_count', $total);
        update_option('onedown_seo_push_last_time', current_time('Y-m-d H:i:s'));

        // 追加推送日志
        $log_entry = array(
            'time'    => current_time('Y-m-d H:i:s'),
            'message' => sprintf(__('批量推送 %d 篇文章到搜索引擎', 'onedown'), $push_count),
        );
        $logs = get_option('onedown_seo_push_logs', array());
        array_unshift($logs, $log_entry);
        // 保留最近 100 条
        $logs = array_slice($logs, 0, 100);
        update_option('onedown_seo_push_logs', $logs);
    }

    wp_send_json_success(array(
        'count'    => $push_count,
        'results'  => $results,
        'has_more' => $has_more,
    ));
}

// ──────────────────────────────────────────────
// AJAX 处理：获取/清空推送日志
// ──────────────────────────────────────────────
function onedown_seo_push_log_ajax_handler()
{
    check_ajax_referer('onedown_seo_push_nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('权限不足', 'onedown')));
    }

    // 清空日志
    if (isset($_POST['clear']) && $_POST['clear'] == 1) {
        delete_option('onedown_seo_push_logs');
        wp_send_json_success(array('logs' => array()));
        return;
    }

    // 获取日志
    $logs = get_option('onedown_seo_push_logs', array());
    $logs = array_slice($logs, 0, 50);

    wp_send_json_success(array('logs' => $logs));
}
