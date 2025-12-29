<?php
/**
 * AI Article Generator v5.2 - PRODUCTION READY
 * 
 * ✅ نسخه نهایی - تمام مشکلات حل شده:
 * 1. JSON extraction از backticks
 * 2. فارسی handling
 * 3. Timeout handling
 * 4. دیباگ کامل
 * 5. Required fields validation
 */

class ai_article_generator_job {

    private $api_endpoint = 'https://api.deepseek.com/v1/chat/completions';
    private $api_key;
    private $lock_key = 'ai_article_generator_lock';
    private $generation_log_file;

    private $trusted_sources = [
        'WHO' => 'https://www.who.int/',
        'NIH' => 'https://www.nih.gov/',
        'Harvard Nutrition' => 'https://www.hsph.harvard.edu/nutritionsource/',
        'Mayo Clinic' => 'https://www.mayoclinic.org/',
        'Cleveland Clinic' => 'https://health.clevelandclinic.org/',
        'Johns Hopkins' => 'https://www.hopkinsmedicine.org/',
        'Stanford Health' => 'https://stanfordhealthcare.org/',
        'CDC Nutrition' => 'https://www.cdc.gov/nutrition/'
    ];

    private $pillars = [
        [
            'key' => 'macronutrients',
            'title' => 'مواد مغذی بزرگ (ماکروها)',
            'slug' => 'macronutrients',
            'description' => 'درمورد کربوهیدرات، پروتئین و چربی و نقش آن‌ها',
            'seo_keyword' => 'ماکروها',
            'category' => 'ماکروها',
            'cta_text' => 'شناخت ماکروها برای رژیم بهتر',
            'cta_button' => 'آموزش مکمل',
            'clusters' => [
                ['title' => 'پروتئین و منابع پروتئینی', 'keyword' => 'پروتئین حیوانی گیاهی'],
                ['title' => 'کربوهیدرات ساده و مختلط', 'keyword' => 'کربوهیدرات صحیح'],
                ['title' => 'چربی‌های سالم و غیرسالم', 'keyword' => 'چربی اشباع غیراشباع'],
                ['title' => 'نسبت‌های صحیح ماکروها', 'keyword' => 'توازن ماکروها'],
            ]
        ],
        [
            'key' => 'micronutrients',
            'title' => 'مواد مغذی کوچک (میکروها)',
            'slug' => 'micronutrients',
            'description' => 'درمورد ویتامین‌ها، مواد معدنی و نقش آن‌ها',
            'seo_keyword' => 'میکروها',
            'category' => 'میکروها',
            'cta_text' => 'تکمیل میکروها برای سلامتی',
            'cta_button' => 'مشاوره تغذیه',
            'clusters' => [
                ['title' => 'ویتامین‌های ضروری بدن', 'keyword' => 'کمبود ویتامین'],
                ['title' => 'معادن و نقش آن‌ها', 'keyword' => 'کلسیم آهن منیزیم'],
                ['title' => 'آنتی‌اکسیدان‌های طبیعی', 'keyword' => 'ویتامین C E'],
                ['title' => 'مکمل‌های رایج و فواید', 'keyword' => 'مکمل تغذیه'],
            ]
        ],
        [
            'key' => 'weight-loss',
            'title' => 'کاهش وزن سالم',
            'slug' => 'weight-loss',
            'description' => 'روشهای ثابت شده برای کاهش وزن پایدار',
            'seo_keyword' => 'کاهش وزن',
            'category' => 'کاهش وزن',
            'cta_text' => 'برنامه شخصی کاهش وزن',
            'cta_button' => 'شروع برنامه',
            'clusters' => [
                ['title' => 'کالری و سوخت و ساز', 'keyword' => 'کالری حرق بدن'],
                ['title' => 'رژیم‌های محبوب و اثربخشی', 'keyword' => 'رژیم کتو کم کربوهیدرات'],
                ['title' => 'ورزش برای کاهش وزن', 'keyword' => 'ورزش و تغذیه'],
                ['title' => 'اشتباهات رایج در کاهش وزن', 'keyword' => 'اشتباه رژیم'],
            ]
        ],
        [
            'key' => 'chronic-diseases',
            'title' => 'تغذیه و بیماریهای مزمن',
            'slug' => 'nutrition-chronic-diseases',
            'description' => 'نقش تغذیه در مدیریت بیماریهای مزمن',
            'seo_keyword' => 'تغذیه بیماری مزمن',
            'category' => 'بیماریهای مزمن',
            'cta_text' => 'مشاوره تغذیه پزشکی',
            'cta_button' => 'درخواست مشاوره',
            'clusters' => [
                ['title' => 'دیابت و کنترل قند خون', 'keyword' => 'دیابت تغذیه'],
                ['title' => 'فشارخون و کاهش سدیم', 'keyword' => 'فشارخون نمک'],
                ['title' => 'بیماری قلبی و کلسترول', 'keyword' => 'کلسترول چربی'],
                ['title' => 'هضم و مشکلات گوارشی', 'keyword' => 'گوارش سالم'],
            ]
        ]
    ];

