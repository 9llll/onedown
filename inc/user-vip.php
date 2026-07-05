<?php
/**
 * Onedown 独立VIP会员系统
 *
 * 不依赖第三方插件，使用 user_meta 存储VIP信息。
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 从主题设置读取会员类型列表
 */
function onedown_vip_levels()
{
    $members = _pz('vip_members', array());

    if (empty($members) || !is_array($members)) {
        return array(
            'monthly'  => array(
                'id'     => 'monthly',
                'name'   => '月度会员',
                'months' => 1,
                'desc'   => '适合短期体验',
                'price'  => 29,
                'show_price' => 49,
                'download_limit' => 50,
                'commission_ratio' => 10,
                'tag'    => '',
            ),
            'yearly'   => array(
                'id'     => 'yearly',
                'name'   => '年度会员',
                'months' => 12,
                'desc'   => '适合长期运营',
                'price'  => 199,
                'show_price' => 399,
                'download_limit' => 200,
                'commission_ratio' => 20,
                'tag'    => '推荐',
            ),
            'forever'  => array(
                'id'     => 'forever',
                'name'   => '永久会员',
                'months' => 0,
                'desc'   => '一次性购买，永久有效',
                'price'  => 399,
                'show_price' => 999,
                'download_limit' => 999999,
                'commission_ratio' => 30,
                'tag'    => '最值',
            ),
        );
    }

    $levels = array();
    foreach ($members as $member) {
        $id = !empty($member['vip_id']) ? sanitize_key($member['vip_id']) : '';
        if (empty($id)) {
            continue;
        }
        $levels[$id] = array(
            'id'     => $id,
            'name'   => !empty($member['vip_name']) ? $member['vip_name'] : $id,
            'months' => isset($member['vip_months']) ? intval($member['vip_months']) : 1,
            'desc'   => !empty($member['vip_desc']) ? $member['vip_desc'] : '',
            'price'  => isset($member['vip_price']) ? floatval($member['vip_price']) : 0,
            'show_price' => isset($member['vip_show_price']) ? floatval($member['vip_show_price']) : 0,
            'download_limit' => isset($member['vip_download_limit']) && $member['vip_download_limit'] !== '' ? intval($member['vip_download_limit']) : -1,
            'commission_ratio' => isset($member['vip_commission_ratio']) ? floatval($member['vip_commission_ratio']) : 0,
            'tag'    => !empty($member['vip_tag']) ? $member['vip_tag'] : '',
        );
    }

    return $levels;
}

/**
 * 获取VIP套餐价格
 */
function onedown_vip_plan_price($plan_id = '')
{
    $levels = onedown_vip_levels();
    $prices = array();
    foreach ($levels as $id => $level) {
        $prices[$id] = $level['price'];
    }
    if ($plan_id) {
        return isset($prices[$plan_id]) ? $prices[$plan_id] : 0;
    }
    return $prices;
}

/**
 * 根据套餐ID获取套餐信息
 */
function onedown_get_vip_plan($plan_id)
{
    $levels = onedown_vip_levels();
    return isset($levels[$plan_id]) ? $levels[$plan_id] : null;
}

/**
 * 获取当前用户 VIP 信息
 */
function onedown_get_user_vip_info($user_id = 0)
{
    $default = array(
        'vip_name'    => '普通会员',
        'vip_class'   => '',
        'is_vip'      => false,
        'expire_date' => '',
        'plan_id'     => '',
        'level_id'    => '',
    );

    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return $default;
    }

    if (function_exists('getUsreMemberType')) {
        return _onedown_get_vip_info_from_erphpdown();
    }

    $vip_level  = get_user_meta($user_id, 'onedown_vip_level', true);
    $vip_expire = get_user_meta($user_id, 'onedown_vip_expire', true);

    if (!$vip_level) {
        return $default;
    }

    if ($vip_level !== 'forever') {
        $now = current_time('timestamp');
        $expire_ts = strtotime($vip_expire);
        if ($expire_ts && $expire_ts < $now) {
            update_user_meta($user_id, 'onedown_vip_expired_level', $vip_level);
            delete_user_meta($user_id, 'onedown_vip_level');
            delete_user_meta($user_id, 'onedown_vip_expire');
            return $default;
        }
    }

    $levels = onedown_vip_levels();
    $plan   = isset($levels[$vip_level]) ? $levels[$vip_level] : null;

    return array(
        'vip_name'    => $plan ? $plan['name'] : 'VIP会员',
        'vip_class'   => 'vip-' . $vip_level,
        'is_vip'      => true,
        'expire_date' => $vip_level === 'forever' ? '永久有效' : ($vip_expire ? date_i18n('Y-m-d H:i', strtotime($vip_expire)) : ''),
        'plan_id'     => $vip_level,
        'level_id'    => $vip_level,
    );
}

