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
            
            // گرفتن امتیاز متوسط از دیتابیس نظرات
            $comments_db = AI_Assistant_Comments_DB::get_instance();
            $average_rating = $comments_db->get_average_rating($service_id);
            $average_rating = $average_rating ? round($average_rating, 1) : 0;
        ?>
        <div class="main-ai-service-card-wrapper">
            <a href="<?php echo esc_url(home_url('/service/' . $service_id . '/')); ?>" class="main-ai-service-card" target="_blank">
                <div class="main-ai-service-info">
                    <h3><?php echo esc_html($service['name']); ?></h3>
                    <p><?php echo esc_html($service['description']); ?></p>
                </div>
                <div class="main-ai-service-image" style="background-image: url('<?= $theme_assets ?>/assets/images/<?= $service_id ?>.jpg')">
                    <!-- اضافه کردن نشان امتیاز -->
                    <?php if ($average_rating > 0): ?>
                    <div class="service-rating-badge">
                        <span class="rating-value"><?php echo esc_html($average_rating); ?></span>
                        <span class="rating-star">★</span>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            
            <!-- آیکن اطلاعات سرویس -->
            <a href="<?php echo esc_url(home_url('/service-info/' . $service_id . '/')); ?>" class="service-info-icon" title="اطلاعات سرویس">
                <i class="fas fa-info-circle"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div><!-- .ai-services-grid -->



<!-- Testimonials Slider Section -->
<div class="testimonials-section">
    <div class="testimonials-slider">
        <?php
        // دریافت نظرات تأیید شده از همه سرویس‌ها
        global $wpdb;
        $table_name = $wpdb->prefix . 'service_comments';
        $all_comments = $wpdb->get_results(
            "SELECT c.*, u.user_login, u.display_name 
             FROM {$table_name} c 
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
             WHERE c.status = 'approved' 
             ORDER BY c.created_at DESC 
             LIMIT 10"
        );
        
        if ($all_comments) :
            foreach ($all_comments as $comment) :
                $service_name = isset($services[$comment->service_id]) ? 
                    $services[$comment->service_id]['name'] : $comment->service_id;
        ?>
        <div class="testimonial-item">
            <div class="testimonial-rating">
                <?php 
                $rating = intval($comment->rating);
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $rating) {
                        echo '<span class="star">★</span>';
                    } else {
                        echo '<span class="star">☆</span>';
                    }
                }
                ?>
            </div>
            <div class="testimonial-content">
                <p class="testimonial-text"><?php echo esc_html($comment->comment_text); ?></p>
            </div>
            <div class="testimonial-author">
                <span class="author-name"><?php echo esc_html($comment->display_name ?: $comment->user_login); ?></span>
                <span class="service-name">- <?php echo esc_html($service_name); ?></span>
            </div>
        </div>
        <?php endforeach; else : ?>
        <div class="no-testimonials">
            <p>هنوز نظری ثبت نشده است</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- بعد از اسلایدر نظرات، فرم ثبت نظر را اضافه کنید -->
<div class="user-comment-section">
    <?php if (is_user_logged_in()) : ?>
        <div class="comment-form-container">
            <h3>ثبت نظر جدید</h3>
            <form class="service-comment-form" method="post">
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

<style>
.main-ai-service-image {
    position: relative;
}

.service-rating-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.6);
    padding: 5px 8px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    color: #ffc107;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(5px);
}

.rating-star {
    color: #ffc107;
    font-size: 14px;
}

.rating-value {
    font-weight: bold;
}


/* استایل جدید برای بخش نظرات */
.testimonials-section {
    max-width: 1200px;
    margin: 2rem auto;
    margin-bottom: 0px;
    padding: 0px 20px;
    box-sizing: border-box;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.testimonials-section::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f5f5f5, #c9c8c8, #f5f5f5)
}