    public function __construct() {
        $this->api_key = defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : '';
        $upload_dir = wp_upload_dir();
        $this->generation_log_file = $upload_dir['basedir'] . '/ai-article-generation.log';

        if (!$this->api_key) {
            $this->log('❌ DEEPSEEK_API_KEY is not defined in wp-config.php');
        }
    }

    private function get_health_safety_preamble() {
        return "⚠️ این محتوا فقط آموزشی است و جایگزین مشاوره پزشک نمی‌شود.\n- هیچ توصیه درمانی قطعی ندهید\n- عبارات احتیاطی استفاده کنید\n- دوز و دارو توصیه نکنید";
    }

    private function get_medical_disclaimer() {
        return '<div style="background:#f0f8ff; border-left:4px solid #0066cc; padding:15px; margin:20px 0; border-radius:4px; direction:rtl;"><strong>⚠️ دیسکلیمر:</strong><p>این مقاله فقط برای اطلاع است و جایگزین مشاوره حرفه‌ای پزشک یا متخصص تغذیه نیست. قبل از هر تغییری در رژیم غذایی یا سبک زندگی، با پزشک خود مشورت کنید.</p></div>';
    }

    private function format_trusted_sources_for_prompt() {
        $formatted = "منابع: ";
        foreach ($this->trusted_sources as $name => $url) {
            $formatted .= $name . ", ";
        }
        return rtrim($formatted, ', ');
    }

    private function generate_cluster_topic($pillar, $existing_articles) {
        $this->log('🎯 Generate cluster for: ' . $pillar['title']);

        if (empty($pillar['clusters']) || !is_array($pillar['clusters'])) {
            $this->log('⚠️ No clusters defined for pillar: ' . $pillar['key']);
            return null;
        }

        $used_keywords = array_map(function($a) {
            return get_post_meta($a['ID'], '_primary_keyword', true);
        }, $existing_articles);

        $available_clusters = array_filter($pillar['clusters'], function($c) use ($used_keywords) {
            return !in_array($c['keyword'], $used_keywords);
        });

        if (empty($available_clusters)) {
            $this->log('⚠️ All clusters used for pillar: ' . $pillar['key']);
            return null;
        }

        $cluster = $available_clusters[array_rand($available_clusters)];

        return [
            'topic' => $cluster['title'],
            'primary_keyword' => $cluster['keyword'],
            'cluster_category' => $pillar['category'],
            'lsi_keywords' => [],
            'user_intent' => 'informational'
        ];
    }