/**
 * 从 erphpdown 获取VIP信息（向下兼容）
 */
function _onedown_get_vip_info_from_erphpdown()
{
    $default = array(
        'vip_name'    => '普通会员',
        'vip_class'   => '',
        'is_vip'      => false,
        'expire_date' => '',
        'plan_id'     => '',
        'level_id'    => '',
    );

    if (!function_exists('getUsreMemberType')) {
        return $default;
    }

    $vip_type = getUsreMemberType();
    if (!$vip_type) {
        return $default;
    }

    $vip_map = array(
        6  => array('name' => '体验会员', 'id' => 'trial'),
        7  => array('name' => '包月会员', 'id' => 'monthly'),
        8  => array('name' => '包季会员', 'id' => 'quarterly'),
        9  => array('name' => '包年会员', 'id' => 'yearly'),
        10 => array('name' => '终身会员', 'id' => 'forever'),
    );

    $info = isset($vip_map[$vip_type]) ? $vip_map[$vip_type] : array('name' => 'VIP会员', 'id' => '');
    return array(
        'vip_name'    => $info['name'],
        'vip_class'   => 'vip-' . $info['id'],
        'is_vip'      => true,
        'expire_date' => '',
        'plan_id'     => $info['id'],
        'level_id'    => $info['id'],
    );
}

/**
 * 获取VIP等级权重（数值越大等级越高）
 * 永久会员（months=0）权重为 999
 */
function onedown_vip_level_weight($plan_id)
{
    $levels = onedown_vip_levels();
    if (!isset($levels[$plan_id])) {
        return 0;
    }
    $months = $levels[$plan_id]['months'];
    return $months > 0 ? intval($months) : 999;
}

/**
 * 检查用户是否可以从当前VIP升级到目标VIP
 *
 * @param int    $user_id        用户ID
 * @param string $target_plan_id 目标套餐ID
 * @return bool
 */
function onedown_vip_can_upgrade($user_id, $target_plan_id)
{
    $vip_info = onedown_get_user_vip_info($user_id);
    if (!$vip_info['is_vip'] || empty($vip_info['plan_id'])) {
        return false;
    }

    $current_plan_id = $vip_info['plan_id'];
    if ($current_plan_id === $target_plan_id) {
        return false;
    }

    $levels = onedown_vip_levels();
    if (!isset($levels[$current_plan_id]) || !isset($levels[$target_plan_id])) {
        return false;
    }

    $current_weight = onedown_vip_level_weight($current_plan_id);
    $target_weight  = onedown_vip_level_weight($target_plan_id);

    return $target_weight > $current_weight;
}

/**
 * 计算升级到目标VIP所需的差价
 *
 * @param int    $user_id        用户ID
 * @param string $target_plan_id 目标套餐ID
 * @return float 需支付的升级价格
 */
function onedown_vip_calc_upgrade_price($user_id, $target_plan_id)
{
    if (!onedown_vip_can_upgrade($user_id, $target_plan_id)) {
        return 0;
    }

    $vip_info = onedown_get_user_vip_info($user_id);
    $levels   = onedown_vip_levels();

    $current_plan = $levels[$vip_info['plan_id']];
    $target_plan  = $levels[$target_plan_id];

    $diff = floatval($target_plan['price']) - floatval($current_plan['price']);
    return max(0, $diff);
}

/**
 * 获取用户所有可升级套餐的差价列表
 *
 * @param int $user_id 用户ID
 * @return array 套餐ID => 升级差价
 */
