<?php
if (post_password_required()) {
    return;
}

if (function_exists('onedown_comments_enabled') && ! onedown_comments_enabled(get_the_ID())) {
    return;
}

$comment_count = get_comments_number();
    ?>
<section class="comment-card" id="comments">
    <h2><i class="fa fa-comments-o"></i> 评论
        <?php echo $comment_count ? '<em class="comment-count">(' . esc_html($comment_count) . ')</em>' : ''; ?>
    </h2>

    <?php
        $commenter = wp_get_current_commenter();
        $req = get_option('require_name_email');
        $aria_req = $req ? " aria-required='true'" : '';

        $fields = array(
            'author' => '<div class="comment-form-row">' .
                '<div class="comment-form-field"><input id="author" name="author" type="text" placeholder="昵称' . ($req ? ' *' : '') . '" value="' . esc_attr($commenter['comment_author']) . '"' . $aria_req . '></div>',
            'email'  => '<div class="comment-form-field"><input id="email" name="email" type="email" placeholder="邮箱' . ($req ? ' *' : '') . '" value="' . esc_attr($commenter['comment_author_email']) . '"' . $aria_req . '></div>',
            'url'    => '<div class="comment-form-field"><input id="url" name="url" type="text" placeholder="网站" value="' . esc_attr($commenter['comment_author_url']) . '"></div></div>',
        );

        if (is_user_logged_in()) {
            $comment_form_args = array(
                'fields'               => $fields,
                'comment_field'        => '<div class="comment-form-field comment-form-textarea"><textarea id="comment" name="comment" placeholder="写下你的想法…" rows="4" aria-required="true"></textarea></div>',
                'submit_button'        => '<button type="submit" class="comment-submit"><i class="fa fa-send-o"></i> 发表评论</button>',
                'comment_notes_before' => '',
                'comment_notes_after'  => '',
                'title_reply'          => '',
                'title_reply_to'       => '<i class="fa fa-reply"></i> 回复 %s',
                'cancel_reply_link'    => '<i class="fa fa-times"></i> 取消回复',
                'class_form'           => 'comment-form',
                'class_submit'         => 'comment-submit',
                'label_submit'         => '发表评论',
                'logged_in_as'         => '<div class="comment-form-logged-in"><i class="fa fa-user-circle"></i> 已登录：<a href="' . esc_url(admin_url('profile.php')) . '">' . esc_html(wp_get_current_user()->display_name) . '</a> <a href="' . esc_url(wp_logout_url(apply_filters('the_permalink', get_permalink()))) . '">退出</a></div>',
            );
            comment_form($comment_form_args);
        } else {
            echo '<div class="comment-login-tip">
                <div class="comment-login-tip-icon"><i class="fa fa-commenting-o"></i></div>
                <div class="comment-login-tip-text"><strong>发表评论</strong><p>请先登录后发表评论</p></div>
                <div class="comment-login-tip-actions">
                    <a class="comment-login-btn primary" href="javascript:;" data-sign-modal><i class="fa fa-sign-in"></i> 登录</a>
                    <a class="comment-login-btn" href="javascript:;" data-sign-modal="signup"><i class="fa fa-user-plus"></i> 注册</a>
                </div>
            </div>';
        }
        ?>

    <?php if (have_comments()) : ?>
    <div class="comment-list">
        <?php
                $GLOBALS['comment_floor'] = 1;
                wp_list_comments(array(
                    'style'       => 'div',
                    'short_ping'  => true,
                    'walker'      => new Onedown_Walker_Comment(),
                    'max_depth'   => get_option('thread_comments_depth') ?: 3,
                    'avatar_size' => _pz('comments_avatar', true) ? 42 : 0,
                ));
                ?>
    </div>
    <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
    <nav class="comment-navigation">
        <span class="comment-nav-prev"><?php previous_comments_link('<i class="fa fa-angle-left"></i> 较早的评论'); ?></span>
        <span class="comment-nav-next"><?php next_comments_link('较新的评论 <i class="fa fa-angle-right"></i>'); ?></span>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</section>
