<?php

/**
 * Onedown 文章付费功能 Metabox
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', 'onedown_register_pay_metabox');
add_action('save_post_post', 'onedown_save_pay_metabox', 10, 2);

/**
 * 注册文章付费功能面板
 */
function onedown_register_pay_metabox()
{
    // 未授权时不显示付费功能面板
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        return;
    }

    add_meta_box(
        'onedown_pay_metabox',
        '付费功能',
        'onedown_render_pay_metabox',
        'post',
        'normal',
        'high'
    );
}

/**
 * 渲染付费功能面板
 */
function onedown_render_pay_metabox($post)
{
    // 检查是否已保存过数据（用于判断是新文章还是已有数据）
    $saved_meta = get_post_meta($post->ID, '_onedown_pay_metabox', true);
    $has_saved_data = is_array($saved_meta) && ! empty($saved_meta);
    $data = $has_saved_data ? $saved_meta : array();

    // 获取主题设置中的默认值
    $def_price      = _pz('pay_default_price', '9.99');
    $def_orig_price = _pz('pay_default_orig_price', '');
    $unified_on     = _pz('pay_unified_price', false);
    $def_vip_raw    = _pz('pay_default_vip_prices', '');

    // 解析默认VIP价格
    $def_vip_prices = array();
    if ($def_vip_raw) {
        $lines = preg_split("/\r\n|\n|\r/", $def_vip_raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode(':', $line);
            if (count($parts) === 2) {
                $id    = trim($parts[0]);
                $price = trim($parts[1]);
                if ($id !== '' && is_numeric($price)) {
                    $def_vip_prices[$id] = floatval($price);
                }
            }
        }
    }

    $pay_type       = $data['pay_type'] ?? 'no';
    $pay_price      = $data['pay_price'] ?? $def_price;
    $pay_orig_price = $data['pay_original_price'] ?? $def_orig_price;
    $pay_sales      = $data['pay_sales'] ?? '0';
    $buy_permission = $data['buy_permission'] ?? 'all';
    $pay_vip_prices = isset($data['pay_vip_prices']) && is_array($data['pay_vip_prices']) ? $data['pay_vip_prices'] : $def_vip_prices;
    $downloads      = isset($data['pay_downloads']) && is_array($data['pay_downloads']) ? $data['pay_downloads'] : array();
    // 至少显示一行
    if (empty($downloads)) {
        $downloads[] = array('name' => '', 'url' => '', 'pwd' => '', 'size' => '');
    }

    $vip_levels = function_exists('onedown_vip_levels') ? onedown_vip_levels() : array();

    wp_nonce_field('onedown_save_pay_metabox', 'onedown_pay_metabox_nonce');
?>
    <style>
        .onedown-pay-admin-grid {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 14px 18px;
            align-items: start
        }

        .onedown-pay-admin-grid label.olabel {
            font-weight: 600
        }

        .onedown-pay-admin-grid input[type=text],
        .onedown-pay-admin-grid input[type=number],
        .onedown-pay-admin-grid textarea,
        .onedown-pay-admin-grid select {
            width: 100%;
            max-width: 640px
        }

        .onedown-pay-admin-grid .desc {
            color: #666;
            font-size: 12px;
            margin-top: 4px
        }

        .onedown-pay-admin-table {
            width: 100%;
            max-width: 860px;
            border-collapse: collapse
        }

        .onedown-pay-admin-table th,
        .onedown-pay-admin-table td {
            border: 1px solid #ddd;
            padding: 8px
        }

        .onedown-pay-admin-table th {
            background: #f6f7f7;
            text-align: left
        }

        .onedown-pay-admin-section {
            grid-column: 1/-1;
            margin: 12px 0 0;
            padding: 10px 12px;
            background: #f6f7f7;
            border-left: 4px solid #2271b1;
            font-weight: 700
        }

        .pay-radio-group {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            padding-top: 4px
        }

        .pay-radio-group label {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            font-weight: 400
        }

        .pay-radio-group label input[type=radio] {
            margin: 0
        }

        .pay-dl-row-remove {
            color: #a00;
            cursor: pointer;
            text-decoration: none;
            font-size: 18px;
            line-height: 1
        }

        .pay-dl-row-remove:hover {
            color: #dc3232
        }

        .pay-dl-add-row {
            margin-top: 8px
        }

        .pay-vip-price-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px
        }

        .pay-vip-price-row label {
            min-width: 80px;
            font-weight: 600
        }

        .pay-vip-price-row input {
            width: 120px
        }

        .pay-vip-price-row .vip-tag {
            display: inline-block;
            font-size: 11px;
            padding: 1px 6px;
            border-radius: 3px;
            background: #f04494;
            color: #fff
        }
    </style>
    <div class="onedown-pay-admin-grid">
        <label class="olabel">付费模式</label>
        <div>
            <div class="pay-radio-group">
                <label><input type="radio" name="onedown_pay[pay_type]" value="no" <?php checked($pay_type, 'no'); ?>> 关闭付费</label>
                <label><input type="radio" name="onedown_pay[pay_type]" value="read" <?php checked($pay_type, 'read'); ?>> 付费阅读</label>
                <label><input type="radio" name="onedown_pay[pay_type]" value="download" <?php checked($pay_type, 'download'); ?>> 付费下载</label>
            </div>
            <div class="desc">付费阅读请在正文中使用短代码：[payshow]隐藏内容[/payshow]</div>
        </div>

        <div class="onedown-pay-admin-section">价格设置</div>

        <?php if ($unified_on) : ?>
            <div style="grid-column:1/-1;padding:8px 12px;margin-bottom:8px;background:#fff3cd;border:1px solid #ffeeba;border-radius:4px;color:#856404;font-size:13px;">
                <i class="fa fa-info-circle"></i> 统一售价已开启（主题设置 → VIP会员），前台将强制使用默认价格。
            </div>
        <?php endif; ?>

        <label class="olabel">售价</label>
        <div><input type="number" step="0.01" min="0" name="onedown_pay[pay_price]" value="<?php echo esc_attr($pay_price); ?>"> 元</div>

        <label class="olabel">划线价</label>
        <div><input type="number" step="0.01" min="0" name="onedown_pay[pay_original_price]" value="<?php echo esc_attr($pay_orig_price); ?>"> 元</div>

        <label class="olabel">初始销量</label>
        <div><input type="number" min="0" name="onedown_pay[pay_sales]" value="<?php echo esc_attr($pay_sales); ?>"> 次</div>

        <?php if (! empty($vip_levels)) : ?>
            <label class="olabel">会员价格</label>
            <div>
                <?php foreach ($vip_levels as $level_id => $level) :
                    $v_price = isset($pay_vip_prices[$level_id]) && $pay_vip_prices[$level_id] !== '' ? $pay_vip_prices[$level_id] : $pay_price;
                ?>
                    <div class="pay-vip-price-row">
                        <label><?php echo esc_html($level['name']); ?></label>
                        <input type="number" step="0.01" min="0" name="onedown_pay[pay_vip_prices][<?php echo esc_attr($level_id); ?>]" value="<?php echo esc_attr($v_price); ?>"> 元
                        <?php if (! empty($level['tag'])) : ?>
                            <span class="vip-tag"><?php echo esc_html($level['tag']); ?></span>
                        <?php endif; ?>
                        <span class="desc">默认与售价一致，调为 0 表示该等级会员免费</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="onedown-pay-admin-section">购买权限</div>

        <label class="olabel">允许购买</label>
        <div>
            <div class="pay-radio-group">
                <label><input type="radio" name="onedown_pay[buy_permission]" value="all" <?php checked($buy_permission, 'all'); ?>> 所有人</label>
                <label><input type="radio" name="onedown_pay[buy_permission]" value="logged_in" <?php checked($buy_permission, 'logged_in'); ?>> 仅登录用户</label>
                <label><input type="radio" name="onedown_pay[buy_permission]" value="vip_only" <?php checked($buy_permission, 'vip_only'); ?>> 仅会员</label>
            </div>
            <div class="desc">选择"仅会员"时，只有开通了会员的用户才能购买</div>
        </div>

        <div class="onedown-pay-admin-section">下载资源（付费下载时使用）</div>

        <label class="olabel">下载链接</label>
        <div>
            <table class="onedown-pay-admin-table" id="pay-downloads-table">
                <thead>
                    <tr>
                        <th>按钮文案</th>
                        <th>下载地址</th>
                        <th>提取码</th>
                        <th>文件大小</th>
                        <th style="width:32px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($downloads as $index => $item) : ?>
                        <tr class="pay-dl-row">
                            <td><input type="text" name="onedown_pay[pay_downloads][<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($item['name'] ?? ''); ?>" placeholder="立即下载"></td>
                            <td><input type="text" name="onedown_pay[pay_downloads][<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($item['url'] ?? ''); ?>" placeholder="https://..."></td>
                            <td><input type="text" name="onedown_pay[pay_downloads][<?php echo esc_attr($index); ?>][pwd]" value="<?php echo esc_attr($item['pwd'] ?? ''); ?>"></td>
                            <td><input type="text" name="onedown_pay[pay_downloads][<?php echo esc_attr($index); ?>][size]" value="<?php echo esc_attr($item['size'] ?? ''); ?>" placeholder="10MB"></td>
                            <td><a class="pay-dl-row-remove" onclick="this.closest('tr').remove()">&times;</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pay-dl-add-row">
                <button type="button" class="button" id="pay-dl-add-btn">+ 添加下载链接</button>
            </div>
        </div>
    </div>

    <script>
        jQuery(function($) {
            var dlIndex = <?php echo count($downloads); ?>;

            $('#pay-dl-add-btn').on('click', function() {
                var row = '<tr class="pay-dl-row">' +
                    '<td><input type="text" name="onedown_pay[pay_downloads][' + dlIndex + '][name]" value="" placeholder="立即下载"></td>' +
                    '<td><input type="text" name="onedown_pay[pay_downloads][' + dlIndex + '][url]" value="" placeholder="https://..."></td>' +
                    '<td><input type="text" name="onedown_pay[pay_downloads][' + dlIndex + '][pwd]" value=""></td>' +
                    '<td><input type="text" name="onedown_pay[pay_downloads][' + dlIndex + '][size]" value="" placeholder="10MB"></td>' +
                    '<td><a class="pay-dl-row-remove" onclick="this.closest(\'tr\').remove()">&times;</a></td>' +
                    '</tr>';
                $('#pay-downloads-table tbody').append(row);
                dlIndex++;
            });
        });
    </script>