function onedown_get_user_vip_upgrade_prices($user_id)
{
    $prices = array();
    $levels = onedown_vip_levels();
    foreach ($levels as $id => $level) {
        if (onedown_vip_can_upgrade($user_id, $id)) {
            $prices[$id] = onedown_vip_calc_upgrade_price($user_id, $id);
        } else {
            $prices[$id] = 0;
        }
    }
    return $prices;
}

/**
 * 更新用户VIP状态（支持升级延长时间）
 */
function onedown_update_user_vip($user_id, $plan_id, $order_id = '')
{
    $levels = onedown_vip_levels();
    if (!isset($levels[$plan_id])) {
        return false;
    }

    $plan = $levels[$plan_id];

    // 检查是否升级：用户已有VIP且目标等级更高
    $current_vip = onedown_get_user_vip_info($user_id);
    $is_upgrade  = $current_vip['is_vip'] && onedown_vip_can_upgrade($user_id, $plan_id);

    if ($is_upgrade) {
        // 升级：在原有到期时间基础上延长
        $current_expire = get_user_meta($user_id, 'onedown_vip_expire', true);
        $base_time = !empty($current_expire) ? strtotime($current_expire) : current_time('timestamp');
        // 如果已过期，以当前时间为基准
        if ($base_time < current_time('timestamp')) {
            $base_time = current_time('timestamp');
        }

        if ($plan['months'] > 0) {
            $expire_date = date('Y-m-d H:i:s', strtotime('+' . $plan['months'] . ' months', $base_time));
        } else {
            $expire_date = '2099-12-31 23:59:59';
        }
    } else {
        // 新开通或平级/降级：直接设置
        if ($plan['months'] > 0) {
            $expire_date = date('Y-m-d H:i:s', strtotime('+' . $plan['months'] . ' months', current_time('timestamp')));
        } else {
            $expire_date = '2099-12-31 23:59:59';
        }
    }

    update_user_meta($user_id, 'onedown_vip_level', $plan_id);
    update_user_meta($user_id, 'onedown_vip_expire', $expire_date);

    if ($order_id) {
        update_user_meta($user_id, 'onedown_vip_last_order', $order_id);
    }

    return true;
}

/**
 * 创建VIP订单
 */
function onedown_create_vip_order($user_id, $plan_id, $method = 'wechat')
{
    $price = onedown_vip_plan_price($plan_id);
    $order_id = 'VIP' . date('YmdHis') . strtoupper(wp_generate_password(6, false));

    $order = array(
        'order_id'   => $order_id,
        'user_id'    => $user_id,
        'plan_id'    => $plan_id,
        'amount'     => $price,
        'method'     => $method,
        'status'     => 'pending',
        'created_at' => current_time('mysql'),
        'paid_at'    => '',
    );

    $orders = get_user_meta($user_id, 'onedown_vip_orders', true);
    if (!is_array($orders)) {
        $orders = array();
    }
    $orders[$order_id] = $order;
    update_user_meta($user_id, 'onedown_vip_orders', $orders);

    return $order;
}

/**
 * 确认支付成功（使用订单表高效查询，替代 get_users 全量扫描）
 */
function onedown_vip_payment_success($order_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'onedown_orders';

    // 直接从订单表查询
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE order_id = %s AND order_type = 'vip' LIMIT 1",
        $order_id
    ));

    if (! $order || $order->status !== 'pending') {
        return false;
    }

    $user_id = intval($order->user_id);
    if ($user_id <= 0) {
        return false;
    }

    // 更新订单状态
    $wpdb->update(
        $table,
        array(
            'status'   => 'paid',
            'pay_time' => current_time('mysql'),
        ),
        array('order_id' => $order_id)
    );

    // 从 pay_detail 中读取 plan_id
    $pay_detail = ! empty($order->pay_detail) ? json_decode($order->pay_detail, true) : array();
    $plan_id    = ! empty($pay_detail['plan_id']) ? $pay_detail['plan_id'] : '';

    if (empty($plan_id)) {
        return false;
    }

    onedown_update_user_vip($user_id, $plan_id, $order_id);

    if (_pz('referral_enabled', false)) {
        $amount = floatval($order->pay_price) > 0 ? floatval($order->pay_price) : floatval($order->order_price);
        onedown_referral_calc_commission($user_id, $order_id, $plan_id, $amount);
    }

    return true;
}

