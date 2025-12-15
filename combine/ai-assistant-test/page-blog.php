<?php
/*
Template Name: وبلاگ سفارشی
Description: صفحة وبلاگ با دیزاین مدرن Tailwind
*/

get_header();

// پارامترهای کوئری برای صفحه‌بندی
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// تعریف آرگومان‌های WP_Query
$args = array(
    'post_type' => 'post',
    'posts_per_page' => 9,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC'
);

$blog_query = new WP_Query($args);
?>

<div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100">
    <!-- هدر صفحة وبلاگ -->
    <div class="bg-gradient-to-r from-teal-600 to-teal-700 text-white py-12 md:py-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php the_title(); ?></h1>
            <p class="text-lg text-teal-100">تازه‌ترین مقالات و راهنمای‌های تغذیه‌ای</p>
        </div>
    </div>

    <!-- فیلتر و جستجو -->
    <div class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- جستجو -->
                <form method="get" class="md:col-span-2">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="s" 
                            placeholder="جستجو در مقالات..." 
                            value="<?php echo get_search_query(); ?>"
                            class="w-full px-4 py-3 pl-12 rounded-lg border border-slate-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition"
                        >
                        <svg class="absolute left-4 top-3.5 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </form>

                <!-- مرتب‌سازی -->
                <form method="get" class="flex items-end">
                    <select name="orderby" onchange="this.form.submit()" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition bg-white">
                        <option value="date" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'date' ? 'selected' : ''; ?>>تازه‌ترین</option>
                        <option value="title" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'title' ? 'selected' : ''; ?>>الفبایی</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- محتوای اصلی -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <?php if ($blog_query->have_posts()) : ?>
            
            <!-- شبکة مقالات -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php
                while ($blog_query->have_posts()) {
                    $blog_query->the_post();
                    $featured_image = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                    $excerpt = wp_trim_words(get_the_excerpt(), 20, '...');
                    $category = get_the_category();
                    $author = get_the_author();
                    $date = get_the_date('d F Y');
                    $reading_time = ceil(str_word_count(get_the_content()) / 200); // تقریبی
                    ?>
                    
                    <article class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden group">
                        
                        <!-- تصویر مقاله -->
                        <div class="relative h-48 overflow-hidden bg-slate-200">
                            <?php if ($featured_image) : ?>
                                <img 
                                    src="<?php echo esc_url($featured_image); ?>" 
                                    alt="<?php the_title(); ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                >
                            <?php else : ?>
                                <div class="w-full h-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-white opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <!-- دسته‌بندی -->
                            <?php if (!empty($category)) : ?>
                                <div class="absolute top-3 left-3">
                                    <span class="inline-block bg-teal-500 text-white text-xs font-semibold px-3 py-1 rounded-full">
                                        <?php echo esc_html($category[0]->name); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- محتوای مقاله -->
                        <div class="p-6">
                            
                            <!-- عنوان -->
                            <h3 class="text-xl font-bold text-slate-900 mb-3 line-clamp-2 group-hover:text-teal-600 transition">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h3>

                            <!-- خلاصة -->
                            <p class="text-slate-600 text-sm mb-4 line-clamp-3">
                                <?php echo $excerpt; ?>
                            </p>

                            <!-- متادیتا -->
                            <div class="flex flex-wrap gap-3 text-xs text-slate-500 border-t border-slate-200 pt-4 mb-4">
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"></path>
                                    </svg>
                                    <span><?php echo $date; ?></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span><?php echo $reading_time; ?> دقیقة</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.5 1.5H3.75A2.25 2.25 0 001.5 3.75v12.5A2.25 2.25 0 003.75 18.5h12.5a2.25 2.25 0 002.25-2.25V9.5m-15-4h12m-12 4v8m12-12v3"></path>
                                    </svg>
                                    <span><?php echo $author; ?></span>
                                </div>
                            </div>

                            <!-- دکمة خواندن -->
                            <a 
                                href="<?php the_permalink(); ?>" 
                                class="inline-flex items-center justify-center w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition-colors duration-200 group/btn"
                            >
                                خواندن مقاله
                                <svg class="w-4 h-4 mr-2 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                        </div>
                    </article>

                <?php } ?>
            </div>

            <!-- صفحه‌بندی -->
            <?php if ($blog_query->max_num_pages > 1) : ?>
                <div class="flex justify-center items-center gap-2 my-12">
                    <?php
                    $pagination_args = array(
                        'total' => $blog_query->max_num_pages,
                        'current' => $paged,
                        'type' => 'array',
                        'prev_text' => '<span class="inline-flex items-center">قبلی</span>',
                        'next_text' => '<span class="inline-flex items-center">بعدی</span>',
                        'mid_size' => 2
                    );
                    
                    $pagination = paginate_links($pagination_args);
                    
                    if ($pagination) {
                        foreach ($pagination as $page) {
                            echo str_replace(
                                array('page-numbers', 'prev page-numbers', 'next page-numbers', 'current'),
                                array('px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-100 transition', 
                                      'px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-100 transition disabled',
                                      'px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-100 transition disabled',
                                      'px-3 py-2 rounded-lg bg-teal-600 text-white border border-teal-600'),
                                $page
                            );
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>

        <?php else : ?>
            
            <!-- پیام خالی -->
            <div class="text-center py-20">
                <svg class="w-20 h-20 mx-auto text-slate-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">مقالات‌ای یافت نشد</h3>
                <p class="text-slate-600 mb-6">متأسفانه هیچ مقالة‌ای مطابق با معیار جستجوی شما وجود ندارد.</p>
                <a href="<?php echo home_url(); ?>" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    بازگشت به صفحة اصلی
                </a>
            </div>

        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
    </div>
</div>

<?php get_footer(); ?>
