<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('onedown_comments_enabled')) :
    function onedown_comments_enabled($post_id = 0)
    {
        $post_id = $post_id ? $post_id : get_the_ID();
        if ($post_id && 'post' !== get_post_type($post_id)) {
            return true;
        }

        return (bool) _pz('comments_enabled', true);
    }
endif;

if (! function_exists('onedown_filter_comments_open')) :
    function onedown_filter_comments_open($open, $post_id)
    {
        if (! onedown_comments_enabled($post_id)) {
            return false;
        }

        return $open;
    }
    add_filter('comments_open', 'onedown_filter_comments_open', 20, 2);
    add_filter('pings_open', 'onedown_filter_comments_open', 20, 2);
endif;

if (! function_exists('onedown_filter_comment_query_args')) :
    function onedown_filter_comment_query_args($comment_args)
    {
        $post_id = isset($comment_args['post_id']) ? (int) $comment_args['post_id'] : get_the_ID();
        if (! onedown_comments_enabled($post_id)) {
            $comment_args['post__in'] = array(0);
            return $comment_args;
        }

        $order = _pz('comments_order', 'desc');
        if (in_array($order, array('asc', 'desc'), true)) {
            $comment_args['order'] = strtoupper($order);
        }

        return $comment_args;
    }
    add_filter('comments_template_query_args', 'onedown_filter_comment_query_args');
endif;
