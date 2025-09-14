<?php
/**
 * Template Name: صفحه سرویس‌ها
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/services-page.php
 */
get_header();
$theme_assets = get_stylesheet_directory_uri();
?>

<div class="ai-container">
    <div class="ai-services-grid">
        <?php 
        $services = AI_Assistant_Service_Manager::get_instance()->get_active_services();
        foreach ($services as $service_id => $service): 
            $service_url = add_query_arg('service', $service_id, home_url('/service/'));
            
            
        // گرفتن تعداد اجرا از پست‌های history
        $run_count = new WP_Query([
            'post_type'      => 'ai_service_history',
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => 'service_id',
                    'value'   => $service_id,
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'nopaging' => true,
        ]);
        $total_runs = $run_count->found_posts;
        
        ?>
        <a href="<?php echo esc_url(home_url('/service/' . $service_id . '/')); ?>" class="main-ai-service-card" target="_blank">
            <div class="main-ai-service-info">
                <h3><?php echo esc_html($service['name']); ?></h3>
                <p><?php echo esc_html($service['description']); ?></p>
            </div>
            <div class="main-ai-service-image" style="background-image: url('<?= $theme_assets ?>/assets/images/<?= $service_id ?>.jpg')"></div>
        </a>

        <?php endforeach; ?>
    </div>
</div><!-- .ai-services-grid -->


<?php
// اضافه کردن استایل‌ها و اسکریپت‌ها
wp_enqueue_style('service-comments-css', get_template_directory_uri() . '/assets/css/services/comments.css');
wp_enqueue_script('service-comments-js', get_template_directory_uri() . '/assets/js/services/comments.js', array('jquery'), null, true);

// Localize script - این قسمت را اصلاح کنید
wp_localize_script('service-comments-js', 'serviceCommentsVars', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'security' => wp_create_nonce('service_comment_nonce')
));
?>

<?php foreach ($services as $service_id => $service): ?>
<div class="service-comments-section" data-service="<?php echo esc_attr($service_id); ?>">
    <div class="comments-header">
        <div class="average-rating" data-service="<?php echo esc_attr($service_id); ?>">
            <span class="rating-text">0.0</span>
            <div class="rating-stars">
                <i class="far fa-star"></i>
                <i class="far fa-star"></i>
                <i class="far fa-star"></i>
                <i class="far fa-star"></i>
                <i class="far fa-star"></i>
            </div>
            <span class="comments-count">(0 نظر)</span>
        </div>
        
        <?php if (is_user_logged_in()): ?>
            <button class="add-comment-btn" data-service="<?php echo esc_attr($service_id); ?>">
                ثبت نظر
            </button>
        <?php else: ?>
            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="add-comment-btn">
                برای ثبت نظر وارد شوید
            </a>
        <?php endif; ?>
    </div>

    <?php if (is_user_logged_in()): ?>
    <div class="comment-form" data-service="<?php echo esc_attr($service_id); ?>">
        <div class="rating-input">
            <label>امتیاز شما:</label>
            <div class="stars-input" data-service="<?php echo esc_attr($service_id); ?>">
                <i class="far fa-star" data-value="1"></i>
                <i class="far fa-star" data-value="2"></i>
                <i class="far fa-star" data-value="3"></i>
                <i class="far fa-star" data-value="4"></i>
                <i class="far fa-star" data-value="5"></i>
            </div>
            <input type="hidden" name="rating" value="0">
        </div>
        
        <textarea class="comment-textarea" placeholder="نظر خود را درباره این سرویس بنویسید..."></textarea>
        
        <button class="comment-submit-btn" data-service="<?php echo esc_attr($service_id); ?>">
            ثبت نظر
        </button>
    </div>
    <?php endif; ?>

    <div class="comments-list" data-service="<?php echo esc_attr($service_id); ?>"></div>
    
    <div class="load-more-comments" data-service="<?php echo esc_attr($service_id); ?>"></div>
</div>
<?php endforeach; ?>

<?php
get_footer(); 