/**
 * 获取用户订单列表
 */
function onedown_get_user_orders($user_id)
{
    $orders = get_user_meta($user_id, 'onedown_vip_orders', true);
    if (!is_array($orders)) {
        return array();
    }
    krsort($orders);
    return $orders;
}

/**
 * 获取VIP到期时间文本
 */
function onedown_get_vip_expire_text($user_id)
{
    $info = onedown_get_user_vip_info($user_id);
    if (!$info['is_vip']) {
        return '未开通';
    }
    return $info['expire_date'];
}

/**
 * 获取用户订单数量
 */
function onedown_get_user_orders_count($user_id)
{
    $orders = onedown_get_user_orders($user_id);
    return count($orders);
}

/**
 * 获取订单状态标签
 */
function onedown_order_status_label($status)
{
    $labels = array(
        'pending' => '待付款',
        'paid'    => '已完成',
        'expired' => '已关闭',
    );
    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * 获取订单状态CSS类
 */
function onedown_order_status_class($status)
{
    $classes = array(
        'pending' => '',
        'paid'    => '',
        'expired' => '',
    );
    return isset($classes[$status]) ? $classes[$status] : '';
}

/**
 * 获取用户VIP对应的下载限制
 */
function onedown_get_user_download_limit($user_id = 0)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $vip_info = onedown_get_user_vip_info($user_id);

    if (!$vip_info['is_vip']) {
        $limit = _pz('vip_download_unlimit', '5');
        return ($limit === '' || intval($limit) < 0) ? -1 : intval($limit);
    }

    $levels = onedown_vip_levels();
    $plan_id = $vip_info['level_id'];

    if (isset($levels[$plan_id])) {
        $limit = $levels[$plan_id]['download_limit'];
        return ($limit < 0) ? -1 : $limit;
    }

    return -1;
}

/**
 * 检查用户今日是否还能下载
 */
function onedown_check_download_today($user_id = 0)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $limit = onedown_get_user_download_limit($user_id);

    if ($limit === -1) {
        return array('can_download' => true, 'used' => 0, 'limit' => -1);
    }

    if ($limit === 0) {
        return array('can_download' => false, 'used' => 0, 'limit' => 0);
    }

    $today = date('Y-m-d');
    $downloads = get_user_meta($user_id, 'onedown_downloads', true);
    $today_count = 0;

    if (is_array($downloads)) {
        foreach ($downloads as $dl) {
            if (isset($dl['time']) && substr($dl['time'], 0, 10) === $today) {
                $today_count++;
            }
        }
    }

    return array(
        'can_download' => $today_count < $limit,
        'used'  => $today_count,
        'limit' => $limit,
    );
}

// ═════════════════════════════════════════════════════
// 后台用户管理 - 会员状态列
// ═════════════════════════════════════════════════════

/**
 * 在用户列表中添加会员状态列
 */
add_filter('manage_users_columns', 'onedown_admin_users_add_vip_column');
function onedown_admin_users_add_vip_column($columns)
{
    $columns['onedown_vip_status'] = '会员状态';
    $columns['onedown_balance']    = '余额';
    return $columns;
}

/**
 * 渲染用户列表会员状态列内容
 */
add_filter('manage_users_custom_column', 'onedown_admin_users_vip_column_content', 10, 3);
function onedown_admin_users_vip_column_content($value, $column_name, $user_id)
{
    if ($column_name === 'onedown_vip_status') {
        $vip_info = onedown_get_user_vip_info($user_id);
        $output = '';

        if ($vip_info['is_vip']) {
            $vip_name = esc_html($vip_info['vip_name']);
            $expire   = esc_html($vip_info['expire_date']);
            $output .= '<span style="color:#e04494;font-weight:600;">' . $vip_name . '</span><br>';
            $output .= '<small style="color:#888;">' . $expire . '</small>';
        } else {
            $output .= '<span style="color:#999;">普通用户</span>';
        }

        return $output;
    }

    if ($column_name === 'onedown_balance') {
        $balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
        $color   = $balance >= 0 ? '#52c41a' : '#ff4d4f';
        return '<span style="color:' . $color . ';font-weight:600;">￥' . number_format($balance, 2) . '</span>';
    }

    return $value;
}

