<?php
/**
 * Template Name: صفحه اطلاعات سرویس
 * Template Post Type: page
 */

// بررسی اینکه آیا مستقیماً از وردپرس فراخوانی شده
if (!defined('ABSPATH')) {
    // اگر مستقیم فراخوانی شده، وردپرس را بارگذاری کن
    $wp_path = preg_replace('/wp-content.*$/', '', __DIR__);
    require_once $wp_path . 'wp-load.php';
}

get_header();
$theme_assets = get_stylesheet_directory_uri();

// دریافت اطلاعات سرویس از پارامتر URL
$service_id = get_query_var('service_id');
if (empty($service_id)) {
    // اگر service_id از طریق rewrite rule نیامد، از پارامتر GET بگیر
    $service_id = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
}

$services = AI_Assistant_Service_Manager::get_instance()->get_active_services();
$service = isset($services[$service_id]) ? $services[$service_id] : null;

if (!$service) {
    // اگر سرویس یافت نشد، به صفحه 404 هدایت شود
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit();
} 

$full_description = $service['full_description'] ?? '
    <h3>درباره این سرویس</h3>
    <p>این سرویس با استفاده از هوش مصنوعی پیشرفته، بهترین و متناسب‌ترین راهکار را برای شما ارائه می‌دهد. با تحلیل اطلاعات ورودی و تطبیق آن با استانداردهای روز، خروجی دقیق و کاربردی تولید می‌کند.</p>
    <p>مزایای استفاده از این سرویس شامل صرفه‌جویی در زمان، دقت بالا، قابلیت شخصی‌سازی، پشتیبانی از به‌روزرسانی و امکان استفاده نامحدود می‌باشد. شما می‌توانید بارها از این سرویس استفاده کنید و هر بار نتیجه‌ای متناسب با نیاز خود دریافت نمایید.</p>
';

// گرفتن اطلاعات قیمت و تخفیف (فرضی)
$service_price = isset($service['price']) ? $service['price'] : 50000;
// $service_discount = isset($service['discount']) ? $service['discount'] : 0;
//$service_discount = 20;


$final_price = $service_price;

$best_discount = AI_Assistant_Discount_Manager::find_best_discount($service_id,get_current_user_id(), '');

$service_discount = $best_discount->amount;
if ($best_discount) {
    $final_price = AI_Assistant_Discount_Manager::calculate_discounted_price($service_price, $best_discount);
    
    error_log("✅ تخفیف اعمال شد: {$best_discount->name} - نوع: {$best_discount->type} - مقدار: {$best_discount->amount}");
    error_log("💰 قیمت اصلی: {$service_price} - - قیمت نهایی: {$final_price}");
    

}


// گرفتن نظرات سرویس
$comments_db = AI_Assistant_Comments_DB::get_instance();
$service_comments = $comments_db->get_comments($service_id, 'approved', 5);
$average_rating = $comments_db->get_average_rating($service_id);
$average_rating = $average_rating ? round($average_rating, 1) : 0;
$total_comments = $comments_db -> get_comment_count($service_id , 'approved');
?>

