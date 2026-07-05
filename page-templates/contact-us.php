<?php

/**
 * Template Name: 联系我们
 *
 * @package onedown
 */

get_header();

$contact_email   = _pz('footer_contact_email', get_option('admin_email'));
$contact_phone   = _pz('footer_contact_phone', '');
$contact_address = _pz('footer_contact_address', '');
$contact_hours   = _pz('footer_business_hours', '周一至周五 9:00 - 18:00');
?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span>联系我们</span>
    </nav>

    <section class="page-hero contact-page-hero">
        <div class="page-hero-copy">
            <h1>联系我们</h1>
            <p>有任何问题或建议？请填写表单，我们会尽快回复你。</p>
        </div>
        <div class="page-hero-stats">
            <span><i class="fa fa-envelope"></i> <?php echo esc_html($contact_email); ?></span>
        </div>
    </section>

    <section class="content-shell">
        <div class="contact-info-wrap">
            <div class="section-card">
                <div class="section-head">
                    <h2 class="section-title"><i class="fa fa-info-circle"></i> 联系方式</h2>
                </div>
                <div class="contact-info-list">
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fa fa-envelope"></i></div>
                        <div class="contact-info-body">
                            <strong>邮箱</strong>
                            <span><a
                                    href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></span>
                        </div>
                    </div>

                    <?php if ($contact_phone) : ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fa fa-phone"></i></div>
                        <div class="contact-info-body">
                            <strong>电话</strong>
                            <span><?php echo esc_html($contact_phone); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($contact_address) : ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fa fa-map-marker"></i></div>
                        <div class="contact-info-body">
                            <strong>地址</strong>
                            <span><?php echo esc_html($contact_address); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fa fa-clock-o"></i></div>
                        <div class="contact-info-body">
                            <strong>工作时间</strong>
                            <span><?php echo esc_html($contact_hours); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php get_sidebar(); ?>
    </section>
</main>
<?php get_footer(); ?>