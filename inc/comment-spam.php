<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('onedown_comment_spam_enabled')) :
    function onedown_comment_spam_enabled(): bool
    {
        return (bool) _pz('comment_spam_enabled', true);
    }
endif;

if (! function_exists('onedown_comment_spam_lines')) :
    function onedown_comment_spam_lines($value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return array();
        }

        $lines = preg_split('/\R/u', $value);
        if (! is_array($lines)) {
            return array();
        }

        $rules = array();
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $rules[] = $line;
            }
        }

        return $rules;
    }
endif;

if (! function_exists('onedown_comment_spam_rule_match')) :
    function onedown_comment_spam_rule_match(string $haystack, string $rule): bool
    {
        $rule = trim($rule);
        if ($rule === '') {
            return false;
        }

        if ($rule[0] === '/' && strrpos($rule, '/') !== 0) {
            $result = @preg_match($rule, $haystack);
            return $result === 1;
        }

        if (strpos($rule, '|') !== false) {
            $parts = explode('|', $rule);
            $pattern = trim($parts[1] ?? '');
            $flags   = trim($parts[2] ?? '');
            if ($pattern !== '') {
                $delim = '~';
                $regex = $delim . str_replace($delim, '\\' . $delim, $pattern) . $delim . $flags;
                $result = @preg_match($regex, $haystack);
                return $result === 1;
            }
        }

        return function_exists('mb_stripos')
            ? mb_stripos($haystack, $rule, 0, 'UTF-8') !== false
            : stripos($haystack, $rule) !== false;
    }
endif;

if (! function_exists('onedown_comment_spam_match_ip')) :
    function onedown_comment_spam_match_ip(string $ip, string $rule): bool
    {
        $ip   = trim($ip);
        $rule = trim($rule);
        if ($ip === '' || $rule === '') {
            return false;
        }

        if (strpos($rule, '*') !== false) {
            $quoted = preg_quote($rule, '~');
            $regex  = '~^' . str_replace('\*', '.*', $quoted) . '$~';
            return @preg_match($regex, $ip) === 1;
        }

        return strcasecmp($ip, $rule) === 0;
    }
endif;

if (! function_exists('onedown_comment_spam_match_url')) :
    function onedown_comment_spam_match_url(string $content, string $rule): bool
    {
        $rule = trim($rule);
        if ($rule === '') {
            return false;
        }

        if ($rule[0] === '/' && strrpos($rule, '/') !== 0) {
            return @preg_match($rule, $content) === 1;
        }

        return function_exists('mb_stripos')
            ? mb_stripos($content, $rule, 0, 'UTF-8') !== false
            : stripos($content, $rule) !== false;
    }
endif;

if (! function_exists('onedown_comment_spam_rules')) :
    function onedown_comment_spam_rules(): array
    {
        return array(
            'keywords' => onedown_comment_spam_lines(_pz('comment_spam_keywords', '')),
            'ips'      => onedown_comment_spam_lines(_pz('comment_spam_ips', '')),
            'emails'   => onedown_comment_spam_lines(_pz('comment_spam_emails', '')),
            'urls'     => onedown_comment_spam_lines(_pz('comment_spam_urls', '')),
        );
    }
endif;

if (! function_exists('onedown_comment_spam_check')) :
    function onedown_comment_spam_check($commentdata)
    {
        if (! onedown_comment_spam_enabled()) {
            return $commentdata;
        }

        if (! is_array($commentdata)) {
            return $commentdata;
        }

        $content = implode("\n", array(
            (string) ($commentdata['comment_author'] ?? ''),
            (string) ($commentdata['comment_author_email'] ?? ''),
            (string) ($commentdata['comment_author_url'] ?? ''),
            (string) ($commentdata['comment_content'] ?? ''),
        ));
        $content = strtolower(wp_unslash($content));

        $ip      = strtolower((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $email   = strtolower(trim((string) ($commentdata['comment_author_email'] ?? '')));
        $url     = strtolower(trim((string) ($commentdata['comment_author_url'] ?? '')));
        $rules   = onedown_comment_spam_rules();

        foreach ($rules['ips'] as $rule) {
            if (onedown_comment_spam_match_ip($ip, $rule)) {
                add_filter('pre_comment_approved', '__return_spam', 99);
                return $commentdata;
            }
        }

        foreach ($rules['emails'] as $rule) {
            $rule = strtolower($rule);
            if ($rule === '') {
                continue;
            }

            if ($rule[0] === '@' && $email !== '') {
                $domain = substr(strrchr($email, '@') ?: '', 1);
                if ($domain !== '' && ltrim($rule, '@') === $domain) {
                    add_filter('pre_comment_approved', '__return_spam', 99);
                    return $commentdata;
                }
            }

            if (onedown_comment_spam_rule_match($email, $rule)) {
                add_filter('pre_comment_approved', '__return_spam', 99);
                return $commentdata;
            }
        }

        foreach ($rules['urls'] as $rule) {
            if ($url !== '' && onedown_comment_spam_rule_match($url, $rule)) {
                add_filter('pre_comment_approved', '__return_spam', 99);
                return $commentdata;
            }
        }

        foreach ($rules['keywords'] as $rule) {
            if (onedown_comment_spam_rule_match($content, $rule)) {
                add_filter('pre_comment_approved', '__return_spam', 99);
                return $commentdata;
            }
        }

        return $commentdata;
    }
    add_filter('preprocess_comment', 'onedown_comment_spam_check', 1);
endif;
