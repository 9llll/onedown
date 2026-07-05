<?php

/**
 * Template Name: VIP会员
 *
 * 独立的VIP会员开通页面，展示会员套餐并引导开通/升级
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$vip_info = array('is_vip' => false, 'vip_name' => '普通会员', 'vip_class' => '', 'expire_date' => '', 'plan_id' => '');
$user_id  = 0;
$is_logged_in = is_user_logged_in();

if ($is_logged_in) {
    $user_id  = get_current_user_id();
    $vip_info = onedown_get_user_vip_info($user_id);
}

$levels = function_exists('onedown_vip_levels') ? onedown_vip_levels() : array();

// 找出最高等级
$highest_plan_id = '';
$max_weight = 0;
foreach ($levels as $id => $level) {
    $weight = function_exists('onedown_vip_level_weight') ? onedown_vip_level_weight($id) : 0;
    if ($weight > $max_weight) {
        $max_weight = $weight;
        $highest_plan_id = $id;
    }
}
$can_upgrade = $vip_info['is_vip'] && $vip_info['plan_id'] !== $highest_plan_id;
?>

<style>
/* ===== VIP独立页面 Hero 样式（不依赖全局 .member-hero） ===== */
.vip-page-hero {
    width: min(1200px, calc(100% - 32px));
    margin: 22px auto;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 350px;
    gap: 26px;
    align-items: center;
    padding: 36px;
    border: 1px solid rgba(255, 255, 255, .72);
    border-radius: 12px;
    color: #fff;
    background:
        linear-gradient(135deg, rgba(23, 29, 48, .9), rgba(var(--od-primary-rgb), .5)),
        url("https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1400&q=82") center/cover;
    box-shadow: 0 18px 50px rgba(31, 38, 62, .08);
}

.vip-page-hero h1 {
    margin: 0;
    font-size: 42px;
    line-height: 1.16;
    font-weight: 800;
}

.vip-page-hero p {
    max-width: 620px;
    margin: 14px 0 0;
    color: rgba(255, 255, 255, .84);
    font-size: 16px;
    line-height: 1.8;
}

.vip-page-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 26px;
}

.vip-page-hero-actions a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 18px;
    border-radius: 999px;
    color: #fff;
    font-weight: 800;
    text-decoration: none;
    transition: all .2s;
}

.vip-page-hero-actions .primary {
    background: var(--od-gradient);
    box-shadow: 0 12px 24px rgba(var(--od-primary-rgb), .24);
}

.vip-page-hero-actions .ghost {
    border: 1px solid rgba(255, 255, 255, .36);
    background: rgba(255, 255, 255, .14);
    backdrop-filter: blur(10px);
}

.vip-page-hero-actions .ghost:hover {
    background: rgba(255, 255, 255, .24);
}

.vip-page-account {
    padding: 24px;
    border: 1px solid rgba(255, 255, 255, .22);
    border-radius: 12px;
    background: rgba(255, 255, 255, .14);
    backdrop-filter: blur(12px);
}

.vip-page-account .member-avatar {
    width: 58px;
    height: 58px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    color: #fff;
    background: var(--od-gradient);
    font-size: 24px;
}

.vip-page-account h2 {
    margin: 16px 0 8px;
    font-size: 24px;
    font-weight: 800;
}

.vip-page-account p {
    margin: 0;
    color: rgba(255, 255, 255, .82);
    font-size: 14px;
}

.vip-page-account .member-account-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin: 16px 0;
}

.vip-page-account .member-account-grid span {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px 14px;
    border-radius: 8px;
    background: rgba(255, 255, 255, .08);
    color: rgba(255, 255, 255, .7);
    font-size: 12px;
    font-weight: 700;
}

.vip-page-account .member-account-grid strong {
    color: #fff;
    font-size: 14px;
    font-weight: 800;
}

.vip-page-account a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    transition: all .25s ease;
    color: #fff;
    background: var(--od-primary);
    border: 1px solid var(--od-primary);
}