    private function generate_seo_optimized_article($topic, $primary_keyword, $pillar, $cluster_topic) {
        $this->log('📝 Generating article: ' . $topic);

        $sources = $this->format_trusted_sources_for_prompt();

        $prompt = "{$this->get_health_safety_preamble()}

شما یک نویسنده محتوای پزشکی و تغذیه متخصص هستید.

موضوع: {$topic}
کلمه کلیدی: {$primary_keyword}
Pillar: {$pillar['title']}
منابع: {$sources}

یک مقاله 1500-2000 کلمه‌ای تولید کن با:
- H1 شامل کلمه کلیدی
- مقدمه جذاب
- 3-4 بخش H2 منظم
- سوالات متداول
- جمع‌بندی

**خروجی JSON فقط این فرمت:**
{
  \"title\": \"عنوان مقاله (شامل کلمه کلیدی)\",
  \"meta_description\": \"توضیح 150-160 کاراکتری برای گوگل\",
  \"content\": \"محتوای HTML کامل\"
}

**مهم: فقط JSON ارسال کن، هیچ متن اضافی نه!**";

        $response = $this->call_api($prompt, 0.6);
        if (!$response) {
            $this->log('❌ Failed to generate article');
            return null;
        }

        $result = json_decode($response, true);
        if (!$result || !isset($result['content'])) {
            $this->log('❌ Invalid article JSON: ' . json_last_error_msg());
            return null;
        }

        $this->log('✅ Article: ' . substr($result['title'] ?? '', 0, 80));
        return $result;
    }
    
    /**
     * ===== UPDATE FEATURE: بروزرسانی مقالات قدیمی =====
     */
    private function get_article_for_update() {
        $this->log('🔍 Finding old article for update...');
        $posts = get_posts([
            'post_type'   => 'post',
            'post_status' => 'publish',
            'numberposts' => 1,
            'orderby'     => 'modified',
            'order'       => 'ASC',
            'date_query'  => [
                [
                    'before' => date('Y-m-d', strtotime('-45 days'))
                ]
            ]
        ]);
        if (empty($posts)) {
            $this->log('⚠️ No old articles found for update');
            return null;
        }
        return $posts[0];
    }
    
    private function update_article_content($post) {
        $this->log('📝 Updating article: ' . $post->post_title);
        $old_content = $post->post_content;
        $old_title = $post->post_title;
        
        $pillar_key = get_post_meta($post->ID, '_pillar_key', true);
        $primary_keyword = get_post_meta($post->ID, '_primary_keyword', true);
        $current_content = mb_substr(
            wp_strip_all_tags($old_content),
            0,
            3000
        );
                        
        
        // استخراج موضوع از عنوان
        $topic = $old_title;
        $prompt = "{$this->get_health_safety_preamble()}
شما یک نویسنده محتوای پزشکی و تغذیه متخصص هستید.
این مقاله قدیمی است و نیاز به بروزرسانی دارد:
عنوان: {$old_title} 
Pillar: {$pillar_key}
Primary Keyword: {$primary_keyword}
محتوای فعلی (خلاصه):
{$current_content}
 
لطفا این مقاله را بروزرسانی کن:
- اطلاعات جدید اضافه کن
- آمار و تحقیقات جدید شامل کن
- ساختار و فرمت یکسان نگاه دار
- حجم حدود 1500-2000 کلمه
- عنوان را فقط در صورت بهبود جزئی SEO تغییر بده
- ساختار کلی عنوان حفظ شود
- کلمه کلیدی اصلی را تغییر نده
** فرمت خروجی JSON بشکل زیر باشد و مطابق استاندارد جیسون با کروشه باز شروع و کروشه بسته تمام شود: **
{
  \"title\": \"عنوان (ممکن است تغییر کند)\",
  \"meta_description\": \"توضیح 150-160 کاراکتری\",
  \"content\": \"محتوای HTML بروزرسانی شده\"
}
**مهم: فقط JSON ارسال کن!**";
        $response = $this->call_api($prompt, 0.5);
        if (!$response) {
            $this->log('❌ Failed to update article');
            return null;
        }
        $result = json_decode($response, true);
        if (!$result || !isset($result['content'])) {
            $this->log('❌ Invalid update JSON: ' . json_last_error_msg());
            return null;
        }
        $this->log('✅ Article updated: ' . substr($result['title'] ?? '', 0, 80));
        return $result;
    }
    
