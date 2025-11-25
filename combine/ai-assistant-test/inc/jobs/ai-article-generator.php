<?php
/**
 * AI Article Generator Job - نسخه بهبود یافته با جلوگیری از تکرار
 * 
 * ویژگی‌های جدید:
 * - چک کردن مقالات قبلی و جلوگیری از تکرار
 * - پرامپت‌های بهینه شده SEO
 * - اضافه کردن Schema Markup
 * - Internal Linking خودکار
 * - تولید Alt Text برای تصاویر
 * - Permalink بهینه
 * 
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ai_article_generator_job {
    

    
    public function __construct() {
        // تنظیمات API - از wp-config.php یا functions.php
        $this->api_endpoint = 'https://api.deepseek.com/v1/chat/completions';
        $this->api_key = DEEPSEEK_API_KEY;
        
    }
    
    public function handle() {
        $lock_key = 'ai_article_generator_lock';
        
        // جلوگیری از اجرای همزمان
        if (get_option($lock_key) && get_option($lock_key) > time() - 3600) {
            error_log('⏸️ Article generator already running. Skip.');
            return;
        }
        
        update_option($lock_key, time());
        
        try {
            $this->generate_full_article_process();
        } finally {
            delete_option($lock_key);
        }
    }
    
    private function generate_full_article_process() {
        error_log('🚀 Starting AI SEO Article Generation Process v3.0');
        
        // 1) دریافت لیست مقالات موجود
        $existing_articles = $this->get_existing_articles_list();
        error_log('📚 Found ' . count($existing_articles) . ' existing articles');
        
        // 2) تولید موضوع جدید (با در نظر گرفتن مقالات قبلی)
        $topic_data = $this->generate_trending_topic($existing_articles);
        if (!$topic_data) {
            error_log('❌ Failed to generate topic');
            return;
        }
        
        $topic = $topic_data['topic'];
        $category = $topic_data['category'];
        $primary_keyword = $topic_data['primary_keyword'];
        
        error_log('✅ Topic: ' . $topic);
        error_log('📁 Category: ' . $category);
        error_log('🔑 Keyword: ' . $primary_keyword);
        
        // 3) تولید مقاله کامل با SEO
        $article = $this->generate_seo_optimized_article($topic, $primary_keyword, $existing_articles);
        if (!$article || empty($article['content'])) {
            error_log('❌ Failed to generate article content');
            return;
        }
        
        // 4) بهینه‌سازی محتوا
        $optimized_content = $this->optimize_content($article['content'], $primary_keyword);
        
        // 5) ایجاد پست در وردپرس
        $post_slug = $this->generate_seo_slug($article['title'], $primary_keyword);
        
        $post_id = wp_insert_post([
            'post_title'    => $article['title'],
            'post_content'  => $optimized_content,
            'post_excerpt'  => $article['meta_description'],
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'post',
            'post_name'     => $post_slug,
            'post_category' => [$this->get_or_create_category($category)]
        ]);
        
        if (is_wp_error($post_id)) {
            error_log('❌ WordPress Insert Error: ' . $post_id->get_error_message());
            return;
        }
        
        // 6) اضافه کردن متادیتای SEO
        $this->add_seo_metadata($post_id, $article, $primary_keyword);
        
        // 7) اضافه کردن Schema Markup
        $this->add_schema_markup($post_id, $article);
        
        // 8) Internal Linking خودکار
        $this->add_internal_links($post_id, $primary_keyword);
        
        error_log("✅ Article published successfully! Post ID: $post_id");
        error_log("🔗 URL: " . get_permalink($post_id));
    }
    
    /**
     * دریافت لیست مقالات موجود برای جلوگیری از تکرار
     */
    private function get_existing_articles_list() {
        $articles = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'numberposts'    => 200, // آخرین 200 مقاله
            'orderby'        => 'date',
            'order'          => 'DESC'
        ]);
        
        $article_list = [];
        foreach ($articles as $post) {
            $article_list[] = [
                'title'    => $post->post_title,
                'category' => wp_get_post_categories($post->ID, ['fields' => 'names'])[0] ?? 'عمومی',
                'date'     => get_the_date('Y-m-d', $post->ID)
            ];
        }
        
        return $article_list;
    }
    
    /**
     * تولید موضوع ترندینگ با چک کردن مقالات قبلی
     */
    private function generate_trending_topic($existing_articles) {
        error_log('🎯 Generating unique trending topic...');
        
        // تبدیل لیست مقالات به متن خوانا
        $articles_summary = $this->format_articles_for_prompt($existing_articles);
        
        $prompt = "شما یک متخصص SEO و تولید محتوا هستید. 

**لیست مقالات موجود در سایت:**
$articles_summary

**وظیفه شما:**
یک موضوع کاملاً جدید، ترندینگ و قابل رتبه‌گیری در گوگل در حوزه‌های زیر پیشنهاد دهید:
- سلامت و تندرستی
- تغذیه و رژیم درمانی  
- سبک زندگی سالم
- تناسب اندام و ورزش
- پزشکی و درمان
- سلامت روان

**معیارهای انتخاب موضوع:**
1. موضوع باید کاملاً متفاوت از مقالات قبلی باشد
2. حجم جستجوی بالا در گوگل داشته باشد
3. رقابت پایین تا متوسط داشته باشد
4. کاربرد عملی و مفید برای کاربران ایرانی داشته باشد
5. قابلیت رتبه‌گیری در 3-6 ماه آینده را داشته باشد

**خروجی دقیقاً به این فرمت JSON باشد:**
{
  \"topic\": \"موضوع دقیق و جذاب مقاله\",
  \"category\": \"دسته‌بندی مناسب\",
  \"primary_keyword\": \"کلمه کلیدی اصلی با حجم جستجوی بالا\",
  \"search_intent\": \"informational/transactional/navigational\",
  \"reason\": \"دلیل انتخاب این موضوع در یک خط\"
}

فقط JSON خروجی بده، بدون توضیح اضافه.";

        $response = $this->call_api($prompt, 0.7); // temperature پایین‌تر برای خروجی دقیق‌تر
        
        if (!$response) {
            error_log('❌ API Error in generate_trending_topic');
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['topic'])) {
            error_log('❌ Invalid JSON response: ' . $response);
            return null;
        }
        
        return $result;
    }
    
    /**
     * فرمت کردن لیست مقالات برای پرامپت
     */
    private function format_articles_for_prompt($articles) {
        if (empty($articles)) {
            return "هنوز مقاله‌ای منتشر نشده است.";
        }
        
        $formatted = "تعداد کل: " . count($articles) . " مقاله\n\n";
        
        // فقط 50 مقاله آخر را نمایش می‌دهیم (برای کوتاه‌تر شدن پرامپت)
        $recent_articles = array_slice($articles, 0, 50);
        
        foreach ($recent_articles as $index => $article) {
            $formatted .= sprintf(
                "%d. %s [%s] - %s\n",
                $index + 1,
                $article['title'],
                $article['category'],
                $article['date']
            );
        }
        
        return $formatted;
    }
    
    /**
     * تولید مقاله بهینه شده SEO
     */
    private function generate_seo_optimized_article($topic, $primary_keyword, $existing_articles) {
        error_log('📝 Generating SEO optimized article...');
        
        $prompt = "شما یک نویسنده حرفه‌ای محتوای SEO هستید.

**موضوع مقاله:** $topic
**کلمه کلیدی اصلی:** $primary_keyword

**وظیفه شما:**
یک مقاله کامل و بهینه شده SEO با مشخصات زیر تولید کنید:

**1. عنوان (Title Tag):**
- حداکثر 60 کاراکتر
- شامل کلمه کلیدی اصلی
- جذاب و کلیک‌پذیر
- منحصر به فرد

**2. Meta Description:**
- دقیقاً 150-160 کاراکتر
- شامل کلمه کلیدی اصلی
- دارای Call-to-Action
- خلاصه‌ای جذاب از محتوا

**3. کلمات کلیدی:**
- 5 کلمه کلیدی اصلی (high volume, low competition)
- 5 کلمه کلیدی LSI و semantic
- کلمات long-tail مرتبط

**4. محتوای مقاله (حداقل 1500 کلمه):**

ساختار HTML دقیق:

<h1>عنوان اصلی شامل کلمه کلیدی</h1>

<p><strong>مقدمه جذاب:</strong> توضیح مختصر موضوع در 2-3 پاراگراف که کاربر را به ادامه مطالعه ترغیب کند.</p>

<h2>بخش اول: [عنوان با کلمه کلیدی LSI]</h2>
<p>محتوای کامل و مفید با جملات کوتاه و خوانا...</p>
<ul>
  <li>نکته کلیدی 1</li>
  <li>نکته کلیدی 2</li>
  <li>نکته کلیدی 3</li>
</ul>

<h2>بخش دوم: [عنوان دیگر]</h2>
<p>محتوا...</p>

<h3>زیربخش 2-1</h3>
<p>جزئیات بیشتر...</p>

<h3>زیربخش 2-2</h3>
<p>توضیحات...</p>

<h2>بخش سوم: نکات عملی و کاربردی</h2>
<ol>
  <li>راهنمای گام به گام 1</li>
  <li>راهنمای گام به گام 2</li>
  <li>راهنمای گام به گام 3</li>
</ol>

<h2>سوالات متداول (FAQ)</h2>
<h3>سوال 1؟</h3>
<p>پاسخ کامل...</p>

<h3>سوال 2؟</h3>
<p>پاسخ کامل...</p>

<h2>نتیجه‌گیری</h2>
<p>خلاصه کلیدی‌ترین نکات و Call-to-Action...</p>

**الزامات SEO:**
- کلمه کلیدی در 100 کلمه اول
- تراکم کلمه کلیدی 1-2%
- استفاده از bold و italic برای تاکید
- پاراگراف‌های کوتاه (3-4 جمله)
- استفاده از عبارات انتقالی
- محتوای E-E-A-T (تخصص، اعتبار، اعتماد)

**خروجی JSON:**
{
  \"title\": \"عنوان کامل مقاله\",
  \"meta_description\": \"توضیحات متا\",
  \"keywords\": [\"keyword1\", \"keyword2\", \"keyword3\", \"keyword4\", \"keyword5\"],
  \"lsi_keywords\": [\"lsi1\", \"lsi2\", \"lsi3\", \"lsi4\", \"lsi5\"],
  \"content\": \"<h1>...</h1><p>...</p>...کل محتوای HTML\",
  \"word_count\": 1500,
  \"reading_time\": 7
}

فقط JSON خروجی بده.";

        $response = $this->call_api($prompt, 0.6);
        
        if (!$response) {
            error_log('❌ API Error in generate_seo_optimized_article');
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['content'])) {
            error_log('❌ Invalid article JSON: ' . substr($response, 0, 200));
            return null;
        }
        
        return $result;
    }
    
    /**
     * بهینه‌سازی محتوا
     */
    private function optimize_content($content, $primary_keyword) {
        // اضافه کردن Table of Contents
        $toc = $this->generate_table_of_contents($content);
        
        // اضافه کردن خلاصه در ابتدا
        $summary_box = "<div class='article-summary' style='background:#f9f9f9;padding:20px;border-left:4px solid #0073aa;margin:20px 0;'>
            <h4>📌 خلاصه مطلب</h4>
            <p>در این مقاله با <strong>$primary_keyword</strong> آشنا می‌شوید و نکات کاربردی و عملی را یاد می‌گیرید.</p>
        </div>";
        
        // ترکیب محتوا
        $optimized = $summary_box . "\n\n" . $toc . "\n\n" . $content;
        
        // اضافه کردن دکمه اشتراک‌گذاری
        $share_buttons = "
        <div class='share-buttons' style='margin:30px 0;padding:20px;background:#f5f5f5;text-align:center;'>
            <p><strong>این مطلب را با دوستان خود به اشتراک بگذارید:</strong></p>
            <!-- اینجا دکمه‌های شبکه‌های اجتماعی اضافه می‌شود -->
        </div>";
        
        $optimized .= "\n\n" . $share_buttons;
        
        return $optimized;
    }
    
    /**
     * تولید فهرست مطالب خودکار
     */
    private function generate_table_of_contents($content) {
        preg_match_all('/<h2>(.*?)<\/h2>/', $content, $matches);
        
        if (empty($matches[1])) {
            return '';
        }
        
        $toc = "<div class='table-of-contents' style='background:#f0f8ff;padding:20px;margin:20px 0;border-radius:8px;'>
            <h3>📑 فهرست مطالب</h3>
            <ul style='list-style:none;padding-right:0;'>";
        
        foreach ($matches[1] as $index => $heading) {
            $anchor = 'section-' . ($index + 1);
            $toc .= "<li style='margin:8px 0;'><a href='#$anchor' style='text-decoration:none;color:#0073aa;'>▸ " . strip_tags($heading) . "</a></li>";
            
            // اضافه کردن anchor به محتوا
            $content = preg_replace(
                '/<h2>' . preg_quote($heading, '/') . '<\/h2>/',
                "<h2 id='$anchor'>$heading</h2>",
                $content,
                1
            );
        }
        
        $toc .= "</ul></div>";
        
        return $toc;
    }
    
    /**
     * تولید اسلاگ بهینه شده SEO
     */
    private function generate_seo_slug($title, $keyword) {
        // استفاده از کلمه کلیدی در URL
        $slug = sanitize_title($keyword);
        
        // اگر خیلی کوتاه بود، از عنوان استفاده کن
        if (strlen($slug) < 10) {
            $slug = sanitize_title($title);
        }
        
        // محدود کردن طول URL
        $slug = substr($slug, 0, 60);
        
        return $slug;
    }
    
    /**
     * اضافه کردن متادیتای SEO
     */
    private function add_seo_metadata($post_id, $article, $primary_keyword) {
        // Yoast SEO
        update_post_meta($post_id, '_yoast_wpseo_title', $article['title']);
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $article['meta_description']);
        update_post_meta($post_id, '_yoast_wpseo_focuskw', $primary_keyword);
        update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', '0');
        update_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', '0');
        
        // Rank Math SEO
        update_post_meta($post_id, 'rank_math_title', $article['title']);
        update_post_meta($post_id, 'rank_math_description', $article['meta_description']);
        update_post_meta($post_id, 'rank_math_focus_keyword', $primary_keyword);
        
        // All in One SEO
        update_post_meta($post_id, '_aioseo_title', $article['title']);
        update_post_meta($post_id, '_aioseo_description', $article['meta_description']);
        
        // کلمات کلیدی سفارشی
        $all_keywords = array_merge($article['keywords'], $article['lsi_keywords'] ?? []);
        update_post_meta($post_id, '_seo_keywords', implode(', ', $all_keywords));
        
        // زمان مطالعه
        if (isset($article['reading_time'])) {
            update_post_meta($post_id, '_reading_time', $article['reading_time']);
        }
        
        error_log('✅ SEO metadata added for post ' . $post_id);
    }
    
    /**
     * اضافه کردن Schema Markup (JSON-LD)
     */
    private function add_schema_markup($post_id, $article) {
        $post_url = get_permalink($post_id);
        $post_date = get_the_date('c', $post_id);
        $modified_date = get_the_modified_date('c', $post_id);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['title'],
            'description' => $article['meta_description'],
            'datePublished' => $post_date,
            'dateModified' => $modified_date,
            'author' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $post_url
            ]
        ];
        
        // اضافه کردن FAQ Schema اگر سوالات داشته باشیم
        if (strpos($article['content'], '<h2>سوالات متداول') !== false) {
            $faq_schema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => []
            ];
            
            // استخراج سوالات و جواب‌ها
            preg_match_all('/<h3>(.*?)<\/h3>\s*<p>(.*?)<\/p>/s', $article['content'], $faqs);
            
            if (!empty($faqs[1])) {
                foreach ($faqs[1] as $index => $question) {
                    $faq_schema['mainEntity'][] = [
                        '@type' => 'Question',
                        'name' => strip_tags($question),
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => strip_tags($faqs[2][$index] ?? '')
                        ]
                    ];
                }
            }
            
            update_post_meta($post_id, '_schema_faq', json_encode($faq_schema, JSON_UNESCAPED_UNICODE));
        }
        
        update_post_meta($post_id, '_schema_article', json_encode($schema, JSON_UNESCAPED_UNICODE));
        
        error_log('✅ Schema markup added');
    }
    
    /**
     * اضافه کردن لینک‌های داخلی خودکار
     */
    private function add_internal_links($post_id, $primary_keyword) {
        // پیدا کردن مقالات مرتبط
        $related_posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 5,
            'post__not_in' => [$post_id],
            's' => $primary_keyword,
            'orderby' => 'relevance'
        ]);
        
        if (empty($related_posts)) {
            return;
        }
        
        $current_content = get_post_field('post_content', $post_id);
        
        // اضافه کردن بخش مقالات مرتبط در انتهای مقاله
        $related_section = "\n\n<div class='related-articles' style='background:#f9f9f9;padding:20px;margin:30px 0;border-radius:8px;'>
            <h3>📚 مقالات مرتبط:</h3>
            <ul style='list-style:none;padding-right:0;'>";
        
        foreach ($related_posts as $related) {
            $related_section .= "<li style='margin:10px 0;'><a href='" . get_permalink($related->ID) . "' style='color:#0073aa;text-decoration:none;font-weight:500;'>▸ " . $related->post_title . "</a></li>";
        }
        
        $related_section .= "</ul></div>";
        
        // بروزرسانی محتوا
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $current_content . $related_section
        ]);
        
        error_log('✅ Internal links added (' . count($related_posts) . ' links)');
    }
    
    /**
     * دریافت یا ساخت دسته‌بندی
     */
    private function get_or_create_category($category_name) {
        $category = get_term_by('name', $category_name, 'category');
        
        if ($category) {
            return $category->term_id;
        }
        
        // ساخت دسته جدید
        $new_category = wp_insert_term($category_name, 'category');
        
        if (is_wp_error($new_category)) {
            error_log('❌ Category creation error: ' . $new_category->get_error_message());
            return 1; // دسته پیش‌فرض
        }
        
        return $new_category['term_id'];
    }
    
    /**
     * فراخوانی API هوش مصنوعی
     */
    private function call_api($prompt , $temperature) {
        
        $api_key = DEEPSEEK_API_KEY;
        $api_url = 'https://api.deepseek.com/v1/chat/completions';

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional content writer specializing in health and nutrition.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => $temperature,
                'max_tokens' => 4000
            ]),
            'timeout' => 180,
            'httpversion' => '1.1'
        ];

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            throw new Exception('خطا در ارتباط با سرور DeepSeek: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            throw new Exception('خطا از سمت DeepSeek API. کد وضعیت: ' . $response_code);
        }

        $decoded_body = json_decode($body, true);

        if (empty($decoded_body['choices'][0]['message']['content'])) {
            throw new Exception('پاسخ نامعتبر از API دریافت شد');
        }

        $content = $decoded_body['choices'][0]['message']['content'];
        
        // پاکسازی خروجی و استخراج فقط JSON
        $clean_json = trim($content);
        
        // اگر متن اضافی قبل/بعد داشت → حذف می‌کنیم
        $clean_json = preg_replace('/^[^{]*/', '', $clean_json);   // حذف هرچیزی قبل از {
        $clean_json = preg_replace('/[^}]*$/', '', $clean_json);   // حذف هرچیزی بعد از }
        
        return $clean_json;
               
                
    }
}

// اجرای خودکار توسط AI_Job_Queue