<div class="ai-container service_info-container">
    <!-- مسیر ناوبری -->
    <div class="service_info-breadcrumb">
        <a href="<?php echo home_url(); ?>">خانه</a> / 
        <a href="<?php echo home_url('/ai-services/'); ?>">سرویس‌ها</a> / 
        <span><?php echo esc_html($service['name']); ?></span>
    </div>

    <!-- بخش اصلی اطلاعات سرویس -->
    <div class="service_info-main">
        <div class="service_info-image-section">
            <div class="service_info-image" style="background-image: url('<?= $theme_assets ?>/assets/images/<?= $service_id ?>.jpg')">
                <?php if ($service_discount > 0): ?>
                <div class="service_info-discount-badge">
                    <?php echo esc_html($service_discount); ?>% تخفیف
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="service_info-details">
            <h1 class="service_info-title"><?php echo esc_html($service['name']); ?></h1>
            
            <div class="service_info-rating">
                <div class="service_info-stars">
                    <?php
                    $full_stars = floor($average_rating);
                    $has_half_star = ($average_rating - $full_stars) >= 0.5;
                    
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars) {
                            echo '<span class="service_info-star full">★</span>';
                        } elseif ($has_half_star && $i == $full_stars + 1) {
                            echo '<span class="service_info-star half">★</span>';
                        } else {
                            echo '<span class="service_info-star">☆</span>';
                        }
                    }
                    ?>
                </div>
                <span class="service_info-rating-value">(<?php echo esc_html($average_rating); ?> از ۵)</span>
                <span class="service_info-review-count"><?php echo esc_html($total_comments); ?> نظر</span>
            </div>
            
            <div class="service_info-price-section">
                <?php if ($service_discount > 0): ?>
                <div class="service_info-original-price"><?php echo number_format($service_price); ?> تومان</div>
                <?php endif; ?>
                <div class="service_info-final-price"><?php echo number_format($final_price); ?> تومان</div>
            </div>
            
            <div class="service_info-action">
                <a href="<?php echo esc_url(home_url('/service/' . $service_id . '/')); ?>" class="ai-button service_info-use-btn">استفاده از سرویس</a>
            </div>
                        
            <div class="service_info-description">
                <?php echo wp_kses_post($full_description); ?>
            </div>
        </div>
    </div>

    <!-- بخش نظرات کاربران -->
    <div class="service_info-comments-section">
        <h2 class="service_info-comments-title">نظرات کاربران</h2>
        
        <?php if ($service_comments): ?>
        <div class="service_info-comments-list">
            <?php foreach ($service_comments as $comment): ?>
            <div class="service_info-comment-item">
                <div class="service_info-comment-header">
                    <div class="service_info-comment-author">کاربر <?php echo esc_html(substr($comment->user_id, 0, 4)); ?></div>
                    <div class="service_info-comment-rating">
                        <?php 
                        $rating = intval($comment->rating);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<span class="service_info-comment-star">★</span>';
                            } else {
                                echo '<span class="service_info-comment-star">☆</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="service_info-comment-content">
                    <p><?php echo esc_html($comment->comment_text); ?></p>
                </div>
                <div class="service_info-comment-date">
                    <?php echo date_i18n('j F Y', strtotime($comment->created_at)); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="service_info-no-comments">
            <p>هنوز نظری برای این سرویس ثبت نشده است.</p>
        </div>
        <?php endif; ?>
        
        <!-- دکمه مشاهده نظرات بیشتر -->
        <?php if ($total_comments > 5): ?>
        <div class="service_info-more-comments">
            <a href="#" class="ai-button service_info-more-btn">مشاهده همه نظرات</a>
        </div>
        <?php endif; ?>
    </div>
    

<!-- بعد از اسلایدر نظرات، فرم ثبت نظر را اضافه کنید -->
<div class="user-comment-section">
    <?php if (is_user_logged_in()) : ?>
        <div class="comment-form-container">
            <h3>ثبت نظر جدید</h3>
            
            
            <form class="service-comment-form" method="post">
                <input type="hidden" name="service_id" id="selected-service-id" value="">
                
                <div class="rating-input">
                    <label>امتیاز شما:</label>
                    <div class="stars-input">
                        <i class="fas fa-star" data-value="1"></i>
                        <i class="fas fa-star" data-value="2"></i>
                        <i class="fas fa-star" data-value="3"></i>
                        <i class="fas fa-star" data-value="4"></i>
                        <i class="fas fa-star" data-value="5"></i>
                    </div>
                    <input type="hidden" name="rating" value="0">
                </div>
                <div class="comment-textarea-container">
                    <textarea name="comment_text" class="comment-textarea" placeholder="نظر خود را اینجا بنویسید..." required></textarea>
                </div>
                <div class="form-submit">
                    <button type="submit" class="comment-submit-btn">ثبت نظر</button>
                </div>
            </form>
        </div>
    <?php else : ?>
        <div class="login-to-comment">
            <p>برای ثبت نظر باید <a href="<?php echo wp_login_url(get_permalink()); ?>">وارد حساب کاربری</a> خود شوید.</p>
        </div>
    <?php endif; ?>
</div>    
</div>

<style>
/* استایل‌های صفحه اطلاعات سرویس */
.service_info-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.service_info-breadcrumb {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

.service_info-breadcrumb a {
    color: var(--primary-color);
    text-decoration: none;
}

.service_info-breadcrumb a:hover {
    text-decoration: underline;
}

.service_info-breadcrumb span {
    color: #333;
    font-weight: 500;
}

.service_info-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
    background: var(--white);
    border-radius: 8px;
    padding: 20px;
    box-shadow: var(--box-shadow);
}

.service_info-image-section {
    position: relative;
}

.service_info-image {
    width: 100%;
    height: 400px;
    background-size: cover;
    background-position: center;
    border-radius: 8px;
    position: relative;
}

.service_info-discount-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: var(--error-color);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 14px;
}