    private function apply_updated_article($post_id, $updated_article) {
        $this->log('💾 Applying updates to post ' . $post_id);
        $old_title = get_the_title($post_id);
        $new_title = $updated_article['title'] ?? $old_title;
        
        if (mb_strlen($new_title) < 10) {
            $new_title = $old_title;
        }
        
        wp_update_post([
            'ID'           => $post_id,
            'post_title'   => $new_title,
            'post_content' => $updated_article['content'] . $this->get_medical_disclaimer(),
            'post_excerpt' => $updated_article['meta_description'] ?? ''
        ]);

        // بروزرسانی metadata
        update_post_meta($post_id, '_seo_title', $updated_article['title'] ?? '');
        update_post_meta($post_id, '_seo_description', $updated_article['meta_description'] ?? '');
        update_post_meta($post_id, '_last_ai_update', current_time('mysql'));
        $this->log('✅ Post updated: ' . $post_id);
        
        $pillar_key = get_post_meta($post_id, '_pillar_key', true);
        $pillar = null;
        
        foreach ($this->pillars as $p) {
            if ($p['key'] === $pillar_key) {
                $pillar = $p;
                break;
            }
        }
        
        if ($pillar) {
            $this->add_strategic_internal_links($post_id, $pillar);
            $this->add_pillar_specific_cta($post_id, $pillar);
        }


    }
    
    // ===== ORIGINAL FEATURES (NO CHANGES) =====    
    

    private function add_article_categories($post_id, $pillar) {
        $this->log('📁 Adding categories...');

        $category_name = $pillar['category'] ?? 'تغذیه';
        $category = get_term_by('name', $category_name, 'category');

        if (!$category) {
            $cat_result = wp_insert_term($category_name, 'category', [
                'slug' => sanitize_title($category_name)
            ]);

            if (is_wp_error($cat_result)) {
                $this->log('❌ Category error: ' . $cat_result->get_error_message());
                return;
            }

            $category_id = $cat_result['term_id'];
        } else {
            $category_id = $category->term_id;
        }

        wp_set_post_terms($post_id, [$category_id], 'category');
        $this->log('✅ Categories added');
    }

    private function add_strategic_internal_links($post_id, $pillar) {
        $this->log('🔗 Adding internal links...');

        $article_content = get_post_field('post_content', $post_id);
        if (!$article_content) {
            $this->log('⚠️ No content to link');
            return;
        }

        $pillar_post = $this->get_pillar_post($pillar['key']);
        if (!$pillar_post) {
            $this->log('⚠️ Pillar post not found');
            return;
        }

        $pillar_link = get_permalink($pillar_post);
        $pillar_title = get_the_title($pillar_post);

        if (preg_match_all('/<\/p>/', $article_content, $matches, PREG_OFFSET_CAPTURE)) {
            $last_p_pos = end($matches[0])[1];
            $link_html = ' <a href="' . esc_url($pillar_link) . '" title="' . esc_attr($pillar_title) . '">' . esc_html($pillar_title) . '</a>';

            $article_content = substr_replace(
                $article_content,
                $link_html . '</p>',
                $last_p_pos,
                4
            );

            wp_update_post([
                'ID' => $post_id,
                'post_content' => $article_content
            ]);

            $this->log('✅ Internal link added: ' . $pillar_title);
        }
    }

    private function check_keyword_cannibalization($primary_keyword) {
        $this->log('🔍 Check cannibalization...');

        $existing = get_posts([
            'post_type'   => 'post',
            'post_status' => 'publish',
            'meta_key'    => '_primary_keyword',
            'meta_value'  => $primary_keyword,
            'numberposts' => 1
        ]);

        if (!empty($existing)) {
            $this->log('⚠️ Keyword exists: ' . $primary_keyword);
            return false;
        }

        $this->log('✅ Keyword unique');
        return true;
    }