// ═════════════════════════════════════════════════════
// 后台用户编辑 - VIP会员设置
// ═════════════════════════════════════════════════════

/**
 * 在用户编辑页面添加VIP会员设置字段
 */
add_action('show_user_profile', 'onedown_admin_user_vip_fields');
add_action('edit_user_profile', 'onedown_admin_user_vip_fields');
function onedown_admin_user_vip_fields($user)
{
    if (!current_user_can('edit_users')) {
        return;
    }

    $vip_info   = onedown_get_user_vip_info($user->ID);
    $levels     = onedown_vip_levels();
    $vip_level  = get_user_meta($user->ID, 'onedown_vip_level', true);
    $vip_expire = get_user_meta($user->ID, 'onedown_vip_expire', true);
    $balance    = floatval(get_user_meta($user->ID, 'onedown_balance', true));
    ?>
    <h2 style="margin-top:30px;"><i class="fa fa-diamond"></i> 会员管理</h2>
    <table class="form-table">
        <tr>
            <th><label>当前会员等级</label></th>
            <td>
                <select name="onedown_vip_level" id="onedown_vip_level">
                    <option value="">-- 普通用户 --</option>
                    <?php foreach ($levels as $id => $level) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($vip_level, $id); ?>>
                            <?php echo esc_html($level['name']); ?>（<?php echo '￥' . number_format($level['price'], 2); ?>）
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">选择会员等级，留空表示普通用户</p>
            </td>
        </tr>
        <tr>
            <th><label for="onedown_vip_expire">会员有效期</label></th>
            <td>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <input type="text" name="onedown_vip_expire" id="onedown_vip_expire"
                        value="<?php echo esc_attr($vip_expire); ?>"
                        class="regular-text csf-datepicker" placeholder="2099-12-31 23:59:59"
                        style="width:200px;">
                    <label style="display:flex;align-items:center;gap:4px;font-weight:600;">
                        <input type="checkbox" id="onedown_vip_permanent" name="onedown_vip_permanent"
                            value="1" <?php checked($vip_level && (!$vip_expire || $vip_expire === '2099-12-31 23:59:59')); ?>>
                        永久有效（Permanent）
                    </label>
                </div>
                <p class="description">选择到期日期时间，或勾选"永久有效"。</p>
            </td>
        </tr>
        <tr>
            <th><label>当前余额</label></th>
            <td>
                <span style="font-size:16px;font-weight:600;color:#52c41a;">￥<?php echo number_format($balance, 2); ?></span>
            </td>
        </tr>
        <tr>
            <th><label for="onedown_balance_adjust">调整余额</label></th>
            <td>
                <input type="number" name="onedown_balance_adjust" id="onedown_balance_adjust"
                    step="0.01" value="" class="small-text" placeholder="0.00">
                <p class="description">正数表示增加余额，负数表示扣除余额。例如：输入 50 表示增加50元，输入 -30 表示扣除30元。</p>
            </td>
        </tr>
        <tr>
            <th><label for="onedown_balance_note">余额调整说明</label></th>
            <td>
                <input type="text" name="onedown_balance_note" id="onedown_balance_note"
                    value="" class="regular-text" placeholder="例如：手动充值、活动奖励、扣除费用等">
                <p class="description">调整余额时必填，用于记录操作原因</p>
            </td>
        </tr>
    </table>
    <?php
    // 防止CSRF
    wp_nonce_field('onedown_admin_user_vip_save', '_onedown_vip_nonce');
}

/**
 * 保存用户编辑页面的VIP会员设置
 */
