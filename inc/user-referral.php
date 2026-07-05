<?php

/**
 * Onedown 推广分成系统
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 获取推广链接
 *
 * @param int    $user_id 用户ID
 * @param string $url     目标URL（默认首页）
 * @return string
 */
function onedown_get_referral_link($user_id = 0, $url = '')
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return '';
    }

    $base_url = $url ?: home_url('/');
    return add_query_arg('ref', $user_id, $base_url);
}


/**
 * 推广跟踪 - 注册时绑定推荐人
 *
 * @param int $user_id 新注册用户ID
 */
add_action('user_register', 'onedown_referral_bind_referrer', 10, 1);
function onedown_referral_bind_referrer($user_id)
{
    $judgment = _pz('referral_judgment', 'all');

    // 仅推广链接模式不绑定注册
    if ($judgment === 'link') {
        return;
    }

    // 检查Cookie中是否有推荐人
    if (!empty($_COOKIE['onedown_ref'])) {
        $referrer_id = intval($_COOKIE['onedown_ref']);
        if ($referrer_id > 0 && $referrer_id !== $user_id) {
            // 检查推荐人是否存在
            $referrer = get_userdata($referrer_id);
            if ($referrer) {
                update_user_meta($user_id, 'onedown_referrer_id', $referrer_id);
                // 记录推广统计
                onedown_referral_update_stats($referrer_id, 'invites', 1);
            }
        }
        // 清除Cookie
        setcookie('onedown_ref', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
}

/**
 * 推广跟踪 - 通过ref参数访问时设置Cookie
 */
add_action('init', 'onedown_referral_track_visit');
function onedown_referral_track_visit()
{
    if (is_admin() || is_user_logged_in()) {
        return;
    }

    if (!empty($_GET['ref'])) {
        $referrer_id = intval($_GET['ref']);
        if ($referrer_id > 0) {
            $referrer = get_userdata($referrer_id);
            if ($referrer) {
                // 设置Cookie，有效期7天
                setcookie('onedown_ref', $referrer_id, time() + 7 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            }
        }
    }
}

/**
 * 计算推广佣金
 *
 * @param int    $buyer_id  购买者用户ID
 * @param string $order_id  订单号
 * @param string $plan_id   套餐ID
 * @param float  $amount    订单金额
 */
function onedown_referral_calc_commission($buyer_id, $order_id, $plan_id, $amount)
{
    $judgment = _pz('referral_judgment', 'all');

    // 查找推荐人
    $referrer_id = 0;

    if ($judgment === 'all' || $judgment === '') {
        // 先检查是否绑定了推荐人
        $referrer_id = intval(get_user_meta($buyer_id, 'onedown_referrer_id', true));
    }

    // 如果没有绑定关系，检查session/cookie
    if (!$referrer_id && ($judgment === 'link' || $judgment === '')) {
        if (!empty($_COOKIE['onedown_ref'])) {
            $referrer_id = intval($_COOKIE['onedown_ref']);
        }
    }

    // 不能自己推广自己
    if ($referrer_id === $buyer_id) {
        $referrer_id = 0;
    }

    if (!$referrer_id) {
        return;
    }

    // 检查推荐人存在
    $referrer = get_userdata($referrer_id);
    if (!$referrer) {
        return;
    }

    // 获取推荐人的佣金比例（基于推荐人自身的VIP等级）
    $commission_ratio = onedown_referral_get_commission_ratio($referrer_id);

    // 计算佣金
    $commission = round($amount * $commission_ratio / 100, 2);

    if ($commission <= 0) {
        return;
    }

    // 获取推荐人佣金记录
    $commissions = get_user_meta($referrer_id, 'onedown_referral_commissions', true);
    if (!is_array($commissions)) {
        $commissions = array();
    }

    // 记录推荐人绑定的用户（如果还没绑定）
    if ($judgment !== 'link') {
        $bound_users = get_user_meta($referrer_id, 'onedown_referral_users', true);
        if (!is_array($bound_users)) {
            $bound_users = array();
        }
        if (!in_array($buyer_id, $bound_users)) {
            $bound_users[] = $buyer_id;
            update_user_meta($referrer_id, 'onedown_referral_users', $bound_users);
        }
    }

    $commission_entry = array(
        'order_id'    => $order_id,
        'buyer_id'    => $buyer_id,
        'plan_id'     => $plan_id,
        'amount'      => $amount,
        'commission'  => $commission,
        'ratio'       => $commission_ratio,
        'status'      => 'pending', // pending | withdrawable | withdrawn
        'created_at'  => current_time('mysql'),
    );

    $commissions[] = $commission_entry;

    update_user_meta($referrer_id, 'onedown_referral_commissions', $commissions);

    // 更新推广统计
    onedown_referral_update_stats($referrer_id, 'earnings', $commission);
    onedown_referral_update_stats($referrer_id, 'orders', 1);

    // 触发同步到新佣金记录表
    do_action('onedown_referral_commission_saved', $referrer_id, $order_id, $commission_entry);
}

/**
 * 获取购买者的推荐人ID
 *
 * 用于新订单系统（pay-gateway.php），在创建订单时捕获推荐人
 *
 * @param int $user_id 当前购买者用户ID
 * @return int 推荐人ID，0表示无推荐人
 */
function onedown_get_referrer_id($user_id = 0)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return 0;
    }

    // 先检查是否绑定了推荐人
    $referrer_id = intval(get_user_meta($user_id, 'onedown_referrer_id', true));

    // 不能自己推荐自己
    if ($referrer_id > 0 && $referrer_id !== $user_id) {
        return $referrer_id;
    }

    // 如果开启推广链接模式，检查 cookie
    $judgment = _pz('referral_judgment', 'all');
    if ($judgment === 'link' || $judgment === '') {
        if (!empty($_COOKIE['onedown_ref'])) {
            $cookie_id = intval($_COOKIE['onedown_ref']);
            if ($cookie_id > 0 && $cookie_id !== $user_id) {
                return $cookie_id;
            }
        }
    }

    return 0;
}

/**
 * 获取推荐人的推广返佣比例
 *
 * @param int    $referrer_id 推荐人用户ID
 * @param string $order_type  订单类型（保留参数，后续可扩展）
 * @return float 百分比值，如 10 表示 10%
 */
function onedown_get_referral_rebate_ratio($referrer_id, $order_type = '')
{
    if (!_pz('referral_enabled', false)) {
        return 0;
    }
    return onedown_referral_get_commission_ratio($referrer_id);
}

/**
 * 为推荐人添加佣金记录（新订单系统使用）
 *
 * @param int    $referrer_id 推荐人ID
 * @param string $order_id    订单号
 * @param float  $commission  佣金金额
 * @param string $order_type  订单类型
 */
function onedown_referral_add_commission($referrer_id, $order_id, $commission, $order_type)
{
    if (!_pz('referral_enabled', false)) {
        return;
    }

    if ($referrer_id <= 0 || $commission <= 0) {
        return;
    }

    $order = onedown_get_order($order_id);
    if (!$order) {
        return;
    }

    // 获取推荐人佣金记录
    $commissions = get_user_meta($referrer_id, 'onedown_referral_commissions', true);
    if (!is_array($commissions)) {
        $commissions = array();
    }

    $commissions[] = array(
        'order_id'    => $order_id,
        'buyer_id'    => intval($order->user_id),
        'plan_id'     => '',
        'amount'      => floatval($order->order_price),
        'commission'  => floatval($commission),
        'ratio'       => onedown_referral_get_commission_ratio($referrer_id),
        'status'      => 'pending',
        'order_type'  => $order_type,
        'created_at'  => current_time('mysql'),
    );

    update_user_meta($referrer_id, 'onedown_referral_commissions', $commissions);

    // 更新推广统计
    onedown_referral_update_stats($referrer_id, 'earnings', $commission);
    onedown_referral_update_stats($referrer_id, 'orders', 1);

    // 触发同步到新佣金记录表
    do_action('onedown_referral_commission_saved', $referrer_id, $order_id, array(
        'buyer_id'   => intval($order->user_id),
        'amount'     => floatval($order->order_price),
        'commission' => floatval($commission),
        'ratio'      => onedown_referral_get_commission_ratio($referrer_id),
        'order_type' => $order_type,
        'status'     => 'pending',
    ));
}

/**
 * 获取用户的推广佣金比例（基于该用户自身的VIP等级）
 *
 * @param int $user_id
 * @return float
 */
function onedown_referral_get_commission_ratio($user_id)
{
    $vip_info = onedown_get_user_vip_info($user_id);
    $levels = onedown_vip_levels();

    if ($vip_info['is_vip'] && !empty($vip_info['level_id'])) {
        $plan_id = $vip_info['level_id'];
        if (isset($levels[$plan_id]) && $levels[$plan_id]['commission_ratio'] > 0) {
            return floatval($levels[$plan_id]['commission_ratio']);
        }
    }

    // 普通用户默认分成比例
    return floatval(_pz('referral_default_ratio', 5));
}

/**
 * 获取用户推广统计
 *
 * @param int    $user_id
 * @param string $key 留空返回全部
 * @return array|int
 */
function onedown_referral_get_stats($user_id, $key = '')
{
    $defaults = array(
        'invites'   => 0,  // 邀请人数
        'orders'    => 0,  // 成交订单数
        'earnings'  => 0,  // 总收益
        'withdrawn' => 0,  // 已提现
    );

    $stats = get_user_meta($user_id, 'onedown_referral_stats', true);
    if (!is_array($stats)) {
        $stats = $defaults;
    } else {
        $stats = wp_parse_args($stats, $defaults);
    }

    if ($key) {
        return isset($stats[$key]) ? $stats[$key] : 0;
    }

    return $stats;
}

/**
 * 更新推广统计
 *
 * @param int    $user_id
 * @param string $key
 * @param int|float $value
 */
function onedown_referral_update_stats($user_id, $key, $value)
{
    $stats = onedown_referral_get_stats($user_id);
    if (!isset($stats[$key])) {
        return;
    }
    $stats[$key] += $value;
    update_user_meta($user_id, 'onedown_referral_stats', $stats);
}

/**
 * 结算指定用户的待结算佣金
 * 将所有待结算佣金立即标记为可提现
 *
 * @param int $user_id 用户ID
 * @return int 结算的佣金数量
 */
function onedown_referral_settle_user_commissions($user_id)
{
    if (!_pz('referral_enabled', false)) {
        return 0;
    }

    $commissions = get_user_meta($user_id, 'onedown_referral_commissions', true);
    if (!is_array($commissions)) {
        return 0;
    }

    $withdraw_enabled = _pz('referral_withdraw_enabled', true);
    $settled = 0;
    $changed = false;
    $auto_balance_total = 0;

    foreach ($commissions as $k => $c) {
        if ($c['status'] === 'pending') {
            if ($withdraw_enabled) {
                // 提现开启 → 标记为可提现
                $commissions[$k]['status'] = 'withdrawable';
            } else {
                // 提现关闭 → 直接转入余额
                $commissions[$k]['status'] = 'withdrawn';
                $auto_balance_total += $c['commission'];
            }
            $settled++;
            $changed = true;
        }
    }

    // 提现关闭时，将佣金自动转入余额
    if ($auto_balance_total > 0) {
        $balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
        $balance += $auto_balance_total;
        update_user_meta($user_id, 'onedown_balance', $balance);
    }

    if ($changed) {
        update_user_meta($user_id, 'onedown_referral_commissions', $commissions);
    }

    return $settled;
}

/**
 * 获取用户的推广佣金列表
 *
 * @param int $user_id
 * @return array
 */
function onedown_referral_get_commissions($user_id)
{
    $commissions = get_user_meta($user_id, 'onedown_referral_commissions', true);
    if (!is_array($commissions)) {
        return array();
    }
    // 按时间倒序
    krsort($commissions);
    return $commissions;
}

/**
 * 获取用户可提现金额
 *
 * @param int $user_id
 * @return float
 */
function onedown_referral_get_withdrawable($user_id)
{
    $commissions = onedown_referral_get_commissions($user_id);
    $total = 0;

    foreach ($commissions as $c) {
        if ($c['status'] === 'withdrawable') {
            $total += $c['commission'];
        }
    }

    return round($total, 2);
}

/**
 * 获取待结算金额
 *
 * @param int $user_id
 * @return float
 */
function onedown_referral_get_pending_amount($user_id)
{
    $commissions = onedown_referral_get_commissions($user_id);
    $total = 0;

    foreach ($commissions as $c) {
        if ($c['status'] === 'pending') {
            $total += $c['commission'];
        }
    }

    return round($total, 2);
}

/**
 * 获取提现记录
 *
 * @param int $user_id
 * @return array
 */
function onedown_referral_get_withdrawals($user_id)
{
    $withdrawals = get_user_meta($user_id, 'onedown_referral_withdrawals', true);
    if (!is_array($withdrawals)) {
        return array();
    }
    krsort($withdrawals);
    return $withdrawals;
}

/**
 * 提交提现申请
 *
 * @param int    $user_id
 * @param float  $amount
 * @param string $account 提现账号
 * @param string $note    备注
 * @return array{success: bool, msg: string}
 */
function onedown_referral_submit_withdraw($user_id, $amount, $account, $note = '')
{
    // 检查提现功能是否开启
    if (!_pz('referral_withdraw_enabled', true)) {
        return array('success' => false, 'msg' => '提现功能已关闭，佣金将自动转入余额');
    }

    $min_withdraw = floatval(_pz('referral_withdraw_min', 50));
    $fee_rate = floatval(_pz('referral_withdraw_fee', 0));

    // 验证金额
    if ($amount <= 0) {
        return array('success' => false, 'msg' => '请输入有效的提现金额');
    }

    if ($amount < $min_withdraw) {
        return array('success' => false, 'msg' => '最低提现金额为 ' . $min_withdraw . ' 元');
    }

    // 检查可提现金额
    $withdrawable = onedown_referral_get_withdrawable($user_id);
    if ($amount > $withdrawable) {
        return array('success' => false, 'msg' => '可提现金额不足，当前可提现 ' . $withdrawable . ' 元');
    }

    if (empty($account)) {
        return array('success' => false, 'msg' => '请输入提现账号');
    }

    // 计算手续费
    $fee = round($amount * $fee_rate / 100, 2);
    $actual = round($amount - $fee, 2);

    // 标记佣金为已提现
    $commissions = get_user_meta($user_id, 'onedown_referral_commissions', true);
    $remaining = $amount;

    if (is_array($commissions)) {
        $new_commissions = array();
        foreach ($commissions as $k => $c) {
            if ($remaining <= 0) {
                $new_commissions[] = $c;
                continue;
            }
            if ($c['status'] === 'withdrawable') {
                $commission_val = floatval($c['commission']);
                if ($commission_val <= $remaining) {
                    // 整笔佣金全部提现
                    $c['status'] = 'withdrawn';
                    $remaining = round($remaining - $commission_val, 2);
                    $new_commissions[] = $c;
                } else {
                    // 部分提现：拆分记录
                    $withdrawn_part = $c;
                    $withdrawn_part['commission'] = $remaining;
                    $withdrawn_part['status'] = 'withdrawn';

                    $remain_part = $c;
                    $remain_part['commission'] = round($commission_val - $remaining, 2);
                    $remain_part['status'] = 'withdrawable';

                    $new_commissions[] = $withdrawn_part;
                    $new_commissions[] = $remain_part;
                    $remaining = 0;
                }
            } else {
                $new_commissions[] = $c;
            }
        }
        update_user_meta($user_id, 'onedown_referral_commissions', $new_commissions);
    }

    // 记录提现
    $withdrawals = get_user_meta($user_id, 'onedown_referral_withdrawals', true);
    if (!is_array($withdrawals)) {
        $withdrawals = array();
    }

    $withdrawal_id = 'WD' . date('YmdHis') . strtoupper(wp_generate_password(6, false));
    $withdrawals[$withdrawal_id] = array(
        'withdrawal_id' => $withdrawal_id,
        'user_id'       => $user_id,
        'amount'        => $amount,
        'fee'           => $fee,
        'actual'        => $actual,
        'account'       => sanitize_text_field($account),
        'note'          => sanitize_textarea_field($note),
        'status'        => 'pending', // pending | approved | rejected
        'created_at'    => current_time('mysql'),
        'processed_at'  => '',
    );

    update_user_meta($user_id, 'onedown_referral_withdrawals', $withdrawals);

    // 更新已提现统计
    onedown_referral_update_stats($user_id, 'withdrawn', $amount);

    return array('success' => true, 'msg' => '提现申请已提交，请等待管理员审核');
}

/**
 * 管理员审核提现
 *
 * @param string $withdrawal_id 提现记录ID
 * @param string $status approved|rejected
 * @param string $reject_reason 拒绝原因（仅在拒绝时有效）
 * @return bool
 */
function onedown_referral_process_withdraw($withdrawal_id, $status, $reject_reason = '')
{
    $args = array(
        'meta_key'   => 'onedown_referral_withdrawals',
        'meta_compare' => 'EXISTS',
    );
    $users = get_users($args);

    foreach ($users as $user) {
        $withdrawals = get_user_meta($user->ID, 'onedown_referral_withdrawals', true);
        if (isset($withdrawals[$withdrawal_id]) && $withdrawals[$withdrawal_id]['status'] === 'pending') {
            $withdrawals[$withdrawal_id]['status'] = $status;
            $withdrawals[$withdrawal_id]['processed_at'] = current_time('mysql');

            // 拒绝时记录原因并退回佣金
            if ($status === 'rejected') {
                $withdrawals[$withdrawal_id]['reject_reason'] = sanitize_text_field($reject_reason);

                $amount = $withdrawals[$withdrawal_id]['amount'];
                $commissions = get_user_meta($user->ID, 'onedown_referral_commissions', true);
                if (is_array($commissions)) {
                    $remaining = $amount;
                    // 反向遍历找到最近被标记为withdrawn的记录
                    for ($k = count($commissions) - 1; $k >= 0; $k--) {
                        if ($remaining <= 0) break;
                        if ($commissions[$k]['status'] === 'withdrawn') {
                            $commissions[$k]['status'] = 'withdrawable';
                            $remaining -= $commissions[$k]['commission'];
                        }
                    }
                    update_user_meta($user->ID, 'onedown_referral_commissions', $commissions);
                }
                // 更新统计
                onedown_referral_update_stats($user->ID, 'withdrawn', -$amount);
            }

            update_user_meta($user->ID, 'onedown_referral_withdrawals', $withdrawals);
            return true;
        }
    }

    return false;
}

/**
 * 获取佣金状态标签
 */
function onedown_referral_commission_status_label($status)
{
    $labels = array(
        'pending'      => '待结算',
        'withdrawable' => '可提现',
        'withdrawn'    => '已提现',
    );
    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * 获取提现状态标签
 */
function onedown_referral_withdraw_status_label($status)
{
    $labels = array(
        'pending'  => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
    );
    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * 获取推广绑定用户列表
 *
 * @param int $user_id
 * @return array
 */
function onedown_referral_get_bound_users($user_id)
{
    $bound = get_user_meta($user_id, 'onedown_referral_users', true);
    if (!is_array($bound)) {
        return array();
    }
    return $bound;
}

/**
 * 获取推广中心页面HTML内容
 *
 * @param int $user_id
 * @return string
 */
function onedown_referral_page_content($user_id)
{
    // 自动结算该用户已过期的待结算佣金
    onedown_referral_settle_user_commissions($user_id);

    $referral_link = onedown_get_referral_link($user_id);
    $stats = onedown_referral_get_stats($user_id);
    $commissions = onedown_referral_get_commissions($user_id);
    $withdrawals = onedown_referral_get_withdrawals($user_id);
    $withdrawable = onedown_referral_get_withdrawable($user_id);
    $pending_amount = onedown_referral_get_pending_amount($user_id);
    $min_withdraw = floatval(_pz('referral_withdraw_min', 50));
    $referral_desc = _pz('referral_desc', '复制您的专属推广链接分享给好友，好友通过您的链接注册并购买会员，您将获得订单金额一定比例的佣金！');

    $levels = onedown_vip_levels();
    $vip_info = onedown_get_user_vip_info($user_id);
    $user_ratio = onedown_referral_get_commission_ratio($user_id);

    // 费率表格数据
    $ratio_rows = '';
    foreach ($levels as $level) {
        $ratio = floatval($level['commission_ratio']);
        if ($ratio > 0) {
            $ratio_rows .= '<tr><td>' . esc_html($level['name']) . '</td><td>' . $ratio . '%</td></tr>';
        }
    }
    if ($ratio_rows) {
        $ratio_rows = '<tr><th>您的等级</th><th>分成比例</th></tr>' . $ratio_rows;
    }

    ob_start();
    $page_url = onedown_user_center_url();
?>
<!-- 推广 Hero -->
<div class="referral-hero-container">
    <div class="referral-hero">
        <div class="referral-hero-icon"><i class="fa fa-share-alt"></i></div>
        <div class="referral-hero-text">
            <h3>推广中心</h3>
            <p><?php echo esc_html($referral_desc); ?></p>
            <div class="referral-link-box">
                <input type="text" id="referralLinkInput" value="<?php echo esc_url($referral_link); ?>" readonly
                    onclick="this.select();">
                <button type="button" data-referral-copy class="referral-copy-btn"><i class="fa fa-copy"></i>
                    复制链接</button>
            </div>
            <p class="muted-color" style="font-size:12px;margin:8px 0 0;">
                <i class="fa fa-info-circle"></i> 您的推广分成比例：<strong><?php echo esc_html($user_ratio); ?>%</strong>
            </p>
        </div>
    </div>


    <!-- 数据统计 -->
    <div class="referral-stats-grid">
        <article>
            <span class="referral-stat-icon stat-invites"><i class="fa fa-users"></i></span>
            <strong><?php echo intval($stats['invites']); ?></strong>
            <p>邀请人数</p>
        </article>
        <article>
            <span class="referral-stat-icon stat-orders"><i class="fa fa-shopping-cart"></i></span>
            <strong><?php echo intval($stats['orders']); ?></strong>
            <p>成交订单</p>
        </article>
        <article>
            <span class="referral-stat-icon stat-earnings"><i class="fa fa-rmb"></i></span>
            <strong>￥<?php echo number_format($stats['earnings'], 2); ?></strong>
            <p>累计收益</p>
        </article>
        <article>
            <span class="referral-stat-icon stat-withdraw"><i class="fa fa-credit-card"></i></span>
            <strong>￥<?php echo number_format($withdrawable, 2); ?></strong>
            <p><?php echo _pz('referral_withdraw_enabled', true) ? '可提现' : '可转余额'; ?></p>
        </article>
    </div>

    <!-- 分成比例表 -->
    <div class="section-card" style="margin-top:18px;">
        <div class="section-head">
            <h3 class="section-title"><i class="fa fa-percent"></i> 各会员等级分成比例</h3>
        </div>
        <div style="padding:4px 16px 16px;">
            <table class="referral-ratio-table">
                <?php echo $ratio_rows; ?>
                <?php if (empty($ratio_rows)) : ?>
                <tr>
                    <td style="text-align:center;color:var(--od-muted);">暂无配置</td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- 提现申请 -->
    <div class="section-card" style="margin-top:18px;">
        <div class="section-head">
            <h3 class="section-title"><i class="fa fa-credit-card"></i> <?php echo _pz('referral_withdraw_enabled', true) ? '申请提现' : '佣金结算'; ?></h3>
        </div>
        <div style="padding:0 16px 16px;">
            <?php if (_pz('referral_withdraw_enabled', true)) : ?>
            <p class="muted-color" style="font-size:13px;margin:12px 0 16px;">
                可提现金额：<strong>￥<?php echo number_format($withdrawable, 2); ?></strong>，
                最低提现：<strong>￥<?php echo number_format($min_withdraw, 2); ?></strong>
                <?php if (_pz('referral_withdraw_fee', 0) > 0) : ?>
                ，手续费：<strong><?php echo floatval(_pz('referral_withdraw_fee', 0)); ?>%</strong>
                <?php endif; ?>
            </p>

            <?php if ($withdrawable >= $min_withdraw) : ?>
            <form class="account-form" id="referralWithdrawForm" method="post" style="max-width:100%;">
                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;grid-column:1/-1;">
                    <div class="form-group">
                        <label>提现金额（元）</label>
                        <input type="number" name="amount" id="withdrawAmount" min="<?php echo $min_withdraw; ?>"
                            max="<?php echo $withdrawable; ?>" step="0.01" placeholder="请输入提现金额" required>
                    </div>
                    <div class="form-group">
                        <label>提现账号</label>
                        <input type="text" name="account" placeholder="支付宝/微信账号" required>
                    </div>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>备注说明</label>
                    <textarea name="note" rows="2" placeholder="可选：备注说明"></textarea>
                </div>
                <input type="hidden" name="action" value="onedown_referral_withdraw">
                <?php wp_nonce_field('onedown_referral_action', '_wpnonce'); ?>
                <div class="account-actions" style="grid-column:1/-1;">
                    <button type="submit"><i class="fa fa-send"></i> 提交提现申请</button>
                    <a href="<?php echo esc_url($page_url); ?>"><i class="fa fa-arrow-left"></i> 返回概览</a>
                </div>
            </form>
            <?php else : ?>
            <div style="padding:32px 0;text-align:center;color:var(--od-muted);">
                <i class="fa fa-info-circle" style="font-size:28px;display:block;margin-bottom:10px;opacity:.6;"></i>
                <p style="margin:0;font-size:14px;">当前可提现金额不足 <?php echo number_format($min_withdraw, 2); ?> 元，无法提现</p>
            </div>
            <?php endif; ?>
            <?php else : ?>
            <div style="padding:24px 0;text-align:center;color:var(--od-muted);">
                <i class="fa fa-exchange" style="font-size:28px;display:block;margin-bottom:10px;opacity:.6;"></i>
                <p style="margin:0;font-size:14px;">提现功能已关闭，佣金结算时自动转入 <strong style="color:var(--od-primary);">账户余额</strong></p>
                <p style="margin:6px 0 0;font-size:12px;">当前佣金累计收益：<strong style="color:#252c3a;">￥<?php echo number_format($stats['earnings'], 2); ?></strong></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 佣金明细 -->
    <div class="section-card" style="margin-top:18px;">
        <div class="section-head">
            <h3 class="section-title"><i class="fa fa-list-alt"></i> 佣金明细</h3>
        </div>
        <div style="padding:4px 0;">
            <?php if (!empty($commissions)) : ?>
            <div class="referral-commission-list">
                <?php foreach (array_slice($commissions, 0, 20) as $c) : ?>
                <div class="commission-item">
                    <span class="commission-icon"><i class="fa fa-rmb"></i></span>
                    <div class="commission-info">
                        <strong>+￥<?php echo number_format($c['commission'], 2); ?></strong>
                        <span>订单 <?php echo esc_html($c['order_id']); ?> · 比例
                            <?php echo floatval($c['ratio']); ?>%</span>
                        <span class="muted-color"
                            style="font-size:12px;"><?php echo esc_html($c['created_at']); ?></span>
                    </div>
                    <span
                        class="commission-status status-<?php echo esc_attr($c['status']); ?>"><?php echo esc_html(onedown_referral_commission_status_label($c['status'])); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div style="padding:32px;text-align:center;color:var(--od-muted);">
                <i class="fa fa-inbox" style="font-size:32px;display:block;margin-bottom:12px;"></i>
                <p style="margin:0;">暂无佣金记录</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 提现记录 -->
    <?php if (!empty($withdrawals)) : ?>
    <div class="section-card" style="margin-top:18px;">
        <div class="section-head">
            <h3 class="section-title"><i class="fa fa-history"></i> 提现记录</h3>
        </div>
        <div style="padding:4px 0;">
            <div class="referral-commission-list">
                <?php foreach (array_slice($withdrawals, 0, 10) as $w) : ?>
                <div class="commission-item">
                    <span class="commission-icon"><i class="fa fa-credit-card"></i></span>
                    <div class="commission-info">
                        <strong>￥<?php echo number_format($w['amount'], 2); ?></strong>
                        <span>
                            <?php if ($w['fee'] > 0) : ?>手续费：￥<?php echo number_format($w['fee'], 2); ?> ·
                            <?php endif; ?>
                            实到：￥<?php echo number_format($w['actual'], 2); ?>
                        </span>
                        <span class="commission-time"><?php echo esc_html($w['created_at']); ?></span>
                    </div>
                    <div class="commission-status-group">
                        <span
                            class="commission-status status-<?php echo esc_attr($w['status']); ?>"><?php echo esc_html(onedown_referral_withdraw_status_label($w['status'])); ?></span>
                        <?php if ($w['status'] === 'rejected' && !empty($w['reject_reason'])) : ?>
                        <span class="commission-reject-reason">拒绝原因：<?php echo esc_html($w['reject_reason']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// 提现表单AJAX提交
(function() {
    var form = document.getElementById('referralWithdrawForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 提交中...';

        var fd = new FormData(form);
        var nonce = window.onedownData ? onedownData.referralNonce : '';

        fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: fd
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success) {
                    // 显示成功消息并刷新页面
                    alert(data.data.msg);
                    window.location.reload();
                } else {
                    alert(data.data && data.data.msg ? data.data.msg : '提交失败');
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }
            })
            .catch(function() {
                alert('网络错误，请重试');
                btn.disabled = false;
                btn.innerHTML = orig;
            });
    });
})();
</script>
<?php
    return ob_get_clean();
}