    private function add_schema_markup($post_id, $article) {  
        
        $date_published = get_the_date('c', $post_id);

        $schema = [
            '@context'      => 'https://schema.org',
            '@type' => 'Article',
            'about' => 'Nutrition and Health',
            'audience' => [
                '@type' => 'Audience',
                'audienceType' => 'General Public'
            ],
            'headline'      => $article['title'] ?? '',
            'description'   => $article['meta_description'] ?? '',
            'datePublished' => $date_published,
            'dateModified'  => current_time('c'),

        ];
        update_post_meta($post_id, '_schema_markup_json_ld', wp_json_encode($schema));
        $this->log('✅ Schema added');
    }

    private function add_seo_metadata($post_id, $article, $primary_keyword) {
    
        // 1. ذخیره هسته SEO (fallback دائمی)
        $this->save_core_seo_meta($post_id, $article, $primary_keyword);
    
        // 2. Sync خودکار با پلاگین فعال
        $this->sync_seo_to_active_plugin($post_id);
    
        // 3. SEO Audit خودکار
        $this->run_seo_audit($post_id);
    
        error_log('✅ SEO pipeline completed for post ' . $post_id);
    }
    
    
    private function save_core_seo_meta($post_id, $article, $primary_keyword) {
    
        update_post_meta($post_id, '_seo_title', trim($article['title']));
        update_post_meta($post_id, '_seo_description', trim($article['meta_description']));
        update_post_meta($post_id, '_primary_keyword', $primary_keyword);
    
        // برای AI و تحلیل داخلی (نه SEO کلاسیک)
        if (!empty($article['keywords'])) {
            update_post_meta($post_id, '_ai_keywords', $article['keywords']);
        }
    
        if (!empty($article['lsi_keywords'])) {
            update_post_meta($post_id, '_ai_lsi_keywords', $article['lsi_keywords']);
        }
    }
    