add_action('edit_user_profile_update', 'onedown_admin_save_user_vip_fields');
add_action('personal_options_update', 'onedown_admin_save_user_vip_fields');
function onedown_admin_save_user_vip_fields($user_id)
{
    if (!current_user_can('edit_users')) {
        return;
    }

    // 验证 nonce
    if (!isset($_POST['_onedown_vip_nonce']) || !wp_verify_nonce($_POST['_onedown_vip_nonce'], 'onedown_admin_user_vip_save')) {
        return;
    }

    // ── 保存 VIP 等级 ──
    if (isset($_POST['onedown_vip_level'])) {
        $vip_level = sanitize_text_field($_POST['onedown_vip_level']);
        if (empty($vip_level)) {
            delete_user_meta($user_id, 'onedown_vip_level');
            delete_user_meta($user_id, 'onedown_vip_expire');
        } else {
            update_user_meta($user_id, 'onedown_vip_level', $vip_level);

            // 保存有效期：优先检查永久复选框，再检查输入框
            $is_permanent = isset($_POST['onedown_vip_permanent']) && $_POST['onedown_vip_permanent'] === '1';
            if ($is_permanent) {
                update_user_meta($user_id, 'onedown_vip_expire', '2099-12-31 23:59:59');
            } elseif (isset($_POST['onedown_vip_expire']) && !empty($_POST['onedown_vip_expire'])) {
                $expire = sanitize_text_field($_POST['onedown_vip_expire']);
                update_user_meta($user_id, 'onedown_vip_expire', $expire);
            } else {
                // 没有设置有效期，默认永久
                update_user_meta($user_id, 'onedown_vip_expire', '2099-12-31 23:59:59');
            }
        }
    }

    // ── 保存余额调整 ──
    if (isset($_POST['onedown_balance_adjust']) && $_POST['onedown_balance_adjust'] !== '' && $_POST['onedown_balance_adjust'] !== null) {
        $adjust_amount = floatval($_POST['onedown_balance_adjust']);
        $note          = isset($_POST['onedown_balance_note']) ? trim(sanitize_text_field($_POST['onedown_balance_note'])) : '';

        if ($adjust_amount != 0) {
            if (empty($note)) {
                // 没有说明则使用默认说明
                $note = $adjust_amount > 0 ? '管理员手动增加余额' : '管理员手动扣除余额';
            }

            $current_balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
            $new_balance     = $current_balance + $adjust_amount;

            // 防止余额为负数
            if ($new_balance < 0) {
                $new_balance = 0;
            }

            update_user_meta($user_id, 'onedown_balance', $new_balance);

            // 记录余额变动日志
            $log_entry = array(
                'time'     => current_time('mysql'),
                'amount'   => $adjust_amount,
                'balance'  => $new_balance,
                'note'     => $note,
                'operator' => get_current_user_id(),
            );

            $balance_log = get_user_meta($user_id, 'onedown_balance_log', true);
            if (!is_array($balance_log)) {
                $balance_log = array();
            }
            array_unshift($balance_log, $log_entry); // 最新的在最前面
            // 只保留最近100条记录
            $balance_log = array_slice($balance_log, 0, 100);
            update_user_meta($user_id, 'onedown_balance_log', $balance_log);
        }
    }
}

/**
 * 在用户编辑页面底部显示余额变动历史
 */