.testimonials-slider {
    display: flex;
    overflow-x: auto;
    gap: 1.5rem;
    padding: 1.5rem 0.5rem;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.testimonials-slider::-webkit-scrollbar {
    display: none;
}

.testimonial-item {
    flex: 0 0 340px;
    background: white;
    padding: 2rem 1.5rem;
    border-radius: 6px;
    scroll-snap-align: start;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    border: none;
}

.testimonial-item::before {
    content: """;
    position: absolute;
    top: 15px;
    left: 20px;
    font-size: 5rem;
    color: rgba(78, 84, 200, 0.08);
    font-family: Georgia, serif;
    line-height: 1;
}

.testimonial-rating {
    text-align: center;
    margin-bottom: 0.7rem;
    color: #ffc107;
    font-size: 1.4rem;
    display: flex;
    justify-content: center;
    gap: 3px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.testimonial-content {
    margin-bottom: 0.7rem;
    position: relative;
    z-index: 1;
}

.testimonial-text {
    font-size: 1.05rem;
    line-height: 1.7;
    color: #555;
    text-align: center;
    margin: 0;
    font-style: italic;
    position: relative;
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
}

.testimonial-author {
    text-align: center;
    font-size: 0.95rem;
    color: #777;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 0.7rem;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.author-name {
    font-weight: 700;
    color: #4e54c8;
    margin-bottom: 5px;
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
}

.service-name {
    font-style: italic;
    color: #6c757d;
    font-size: 0.85rem;
}

.no-testimonials {
    text-align: center;
    color: #999;
    padding: 3rem;
    font-style: italic;
    font-size: 1.1rem;
}

/* استایل برای نشانگرهای اسلاید (در صورت نیاز) */
.testimonial-indicators {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 1.5rem;
}

.testimonial-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
}

.testimonial-indicator.active {
    background: #4e54c8;
    transform: scale(1.2);
    box-shadow: 0 0 8px rgba(78, 84, 200, 0.5);
}

/* رسپانسیو */
@media (max-width: 768px) {
    .testimonial-item {
        flex: 0 0 calc(100% - 2rem);
        padding: 1rem 0.5rem;
    }
    
    .testimonials-section {
        margin: 1rem auto;
        margin-bottom: 0px;
        padding: 0px 15px;
        border-radius: 12px;
    }
    
    .testimonials-slider {
        gap: 1rem;
        padding: 1rem 0.5rem;
    }
}

.user-comment-section {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 20px;
}

.comment-form-container {
    background: #fff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.comment-form-container h3 {
    margin-bottom: 1.5rem;
    color: #333;
    text-align: center;
}

.rating-input {
    margin-bottom: 1.5rem;
    text-align: center;
}

.rating-input label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #555;
}

.stars-input {
    display: flex;
    justify-content: center;
    gap: 5px;
    direction: ltr;
}

.stars-input i {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.stars-input i:hover,
.stars-input i.active {
    color: #ffc107;
}

.comment-textarea-container {
    margin-bottom: 1.5rem;
}

.comment-textarea {
    width: 90%;
    min-height: 120px;
    padding: 5%;
    border: 1px solid #ddd;
    border-radius: 8px;
    resize: vertical;
    font-family: inherit;
    transition: border-color 0.3s;
}

.comment-textarea:focus {
    border-color: #4e54c8;
    outline: none;
}

.form-submit {
    text-align: center;
}

.comment-submit-btn {
    background: linear-gradient(135deg, #4e54c8, #8f94fb);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.comment-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
}

.login-to-comment {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px dashed #dee2e6;
}

.login-to-comment a {
    color: #4e54c8;
    font-weight: 600;
    text-decoration: none;
}

.login-to-comment a:hover {
    text-decoration: underline;
}

/* رسپانسیو */
@media (max-width: 768px) {
    .user-comment-section {
        padding: 0 15px;
    }
    
    .comment-form-container {
        padding: 1.5rem;
    }
    
    .stars-input i {
        font-size: 1.7rem;
    }
}

.comment-message {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-weight: 500;
}

.comment-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.comment-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
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
    
    // ارسال فرم نظر
    $('.service-comment-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const commentText = form.find('.comment-textarea').val().trim();
        const rating = form.find('input[name="rating"]').val();
        
        if (!commentText) {
            alert('لطفاً متن نظر خود را وارد کنید.');
            return;
        }
        
        if (!rating || rating < 1) {
            alert('لطفاً امتیاز دهید.');
            return;
        }

        const submitBtn = form.find('.comment-submit-btn');
        submitBtn.prop('disabled', true).text('در حال ثبت...');

        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'submit_service_comment',
                security: '<?php echo wp_create_nonce("service_comment_nonce"); ?>',
                service_id: 'general', // یا می‌توانید یک service_id مناسب تعیین کنید
                comment_text: commentText,
                rating: rating
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    form.find('.comment-textarea').val('');
                    form.find('input[name="rating"]').val('0');
                    form.find('.stars-input i').removeClass('active');
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('ثبت نظر');
            }
        });
    });
});
</script>

<?php
get_footer(); 