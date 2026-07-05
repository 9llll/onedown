<?php
/**
 * 评论模板包装器 - 加载 templates/comments.php
 */
if (function_exists('onedown_comments_enabled') && ! onedown_comments_enabled(get_the_ID())) {
    return;
}

require get_theme_file_path('templates/comments.php');
