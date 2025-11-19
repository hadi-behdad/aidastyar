<?php
if (!defined('ABSPATH')) exit;

class ai_article_generator_job {

    public function handle() {
        error_log('📝 ai_article_generator_job executed at ' . current_time('mysql'));

        $lock_key = 'ai_article_generator_job_lock';
        $lock = get_option($lock_key);

        if ($lock && $lock > time() - 3600) {
            error_log('⏸️ Already running. Skip.');
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
        error_log('🚀 Starting AI SEO Article Process');

        // 1) generate dynamic topic
        $topic_data = $this->generate_trending_topic();
        if(!$topic_data) return;

        $topic = $topic_data['topic'];
        $category = $topic_data['category'];

        // 2) generate full SEO article package
        $article = $this->generate_seo_optimized_article($topic);

        if (!$article || empty($article['content'])) {
            error_log('❌ Failed generating article content');
            return;
        }

        // 3) publish post in WP
        $post_id = wp_insert_post([
            'post_title'    => $article['title'],
            'post_content'  => $article['content'],
            'post_excerpt'  => $article['meta_description'],
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'post',
            'post_category' => [$this->get_or_create_category($category)]
        ]);

        if (is_wp_error($post_id)) {
            error_log('❌ WP Insert Error: ' . $post_id->get_error_message());
            return;
        }

        // 4) Auto-insert SEO metadata
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $article['meta_description']);
        update_post_meta($post_id, '_rank_math_description', $article['meta_description']);
        update_post_meta($post_id, '_seo_keywords', implode(',', $article['keywords']));

        error_log("✅ Article published successfully: $post_id");
    }


    private function generate_trending_topic() {
        error_log('generate_trending_topic STARTED_____________________________________________________');
        $api_key = DEEPSEEK_API_KEY;
        $api_url = 'https://api.deepseek.com/v1/chat/completions';

        $prompt = "
        ۵ موضوع داغ، قابل رتبه گرفتن و کم‌رقابت در حوزه سلامت، تغذیه، سبک زندگی، تناسب اندام، پزشکی و سلامت عمومی پیشنهاد بده.
        خروجی فقط یک JSON باشد:
        {
            \"topic\": \"...\",
            \"category\": \"...\"
        }
        موضوع باید جدید باشد و مشابه مقالات قبلی سایت نباشد.
        ";

        $response = $this->call_api($prompt);

        if(!$response) return null;
        
        return json_decode($response, true);
    }


    private function generate_seo_optimized_article($topic) {
        
        error_log('generate_seo_optimized_article STARTED_____________________________________________________');
        $prompt = "
        برای موضوع: «$topic»
        یک بسته کامل سئو تولید کن شامل:
        
        - یک عنوان جذاب و کلیک‌خور (SEO Friendly)
        - یک توضیح کوتاه 150 کاراکتری برای Meta Description
        - ۵ کلمه کلیدی اصلی + ۵ کلمه کلیدی فرعی
        - یک مقاله کامل بالای 1200 کلمه
        - دارای ساختار HTML:
            <h1> فقط یکبار
            <h2> تیترهای اصلی
            <h3> زیرتیترها
            <p> متن
            <ul> لیست بولت
        
        - یک پاراگراف شامل ۱ لینک خارجی معتبر (no-follow)
        - یک پاراگراف شامل ۲ لینک داخلی پیشنهادی (فقط متن anchor، بدون URL)
        
        خروجی 100% باید JSON باشد:
        {
            \"title\": \"\",
            \"meta_description\": \"\",
            \"keywords\": [],
            \"content\": \"...\"
        }
        ";

        $response = $this->call_api($prompt);

        if(!$response) return null;

        return json_decode($response, true);
    }


    private function call_api($prompt) {
         error_log($prompt);
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
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2,
                'max_tokens' => 8000
            ]),
            'timeout' => 180,
            'httpversion' => '1.1'
        ];

        $response = wp_remote_post($url, $args);
        error_log($response);
        if (is_wp_error($response)) return null;

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body['choices'][0]['message']['content'] ?? null;
    }


    private function get_or_create_category($category_name) {
        $category = get_category_by_slug(sanitize_title($category_name));
        
        if ($category) return $category->term_id;
        
        $new_category = wp_insert_term($category_name, 'category');
        if (is_wp_error($new_category)) return 1;

        return $new_category['term_id'];
    }
}