add_action('show_user_profile', 'onedown_admin_user_balance_log');
add_action('edit_user_profile', 'onedown_admin_user_balance_log');
function onedown_admin_user_balance_log($user)
{
    if (!current_user_can('edit_users')) {
        return;
    }

    $balance_log = get_user_meta($user->ID, 'onedown_balance_log', true);
    if (!is_array($balance_log) || empty($balance_log)) {
        return;
    }

    echo '<h3 style="margin-top:20px;"><i class="fa fa-google-wallet"></i> 余额变动记录</h3>';
    echo '<table class="widefat striped" style="max-width:600px;">';
    echo '<thead><tr>';
    echo '<th>时间</th><th>变动金额</th><th>余额</th><th>说明</th><th>操作人</th>';
    echo '</tr></thead><tbody>';

    foreach ($balance_log as $log) {
        $time     = isset($log['time']) ? esc_html($log['time']) : '-';
        $amount   = isset($log['amount']) ? floatval($log['amount']) : 0;
        $balance  = isset($log['balance']) ? floatval($log['balance']) : 0;
        $note     = isset($log['note']) ? esc_html($log['note']) : '';
        $operator = isset($log['operator']) ? intval($log['operator']) : 0;
        $op_name  = $operator ? get_userdata($operator) ? get_userdata($operator)->display_name : '#' . $operator : '-';

        $amount_color = $amount >= 0 ? '#52c41a' : '#ff4d4f';
        $amount_sign  = $amount >= 0 ? '+' : '';

        echo '<tr>';
        echo '<td>' . $time . '</td>';
        echo '<td style="color:' . $amount_color . ';font-weight:600;">' . $amount_sign . number_format($amount, 2) . '</td>';
        echo '<td>￥' . number_format($balance, 2) . '</td>';
        echo '<td>' . $note . '</td>';
        echo '<td>' . $op_name . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

// ═════════════════════════════════════════════════════
// 后台用户编辑 - 隐藏不需要的默认字段
// ═════════════════════════════════════════════════════

/**
 * 在用户编辑页面隐藏不需要的默认字段
 */
add_action('admin_head-user-edit.php', 'onedown_hide_user_profile_fields');
add_action('admin_head-profile.php', 'onedown_hide_user_profile_fields');
function onedown_hide_user_profile_fields()
{
    ?>
    <style>
        /* 隐藏管理区配色方案 */
        tr.user-admin-color-wrap,
        .user-admin-color-wrap {
            display: none !important;
        }
        /* 隐藏语法高亮 */
        tr.user-syntax-highlighting-wrap,
        .user-syntax-highlighting-wrap {
            display: none !important;
        }
        /* 隐藏键盘快捷键 */
        tr.user-comment-shortcuts-wrap,
        .user-comment-shortcuts-wrap {
            display: none !important;
        }
        /* 隐藏工具栏选择 */
        tr.show-admin-bar,
        .show-admin-bar {
            display: none !important;
        }
        /* 隐藏语言选择 */
        tr.user-language-wrap,
        .user-language-wrap {
            display: none !important;
        }
        /* 隐藏应用程序密码 */
        .application-passwords,
        #application-passwords-section,
        [id*="application-password"] {
            display: none !important;
        }
    </style>
    <?php
}

/**
 * 在用户编辑页面加载日期选择器
 */
add_action('admin_enqueue_scripts', 'onedown_admin_user_vip_enqueue');
function onedown_admin_user_vip_enqueue($hook)
{
    if (!in_array($hook, array('user-edit.php', 'profile.php'))) {
        return;
    }

    // 加载 jQuery UI Datepicker（csf 框架也使用它）
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-style', '//code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css', array(), '1.13.2');

    // 加载 Flatpickr（csf 框架的 datetime 字段使用）
    wp_enqueue_script('csf-plugins');
    wp_enqueue_style('csf');

    add_action('admin_footer', 'onedown_admin_user_vip_datepicker_js');
}

/**
 * 日期选择器 + 永久复选框交互 JS
 */
function onedown_admin_user_vip_datepicker_js()
{
    ?>
    <script>
    jQuery(function($) {
        var $dateInput = $('#onedown_vip_expire');
        var $permanent = $('#onedown_vip_permanent');

        // 初始化 jQuery UI Datepicker
        $dateInput.datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: '2020:2100',
            showButtonPanel: true,
            onSelect: function(dateText) {
                // 选择了日期后自动追加时间 23:59:59，取消勾选永久
                var timePart = ' 23:59:59';
                if ($dateInput.val().indexOf(' ') === -1) {
                    $dateInput.val(dateText + timePart);
                }
                $permanent.prop('checked', false);
            }
        });

        // 初始化 Flatpickr（支持日期和时间选择）
        if (typeof flatpickr !== 'undefined') {
            try {
                flatpickr($dateInput[0], {
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i:S',
                    time_24hr: true,
                    allowInput: true,
                    onChange: function(selectedDates, dateStr) {
                        if (selectedDates.length > 0) {
                            $permanent.prop('checked', false);
                        }
                    }
                });
            } catch(e) {
                // flatpickr 初始化失败时回退到 datepicker
            }
        }

        // 永久复选框交互
        $permanent.on('change', function() {
            if ($(this).is(':checked')) {
                $dateInput.val('2099-12-31 23:59:59').prop('readonly', true);
            } else {
                var currentVal = $dateInput.val();
                if (currentVal === '2099-12-31 23:59:59') {
                    $dateInput.val('');
                }
                $dateInput.prop('readonly', false);
            }
        });

        // 页面加载时触发一次
        if ($permanent.is(':checked')) {
            $dateInput.val('2099-12-31 23:59:59').prop('readonly', true);
        }
    });
    </script>
    <?php
}
