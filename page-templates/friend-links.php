<?php

/**
 * Template Name: 友情链接
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$terms = get_terms(array(
    'taxonomy'   => 'friend_link_cat',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
));

$link_count_obj = wp_count_posts('onedown_friend_link');
$publish_count  = isset($link_count_obj->publish) ? (int) $link_count_obj->publish : 0;

$render_link_card = function ($post_id) {
    $url    = get_post_meta($post_id, '_friend_link_url', true);
    $url    = $url ? $url : '#';
    $host   = wp_parse_url($url, PHP_URL_HOST);
    $host   = $host ? preg_replace('/^www\./', '', $host) : $url;
    $desc   = get_the_excerpt($post_id);
    $target = $url && $url !== '#' ? ' target="_blank" rel="noopener nofollow"' : '';
    $favicon = get_post_meta($post_id, '_friend_link_favicon', true);
    $favicon = $favicon ? $favicon : onedown_friend_link_default_icon_url();
    $default_icon = onedown_friend_link_default_icon_url();
?>
    <a class="friend-link-card" href="<?php echo esc_url($url); ?>" <?php echo $target; ?>>
        <span class="friend-link-icon"><img src="<?php echo esc_url($favicon); ?>" alt="" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url($default_icon); ?>';"></span>
        <span class="friend-link-content">
            <strong><?php echo esc_html(get_the_title($post_id)); ?></strong>
            <?php if ($desc) : ?>
                <em><?php echo esc_html(wp_trim_words($desc, 26, '...')); ?></em>
            <?php else : ?>
                <em>站点暂未填写描述</em>
            <?php endif; ?>
            <small><i class="fa fa-link"></i> <?php echo esc_html($host); ?></small>
        </span>
        <span class="friend-link-visit">访问</span>
    </a>
<?php
};
?>

<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span>友情链接</span>
    </nav>

    <section class="content-shell content-shell--full friend-links-page-shell">
        <div class="main-column">
            <div class="section-card friend-link-overview">
                <div class="friend-link-overview-copy">
                    <span class="friend-link-kicker"><i class="fa fa-link"></i> LINKS</span>
                    <h1>友情链接</h1>
                    <p>收录已审核通过的合作站点，按分类展示，便于快速发现相关资源。</p>
                </div>
                <div class="friend-link-overview-stats">
                    <span><strong><?php echo esc_html($publish_count); ?></strong> 个友链</span>
                    <span><strong><?php echo esc_html(is_array($terms) ? count($terms) : 0); ?></strong> 个分类</span>
                </div>
            </div>

            <?php if (! empty($terms) && ! is_wp_error($terms)) : ?>
                <div class="friend-link-tabs">
                    <a href="#friendLinkGroups" class="active">全部</a>
                    <?php foreach ($terms as $term) : ?>
                        <a href="#friendLinkCat<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></a>
                    <?php endforeach; ?>
                    <a href="#friendLinkApply" data-friend-link-open>申请友链</a>
                </div>
            <?php endif; ?>

            <div class="section-card friend-link-page-card" id="friendLinkGroups">
                <div class="section-head">
                    <h2 class="section-title"><i class="fa fa-link"></i> 全部友链</h2>
                    <button class="friend-link-apply-trigger" type="button" data-friend-link-open><i class="fa fa-plus-circle"></i> 申请友链</button>
                </div>

                <div class="friend-link-groups">
                    <?php
                    $has_links = false;

                    if (! empty($terms) && ! is_wp_error($terms)) :
                        foreach ($terms as $term) :
                            $links = new WP_Query(array(
                                'post_type'      => 'onedown_friend_link',
                                'post_status'    => 'publish',
                                'posts_per_page' => -1,
                                'orderby'        => 'date',
                                'order'          => 'DESC',
                                'tax_query'      => array(
                                    array(
                                        'taxonomy' => 'friend_link_cat',
                                        'field'    => 'term_id',
                                        'terms'    => $term->term_id,
                                    ),
                                ),
                            ));

                            if ($links->have_posts()) :
                                $has_links = true;
                    ?>
                                <section class="friend-link-section" id="friendLinkCat<?php echo esc_attr($term->term_id); ?>">
                                    <div class="friend-link-section-head">
                                        <h3><?php echo esc_html($term->name); ?></h3>
                                        <span><?php echo esc_html($links->post_count); ?> 个</span>
                                    </div>
                                    <div class="friend-link-grid">
                                        <?php
                                        while ($links->have_posts()) :
                                            $links->the_post();
                                            $render_link_card(get_the_ID());
                                        endwhile;
                                        ?>
                                    </div>
                                </section>
                        <?php
                                wp_reset_postdata();
                            endif;
                        endforeach;
                    endif;

                    $uncategorized_links = new WP_Query(array(
                        'post_type'      => 'onedown_friend_link',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'friend_link_cat',
                                'operator' => 'NOT EXISTS',
                            ),
                        ),
                    ));

                    if ($uncategorized_links->have_posts()) :
                        $has_links = true;
                        ?>
                        <section class="friend-link-section">
                            <div class="friend-link-section-head">
                                <h3>默认分类</h3>
                                <span><?php echo esc_html($uncategorized_links->post_count); ?> 个</span>
                            </div>
                            <div class="friend-link-grid">
                                <?php
                                while ($uncategorized_links->have_posts()) :
                                    $uncategorized_links->the_post();
                                    $render_link_card(get_the_ID());
                                endwhile;
                                ?>
                            </div>
                        </section>
                    <?php
                        wp_reset_postdata();
                    endif;

                    if (! $has_links) :
                    ?>
                        <div class="friend-link-empty">
                            <i class="fa fa-inbox"></i>
                            <p>暂无已通过的友情链接。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="friend-link-modal vip-pay-modal" id="friendLinkApply" aria-hidden="true">
                <div class="vip-pay-mask" data-friend-link-close></div>
                <div class="vip-pay-dialog friend-link-modal-panel friend-link-apply" role="dialog" aria-modal="true" aria-labelledby="friendLinkApplyTitle">
                    <button class="vip-pay-close" type="button" data-friend-link-close aria-label="关闭"><i class="fa fa-times"></i></button>
                    <div class="vip-pay-head">
                        <span class="vip-pay-icon"><i class="fa fa-link"></i></span>
                        <div>
                            <span class="vip-pay-kicker">FRIEND LINK</span>
                            <h2 id="friendLinkApplyTitle">申请友情链接</h2>
                            <p>输入站点地址后可一键获取站点信息，提交后自动审核展示。</p>
                        </div>
                    </div>
                    <div class="vip-pay-body friend-link-apply-body">
                        <div class="vip-pay-block friend-link-apply-note">
                            <h3>收录说明</h3>
                            <ul>
                                <li><i class="fa fa-check"></i> 站点可正常访问</li>
                                <li><i class="fa fa-check"></i> 建议填写清晰描述</li>
                                <li><i class="fa fa-check"></i> 提交后自动审核展示</li>
                            </ul>
                        </div>
                        <div class="vip-pay-block friend-link-apply-form">
                        <div class="friend-link-message" id="friendLinkMessage"></div>
                        <form id="friendLinkApplyForm">
                            <input type="hidden" name="action" value="onedown_friend_link_apply">
                            <input type="hidden" name="_wpnonce"
                                value="<?php echo esc_attr(wp_create_nonce('onedown_friend_link_apply')); ?>">

                            <div class="friend-link-form-grid">
                                <label class="friend-link-field-wide">
                                    <span>链接地址</span>
                                    <span class="friend-link-url-row">
                                        <input type="url" name="site_url" required placeholder="https://example.com">
                                        <button type="button" class="friend-link-fetch-btn" id="friendLinkFetchBtn"><i class="fa fa-magic"></i> 一键获取</button>
                                    </span>
                                </label>
                                <label>
                                    <span>站点名称</span>
                                    <input type="text" name="site_name" required maxlength="60" placeholder="请输入站点名称">
                                </label>
                                <label>
                                    <span>友链分类</span>
                                    <select name="category">
                                        <option value="0">默认分类</option>
                                        <?php if (! empty($terms) && ! is_wp_error($terms)) : ?>
                                            <?php foreach ($terms as $term) : ?>
                                                <option value="<?php echo esc_attr($term->term_id); ?>">
                                                    <?php echo esc_html($term->name); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </label>
                            </div>

                            <label class="friend-link-desc">
                                <span>站点描述</span>
                                <textarea name="site_desc" rows="4" maxlength="180" placeholder="简单介绍你的站点"></textarea>
                            </label>

                            <div class="vip-pay-actions friend-link-form-actions">
                                <button class="vip-pay-secondary" type="button" data-friend-link-close>取消</button>
                                <button class="vip-pay-primary" type="submit"><i class="fa fa-paper-plane"></i> 提交申请</button>
                            </div>
                        </form>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    (function() {
        var form = document.getElementById('friendLinkApplyForm');
        var message = document.getElementById('friendLinkMessage');
        var modal = document.getElementById('friendLinkApply');
        var openers = document.querySelectorAll('[data-friend-link-open]');
        var closers = document.querySelectorAll('[data-friend-link-close]');
        var fetchBtn = document.getElementById('friendLinkFetchBtn');

        function openModal(e) {
            if (e) {
                e.preventDefault();
            }
            if (!modal) {
                return;
            }
            modal.classList.add('is-show');
            modal.setAttribute('aria-hidden', 'false');
            document.documentElement.classList.add('friend-link-modal-lock');
        }

        function closeModal() {
            if (!modal) {
                return;
            }
            modal.classList.remove('is-show');
            modal.setAttribute('aria-hidden', 'true');
            document.documentElement.classList.remove('friend-link-modal-lock');
        }

        openers.forEach(function(item) {
            item.addEventListener('click', openModal);
        });

        closers.forEach(function(item) {
            item.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        if (!form || !message) {
            return;
        }

        if (fetchBtn) {
            fetchBtn.addEventListener('click', function() {
                var urlInput = form.querySelector('[name="site_url"]');
                var nameInput = form.querySelector('[name="site_name"]');
                var descInput = form.querySelector('[name="site_desc"]');
                var url = urlInput ? urlInput.value.trim() : '';

                message.className = 'friend-link-message';
                message.textContent = '';

                if (!url) {
                    message.className = 'friend-link-message is-show error';
                    message.textContent = '请先输入链接地址';
                    return;
                }

                fetchBtn.disabled = true;
                fetchBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 获取中';

                var fetchData = new FormData();
                fetchData.append('action', 'onedown_friend_link_fetch');
                fetchData.append('_wpnonce', form.querySelector('[name="_wpnonce"]').value);
                fetchData.append('site_url', url);

                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fetchData
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    if (!data || !data.success) {
                        var errorMsg = data && data.data && data.data.msg ? data.data.msg : '获取失败，请手动填写';
                        message.className = 'friend-link-message is-show error';
                        message.textContent = errorMsg;
                        return;
                    }

                    if (nameInput && data.data.title) {
                        nameInput.value = data.data.title;
                    }
                    if (descInput && data.data.description) {
                        descInput.value = data.data.description;
                    }
                    message.className = 'friend-link-message is-show success';
                    message.textContent = '获取成功，已自动填写站点信息';
                }).catch(function() {
                    message.className = 'friend-link-message is-show error';
                    message.textContent = '获取失败，请手动填写';
                }).finally(function() {
                    fetchBtn.disabled = false;
                    fetchBtn.innerHTML = '<i class="fa fa-magic"></i> 一键获取';
                });
            });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var button = form.querySelector('button[type="submit"]');
            var formData = new FormData(form);

            message.className = 'friend-link-message';
            message.textContent = '';
            if (button) {
                button.disabled = true;
            }

            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                var ok = data && data.success;
                var msg = data && data.data && data.data.msg ? data.data.msg : (ok ? '申请已提交，等待管理员审核' :
                    '提交失败，请稍后重试');
                message.className = 'friend-link-message is-show ' + (ok ? 'success' : 'error');
                message.textContent = msg;
                if (ok) {
                    form.reset();
                    setTimeout(closeModal, 900);
                }
            }).catch(function() {
                message.className = 'friend-link-message is-show error';
                message.textContent = '提交失败，请稍后重试';
            }).finally(function() {
                if (button) {
                    button.disabled = false;
                }
            });
        });
    })();
</script>

<?php get_footer(); ?>
