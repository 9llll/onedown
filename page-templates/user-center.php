<?php

/**
 * Template Name: 用户中心
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

// 获取当前标签页
$tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
$order_page = isset($_GET['order_page']) ? max(1, intval($_GET['order_page'])) : 1;
$page_url = onedown_user_center_url();

// 未登录不显示内容
if (! is_user_logged_in()) :
?>
    <main>
        <section class="content-shell" style="margin-top:22px;">
            <div class="main-column">
                <div class="section-card" style="padding:80px 40px;text-align:center;">
                    <i class="fa fa-user" style="font-size:80px;display:block;margin:0 auto 20px;color:var(--od-muted);"></i>
                    <h2 style="margin:0 0 12px;color:#252c3a;font-weight:800;">请先登录</h2>
                    <p style="margin:0 0 24px;color:var(--od-muted);">登录后可查看用户中心</p>
                    <a class="pill-btn" href="javascript:;" data-sign-modal="signin"><i
                            class="fa fa-sign-in"></i> 立即登录</a>
                </div>
            </div>
        </section>
    </main>
<?php
    get_footer();
    return;
endif;

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$user_name    = $current_user->display_name;
$user_email   = $current_user->user_email;
$user_reg     = $current_user->user_registered;
$user_bio     = get_user_meta($user_id, 'description', true);
$user_initial = strtoupper(substr($user_name ?: 'U', 0, 1));
$user_avatar  = get_avatar_url($user_id, array('size' => 86));

// 统计
$post_count    = count_user_posts($user_id);
$comment_count = get_comments(array('user_id' => $user_id, 'count' => true));
$fav_count     = onedown_get_favorites_count($user_id);
$order_count   = onedown_get_user_orders_count_db($user_id);
$download_count = onedown_get_download_count($user_id);
$vip_info      = onedown_get_user_vip_info($user_id);

// 成功/错误消息
$profile_message = '';
if (isset($_GET['profile_updated']) && '1' === $_GET['profile_updated']) {
    $profile_message = '<div class="vip-toast is-show" style="position:static;opacity:1;transform:none;pointer-events:auto;margin-bottom:16px;justify-content:center;"><i class="fa fa-check-circle"></i><span>资料已更新</span></div>';
}
$profile_error = '';
if (isset($_GET['profile_error'])) {
    $profile_error = '<div class="vip-toast is-show toast-error" style="position:static;opacity:1;transform:none;pointer-events:auto;margin-bottom:16px;justify-content:center;background:var(--od-red);"><i class="fa fa-exclamation-circle"></i><span>' . esc_html($_GET['profile_error']) . '</span></div>';
}

?>
<style>
    /* ===== 用户中心样式优化 ===== */

    /* 用户 Hero */
    .uc-hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 22px;
        padding: 30px 32px;
        margin: 22px auto 0;
        width: min(1200px, calc(100% - 32px));
        background: linear-gradient(135deg, #1a1f2e 0%, #2d3352 100%);
        border-radius: 16px;
        color: #fff;
        overflow: hidden;
    }

    .uc-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 20% 50%, rgba(79, 124, 255, .2) 0%, transparent 60%),
            radial-gradient(ellipse at 80% 50%, rgba(var(--od-primary-rgb), .15) 0%, transparent 60%);
        pointer-events: none;
    }

    .uc-hero-inner {
        position: relative;
        display: flex;
        align-items: center;
        gap: 20px;
        min-width: 0;
        z-index: 1;
    }

    .uc-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, .3);
        flex-shrink: 0;
        object-fit: cover;
        background: var(--od-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
    }

    .uc-hero-info h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        line-height: 1.3;
    }

    .uc-hero-info p {
        margin: 6px 0 0;
        color: rgba(255, 255, 255, .65);
        font-size: 13px;
    }

    .uc-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 10px;
        padding: 4px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .12);
        font-size: 12px;
        font-weight: 700;
        color: rgba(255, 255, 255, .85);
    }

    .uc-hero-badge i {
        font-size: 11px;
        color: #ffd16f;
    }

    .uc-hero-actions {
        position: relative;
        z-index: 1;
        display: flex;
        gap: 10px;
        flex-shrink: 0;
    }

    .uc-hero-actions a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 18px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
        transition: all .2s;
    }

    .uc-btn-primary {
        background: var(--od-gradient);
        color: #fff;
    }

    .uc-btn-ghost {
        border: 1px solid rgba(255, 255, 255, .3);
        color: #fff;
        background: rgba(255, 255, 255, .1);
    }

    .uc-btn-ghost:hover {
        background: rgba(255, 255, 255, .2);
    }

    /* 数据统计卡片 */
    .uc-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        width: min(1200px, calc(100% - 32px));
        margin: 16px auto 0;
    }

    .uc-stat-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 20px;
        background: var(--od-card);
        border: 1px solid var(--od-line);
        border-radius: 12px;
        box-shadow: var(--od-shadow);
    }

    .uc-stat-icon {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        color: #fff;
        font-size: 18px;
        flex-shrink: 0;
    }

    .uc-stat-body strong {
        display: block;
        color: #252c3a;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.2;
    }

    .uc-stat-body p {
        margin: 4px 0 0;
        color: var(--od-muted);
        font-size: 12px;
        font-weight: 700;
    }

    /* 主体布局 */
    .uc-main-layout {
        display: grid;
        grid-template-columns: 220px minmax(0, 1fr);
        gap: 18px;
        width: min(1200px, calc(100% - 32px));
        margin: 18px auto 22px;
    }

    /* 侧边栏 */
    .uc-sidebar {
        padding: 10px;
        background: var(--od-card);
        border: 1px solid var(--od-line);
        border-radius: 12px;
        box-shadow: var(--od-shadow);
        align-self: start;
    }

    .uc-sidebar a {
        display: flex;
        align-items: center;
        gap: 10px;
        height: 42px;
        padding: 0 14px;
        margin-bottom: 2px;
        border-radius: 10px;
        color: #596170;
        font-weight: 800;
        font-size: 14px;
        text-decoration: none;
        transition: all .2s;
    }

    .uc-sidebar a i {
        width: 16px;
        text-align: center;
        color: #9aa1ad;
        font-size: 14px;
    }

    .uc-sidebar a:hover,
    .uc-sidebar a.active {
        color: var(--od-primary);
        background: rgba(var(--od-primary-rgb), .08);
    }

    .uc-sidebar a:hover i,
    .uc-sidebar a.active i {
        color: var(--od-primary);
    }

    .uc-sidebar-logout {
        margin-top: 10px !important;
        padding-top: 10px !important;
        border-top: 1px solid var(--od-line);
        color: var(--od-muted) !important;
        font-weight: 700 !important;
    }

    .uc-sidebar-logout i {
        color: var(--od-muted) !important;
    }

    /* 主体内容 */
    .uc-content {
        min-width: 0;
    }

    /* 响应式 */
    @media (max-width: 900px) {
        .uc-hero {
            flex-direction: column;
            align-items: flex-start;
            padding: 24px;
        }

        .uc-hero-actions {
            width: 100%;
        }

        .uc-hero-actions a {
            flex: 1;
            justify-content: center;
        }

        .uc-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .uc-main-layout {
            grid-template-columns: 1fr;
        }

        /* 移动端侧边栏改为水平可滚动标签栏 */
        .uc-sidebar {
            display: flex;
            flex-direction: row;
            overflow-x: auto;
            flex-wrap: nowrap;
            gap: 4px;
            padding: 8px 10px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .uc-sidebar::-webkit-scrollbar {
            display: none;
        }
        .uc-sidebar a {
            flex-shrink: 0;
            height: auto;
            padding: 8px 12px;
            margin-bottom: 0;
            white-space: nowrap;
            font-size: 13px;
        }
        .uc-sidebar a i {
            margin-right: 4px;
        }
        .uc-sidebar-logout {
            margin-top: 0 !important;
            padding-top: 8px !important;
            border-top: 0;
            color: #596170 !important;
            font-weight: 800 !important;
        }
        .uc-sidebar-logout i {
            color: #9aa1ad !important;
        }
    }

    @media (max-width: 600px) {
        .uc-hero-inner {
            flex-direction: column;
            text-align: center;
            width: 100%;
        }

        .uc-stats {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .uc-stats .uc-stat-card {
            padding: 14px;
        }

        /* 移动端更紧凑 */
        .uc-sidebar {
            padding: 6px 8px;
            gap: 3px;
        }
        .uc-sidebar a {
            padding: 6px 10px;
            font-size: 12px;
        }
    }
</style>

<main>
    <!-- 面包屑 -->
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑" style="width:min(1200px,calc(100% - 32px));margin:18px auto 0;">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <?php if ('dashboard' !== $tab) : ?>
            <a href="<?php echo esc_url($page_url); ?>">用户中心</a>
            <i class="fa fa-angle-right"></i>
        <?php endif; ?>
        <span><?php echo onedown_get_tab_title($tab); ?></span>
    </nav>

    <!-- 用户 Hero -->
    <section class="uc-hero">
        <div class="uc-hero-inner">
            <div>
                <?php if (!empty($user_avatar)) : ?>
                    <img class="uc-avatar" src="<?php echo esc_url($user_avatar); ?>"
                        alt="<?php echo esc_attr($user_name); ?>"
                        onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <span class="uc-avatar" style="display:none;"><i class="fa fa-user"></i></span>
                <?php else : ?>
                    <span class="uc-avatar"><i class="fa fa-user"></i></span>
                <?php endif; ?>
            </div>
            <div class="uc-hero-info">
                <h1><?php echo esc_html($user_name); ?></h1>
                <p><?php echo esc_html($user_email); ?> · 注册于 <?php echo esc_html(date_i18n('Y-m-d', strtotime($user_reg))); ?></p>
                <span class="uc-hero-badge"><i class="fa fa-diamond"></i> <?php echo esc_html($vip_info['vip_name']); ?></span>
            </div>
        </div>
        <div class="uc-hero-actions">
            <?php if (!$vip_info['is_vip']) : ?>
                <a class="uc-btn-primary" href="<?php echo esc_url(add_query_arg('tab', 'vip', $page_url)); ?>" data-vip-modal><i class="fa fa-diamond"></i> 开通会员</a>
            <?php else : ?>
                <a class="uc-btn-primary" href="<?php echo esc_url(add_query_arg('tab', 'vip', $page_url)); ?>"><i class="fa fa-diamond"></i> 会员中心</a>
            <?php endif; ?>
            <a class="uc-btn-ghost" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><i class="fa fa-sign-out"></i> 退出</a>
        </div>
    </section>

    <!-- 数据统计 -->
    <section class="uc-stats" aria-label="账户概览">
        <a class="uc-stat-card" href="<?php echo esc_url(add_query_arg('tab', 'orders', $page_url)); ?>">
            <span class="uc-stat-icon" style="background:linear-gradient(135deg,#4f7cff,#18c7d5);"><i class="fa fa-list-alt"></i></span>
            <div class="uc-stat-body">
                <strong><?php echo esc_html($order_count); ?></strong>
                <p>我的订单</p>
            </div>
        </a>
        <a class="uc-stat-card" href="<?php echo esc_url(add_query_arg('tab', 'downloads', $page_url)); ?>">
            <span class="uc-stat-icon" style="background:var(--od-gradient);"><i class="fa fa-download"></i></span>
            <div class="uc-stat-body">
                <strong><?php echo esc_html($download_count); ?></strong>
                <p>下载记录</p>
            </div>
        </a>
        <a class="uc-stat-card" href="<?php echo esc_url(add_query_arg('tab', 'favorites', $page_url)); ?>">
            <span class="uc-stat-icon" style="background:linear-gradient(135deg,#ffb347,#ff8a65);"><i class="fa fa-star-o"></i></span>
            <div class="uc-stat-body">
                <strong><?php echo esc_html($fav_count); ?></strong>
                <p>我的收藏</p>
            </div>
        </a>
        <a class="uc-stat-card" href="<?php echo esc_url(add_query_arg('tab', 'comments', $page_url)); ?>">
            <span class="uc-stat-icon" style="background:linear-gradient(135deg,#65dba3,#18c7d5);"><i class="fa fa-comments-o"></i></span>
            <div class="uc-stat-body">
                <strong><?php echo esc_html($comment_count); ?></strong>
                <p>我的评论</p>
            </div>
        </a>
    </section>

    <!-- 通知消息 -->
    <div style="width:min(1200px,calc(100% - 32px));margin:16px auto 0;">
        <?php if ($profile_message) : ?>
            <?php echo $profile_message; ?>
        <?php endif; ?>
        <?php if ($profile_error) : ?>
            <?php echo $profile_error; ?>
        <?php endif; ?>
    </div>

    <!-- 用户中心主体：侧边栏 + 内容 -->
    <section class="uc-main-layout">
        <aside class="uc-sidebar" aria-label="用户中心导航" data-user-sidebar>
            <a href="<?php echo esc_url($page_url); ?>" data-tab="dashboard"
                class="<?php echo 'dashboard' === $tab ? 'active' : ''; ?>"><i class="fa fa-dashboard"></i> 概览</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'vip', $page_url)); ?>" data-tab="vip"
                class="<?php echo 'vip' === $tab ? 'active' : ''; ?>"><i class="fa fa-diamond"></i> 我的会员</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'orders', $page_url)); ?>" data-tab="orders"
                class="<?php echo 'orders' === $tab ? 'active' : ''; ?>"><i class="fa fa-list-alt"></i> 我的订单</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'downloads', $page_url)); ?>" data-tab="downloads"
                class="<?php echo 'downloads' === $tab ? 'active' : ''; ?>"><i class="fa fa-download"></i> 下载记录</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'favorites', $page_url)); ?>" data-tab="favorites"
                class="<?php echo 'favorites' === $tab ? 'active' : ''; ?>"><i class="fa fa-star-o"></i> 我的收藏</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'comments', $page_url)); ?>" data-tab="comments"
                class="<?php echo 'comments' === $tab ? 'active' : ''; ?>"><i class="fa fa-comments-o"></i> 我的评论</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'profile', $page_url)); ?>" data-tab="profile"
                class="<?php echo 'profile' === $tab ? 'active' : ''; ?>"><i class="fa fa-cog"></i> 账号设置</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'password', $page_url)); ?>" data-tab="password"
                class="<?php echo 'password' === $tab ? 'active' : ''; ?>"><i class="fa fa-lock"></i> 修改密码</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'referral', $page_url)); ?>" data-tab="referral"
                class="<?php echo 'referral' === $tab ? 'active' : ''; ?>"><i class="fa fa-share-alt"></i> 推广中心</a>
            <?php if (_pz('ad_self_service_enabled', false)) : ?>
            <a href="<?php echo esc_url(add_query_arg('tab', 'ad-apply', $page_url)); ?>" data-tab="ad-apply"
                class="<?php echo 'ad-apply' === $tab ? 'active' : ''; ?>"><i class="fa fa-bullhorn"></i> 广告申请</a>
            <?php endif; ?>
            <a class="uc-sidebar-logout" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><i class="fa fa-sign-out"></i> 退出登录</a>
        </aside>

        <div class="uc-content" data-user-main>
            <?php onedown_render_tab_content($tab, $user_id, $order_page); ?>
        </div>
    </section>