.service_info-details {
    display: flex;
    flex-direction: column;
}

.service_info-title {
    font-size: 24px;
    margin: 0 0 15px 0;
    color: var(--primary-color);
}

.service_info-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.service_info-stars {
    display: flex;
    direction: ltr;
}

.service_info-star {
    color: #ddd;
    font-size: 20px;
}

.service_info-star.full {
    color: #ffc107;
}

.service_info-star.half {
    background: linear-gradient(90deg, #ffc107 50%, #ddd 50%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.service_info-rating-value {
    font-size: 14px;
    color: #666;
}

.service_info-review-count {
    font-size: 14px;
    color: var(--primary-color);
    cursor: pointer;
}

.service_info-price-section {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.service_info-original-price {
    font-size: 16px;
    color: #999;
    text-decoration: line-through;
    margin-bottom: 5px;
}

.service_info-final-price {
    font-size: 24px;
    font-weight: bold;
    color: var(--success-color);
}

.service_info-action {
    margin-bottom: 25px;
}

.service_info-use-btn {
    display: block;
    text-align: center;
    padding: 15px;
    font-size: 16px;
    font-weight: bold;
}

.service_info-description h3 {
    font-size: 18px;
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.service_info-description p {
    line-height: 1.8;
    color: #555;
    margin-bottom: 15px;
    text-align: justify;
}

.service_info-comments-section {
    background: var(--white);
    border-radius: 8px;
    padding: 20px;
    box-shadow: var(--box-shadow);
    margin-bottom: 30px;
}

.service_info-comments-title {
    font-size: 20px;
    margin: 0 0 20px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.service_info-comments-list {
    margin-bottom: 20px;
}

.service_info-comment-item {
    border-bottom: 1px solid #f0f0f0;
    padding: 15px 0;
}

.service_info-comment-item:last-child {
    border-bottom: none;
}

.service_info-comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.service_info-comment-author {
    font-weight: bold;
    color: #333;
}

.service_info-comment-rating {
    direction: ltr;
}

.service_info-comment-star {
    color: #ffc107;
}

.service_info-comment-content p {
    margin: 0;
    line-height: 1.6;
    color: #555;
}

.service_info-comment-date {
    font-size: 12px;
    color: #999;
    margin-top: 10px;
}

.service_info-no-comments {
    text-align: center;
    padding: 30px;
    color: #999;
}

.service_info-more-comments {
    text-align: center;
    margin-top: 20px;
}

.service_info-more-btn {
    padding: 10px 25px;
}

/* comment */

.user-comment-section {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 20px;
}

.comment-form-container {
    background: #fff;
    padding: 2.5rem;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.comment-form-container:hover {
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
}

.comment-form-container h3 {
    margin-bottom: 1.8rem;
    margin-top: 0.5rem;
    color: #2d3748;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
}


.rating-input {
    margin-bottom: 1.5rem;
    text-align: center;
}

.rating-input label {
    display: block;
    margin-bottom: 0.8rem;
    font-weight: 600;
    color: #4a5568;
    font-size: 1.1rem;
}

.stars-input {
    display: flex;
    justify-content: center;
    gap: 5px;
    direction: ltr;
}

.stars-input i {
    font-size: 1.5rem;
    color: #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
}

.stars-input i:hover,
.stars-input i.active {
    color: #ffc107;
    transform: scale(1.15);
}


.comment-textarea-container {
    margin-bottom: 1.5rem;
}


.comment-textarea {
    width: 100%;
    min-height: 140px;
    padding: 1.2rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    resize: none;
    font-family: inherit;
    font-size: 1rem;
    line-height: 1.6;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.comment-textarea:focus {
    border-color: #4e54c8;
    outline: none;
    box-shadow: 0 0 0 3px rgba(78, 84, 200, 0.1);
}

.form-submit {
    text-align: center;
}

.comment-submit-btn {
    background: linear-gradient(135deg, #4e54c8, #8f94fb);
    color: white;
    border: none;
    padding: 14px 36px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(78, 84, 200, 0.25);
}

.comment-submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(78, 84, 200, 0.35);
}

.comment-submit-btn:active {
    transform: translateY(-1px);
}


.login-to-comment {
    text-align: center;
    padding: 2.5rem;
    background: #f8f9fa;
    border-radius: 16px;
    border: 1px dashed #d2d6dc;
    color: #718096;
}

.login-to-comment a {
    color: #4e54c8;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.login-to-comment a:hover {
    color: #3b42b5;
    text-decoration: underline;
}


.comment-message {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    font-weight: 500;
    text-align: center;
}

.comment-success {
    background-color: #f0fff4;
    color: #2f855a;
    border: 1px solid #c6f6d5;
}

.comment-error {
    background-color: #fff5f5;
    color: #c53030;
    border: 1px solid #fed7d7;
}


/* رسپانسیو برای موبایل */
@media (max-width: 768px) {
    .service_info-main {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 15px;
    }
    
    .service_info-image {
        height: 250px;
    }
    
    .service_info-title {
        font-size: 20px;
    }
    
    .service_info-rating {
        flex-wrap: wrap;
    }
    
    .service_info-final-price {
        font-size: 20px;
    }
    
    .service_info-comment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .user-comment-section {
        padding: 0 15px;
        margin: 2rem auto;
    } 
    
    .comment-form-container {
        padding: 1.3rem;
    }
    
    .stars-input i {
        font-size: 1.3rem;
    }    
}
</style>


<script>
jQuery(document).ready(function($) {
    const slider = $('.testimonials-slider');
    const items = $('.testimonial-item');
    
    if (items.length > 0) {
        
        // محاسبه عرض هر آیتم و فضای بین آنها
        const itemStyle = window.getComputedStyle(items[0]);
        const itemWidth = items[0].offsetWidth + 
                         parseInt(itemStyle.marginLeft) + 
                         parseInt(itemStyle.marginRight);
        
        let currentIndex = 0;
        let autoScroll;
        
        function startAutoScroll() {
            autoScroll = setInterval(function() {
                if (currentIndex < items.length - 1) {
                    currentIndex++;
                } else {
                    currentIndex = 0;
                }
                scrollToSlide(currentIndex);
            }, 5000);
        }
        
        function safeScrollTo(element, position) {
            // ذخیره موقعیت اسکرول عمودی فعلی
            const currentVerticalScroll = window.pageYOffset || document.documentElement.scrollTop;
            
            // انجام اسکرول افقی
            element.animate({
                scrollLeft: position
            }, 10);
            
            // بازگرداندن موقعیت اسکرول عمودی به حالت قبلی
            window.scrollTo(0, currentVerticalScroll);
        }
        
        // و در تابع scrollToSlide از آن استفاده کنید:
        function scrollToSlide(index) {
            const slide = items.eq(index);
            const position = slide.offset().left - slider.offset().left + slider.scrollLeft() - 15;
            
            safeScrollTo(slider, position);
        }
        
        // شروع اسلایدشو اتوماتیک
        startAutoScroll();
        
        // توقف اسکرول خودکار هنگام هاور
        slider.hover(
            function() {
                clearInterval(autoScroll);
            },
            function() {
                startAutoScroll();
            }
        );
        
        // اضافه کردن قابلیت درگ برای موبایل
        let isDown = false;
        let startX;
        let scrollLeft;
        
        slider.on('mousedown', function(e) {
            isDown = true;
            startX = e.pageX - slider.offset().left;
            scrollLeft = slider.scrollLeft();
            clearInterval(autoScroll);
        });
        
        slider.on('mouseleave', function() {
            isDown = false;
        });
        
        slider.on('mouseup', function() {
            isDown = false;
        });
        
        slider.on('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offset().left;
            const walk = (x - startX) * 2;
            slider.scrollLeft(scrollLeft - walk);
        });
        
        // تشخیص اسکرول لمسی برای دستگاه‌های موبایل
        slider.on('touchstart', function(e) {
            startX = e.originalEvent.touches[0].pageX - slider.offset().left;
            scrollLeft = slider.scrollLeft();
            clearInterval(autoScroll);
        });
        
        slider.on('touchmove', function(e) {
            if (!startX) return;
            const x = e.originalEvent.touches[0].pageX - slider.offset().left;
            const walk = (x - startX) * 2;
            slider.scrollLeft(scrollLeft - walk);
        });
    }
});

jQuery(document).ready(function($) {
    // مدیریت ستاره‌های امتیازدهی
    $('.stars-input i').on('click', function(e) {
        const stars = $(this).parent().find('i');
        const rating = parseInt($(this).data('value'));
        
        stars.removeClass('active');
        stars.each(function() {
            if (parseInt($(this).data('value')) <= rating) {
                $(this).addClass('active');
            }
        });
        
        $(this).closest('.rating-input').find('input[name="rating"]').val(rating);
    });
});

jQuery(document).ready(function($) {

    
    // اعتبارسنجی فرم قبل از ارسال
    function validateCommentForm() {
        let isValid = true;
        
        // حذف پیام خطای قبلی اگر وجود دارد
        $('.comment-message').remove();
        
        //const selectedService = $('#selected-service-id').val();
        const selectedService = "<?php echo get_query_var('service_id'); ?>";
        const commentText = $('.comment-textarea').val().trim();
        const rating = $('input[name="rating"]').val();
        

        
        // اعتبارسنجی متن نظر
        if (!commentText) {
            const messageEl = $('<div class="comment-message comment-error">لطفاً متن نظر خود را وارد کنید.</div>');
            $('.comment-form-container').prepend(messageEl);
            isValid = false;
        }
        
        // اعتبارسنجی امتیاز
        if (!rating || rating < 1) {
            const messageEl = $('<div class="comment-message comment-error">لطفاً امتیاز دهید.</div>');
            $('.comment-form-container').prepend(messageEl);
            isValid = false;
        }
        
        return isValid;
    }
    
    $('.service-comment-form').on('submit', function(e) {
        e.preventDefault();
        
        // حذف تمام پیام‌های قبلی قبل از اعتبارسنجی جدید
        $('.comment-message').remove();
        
        // اعتبارسنجی فرم
        if (!validateCommentForm()) {
            return;
        }

        const form = $(this);
        //const selectedService = $('#selected-service-id').val();
        const selectedService = "<?php echo get_query_var('service_id'); ?>";
        const commentText = form.find('.comment-textarea').val().trim();
        const rating = form.find('input[name="rating"]').val();
        const submitBtn = form.find('.comment-submit-btn');
        
        submitBtn.prop('disabled', true).text('در حال ثبت...');

        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'submit_service_comment',
                security: '<?php echo wp_create_nonce("service_comment_nonce"); ?>',
                service_id: selectedService,
                comment_text: commentText,
                rating: rating
            },
            success: function(response) {
                if (response.success) {
                    // نمایش پیام موفقیت
                    const messageEl = $('<div class="comment-message comment-success">' + response.data + '</div>');
                    $('.comment-form-container').prepend(messageEl);
                    
                    // ریست فرم
                    form.find('.comment-textarea').val('');
                    form.find('input[name="rating"]').val('0');
                    form.find('.stars-input i').removeClass('active');
                    $('.service-selection-card').removeClass('selected');
                    $('#selected-service-id').val('');
                    
                    // اسکرول به بالای فرم
                    $('html, body').animate({
                        scrollTop: $('.comment-form-container').offset().top - 100
                    }, 500);
                } else {
                    // نمایش پیام خطا
                    const messageEl = $('<div class="comment-message comment-error">' + response.data + '</div>');
                    $('.comment-form-container').prepend(messageEl);
                }
            },
            error: function() {
                // نمایش پیام خطای سرور
                const messageEl = $('<div class="comment-message comment-error">خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.</div>');
                $('.comment-form-container').prepend(messageEl);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('ثبت نظر');
            }
        });
    });
    
    // اضافه کردن event listener برای تغییرات در فرم که پیام‌های خطا را پاک کند
    $('.comment-textarea, .stars-input i').on('input change', function() {
        // اگر کاربر شروع به تایپ کرد یا امتیاز تغییر کرد، پیام‌های خطا را پاک کن
        $('.comment-message').remove();
    });
});
</script>

<?php
get_footer();