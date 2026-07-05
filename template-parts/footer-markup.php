<div class="search-modal-overlay" id="searchModal" aria-hidden="true">
    <div class="search-modal-mask"></div>
    <div class="search-modal-dialog" role="dialog" aria-modal="true" aria-label="搜索">
        <div class="search-modal-head">
            <span class="search-modal-icon"><i class="fa fa-search"></i></span>
            <h2>站内搜索</h2>
            <p>输入关键词快速查找内容</p>
        </div>
        <button class="search-modal-close" type="button" aria-label="关闭搜索" data-search-close><i class="fa fa-times"></i></button>
        <form class="search-modal-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
            <div class="search-modal-input-wrap">
                <i class="fa fa-search"></i>
                <input type="search" name="s" id="searchModalInput" placeholder="搜索 WordPress、会员、商城..." autocomplete="off">
                <button class="search-modal-clear" type="button" id="searchClearBtn" aria-label="清空输入"><i class="fa fa-times-circle"></i></button>
            </div>
            <button class="search-modal-submit" type="submit">搜索</button>
        </form>
        <div class="search-modal-hot" id="searchHotTags">
            <span class="search-hot-label"><i class="fa fa-fire"></i> 热门搜索</span>
            <div class="search-hot-tags">
                <a href="<?php echo esc_url( home_url( '/?s=WordPress' ) ); ?>">WordPress</a>
                <a href="<?php echo esc_url( home_url( '/?s=主题' ) ); ?>">主题</a>
                <a href="<?php echo esc_url( home_url( '/?s=会员' ) ); ?>">会员</a>
                <a href="<?php echo esc_url( home_url( '/?s=商城' ) ); ?>">商城</a>
                <a href="<?php echo esc_url( home_url( '/?s=教程' ) ); ?>">教程</a>
                <a href="<?php echo esc_url( home_url( '/?s=插件' ) ); ?>">插件</a>
            </div>
        </div>
    </div>
</div>

<footer class="site-footer">
    <?php if ( is_active_sidebar( 'footer_widgets' ) ) : ?>
        <div class="footer-widget-area" aria-label="页脚小工具区">
            <?php dynamic_sidebar( 'footer_widgets' ); ?>
        </div>
    <?php endif; ?>
    <div class="footer-inner">
        <div class="footer-brand">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" aria-label="<?php bloginfo( 'name' ); ?>">
                <span class="brand-mark"><i class="fa fa-bolt"></i></span>
                <span class="brand-name"><strong><?php bloginfo('name'); ?></strong><span><?php echo esc_html( get_bloginfo( 'description' ) ); ?></span></span>
            </a>
            <p><?php echo esc_html( get_bloginfo( 'description' ) ?: '面向 WordPress 内容社区、资源商城和付费会员场景的主题。' ); ?></p>
        </div>
        <div class="footer-links">
            <div>
                <h3>产品</h3>
                <?php if ( has_nav_menu( 'footer_product' ) ) : ?>
                    <?php wp_nav_menu( array( 'theme_location' => 'footer_product', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?>
                <?php else : ?>
                    <ul class="menu"><?php onedown_footer_links_from_options( 'footer_product_links' ); ?></ul>
                <?php endif; ?>
            </div>

            <div>
                <h3>支持</h3>
                <?php if ( has_nav_menu( 'footer_support' ) ) : ?>
                    <?php wp_nav_menu( array( 'theme_location' => 'footer_support', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?>
                <?php else : ?>
                    <ul class="menu"><?php onedown_footer_links_from_options( 'footer_support_links' ); ?></ul>
                <?php endif; ?>
            </div>
            <div class="footer-contact">
                <h3>联系我们</h3>
                <div class="footer-contact-items">
                    <?php if ( $phone = onedown_get_option( 'footer_contact_phone' ) ) : ?>
                        <span class="contact-item">
                            <i class="fa fa-phone"></i>
                            <span><?php echo esc_html( $phone ); ?></span>
                        </span>
                    <?php endif; ?>
                    <?php if ( $email = onedown_get_option( 'footer_contact_email' ) ) : ?>
                        <span class="contact-item">
                            <i class="fa fa-envelope"></i>
                            <span><?php echo esc_html( $email ); ?></span>
                        </span>
                    <?php endif; ?>
                    <?php if ( $wechat = onedown_get_option( 'footer_contact_wechat' ) ) : ?>
                        <span class="contact-item">
                            <i class="fa fa-weixin"></i>
                            <span><?php echo esc_html( $wechat ); ?></span>
                        </span>
                    <?php endif; ?>
                    <?php if ( $address = onedown_get_option( 'footer_contact_address' ) ) : ?>
                        <span class="contact-item">
                            <i class="fa fa-map-marker"></i>
                            <span><?php echo esc_html( $address ); ?></span>
                        </span>
                    <?php endif; ?>
                    <?php if ( $hours = onedown_get_option( 'footer_business_hours' ) ) : ?>
                        <span class="contact-item contact-hours">
                            <i class="fa fa-clock-o"></i>
                            <span><?php echo esc_html( $hours ); ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="footer-bottom-left">
            <span>© <?php echo esc_html( date_i18n( 'Y' ) ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></span>
            <?php if ( $icp = onedown_get_option( 'footer_icp_beian' ) ) : ?>
                <?php if ( onedown_get_option( 'footer_icp_link', true ) ) : ?>
                    <a href="https://beian.miit.gov.cn/" target="_blank" rel="nofollow"><?php echo esc_html( $icp ); ?></a>
                <?php else : ?>
                    <span><?php echo esc_html( $icp ); ?></span>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ( $ps = onedown_get_option( 'footer_ps_beian' ) ) : ?>
                <?php $ps_link = onedown_get_option( 'footer_ps_link' ) ?: 'http://www.beian.gov.cn/portal/registerSystemInfo'; ?>
                <a href="<?php echo esc_url( $ps_link ); ?>" target="_blank" rel="nofollow">
                    <i class="fa fa-shield"></i> <?php echo esc_html( $ps ); ?>
                </a>
            <?php endif; ?>
            <a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" class="footer-sitemap-link" target="_blank">
                <i class="fa fa-sitemap"></i> <?php esc_html_e( '站点地图', ONEDOWN_TEXT_DOMAIN ); ?>
            </a>
        </div>
        <span class="footer-bottom-right"><?php esc_html_e( 'Powered by WordPress', ONEDOWN_TEXT_DOMAIN ); ?></span>
    </div>
</footer>

<?php onedown_render_mobile_tabbar(); ?>