</main>

<!-- 订单详情弹窗 -->
<div class="onedown-pay-modal" id="order-detail-modal" aria-hidden="true">
    <div class="onedown-pay-mask"></div>
    <div class="onedown-pay-dialog" role="dialog" style="max-width:520px;">
        <button class="onedown-pay-close" type="button" aria-label="关闭" data-order-detail-close><i class="fa fa-times"></i></button>
        <div class="onedown-pay-dialog-head">
            <span class="onedown-pay-dialog-icon"><i class="fa fa-file-text-o"></i></span>
            <div>
                <span class="onedown-pay-dialog-kicker" id="order-detail-kicker">ORDER DETAIL</span>
                <h2>订单详情</h2>
            </div>
        </div>
        <div class="onedown-pay-dialog-body">
            <div id="order-detail-content" style="display:grid;gap:12px;padding:4px 0;">
                <div class="order-detail-row">
                    <span class="order-detail-label">订单标题</span>
                    <strong class="order-detail-value" id="od-title"></strong>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">订单编号</span>
                    <span class="order-detail-value" id="od-id"></span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">订单类型</span>
                    <span class="order-detail-value" id="od-type"></span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">相关文章</span>
                    <span class="order-detail-value" id="od-post"></span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">订单金额</span>
                    <strong class="order-detail-value" id="od-price" style="color:var(--od-primary);"></strong>
                </div>
                <div class="order-detail-row" id="od-pay-price-row" style="display:none;">
                    <span class="order-detail-label">实付金额</span>
                    <strong class="order-detail-value" id="od-pay-price" style="color:#4CAF50;"></strong>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">支付方式</span>
                    <span class="order-detail-value" id="od-pay-type"></span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">支付单号</span>
                    <span class="order-detail-value" id="od-trade-no"></span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">订单状态</span>
                    <span class="order-detail-value" id="od-status"></span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">创建时间</span>
                    <span class="order-detail-value" id="od-create-time"></span>
                </div>
                <div class="order-detail-row" id="od-pay-time-row" style="display:none;">
                    <span class="order-detail-label">付款时间</span>
                    <span class="order-detail-value" id="od-pay-time"></span>
                </div>
                <div class="order-detail-divider" id="od-license-divider"></div>
                <div id="od-license-section" style="display:none;">
                    <div style="font-size:13px;font-weight:800;color:#252c3a;margin-bottom:8px;">
                        <i class="fa fa-key"></i> 授权码
                    </div>
                    <div id="od-license-codes" style="display:grid;gap:6px;"></div>
                </div>
            </div>
            <p class="pay-status" id="order-detail-status" style="min-height:24px;font-size:13px;color:var(--od-muted);margin:10px 0 0;"></p>
        </div>
        <div class="onedown-pay-dialog-actions">
            <button class="onedown-pay-primary" type="button" data-order-detail-close>关闭</button>
        </div>
    </div>