<?php
}

/**
 * 保存付费功能面板数据
 */
function onedown_save_pay_metabox($post_id, $post)
{
    if (! isset($_POST['onedown_pay_metabox_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['onedown_pay_metabox_nonce'])), 'onedown_save_pay_metabox')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if ('post' !== $post->post_type || ! current_user_can('edit_post', $post_id)) {
        return;
    }

    $raw = isset($_POST['onedown_pay']) && is_array($_POST['onedown_pay']) ? wp_unslash($_POST['onedown_pay']) : array();

    $data = array(
        'pay_type'           => in_array($raw['pay_type'] ?? 'no', array('no', 'read', 'download'), true) ? $raw['pay_type'] : 'no',
        'pay_price'          => max(0, floatval($raw['pay_price'] ?? 0)),
        'pay_original_price' => max(0, floatval($raw['pay_original_price'] ?? 0)),
        'pay_sales'          => max(0, intval($raw['pay_sales'] ?? 0)),
        'buy_permission'     => in_array($raw['buy_permission'] ?? 'all', array('all', 'logged_in', 'vip_only'), true) ? $raw['buy_permission'] : 'all',
        'pay_vip_prices'     => array(),
        'pay_downloads'      => array(),
    );

    // 会员价格
    if (! empty($raw['pay_vip_prices']) && is_array($raw['pay_vip_prices'])) {
        $levels = function_exists('onedown_vip_levels') ? onedown_vip_levels() : array();
        foreach ($raw['pay_vip_prices'] as $level_id => $price) {
            $level_id = sanitize_key($level_id);
            if (isset($levels[$level_id])) {
                $data['pay_vip_prices'][$level_id] = max(0, floatval($price));
            }
        }
    }

    // 下载链接
    if (! empty($raw['pay_downloads']) && is_array($raw['pay_downloads'])) {
        foreach ($raw['pay_downloads'] as $item) {
            $url = isset($item['url']) ? esc_url_raw($item['url']) : '';
            if ('' === $url) {
                continue;
            }
            $data['pay_downloads'][] = array(
                'name' => sanitize_text_field($item['name'] ?? '立即下载'),
                'url'  => $url,
                'pwd'  => sanitize_text_field($item['pwd'] ?? ''),
                'size' => sanitize_text_field($item['size'] ?? ''),
            );
        }
    }

    update_post_meta($post_id, '_onedown_pay_metabox', $data);
}