    private function sync_seo_to_active_plugin($post_id) {
    
        $title = get_post_meta($post_id, '_seo_title', true);
        $desc  = get_post_meta($post_id, '_seo_description', true);
        $focus = get_post_meta($post_id, '_primary_keyword', true);
    
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            update_post_meta($post_id, '_yoast_wpseo_title', $title);
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $desc);
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus);
            return;
        }
    
        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            update_post_meta($post_id, 'rank_math_title', $title);
            update_post_meta($post_id, 'rank_math_description', $desc);
            update_post_meta($post_id, 'rank_math_focus_keyword', $focus);
            return;
        }
    
        // All in One SEO
        if (defined('AIOSEO_VERSION')) {
            update_post_meta($post_id, '_aioseo_title', $title);
            update_post_meta($post_id, '_aioseo_description', $desc);
            return;
        }
    
        // اگر هیچ پلاگینی فعال نبود
        error_log('ℹ️ No SEO plugin detected – using fallback meta only');
    }
       
       
    private function run_seo_audit($post_id) {
    
        $title = get_post_meta($post_id, '_seo_title', true);
        $desc  = get_post_meta($post_id, '_seo_description', true);
        $focus = get_post_meta($post_id, '_primary_keyword', true);
        $content = get_post_field('post_content', $post_id);
    
        $issues = [];
    
        if (mb_strlen($title) < 30 || mb_strlen($title) > 60) {
            $issues[] = 'Title length not optimal';
        }
    
        if (mb_strlen($desc) < 70 || mb_strlen($desc) > 160) {
            $issues[] = 'Meta description length not optimal';
        }
    
        if ($focus && substr_count(mb_strtolower($content), mb_strtolower($focus)) < 2) {
            $issues[] = 'Primary keyword usage is low';
        }
    
        update_post_meta($post_id, '_seo_audit', [
            'status' => empty($issues) ? 'pass' : 'warning',
            'issues' => $issues,
            'checked_at' => current_time('mysql')
        ]);
    
        if (!empty($issues)) {
            error_log('⚠️ SEO Audit warnings for post ' . $post_id . ': ' . implode(' | ', $issues));
        } else {
            error_log('✅ SEO Audit passed for post ' . $post_id);
        }
    }
            
        


    private function add_pillar_specific_cta($post_id, $pillar) {
        update_post_meta($post_id, '_cta_text', $pillar['cta_text'] ?? '');
        update_post_meta($post_id, '_cta_button', $pillar['cta_button'] ?? '');
        $this->log('✅ CTA added');
    }

    private function get_pillar_post($pillar_key) {
        $posts = get_posts([
            'post_type'  => 'page',
            'meta_key'   => '_is_pillar_page',
            'meta_value' => $pillar_key,
            'numberposts'=> 1
        ]);
        return $posts[0] ?? null;
    }

    private function get_existing_articles_list() {
        $articles = get_posts([
            'post_type'   => 'post',
            'post_status' => 'publish',
            'numberposts' => 100
        ]);
        $list = [];
        foreach ($articles as $post) {
            $list[] = [
                'ID'              => $post->ID,
                'title'           => $post->post_title,
                'pillar_key'      => get_post_meta($post->ID, '_pillar_key', true) ?: '',
                'primary_keyword' => get_post_meta($post->ID, '_primary_keyword', true) ?: ''
            ];
        }
        return $list;
    }

    private function call_api($prompt, $temperature) {
        if (!$this->api_key) {
            $this->log('❌ API Key missing');
            return null;
        }

        $this->log('🌐 API call (temp: ' . $temperature . ')');

        $max_tokens = 6000;

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body'    => json_encode([
                'model'       => 'deepseek-chat',
                'messages'    => [
                    ['role' => 'system', 'content' => 'You are a professional health and nutrition content writer. Always respond with valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => $temperature,
                'max_tokens'  => $max_tokens
            ]),
            'timeout'  => 300,
            'sslverify' => true

        ];

        try {
            $response = wp_remote_post($this->api_endpoint, $args);
        } catch (Exception $e) {
            $this->log('❌ Exception: ' . substr($e->getMessage(), 0, 200));
            return null;
        }

        if (is_wp_error($response)) {
            $this->log('❌ WP Error: ' . substr($response->get_error_message(), 0, 200));
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        $this->log('📊 HTTP Status: ' . $code);

        if ($code !== 200) {
            $this->log('❌ HTTP Error ' . $code);
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            $this->log('❌ Empty response body');
            return null;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('❌ API Response not JSON: ' . json_last_error_msg());
            return null;
        }

        if (!isset($decoded['choices'][0]['message']['content'])) {
            $this->log('❌ No content in API response');
            return null;
        }

        $raw_content = $decoded['choices'][0]['message']['content'];
        $this->log('📥 Raw content length: ' . strlen($raw_content) . ' bytes');


        // ⛔ Detect truncated JSON
        if (substr_count($raw_content, '{') > substr_count($raw_content, '}')) {
            $this->log('⚠️ Truncated JSON detected — retrying with higher token limit');
            return null;
        }
  
     //   $response =  $this->extract_json_from_response($raw_content);
        
    //    return $this->clean_api_response($response);        
    
          return $this->extract_json_from_response($raw_content); 
    }