</div>

<style>
    .order-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        font-size: 13px;
    }
    .order-detail-label {
        color: var(--od-muted, #9aa1ad);
        flex-shrink: 0;
    }
    .order-detail-value {
        color: #252c3a;
        text-align: right;
        word-break: break-all;
    }
    .order-detail-divider {
        height: 1px;
        background: var(--od-line, #eef0f4);
        margin: 4px 0;
    }
    #od-license-codes .license-code-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: rgba(var(--od-primary-rgb), .04);
        border: 1px solid rgba(var(--od-primary-rgb), .12);
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        font-weight: 700;
        color: #252c3a;
        letter-spacing: .5px;
    }
    #od-license-codes .copy-btn {
        padding: 4px 12px;
        font-size: 12px;
        border: 1px solid var(--od-primary, #e02e6a);
        background: transparent;
        color: var(--od-primary, #e02e6a);
        border-radius: 4px;
        cursor: pointer;
        transition: all .2s;
        flex-shrink: 0;
        margin-left: 8px;
    }
    #od-license-codes .copy-btn:hover {
        background: var(--od-primary, #e02e6a);
        color: #fff;
    }

    /* 批量删除 */
    .order-toolbar {
        display: flex !important;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
    }
    .order-batch-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .order-select-all {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #596170;
        cursor: pointer;
        user-select: none;
        margin: 0;
        padding: 4px 8px;
        border-radius: 6px;
        transition: background .2s;
        vertical-align: middle;
    }
    .order-select-all:hover {
        background: rgba(99, 102, 241, .06);
    }
    .order-select-all input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--od-primary);
        cursor: pointer;
        border-radius: 3px;
        margin: 0;
        flex-shrink: 0;
    }
    .order-checkbox-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        transition: background .2s;
        cursor: pointer;
    }
    .order-checkbox-wrap:hover {
        background: rgba(99, 102, 241, .06);
    }
    .order-checkbox-wrap input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--od-primary);
        cursor: pointer;
        border-radius: 3px;
        margin: 0;
        flex-shrink: 0;
    }
    .order-batch-delete-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 700;
        color: #e74c3c;
        background: transparent;
        border: 1px solid #e74c3c;
        border-radius: 999px;
        cursor: pointer;
        transition: all .2s;
        white-space: nowrap;
        line-height: 1.4;
    }
    .order-batch-delete-btn:hover:not(:disabled) {
        background: #e74c3c;
        color: #fff;
    }
    .order-batch-delete-btn:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    /* 分页 */
    .order-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        padding: 16px 0 20px;
        border-top: 1px solid var(--od-line, #eef0f4);
    }
    .order-page-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        font-size: 13px;
        font-weight: 700;
        color: #596170;
        background: var(--od-card, #fff);
        border: 1px solid var(--od-line, #eef0f4);
        border-radius: 8px;
        text-decoration: none;
        transition: all .2s;
        line-height: 1.4;
    }
    .order-page-link:hover {
        color: var(--od-primary);
        border-color: var(--od-primary);
    }
    .order-page-link.active {
        color: #fff;
        background: var(--od-primary);
        border-color: var(--od-primary);
    }
</style>

<script>
    (function() {
        var mainEl = document.querySelector('[data-user-main]');
        var sidebar = document.querySelector('[data-user-sidebar]');

        if (sidebar && mainEl) {
            sidebar.addEventListener('click', function(e) {
                var link = e.target.closest('a[data-tab]');
                if (!link) return;
                e.preventDefault();

                var tab = link.getAttribute('data-tab');
                if (!tab) return;

                // 更新 sidebar 激活状态
                sidebar.querySelectorAll('a[data-tab]').forEach(function(a) {
                    a.classList.toggle('active', a.getAttribute('data-tab') === tab);
                });

                // 显示加载状态
                mainEl.innerHTML =
                    '<div class="section-card" style="padding:40px;text-align:center;color:var(--od-muted);"><i class="fa fa-spinner fa-spin" style="font-size:28px;display:block;margin-bottom:12px;"></i><p style="margin:0;">加载中...</p></div>';

                // AJAX 加载
                var formData = new FormData();
                formData.set('action', 'onedown_load_tab');
                formData.set('tab', tab);
                formData.set('page', tab === 'orders' ? 1 : '');
                formData.set('_wpnonce', window.onedownData ? onedownData.tabNonce : '');

                fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            mainEl.innerHTML = data.data.html;
                            // 初始化广告申请交互（事件委托，不依赖内联脚本）
                            window.initAdApply && window.initAdApply();
                            // 更新 URL
                            if (window.history && window.history.pushState) {
                                var baseUrl = window.location.href.split('?')[0].split('#')[0];
                                var newUrl = baseUrl + (tab === 'dashboard' ? '' : '?tab=' + tab);
                                window.history.pushState({
                                    tab: tab
                                }, '', newUrl);
                            }
                            // 重新绑定订单筛选
                            rebindOrderFilter();
                            // 更新 title
                            var titles = {
                                dashboard: '用户中心',
                                vip: '我的会员',
                                orders: '我的订单',
                                downloads: '下载记录',
                                favorites: '我的收藏',
                                comments: '我的评论',
                                profile: '账号设置',
                                password: '修改密码',
                                referral: '推广中心'
                            };
                            var siteName = window.onedownData && onedownData.siteName ? onedownData.siteName : '爱铁粉';
                            document.title = (titles[tab] || '用户中心') + ' - ' + siteName;
                        } else {
                            mainEl.innerHTML =
                                '<div class="section-card" style="padding:40px;text-align:center;color:var(--od-muted);"><p style="margin:0;">加载失败，请刷新页面重试</p></div>';
                        }
                    })
                    .catch(function() {
                        mainEl.innerHTML =
                            '<div class="section-card" style="padding:40px;text-align:center;color:var(--od-muted);"><p style="margin:0;">网络错误，请重试</p></div>';
                    });
            });
        }

        function rebindOrderFilter() {
            var tabs = document.querySelector('[data-order-tabs]');
            var list = document.querySelector('[data-order-list]');
            if (tabs && list) {
                tabs.addEventListener('click', function(e) {
                    var link = e.target.closest('a[data-filter]');
                    if (!link) return;
                    e.preventDefault();
                    tabs.querySelectorAll('a').forEach(function(a) {
                        a.classList.remove('active');
                    });
                    link.classList.add('active');
                    var filter = link.getAttribute('data-filter');
                    list.querySelectorAll('.order-item').forEach(function(item) {
                        if (filter === 'all' || item.getAttribute('data-status') === filter) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
        }

        // ── 广告申请交互初始化（事件委托，不依赖内联脚本） ──
        window.initAdApply = function() {
            var form = document.getElementById('ad-apply-form');
            var modal = document.getElementById('ad-pay-modal');
            if (!form || !modal) return;

            var msgEl = document.getElementById('ad-apply-msg');
            var payAmountEl = document.getElementById('ad-pay-amount');
            var balanceDisplayEl = document.getElementById('ad-balance-display');
            var payStatusEl = document.getElementById('ad-pay-status');
            var confirmBtn = document.getElementById('ad-confirm-pay-btn');
            var payMethodsContainer = document.getElementById('ad-pay-methods');

            var currentBalance = parseFloat(form.getAttribute('data-balance') || '0');
            var pendingAdId = null;
            var pendingOrderId = null;
            var selectedMethod = null;

            // ── 缓存常用DOM引用 ──
            var balanceRow = document.querySelector('#ad-pay-modal .vip-order-box div:last-child');

            // ── 支付方式按钮（与开通会员弹窗一致） ──
            var payMethods = [];
            var hasBalance = false;
            if (window.onedownData && onedownData.payMethods) {
                var pm = onedownData.payMethods;
                var iconMap = { epay: 'fa-credit-card', alipay: 'fa-credit-card', wechat: 'fa-wechat', balance: 'fa-google-wallet', offline: 'fa-money' };
                Object.keys(pm).forEach(function(id) {
                    payMethods.push({ id: id, name: pm[id].name, icon: iconMap[id] || 'fa-credit-card' });
                    if (id === 'balance') hasBalance = true;
                });
            }
            if (window.onedownData && window.onedownData.balanceEnabled && !hasBalance) {
                payMethods.push({ id: 'balance', name: '余额支付', icon: 'fa-google-wallet' });
            }
            if (!payMethods.length) {
                payMethods = [{ id: 'wechat', name: '微信支付', icon: 'fa-wechat' }, { id: 'alipay', name: '支付宝', icon: 'fa-credit-card' }];
                if (window.onedownData && window.onedownData.balanceEnabled) {
                    payMethods.push({ id: 'balance', name: '余额支付', icon: 'fa-google-wallet' });
                }
            }

            function renderPayMethods() {
                payMethodsContainer.innerHTML = payMethods.map(function(m) {
                    return '<button class="vip-method-option" type="button" data-ad-pay-method="' + m.id + '">' +
                        '<i class="fa ' + m.icon + '"></i><span>' + m.name + '</span></button>';
                }).join('');
                payMethodsContainer.querySelectorAll('[data-ad-pay-method]').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        payMethodsContainer.querySelectorAll('[data-ad-pay-method]').forEach(function(b) { b.classList.remove('active'); });
                        btn.classList.add('active');
                        selectedMethod = btn.getAttribute('data-ad-pay-method');
                        if (balanceRow) balanceRow.style.display = (selectedMethod === 'balance') ? '' : 'none';
                    });
                });
                selectedMethod = payMethods[0] ? payMethods[0].id : 'alipay';
                if (balanceRow) balanceRow.style.display = 'none';
            }
            renderPayMethods();

            // ── 协议复选框控制提交按钮 ──
            var agreedEl = document.getElementById('ad-agreed');
            var submitBtn = document.getElementById('ad-submit-btn');
            if (agreedEl && submitBtn) {
                function toggleSubmit() {
                    submitBtn.disabled = !agreedEl.checked;
                    submitBtn.style.opacity = agreedEl.checked ? '1' : '.5';
                }
                agreedEl.addEventListener('change', toggleSubmit);
                toggleSubmit();
            }

            // ── 协议弹窗 ──
            var agreementModal = document.getElementById('ad-agreement-modal');
            var agreementLink = document.getElementById('ad-agreement-link');
            if (agreementModal && agreementLink) {
                var acceptBtn = document.getElementById('ad-agreement-accept-btn');
                var rejectBtn = document.getElementById('ad-agreement-reject-btn');
                var payAgreed = document.getElementById('ad-pay-agreed');

                function openAgreement() {
                    agreementModal.classList.add('is-show');
                    agreementModal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                }

                function closeAgreement() {
                    agreementModal.classList.remove('is-show');
                    agreementModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                }

                agreementLink.addEventListener('click', openAgreement);
                agreementModal.addEventListener('click', function(e) {
                    if (e.target.closest('[data-ad-agreement-close]')) closeAgreement();
                });

                if (acceptBtn) {
                    acceptBtn.addEventListener('click', function() {
                        if (agreedEl) { agreedEl.checked = true;
                            agreedEl.dispatchEvent(new Event('change')); }
                        if (payAgreed) { payAgreed.checked = true;
                            payAgreed.dispatchEvent(new Event('change')); }
                        closeAgreement();
                    });
                }
                if (rejectBtn) {
                    rejectBtn.addEventListener('click', function() {
                        if (agreedEl) { agreedEl.checked = false;
                            agreedEl.dispatchEvent(new Event('change')); }
                        if (payAgreed) { payAgreed.checked = false;
                            payAgreed.dispatchEvent(new Event('change')); }
                        closeAgreement();
                    });
                }
            }

            // ── 支付弹窗协议链接 ──
            var payAgreeLink = document.getElementById('ad-pay-agreement-link');
            if (payAgreeLink && agreementModal) {
                payAgreeLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    openAgreement();
                });
            }

            // ── 支付弹窗协议复选框 → 控制确认按钮 ──
            var payAgreedEl = document.getElementById('ad-pay-agreed');
            if (payAgreedEl) {
                payAgreedEl.addEventListener('change', function() {
                    confirmBtn.disabled = !this.checked;
                });
            }

            // ── 弹窗控制 ──
            function openPayModal(id, amount) {
                if (/^\d+$/.test(id)) { pendingAdId = id; pendingOrderId = null; }
                else { pendingOrderId = id; pendingAdId = null; }
                payAmountEl.textContent = '￥' + parseFloat(amount).toFixed(2);
                balanceDisplayEl.textContent = '￥' + currentBalance.toFixed(2);
                payStatusEl.innerHTML = '';
                confirmBtn.innerHTML = '<i class="fa fa-check"></i> 确认支付';
                if (payAgreedEl) { payAgreedEl.checked = false; confirmBtn.disabled = true; }
                else { confirmBtn.disabled = false; }
                selectedMethod = payMethods[0] ? payMethods[0].id : 'alipay';
                var methods = payMethodsContainer.querySelectorAll('[data-ad-pay-method]');
                for (var i = 0; i < methods.length; i++) {
                    methods[i].classList.remove('active');
                    if (methods[i].getAttribute('data-ad-pay-method') === selectedMethod) methods[i].classList.add('active');
                }
                if (balanceRow) balanceRow.style.display = 'none';
                modal.classList.add('is-show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            }

            function closePayModal() {
                modal.classList.remove('is-show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                payStatusEl.innerHTML = '';
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fa fa-check"></i> 确认支付';
            }
            // 暴露到全局，供事件委托使用（独立于闭包，始终指向最新实例）
            window._adOpenPayModal = openPayModal;
            window._adClosePayModal = closePayModal;

            modal.addEventListener('click', function(e) {
                if (e.target.closest('[data-ad-pay-close]')) closePayModal();
            });

            // ── 广告详情弹窗关闭 ──
            var detailModal = document.getElementById('ad-detail-modal');
            if (detailModal) {
                detailModal.addEventListener('click', function(e) {
                    if (e.target.closest('[data-ad-detail-close]')) {
                        detailModal.classList.remove('is-show');
                        detailModal.setAttribute('aria-hidden', 'true');
                        document.body.classList.remove('modal-open');
                    }
                });
            }

            // ── 提交广告表单 ──
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (agreedEl && !agreedEl.checked) {
                    msgEl.innerHTML = '<span style="color:#e74c3c;"><i class="fa fa-exclamation-circle"></i> 请阅读并同意广告投放协议</span>';
                    return;
                }
                var btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';
                msgEl.innerHTML = '';
                var fd = new FormData(form);
                fd.set('action', 'onedown_submit_ad_apply');
                fd.set('_wpnonce', document.getElementById('ad_apply_nonce').value);
                fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', { method: 'POST', body: new URLSearchParams(fd) })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.success) {
                            msgEl.innerHTML = '<span style="color:#4CAF50;"><i class="fa fa-check-circle"></i> ' + res.data.msg + '</span>';
                            btn.innerHTML = '<i class="fa fa-check-circle"></i> 已提交';
                            setTimeout(function() { openPayModal(res.data.ad_id, res.data.price); }, 600);
                        } else {
                            msgEl.innerHTML = '<span style="color:#e74c3c;"><i class="fa fa-exclamation-circle"></i> ' + res.data.msg + '</span>';
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fa fa-credit-card"></i> 提交并支付（￥' + parseFloat(form.getAttribute('data-price') || '0').toFixed(2) + '）';
                        }
                    })
                    .catch(function() {
                        msgEl.innerHTML = '<span style="color:#e74c3c;">网络错误，请重试</span>';
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa fa-credit-card"></i> 提交并支付';
                    });
            });

            // ── 确认支付 ──
            confirmBtn.addEventListener('click', function() {
                if ((!pendingAdId && !pendingOrderId) || !selectedMethod) return;
                var payAgreedCheck = document.getElementById('ad-pay-agreed');
                if (payAgreedCheck && !payAgreedCheck.checked) {
                    payStatusEl.innerHTML = '<span style="color:#e74c3c;"><i class="fa fa-exclamation-circle"></i> 请阅读并同意广告投放协议</span>';
                    return;
                }
                if (selectedMethod === 'balance') {
                    var amt = parseFloat(payAmountEl.textContent.replace('￥', ''));
                    if (currentBalance < amt) {
                        payStatusEl.innerHTML = '<span style="color:#e74c3c;"><i class="fa fa-exclamation-circle"></i> 余额不足，当前余额 <strong>￥' +
                            currentBalance.toFixed(2) + '</strong>。<br><a href="javascript:void(0)" onclick="adClosePayModal();showRechargeModal();" style="color:var(--od-primary);font-weight:800;text-decoration:underline;">去充值 <i class="fa fa-angle-right"></i></a>';
                        return;
                    }
                }
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';
                payStatusEl.innerHTML = '正在创建订单并发起支付...';
                var fd = new FormData();
                fd.set('action', 'onedown_initiate_pay');
                fd.set('order_type', 'ad');
                fd.set('pay_method', selectedMethod);
                if (pendingAdId) fd.set('ad_id', pendingAdId);
                else if (pendingOrderId) fd.set('ad_order_id', pendingOrderId);
                fd.set('_wpnonce', window.onedownData ? onedownData.payNonce : '');
                fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var r = data.data;
                            if (r.pay_type === 'redirect') { window.location.href = r.pay_url; } else if (r.pay_type === 'qrcode') {
                                closePayModal();
                                if (typeof openQrcodeModal === 'function') {
                                    r.pay_method = selectedMethod;
                                    r.order_title = r.order_title || '广告投放支付';
                                    openQrcodeModal(r);
                                } else {
                                    window.location.href = r.pay_url || window.location.href;
                                }
                            } else if (r.pay_type === 'success') {
                                payStatusEl.innerHTML = '';
                                closePayModal();
                                msgEl.innerHTML = '<span style="color:#4CAF50;"><i class="fa fa-check-circle"></i> 支付成功！页面即将刷新...</span>';
                                setTimeout(function() { window.location.reload(); }, 1000);
                            } else if (r.pay_type === 'offline') {
                                payStatusEl.innerHTML = r.msg || '订单已创建，请线下付款';
                                if (r.offline_info) payStatusEl.innerHTML += '<br><small>' + r.offline_info + '</small>';
                                confirmBtn.disabled = false;
                                confirmBtn.innerHTML = '<i class="fa fa-check"></i> 确认支付';
                            } else {
                                payStatusEl.innerHTML = r.msg || '支付发起失败';
                                confirmBtn.disabled = false;
                                confirmBtn.innerHTML = '<i class="fa fa-check"></i> 确认支付';
                            }
                        } else {
                            payStatusEl.innerHTML = (data.data && data.data.msg) || '支付发起失败';
                            confirmBtn.disabled = false;
                            confirmBtn.innerHTML = '<i class="fa fa-check"></i> 确认支付';
                        }
                    })
                    .catch(function() {
                        payStatusEl.innerHTML = '网络错误，请重试';
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="fa fa-check"></i> 确认支付';
                    });
            });

            // ── 广告列表操作：详情/继续支付/取消/删除（事件委托，只绑定一次） ──
            var adListEl = document.querySelector('[data-user-main]');
            if (adListEl && !adListEl.hasAttribute('data-ad-delegation')) {
                adListEl.setAttribute('data-ad-delegation', '1');
                adListEl.addEventListener('click', function(e) {
                    var btn = e.target.closest('.ad-detail-btn');
                    if (btn) {
                        var detailModal = document.getElementById('ad-detail-modal');
                        if (!detailModal) return;
                        document.getElementById('ad-detail-title').textContent = btn.getAttribute('data-title') || '-';
                        var url = btn.getAttribute('data-url') || '';
                        document.getElementById('ad-detail-url').innerHTML = url ? '<a href="' + url + '" target="_blank" style="color:var(--od-primary);">' + url + '</a>' : '-';
                        document.getElementById('ad-detail-price').textContent = '￥' + parseFloat(btn.getAttribute('data-price') || '0').toFixed(2);
                        document.getElementById('ad-detail-contact').textContent = btn.getAttribute('data-contact') || '-';
                        document.getElementById('ad-detail-expire').textContent = btn.getAttribute('data-expire') || '-';
                        document.getElementById('ad-detail-order-id').textContent = btn.getAttribute('data-order-id') || '-';
                        var statusMap = { draft: '未支付', pending: '待审核', paid: '已完成', publish: '投放中', closed: '已取消' };
                        document.getElementById('ad-detail-status').textContent = statusMap[btn.getAttribute('data-status')] || btn.getAttribute('data-status') || '-';
                        detailModal.classList.add('is-show');
                        detailModal.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('modal-open');
                        return;
                    }
                    btn = e.target.closest('.ad-continue-pay-btn');
                    if (btn) {
                        var id = btn.getAttribute('data-order-id') || btn.getAttribute('data-ad-id');
                        var price = btn.getAttribute('data-price');
                        if (id && price && window._adOpenPayModal) window._adOpenPayModal(id, price);
                        return;
                    }
                    btn = e.target.closest('.ad-cancel-btn');
                    if (btn) {
                        var adId = btn.getAttribute('data-ad-id');
                        if (!adId || !confirm('确认取消此广告订单？取消后可从回收站删除此记录。')) return;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';
                        var fd = new FormData();
                        fd.set('action', 'onedown_cancel_ad');
                        fd.set('ad_id', adId);
                        fd.set('_wpnonce', document.getElementById('ad_apply_nonce').value);
                        fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', { method: 'POST', body: new URLSearchParams(fd) })
                            .then(function(r) { return r.json(); })
                            .then(function(res) {
                                if (res.success) { alert(res.data.msg);
                                    window.location.reload(); } else { alert(res.data.msg || '操作失败');
                                    btn.disabled = false;
                                    btn.innerHTML = '<i class="fa fa-times"></i> 取消'; }
                            })
                            .catch(function() { alert('网络错误，请重试');
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fa fa-times"></i> 取消'; });
                        return;
                    }
                    btn = e.target.closest('.ad-delete-btn');
                    if (btn) {
                        var adId = btn.getAttribute('data-ad-id');
                        if (!adId || !confirm('确认删除此广告记录？删除后不可恢复。')) return;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';
                        var fd = new FormData();
                        fd.set('action', 'onedown_delete_ad');
                        fd.set('ad_id', adId);
                        fd.set('_wpnonce', document.getElementById('ad_apply_nonce').value);
                        fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', { method: 'POST', body: new URLSearchParams(fd) })
                            .then(function(r) { return r.json(); })
                            .then(function(res) {
                                if (res.success) { alert(res.data.msg);
                                    window.location.reload(); } else { alert(res.data.msg || '操作失败');
                                    btn.disabled = false;
                                    btn.innerHTML = '<i class="fa fa-trash"></i> 删除'; }
                            })
                            .catch(function() { alert('网络错误，请重试');
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fa fa-trash"></i> 删除'; });
                    }
                });
            }
        };

        // 处理浏览器后退/前进
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.tab) {
                var link = sidebar.querySelector('a[data-tab="' + e.state.tab + '"]');
                if (link) link.click();
            }
        });

        // 初始加载时绑定订单筛选
        rebindOrderFilter();

        // 初始加载时初始化广告申请交互
        if (window.initAdApply) setTimeout(window.initAdApply, 0);

        // ── 订单详情弹窗 ──
        var modal = document.getElementById('order-detail-modal');
        if (modal) {
            function openOrderDetail(orderId) {
                var statusEl = document.getElementById('order-detail-status');
                modal.classList.add('is-show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                statusEl.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 加载中...';

                var fd = new FormData();
                fd.set('action', 'onedown_order_detail');
                fd.set('order_id', orderId);
                fd.set('_wpnonce', window.onedownData ? onedownData.tabNonce : '');

                fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: new URLSearchParams(fd)
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        statusEl.innerHTML = '';
                        if (!res.success) {
                            statusEl.innerHTML = '<span style="color:#e74c3c;">' + (res.data.msg || '加载失败') + '</span>';
                            return;
                        }
                        var d = res.data;
                        document.getElementById('od-title').textContent = d.order_title || '-';
                        document.getElementById('od-id').textContent = '#' + d.order_id;
                        document.getElementById('od-type').textContent = d.order_type_label || '-';
                        document.getElementById('od-post').textContent = d.post_title || '-';
                        document.getElementById('od-post').parentElement.style.display = d.post_title ? '' : 'none';
                        document.getElementById('od-price').textContent = '￥' + d.order_price;

                        var payPriceRow = document.getElementById('od-pay-price-row');
                        if (d.pay_price) {
                            payPriceRow.style.display = '';
                            document.getElementById('od-pay-price').textContent = '￥' + d.pay_price;
                        } else {
                            payPriceRow.style.display = 'none';
                        }

                        document.getElementById('od-pay-type').textContent = d.pay_type_label || '-';
                        document.getElementById('od-trade-no').textContent = d.pay_trade_no || '-';
                        document.getElementById('od-status').innerHTML = '<span class="order-status order-status--' + d.status + '">' + d.status_label + '</span>';
                        document.getElementById('od-create-time').textContent = d.create_time || '-';

                        var payTimeRow = document.getElementById('od-pay-time-row');
                        if (d.pay_time) {
                            payTimeRow.style.display = '';
                            document.getElementById('od-pay-time').textContent = d.pay_time;
                        } else {
                            payTimeRow.style.display = 'none';
                        }

                        // 授权码
                        var licenseSection = document.getElementById('od-license-section');
                        var licenseDivider = document.getElementById('od-license-divider');
                        var licenseContainer = document.getElementById('od-license-codes');
                        if (d.license_codes && d.license_codes.length > 0) {
                            licenseSection.style.display = '';
                            licenseDivider.style.display = '';
                            licenseContainer.innerHTML = '';
                            d.license_codes.forEach(function(code, idx) {
                                var item = document.createElement('div');
                                item.className = 'license-code-item';
                                var idxStr = d.license_codes.length > 1 ? (idx + 1) + '. ' : '';
                                item.innerHTML = '<span>' + idxStr + code + '</span>' +
                                    '<button type="button" class="copy-btn" onclick="odCopyText(\'' + code.replace(/'/g, "\\'") + '\',\'已复制\')">复制</button>';
                                licenseContainer.appendChild(item);
                            });
                        } else {
                            licenseSection.style.display = 'none';
                            licenseDivider.style.display = 'none';
                        }

                        var kicker = document.getElementById('order-detail-kicker');
                        if (d.order_type === 'vip') kicker.textContent = 'VIP ORDER';
                        else if (d.order_type === 'post_read') kicker.textContent = 'READ ORDER';
                        else if (d.order_type === 'post_download') kicker.textContent = 'DOWNLOAD ORDER';
                        else if (d.order_type === 'balance_recharge') kicker.textContent = 'RECHARGE ORDER';
                        else if (d.order_type === 'ad') kicker.textContent = 'AD ORDER';
                        else if (d.order_type === 'license') kicker.textContent = 'LICENSE ORDER';
                        else kicker.textContent = 'ORDER DETAIL';
                    })
                    .catch(function() {
                        statusEl.innerHTML = '<span style="color:#e74c3c;">网络错误，请重试</span>';
                    });
            }

            function closeOrderDetail() {
                modal.classList.remove('is-show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            }

            // 点击遮罩/关闭按钮关闭
            modal.addEventListener('click', function(e) {
                if (e.target.closest('[data-order-detail-close]')) {
                    closeOrderDetail();
                }
            });

            // 使用事件委托监听所有"详情"按钮
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.order-detail-btn');
                if (!btn) return;
                e.preventDefault();
                var orderId = btn.getAttribute('data-order-id');
                if (orderId) {
                    openOrderDetail(orderId);
                }
            });

            // 使用事件委托监听所有"取消订单"按钮
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.order-cancel-btn');
                if (!btn) return;
                e.preventDefault();

                if (!confirm('确认取消此订单？')) return;

                var orderId = btn.getAttribute('data-order-id');
                if (!orderId) return;

                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

                var fd = new FormData();
                fd.set('action', 'onedown_cancel_order');
                fd.set('order_id', orderId);
                fd.set('_wpnonce', window.onedownData ? onedownData.tabNonce : '');

                fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: new URLSearchParams(fd)
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(res) {
                        if (res.success) {
                            var orderItem = btn.closest('.order-item');
                            if (orderItem) {
                                orderItem.setAttribute('data-status', 'closed');
                                var statusEl = orderItem.querySelector('.order-status');
                                if (statusEl) {
                                    statusEl.className = 'order-status order-status--closed';
                                    statusEl.textContent = '已关闭';
                                }
                                btn.remove();
                                var repayBtn = orderItem.querySelector('.order-repay-btn');
                                if (repayBtn) repayBtn.remove();
                            }
                            var refreshBtn = document.querySelector('.user-page-tabs .active');
                            if (refreshBtn) {
                                var filter = refreshBtn.getAttribute('data-filter');
                                if (filter && filter !== 'all') {
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 500);
                                }
                            }
                        } else {
                            alert(res.data.msg || '取消失败');
                            btn.disabled = false;
                            btn.innerHTML = '取消';
                        }
                    })
                    .catch(function() {
                        alert('网络错误，请重试');
                        btn.disabled = false;
                        btn.innerHTML = '取消';
                    });
            });
        }
    })();

    // ── 批量选择 & 删除 & 分页 ──
    (function() {
        // 用事件委托监听全选/取消全选（兼容 AJAX 重新渲染）
        document.addEventListener('change', function(e) {
            if (e.target.id === 'order-select-all') {
                var checked = e.target.checked;
                document.querySelectorAll('.order-checkbox').forEach(function(cb) {
                    cb.checked = checked;
                });
                updateBatchDeleteBtn();
            }
        });

        // 单个复选框变化时更新按钮状态和全选状态
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('order-checkbox')) {
                updateBatchDeleteBtn();
                syncSelectAll();
            }
        });

        function syncSelectAll() {
            var selectAllEl = document.getElementById('order-select-all');
            if (!selectAllEl) return;
            var allCbs = document.querySelectorAll('.order-checkbox');
            var checkedCbs = document.querySelectorAll('.order-checkbox:checked');
            selectAllEl.checked = allCbs.length > 0 && allCbs.length === checkedCbs.length;
        }

        function updateBatchDeleteBtn() {
            var btn = document.getElementById('order-batch-delete-btn');
            if (!btn) return;
            var checked = document.querySelectorAll('.order-checkbox:checked');
            var count = checked.length;
            btn.disabled = count === 0;
            btn.innerHTML = count > 0
                ? '<i class="fa fa-trash"></i> 批量删除（' + count + '）'
                : '<i class="fa fa-trash"></i> 批量删除';
        }

        // 重置全选状态（翻页/筛选时调用）
        function resetBatchState() {
            var selectAllEl = document.getElementById('order-select-all');
            if (selectAllEl) selectAllEl.checked = false;
            updateBatchDeleteBtn();
        }

        // 批量删除
        document.addEventListener('click', function(e) {
            var batchDeleteBtn = e.target.closest('#order-batch-delete-btn');
            if (!batchDeleteBtn) return;

            var checked = document.querySelectorAll('.order-checkbox:checked');
            if (checked.length === 0) return;
            if (!confirm('确认删除选中的 ' + checked.length + ' 条订单记录？此操作不可恢复！')) return;

            var ids = [];
            checked.forEach(function(cb) {
                ids.push(cb.value);
            });

            batchDeleteBtn.disabled = true;
            batchDeleteBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 删除中...';

            var fd = new FormData();
            fd.set('action', 'onedown_batch_delete_orders');
            fd.set('order_ids', JSON.stringify(ids));
            fd.set('_wpnonce', window.onedownData ? onedownData.tabNonce : '');

            fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: new URLSearchParams(fd)
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        checked.forEach(function(cb) {
                            var item = cb.closest('.order-item');
                            if (item) item.remove();
                        });
                        var list = document.querySelector('[data-order-list]');
                        if (list && !list.querySelector('.order-item')) {
                            list.innerHTML = '<div class="uc-empty"><i class="fa fa-inbox"></i><p>暂无订单记录</p></div>';
                            // 清空分页
                            var pagination = document.querySelector('[data-order-pagination]');
                            if (pagination) pagination.remove();
                        }
                        resetBatchState();
                    } else {
                        alert(res.data.msg || '删除失败');
                        batchDeleteBtn.disabled = false;
                        batchDeleteBtn.innerHTML = '<i class="fa fa-trash"></i> 批量删除';
                    }
                })
                .catch(function() {
                    alert('网络错误，请重试');
                    batchDeleteBtn.disabled = false;
                    batchDeleteBtn.innerHTML = '<i class="fa fa-trash"></i> 批量删除';
                });
        });

        // ── 筛选标签切换时重置全选 ──
        document.addEventListener('click', function(e) {
            var filterLink = e.target.closest('[data-order-tabs] a[data-filter]');
            if (!filterLink) return;
            // 过滤器的点击事件由 rebindOrderFilter 处理，我们只重置全选状态
            resetBatchState();
        });

        // ── 分页 ──
        document.addEventListener('click', function(e) {
            var link = e.target.closest('.order-page-link');
            if (!link) return;
            e.preventDefault();

            var page = link.getAttribute('data-page');
            if (!page) return;

            var pagination = link.closest('[data-order-pagination]');
            var list = document.querySelector('[data-order-list]');
            if (!list) return;

            if (pagination) {
                pagination.querySelectorAll('.order-page-link').forEach(function(a) {
                    a.classList.remove('active');
                });
            }
            link.classList.add('active');

            list.style.opacity = '0.5';

            var tabs = document.querySelector('[data-order-tabs]');
            var filter = tabs ? (tabs.querySelector('.active')?.getAttribute('data-filter') || 'all') : 'all';

            var fd = new FormData();
            fd.set('action', 'onedown_load_order_page');
            fd.set('page', page);
            fd.set('filter', filter);
            fd.set('_wpnonce', window.onedownData ? onedownData.tabNonce : '');

            fetch(window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: new URLSearchParams(fd)
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    list.style.opacity = '';
                    if (res.success) {
                        list.innerHTML = res.data.html;
                        if (pagination && res.data.pagination) {
                            pagination.outerHTML = res.data.pagination;
                        } else if (pagination && !res.data.pagination) {
                            pagination.remove();
                        }
                        if (window.history && window.history.pushState) {
                            var baseUrl = window.location.href.split('?')[0].split('#')[0];
                            var params = new URLSearchParams(window.location.search);
                            params.set('tab', 'orders');
                            if (page > 1) params.set('order_page', page);
                            else params.delete('order_page');
                            window.history.pushState({
                                tab: 'orders',
                                order_page: page
                            }, '', baseUrl + '?' + params.toString());
                        }
                        var activeFilter = document.querySelector('[data-order-tabs] .active');
                        if (activeFilter) {
                            var f = activeFilter.getAttribute('data-filter');
                            if (f && f !== 'all') {
                                list.querySelectorAll('.order-item').forEach(function(item) {
                                    item.style.display = item.getAttribute('data-status') === f ? '' : 'none';
                                });
                            }
                        }
                        // 翻页后重置全选状态
                        resetBatchState();
                    } else {
                        alert(res.data.msg || '加载失败');
                    }
                })
                .catch(function() {
                    list.style.opacity = '';
                    alert('网络错误，请重试');
                });
        });
    })();

    // 复制授权码（全局函数，供 onclick 调用）
    function odCopyText(text, msg) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                alert(msg || '已复制');
            }).catch(function() {
                fallbackCopy(text, msg);
            });
        } else {
            fallbackCopy(text, msg);
        }
    }
    function fallbackCopy(text, msg) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            alert(msg || '已复制');
        } catch (e) {
            alert('复制失败，请手动复制');
        }
        document.body.removeChild(ta);
    }
</script>

<?php get_footer(); ?>
