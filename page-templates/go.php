<?php
/**
 * Onedown - 外部链接跳转中转页面
 *
 * 用于安全地跳转到外部链接，防止盗链、XSS 攻击。
 * 支持通过 query var (?golink=xxx) 或 SESSION 传递 URL。
 *
 * @package onedown
 */

if (
    strlen($_SERVER['REQUEST_URI']) > 384 ||
    strpos($_SERVER['REQUEST_URI'], "eval(") ||
    strpos($_SERVER['REQUEST_URI'], "base64")
) {
    @header("HTTP/1.1 414 Request-URI Too Long");
    @header("Status: 414 Request-URI Too Long");
    @header("Connection: Close");
    @exit;
}

$query_args = array();
parse_str($_SERVER['QUERY_STRING'] ?? '', $query_args);

if (! empty($query_args['golink'])) {
    $t_url = (string) $query_args['golink'];
} elseif (! empty($query_args['url'])) {
    $t_url = (string) $query_args['url'];
} else {
    $t_url = preg_replace('/^url=(.*)$/i', '$1', $_SERVER['QUERY_STRING'] ?? '');
}

// 数据处理
if (!empty($t_url)) {
    // 判断取值是否加密
    $decoded_url = base64_decode($t_url, true);
    if ($decoded_url !== false && rtrim($t_url, '=') === rtrim(base64_encode($decoded_url), '=')) {
        $t_url = $decoded_url;
    }

    // 防止 XSS
    $t_url = htmlspecialchars($t_url);

    // 对取值进行网址校验和判断
    preg_match('/^(http|https|thunder|qqdl|ed2k|Flashget|qbrowser):\/\//i', $t_url, $matches);
    if ($matches) {
        $url   = $t_url;
        $title = __('页面加载中，请稍候...', 'onedown');
    } else {
        preg_match('/\./i', $t_url, $matche);
        if ($matche) {
            $url   = 'http://' . $t_url;
            $title = __('页面加载中，请稍候...', 'onedown');
        } else {
            $url   = 'http://' . $_SERVER['HTTP_HOST'];
            $title = __('参数错误，正在返回首页...', 'onedown');
        }
    }
} else {
    $title = __('参数缺失，正在返回首页...', 'onedown');
    $url   = 'http://' . $_SERVER['HTTP_HOST'];
}

$url = str_replace('&amp;', '&', $url);

?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="noindex, nofollow" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
<noscript>
    <meta http-equiv="refresh" content="1;url='<?php echo $url; ?>';">
</noscript>
<script>
function link_jump() {
    // 禁止其他网站使用我们的跳转页面
    var MyHOST = new RegExp("<?php echo $_SERVER['HTTP_HOST']; ?>");
    if (!MyHOST.test(document.referrer)) {
        location.href = "http://" + MyHOST;
    }
    location.href = "<?php echo $url; ?>";
}
// 延时跳转
setTimeout(link_jump, 1500);
// 延时关闭跳转页面（用于文件下载场景）
setTimeout(function() {
    window.opener = null;
    window.close();
}, 50000);
</script>
<title><?php echo $title; ?></title>
<style type="text/css">
body { background: #fff; margin: 0; padding: 0; }
.loading-container {
    position: fixed;
    top: 0; left: 0; bottom: 0; right: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.loading-spinner {
    transform: scale(1) translateY(-30px);
}
.loading-spinner > div:nth-child(2) {
    -webkit-animation-delay: -.4s;
    animation-delay: -.4s;
}
.loading-spinner > div:nth-child(3) {
    -webkit-animation-delay: -.2s;
    animation-delay: -.2s;
}
.loading-spinner > div {
    position: absolute;
    top: 0; left: -30px;
    margin: 0;
    width: 60px; height: 60px;
    border-radius: 100%;
    background-color: #f156b4;
    opacity: 0;
    -webkit-animation-fill-mode: both;
    animation-fill-mode: both;
    -webkit-animation: ball-scale-multiple 1s .5s linear infinite;
    animation: ball-scale-multiple 1s .5s linear infinite;
}
@-webkit-keyframes ball-scale-multiple {
    0%   { opacity: 0; -webkit-transform: scale(0); transform: scale(0); }
    5%   { opacity: 1; }
    to   { -webkit-transform: scale(1); transform: scale(1); }
}
@keyframes ball-scale-multiple {
    0%   { opacity: 0; -webkit-transform: scale(0); transform: scale(0); }
    5%   { opacity: 1; }
    to   { opacity: 0; -webkit-transform: scale(1); transform: scale(1); }
}
@keyframes fade-in-up {
    0%   { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
.sr-only {
    display: none;
}
.loading-text {
    position: fixed;
    top: 60px; left: 0; bottom: 0; right: 0;
    color: #f156b4;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fade-in-up .8s cubic-bezier(0.36, 0.29, 0.62, 1.36);
}
</style>
</head>
<body>
    <div class="loading-container">
        <h1 class="sr-only"><?php echo $title; ?></h1>
        <div class="loading-spinner">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
    <div class="loading-text"><?php echo $title; ?></div>
</body>
</html>