private function extract_json_from_response($raw_response) {

    if (empty($raw_response) || !is_string($raw_response)) {
        $this->log('❌ Empty or invalid raw response');
        return null;
    }

    $text = $raw_response;

    // 1️⃣ حذف BOM
    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);

    // 2️⃣ اگر داخل ```json ``` بود، فقط همون رو بکش بیرون
    if (preg_match('/```json\s*(\{[\s\S]*?\})\s*```/i', $text, $m)) {
        $json_string = trim($m[1]);
    }
    // 3️⃣ اگر ```json نبود ولی ``` بود
    elseif (preg_match('/```\s*(\{[\s\S]*?\})\s*```/i', $text, $m)) {
        $json_string = trim($m[1]);
    }
    // 4️⃣ fallback نهایی: از اولین { تا آخرین }
    else {
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            $this->log('❌ No JSON boundaries found');
            $this->log('🔍 First 500 chars: ' . substr($raw_response, 0, 500));
            return null;
        }

        $json_string = substr($text, $start, $end - $start + 1);
    }

    // 5️⃣ decode
    $decoded = json_decode($json_string, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $this->log('❌ JSON Decode Error: ' . json_last_error_msg());
        $this->log('🔍 JSON preview: ' . substr($json_string, 0, 300));
        return null;
    }

    // 6️⃣ validate fields
    foreach (['title', 'meta_description', 'content'] as $field) {
        if (!isset($decoded[$field])) {
            $this->log('❌ Missing required field: ' . $field);
            return null;
        }
    }

    $this->log('✅ JSON extracted successfully (' . strlen($json_string) . ' bytes)');
    return $json_string;
}

    
    
    /**
     * Clean API response
     */
    private function clean_api_response($response_content) {
        if (empty($response_content)) {
            return '';
        }
        
        // Remove markdown code blocks
        $patterns = [
            '/^```json\s*/',
            '/\s*```$/',
            '/^```\s*/',
            '/\s*```$/',
        ];
        
        $cleaned_response = preg_replace($patterns, '', $response_content);
        $cleaned_response = trim($cleaned_response);
        
        // Remove control characters
        $cleaned_response = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned_response);
        
        return $cleaned_response;
    }    

    private function log($message) {
        $ts = current_time('Y-m-d H:i:s');
        error_log("[$ts] $message");
        @file_put_contents($this->generation_log_file, "[$ts] $message\n", FILE_APPEND);
    }
    
    
    /**
     * ===== DECISION LAYER: 30% UPDATE / 70% CREATE =====
     */
    private function decide_job_type() {
        
        
        $posts = get_posts([
            'post_type'   => 'post',
            'post_status' => 'publish',
            'numberposts' => 1,
            'orderby'     => 'modified',
            'order'       => 'ASC',
            'date_query'  => [
                [
                    'before' => date('Y-m-d', strtotime('-45 days'))
                ]
            ]
        ]);
        if (empty($posts)) {
            $this->log('⚠️ No old articles found for update');
            return 'create';
        }
        
        return (rand(1, 100) <= 30) ? 'update' : 'create';
     
    }    

    public function handle() {
        ignore_user_abort(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $lock_value = get_option($this->lock_key);

        if ($lock_value) {
            $lock_age = time() - (int)$lock_value;

            if ($lock_age > 10 * 60) {
                $this->log('🔓 Stale lock removed (age: ' . $lock_age . 's)');
                delete_option($this->lock_key);
            } else {
                $this->log('⏸️ Already running');
                return;
            }
        }

        update_option($this->lock_key, time());

        try {
            $job_type = $this->decide_job_type();
            
            if ($job_type === 'update') {
                $this->update_old_article_process();
            } else {
                $this->generate_full_article_process();
            }
        } catch (Exception $e) {
            $this->log('❌ Exception: ' . substr($e->getMessage(), 0, 200));
        } finally {
            delete_option($this->lock_key);
        }
    }
    
    
    /**
     * ===== UPDATE PROCESS (30%) =====
     */
    private function update_old_article_process() {
        $this->log('♻️ UPDATE MODE - Refreshing old article');

        $post = $this->get_article_for_update();
        if (!$post) {
            $this->log('ℹ️ No old article found for update');
            return;
        }

        $updated_article = $this->update_article_content($post);
        if (!$updated_article) {
            return;
        }

        $this->apply_updated_article($post->ID, $updated_article);
        $this->log('✅ UPDATE COMPLETE! ' . get_permalink($post->ID));
    }   
    
    /**
     * ===== CREATE PROCESS (70%) =====
     */    

    private function generate_full_article_process() {
        $this->log('🚀 Start v5.2 - Production Ready');

        // $existing = $this->get_existing_articles_list();
        // $this->log('📊 Found ' . count($existing) . ' articles');

        // $pillar = $this->pillars[array_rand($this->pillars)];
        // $this->log('🏛️ Pillar: ' . $pillar['title']);

        // 1) دریافت اطلاعات موجود
        $existing_articles = $this->get_existing_articles_list();
        error_log('📚 Found ' . count($existing_articles) . ' existing articles');

        // 2) انتخاب Pillar برای این دور
        $selected_pillar = $this->select_pillar_for_generation($existing_articles);
        if (!$selected_pillar) {
            error_log('❌ Failed to select pillar');
            return;
        }

        error_log('📁 Selected Pillar: ' . $selected_pillar['title']);

        // 3) تولید Cluster Topic (زیر مجموعه Pillar)
        $cluster = $this->generate_cluster_topic($selected_pillar, $existing_articles);
        if (!$cluster) {
            error_log('❌ Failed to generate cluster topic');
            return;
        }

        error_log('✅ Cluster Topic: ' . $cluster['topic']);


        if (!$this->check_keyword_cannibalization($cluster['primary_keyword'])) return;

        $article = $this->generate_seo_optimized_article(
            $cluster['topic'],
            $cluster['primary_keyword'],
            $selected_pillar,
            $cluster
        );
        if (!$article) return;

        $article['content'] .= $this->get_medical_disclaimer();

        $post_id = wp_insert_post([
            'post_title'   => $article['title'],
            'post_content' => $article['content'],
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_excerpt' => $article['meta_description'] ?? ''
        ]);
        
        update_post_meta($post_id, '_pillar_key', $selected_pillar['key']);


        if (is_wp_error($post_id)) {
            $this->log('❌ Post error: ' . $post_id->get_error_message());
            return;
        }

        $this->log('📄 Post ID: ' . $post_id);

        $this->add_seo_metadata(
            $post_id,
            $article,
            $cluster['primary_keyword']
        );
        $this->add_schema_markup($post_id, $article);
        $this->add_pillar_specific_cta($post_id, $selected_pillar);
        $this->add_article_categories($post_id, $selected_pillar);
        $this->add_strategic_internal_links($post_id, $selected_pillar);

        wp_update_post([
            'ID'        => $post_id,
            'post_name' => sanitize_title($article['title'] ?? '')
        ]);

        $this->log('✅ COMPLETE! ' . get_permalink($post_id));
    }
    

    /**
     * انتخاب Pillar برای تولید (Round-robin واقعی)
     * کم‌محتواترین Pillar انتخاب می‌شود
     */
    private function select_pillar_for_generation($existing_articles) {
    
        // 1️⃣ شمارش مقاله‌ها برای هر pillar_key
        $pillar_counts = [];
    
        foreach ($this->pillars as $pillar) {
            $pillar_key = $pillar['key'];
            $pillar_counts[$pillar_key] = 0;
    
            foreach ($existing_articles as $article) {
                if (
                    !empty($article['pillar_key']) &&
                    $article['pillar_key'] === $pillar_key
                ) {
                    $pillar_counts[$pillar_key]++;
                }
            }
        }
    
        // 2️⃣ مرتب‌سازی بر اساس کمترین تعداد مقاله
        asort($pillar_counts); // ascending
    
        // 3️⃣ انتخاب pillar با کمترین مقاله
        $selected_pillar_key = array_key_first($pillar_counts);
    
        // 4️⃣ برگرداندن آبجکت کامل pillar
        foreach ($this->pillars as $pillar) {
            if ($pillar['key'] === $selected_pillar_key) {
                return $pillar;
            }
        }
    
        // fallback (نباید به اینجا برسد)
        return null;
    }
   
}




?>