.vip-page-account a:hover {
    background: #d43d7f;
    border-color: #d43d7f;
    box-shadow: 0 4px 14px rgba(var(--od-primary-rgb), .3);
    transform: translateY(-2px);
}

.vip-page-account a[style*="cursor:default"]:hover {
    transform: none;
    box-shadow: none;
}

.member-kicker {
    display: block;
    margin-bottom: 8px;
    color: rgba(255, 255, 255, .7);
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 1px;
}

/* 套餐列表自适应 */
.vip-plan-options-wrap {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    padding: 20px;
}

.plan-original-price {
    text-decoration: line-through;
    color: rgba(255, 255, 255, .5);
    font-weight: 400;
    margin-left: 4px;
}

@media (max-width: 900px) {
    .vip-page-hero {
        grid-template-columns: 1fr;
        padding: 24px;
    }

    .vip-page-hero h1 {
        font-size: 28px;
    }

    .vip-page-hero p {
        font-size: 14px;
    }

    .vip-page-hero-actions a {
        flex: 1;
        justify-content: center;
    }
}

@media (max-width: 600px) {
    .vip-page-hero {
        padding: 18px;
    }

    .vip-page-hero h1 {
        font-size: 24px;
    }

    .vip-page-account {
        padding: 18px;
    }

    .vip-page-account .member-account-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main>
    <!-- 面包屑 -->
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑"
        style="width:min(1200px,calc(100% - 32px));margin:18px auto 0;">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span>VIP会员</span>
    </nav>

    <!-- Hero 区域 -->
    <section class="vip-page-hero">
        <div class="vip-page-hero-copy">
            <span class="member-kicker"><i class="fa fa-diamond"></i> VIP MEMBER</span>
            <h1><?php echo $vip_info['is_vip'] ? '会员已开通' : '开通会员，解锁全部权益'; ?></h1>
            <p><?php echo $vip_info['is_vip'] ? '当前会员：' . esc_html($vip_info['vip_name']) . '，享受专属资源与技术支持。' : '开通后即可下载会员资源、阅读付费内容并获得积分福利。'; ?>
            </p>
            <div class="vip-page-hero-actions">
                <?php if (!$vip_info['is_vip']) : ?>
                <a class="primary" href="javascript:;" data-vip-modal><i class="fa fa-shopping-bag"></i> 选择套餐</a>
                <?php elseif ($can_upgrade) : ?>
                <a class="primary" href="javascript:;" data-vip-modal data-vip-upgrade="true"><i
                        class="fa fa-arrow-up"></i> 升级会员</a>
                <?php else : ?>
                <a class="primary" href="<?php echo esc_url(onedown_user_center_url(array('tab' => 'vip'))); ?>"><i
                        class="fa fa-diamond"></i> 会员中心</a>
                <?php endif; ?>
                <?php if (!$is_logged_in) : ?>
                <a class="ghost" href="javascript:;" data-sign-modal="signin"><i class="fa fa-sign-in"></i> 立即登录</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="vip-page-account">
            <div class="member-avatar"><i class="fa fa-diamond"></i></div>
            <h2><?php echo esc_html($vip_info['vip_name']); ?></h2>
            <p>查看会员期限与下载权益</p>
            <div class="member-account-grid">
                <span><strong
                        data-vip-status-text><?php echo $vip_info['is_vip'] ? '已开通' : '未开通'; ?></strong>会员状态</span>
                <span><strong
                        data-vip-expire-text><?php echo $vip_info['is_vip'] ? esc_html($vip_info['expire_date']) : '-'; ?></strong>到期时间</span>
            </div>
            <?php if (!$vip_info['is_vip']) : ?>
            <a href="javascript:;" data-vip-modal>立即开通</a>
            <?php elseif ($can_upgrade) : ?>
            <a href="javascript:;" data-vip-modal data-vip-upgrade="true" class="member-upgrade-btn">升级会员 <i
                    class="fa fa-angle-right"></i></a>
            <?php else : ?>
            <a href="#" style="background:rgba(255,255,255,.18);cursor:default;">已激活 <i
                    class="fa fa-check-circle"></i></a>
            <?php endif; ?>
        </div>
    </section>

    <!-- 会员套餐列表 -->
    <section style="width:min(1200px,calc(100% - 32px));margin:0 auto 22px;">
        <div class="section-card">
            <div class="user-page-toolbar">
                <h3 style="margin:0;font-weight:800;color:#252c3a;"><i class="fa fa-diamond"></i> 会员套餐</h3>
            </div>

            <div class="vip-plan-options-wrap">
                <?php foreach ($levels as $id => $level) :
                    $is_current = $vip_info['is_vip'] && $vip_info['plan_id'] === $id;
                    $is_disabled = $vip_info['is_vip'] && !$can_upgrade && !$is_current && $vip_info['plan_id'] !== $id;
                    $is_upgrade_target = $vip_info['is_vip'] && !$is_current && $can_upgrade && (function_exists('onedown_vip_can_upgrade') ? onedown_vip_can_upgrade($user_id, $id) : false);
                    $real_price = $level['price'];
                    $show_price = isset($level['show_price']) && $level['show_price'] > 0 ? $level['show_price'] : 0;
                    // 升级模式：显示差价而非原价
                    $display_price = $is_upgrade_target && function_exists('onedown_vip_calc_upgrade_price')
                        ? onedown_vip_calc_upgrade_price($user_id, $id)
                        : $real_price;
                ?>
                <div class="vip-plan-option <?php echo $is_current ? 'current-plan' : ''; ?> <?php echo $is_disabled ? 'disabled-plan' : ''; ?>"
                    data-plan-id="<?php echo esc_attr($id); ?>" data-plan-name="<?php echo esc_attr($level['name']); ?>"
                    data-plan-price="<?php echo esc_attr($real_price); ?>"
                    <?php echo ($is_current || $is_disabled) ? '' : 'data-vip-modal'; ?>>
                    <?php if (!empty($level['tag'])) : ?>
                    <span class="plan-badge"
                        style="background:var(--od-gradient);color:#fff;"><?php echo esc_html($level['tag']); ?></span>
                    <?php endif; ?>
                    <strong><?php echo esc_html($level['name']); ?></strong>
                    <em>￥<?php echo esc_html(number_format($display_price, $display_price == intval($display_price) ? 0 : 2)); ?>
                        <?php if ($is_upgrade_target) : ?>
                        <small
                            style="font-weight:400;">（原价￥<?php echo esc_html(number_format($real_price, $real_price == intval($real_price) ? 0 : 2)); ?>）</small>
                        <?php elseif ($show_price > 0 && $show_price > $real_price) : ?>
                        <small
                            class="plan-original-price">￥<?php echo esc_html(number_format($show_price, $show_price == intval($show_price) ? 0 : 2)); ?></small>
                        <?php endif; ?>
                        <small>/ <?php echo $level['months'] > 0 ? $level['months'] . '个月' : '永久'; ?></small>
                    </em>
                    <small><?php echo esc_html($level['desc']); ?></small>
                    <span class="plan-btn-text">
                        <?php if ($is_current) : ?>
                        <i class="fa fa-check-circle" style="color:var(--od-primary);"></i> 当前套餐
                        <?php elseif ($is_upgrade_target) : ?>
                        <i class="fa fa-arrow-up"></i> 升级至 <?php echo esc_html($level['name']); ?>
                        <?php elseif ($is_disabled) : ?>
                        已是最优套餐
                        <?php else : ?>
                        <i class="fa fa-shopping-bag"></i> 立即开通
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 会员权益说明 -->
    <section style="width:min(1200px,calc(100% - 32px));margin:0 auto 22px;">
        <div class="section-card">
            <div class="user-page-toolbar">
                <h3 style="margin:0;font-weight:800;color:#252c3a;"><i class="fa fa-gift"></i> 会员权益</h3>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;padding:20px;">
                <div style="padding:18px;border:1px solid var(--od-line);border-radius:12px;text-align:center;">
                    <span
                        style="display:inline-flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:12px;background:var(--od-gradient);color:#fff;font-size:22px;margin-bottom:12px;"><i
                            class="fa fa-download"></i></span>
                    <h4 style="margin:0 0 6px;font-size:15px;font-weight:800;color:#252c3a;">高额下载</h4>
                    <p style="margin:0;font-size:13px;color:var(--od-muted);">每日海量资源下载，满足您的各种需求</p>
                </div>
                <div style="padding:18px;border:1px solid var(--od-line);border-radius:12px;text-align:center;">
                    <span
                        style="display:inline-flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:12px;background:var(--od-gradient);color:#fff;font-size:22px;margin-bottom:12px;"><i
                            class="fa fa-unlock-alt"></i></span>
                    <h4 style="margin:0 0 6px;font-size:15px;font-weight:800;color:#252c3a;">付费内容免费</h4>
                    <p style="margin:0;font-size:13px;color:var(--od-muted);">站内付费资源/文章全部免费查看下载</p>
                </div>
                <div style="padding:18px;border:1px solid var(--od-line);border-radius:12px;text-align:center;">
                    <span
                        style="display:inline-flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:12px;background:var(--od-gradient);color:#fff;font-size:22px;margin-bottom:12px;"><i
                            class="fa fa-percent"></i></span>
                    <h4 style="margin:0 0 6px;font-size:15px;font-weight:800;color:#252c3a;">高额佣金</h4>
                    <p style="margin:0;font-size:13px;color:var(--od-muted);">推广会员获取高额佣金返利</p>
                </div>
                <div style="padding:18px;border:1px solid var(--od-line);border-radius:12px;text-align:center;">
                    <span
                        style="display:inline-flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:12px;background:var(--od-gradient);color:#fff;font-size:22px;margin-bottom:12px;"><i
                            class="fa fa-headphones"></i></span>
                    <h4 style="margin:0 0 6px;font-size:15px;font-weight:800;color:#252c3a;">优先支持</h4>
                    <p style="margin:0;font-size:13px;color:var(--od-muted);">尊享专属客服，问题优先处理</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ 常见问题 -->
    <section style="width:min(1200px,calc(100% - 32px));margin:0 auto 22px;">
        <div class="section-card">
            <div class="user-page-toolbar">
                <h3 style="margin:0;font-weight:800;color:#252c3a;"><i class="fa fa-question-circle"></i> 常见问题</h3>
            </div>
            <div style="display:grid;gap:12px;padding:20px;">
                <div style="padding:16px;border:1px solid var(--od-line);border-radius:10px;">
                    <strong style="color:#252c3a;">Q: 会员到期后下载次数会重置吗？</strong>
                    <p style="margin:8px 0 0;font-size:13px;color:var(--od-muted);">A:
                        会员到期后下载次数限制将恢复为普通用户标准。续费后将重新获得会员下载权益。</p>
                </div>
                <div style="padding:16px;border:1px solid var(--od-line);border-radius:10px;">
                    <strong style="color:#252c3a;">Q: 如何查看我的会员有效期？</strong>
                    <p style="margin:8px 0 0;font-size:13px;color:var(--od-muted);">A: 登录后可在<a
                            href="<?php echo esc_url(onedown_user_center_url(array('tab' => 'vip'))); ?>">用户中心 -
                            我的会员</a>中查看。</p>
                </div>
                <div style="padding:16px;border:1px solid var(--od-line);border-radius:10px;">
                    <strong style="color:#252c3a;">Q: 会员可以开发票吗？</strong>
                    <p style="margin:8px 0 0;font-size:13px;color:var(--od-muted);">A: 不可以。</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>