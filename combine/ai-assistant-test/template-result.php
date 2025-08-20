<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/template-result.php
 * Template Name: نمایش نتیجه رژیم غذایی
 * Template Post Type: page
 */
 
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برنامه تغذیه‌ای بالینی</title>
    <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">-->
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/fonts/webfonts/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #2E7D32;
            --primary-light: #E8F5E8;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-radius: 12px;
            --box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        /* به استایل‌های موجود اضافه کنید */
        body {
            font-family: Vazir, Tahoma, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            line-height: 1.6;
            direction: rtl;
            color: var(--text-color);
            padding: 10px;
            text-align: justify;
            text-justify: inter-word;
        }
        
        /* عناوین راست‌چین باقی بمونن */
        h1, h2, h3, h4, h5, h6 {
            text-align: right !important;
        }
        
        /* برای لیست‌ها */
        ul, ol {
            text-align: justify;
        }
        
        li {
            text-align: justify;
        }
        
        .result-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 10px;
        }
        
        .header {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--border-radius);
            color: white;
            box-shadow: var(--box-shadow);
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .section {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            padding: 20px;
            border-top: 5px solid var(--primary-color);
        }
        
        h2 {
            color: var(--primary-dark);
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h2 i {
            color: var(--primary-color);
        }
        
        h3 {
            color: var(--primary-color);
            margin: 15px 0 10px;
            font-size: 1.1rem;
            padding-right: 10px;
            border-right: 3px solid var(--primary-color);
        }
        
        ul {
            list-style-type: none;
            padding: 0;
        }
        
        ul li {
            padding: 10px;
            border-bottom: 1px dashed #ddd;
            background-color: var(--light-gray);
            margin-bottom: 8px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        ul li:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        /* استایل جدول برای دسکتاپ */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            display: none; /* در موبایل نمایش داده نمی‌شود */
        }
        
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        
        table th {
            background: var(--primary-color);
            color: white;
        }
        
        /* استایل کارت‌ها برای نمایش در موبایل */
        .mobile-cards {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 15px;
        }
        
        .day-card {
            background: var(--primary-light);
            border-radius: var(--border-radius);
            padding: 15px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .day-card:hover {
            transform: translateY(-5px);
        }
        
        .day-header {
            background: var(--primary-color);
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .meal-section {
            margin-bottom: 12px;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }
        
        .meal-title {
            font-weight: bold;
            color: var(--primary-dark);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #777;
            padding: 15px;
            font-size: 0.9rem;
        }
        
        .back-button {
            display: inline-block;
            margin: 20px auto;
            padding: 12px 25px;
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            cursor: pointer;
            text-align: center;
            font-weight: bold;
            border: none;
            box-shadow: var(--box-shadow);
            width: 80%;
            max-width: 300px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .back-button:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .error {
            color: #d32f2f;
            background: #ffebee;
            padding: 15px;
            border-radius: var(--border-radius);
            margin: 15px 0;
            border-right: 4px solid #d32f2f;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .credit-info {
            background: var(--primary-light);
            padding: 15px;
            border-radius: var(--border-radius);
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
            color: var(--primary-dark);
            border: 1px solid var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        /* رسپانسیو برای تبلت و دسکتاپ */
        @media (min-width: 768px) {
            body {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            .mobile-cards {
                display: none; /* در دسکتاپ نمایش داده نمی‌شود */
            }
            
            table {
                display: table; /* در دسکتاپ جدول نمایش داده می‌شود */
            }
            
            .back-button {
                width: auto;
            }
        }
        
        @media (max-width: 480px) {
            .section {
                padding: 15px;
            }
            
            h2 {
                font-size: 1.2rem;
            }
            
            ul li {
                padding: 8px;
                font-size: 0.9rem;
                flex-direction: column;
                gap: 5px;
            }
            
            .day-card {
                padding: 12px;
            }
            
            .day-header {
                font-size: 0.9rem;
            }
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-light {
            background-color: var(--light-gray);
            color: var(--text-color);
        }
        
        .nutrition-facts {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .nutrition-item {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 10px;
            background: var(--primary-light);
            border-radius: 8px;
        }
        
        .nutrition-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-dark);
        }
        
        .nutrition-label {
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="header">
            <h1><i class="fas fa-utensils"></i> برنامه تغذیه‌ای بالینی</h1>
            <p>برنامه غذایی شخصی شده بر اساس مشخصات شما</p>
        </div>
        
        <div id="content"></div>
        <div id="debug-info"></div>
        
        <div style="text-align: center;">
            <a href="<?php echo home_url(); ?>" class="back-button">
                <i class="fas fa-home"></i>
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>

    <script>
    
    
    // بازیابی داده از sessionStorage
    document.addEventListener('DOMContentLoaded', function() {
        const resultData = sessionStorage.getItem('diet_form_result');
        const debugContainer = document.getElementById('debug-info');
        const contentContainer = document.getElementById('content');
        
        console.log(resultData);
        
        if (resultData) {
            try {
                const parsedData = JSON.parse(resultData);
                
                // نمایش اطلاعات اعتبار باقیمانده
                if (parsedData.remaining_credit !== undefined) {
                    debugContainer.innerHTML += '<div class="credit-info"><i class="fas fa-coins"></i> اعتبار باقیمانده: ' + parsedData.remaining_credit.toLocaleString() + ' تومان</div>';
                }
                
                // پردازش response اگر وجود دارد
                if (parsedData.response) {
                    try {
                        // response خودش یک رشته JSON است، پس باید آن را هم parse کنیم
                        const responseData = JSON.parse(parsedData.response);
                        renderContent(responseData);
                    } catch (e) {
                        debugContainer.innerHTML += '<div class="error"><i class="fas fa-exclamation-circle"></i> خطا در پردازش response: ' + e.message + '</div>';
                        // اگر parse نشد، به عنوان متن ساده نمایش دهیم
                        contentContainer.innerHTML = '<div class="section"><h2><i class="fas fa-exclamation-triangle"></i> نتایج برنامه غذایی</h2><div class="error">' + parsedData.response + '</div></div>';
                    }
                } else {
                    // اگر response وجود ندارد، کل داده را نمایش دهیم
                    renderContent(parsedData);
                }
            } catch (e) {
                debugContainer.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> خطا در پردازش داده‌ها: ' + e.message + '</div>';
                contentContainer.innerHTML = '<div class="section"><p>خطا در پردازش داده‌ها</p></div>';
            }
        } else {
            debugContainer.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> هیچ داده‌ای در sessionStorage یافت نشد</div>';
            contentContainer.innerHTML = '<div class="section"><p>داده‌ای برای نمایش وجود ندارد.</p></div>';
        }
    });

    function renderContent(data) {
        const container = document.getElementById("content");
        container.innerHTML = ''; // پاک کردن محتوای قبلی
        
        // اگر داده ساختار مورد انتظار را ندارد، از ساختار عمومی استفاده کنیم
        if (!data.sections || !Array.isArray(data.sections)) {
            container.innerHTML = '<div class="section"><h2><i class="fas fa-utensils"></i> نتایج برنامه غذایی</h2>' +
                '<div class="debug">' + JSON.stringify(data, null, 2) + '</div></div>';
            return;
        }

        // ایجاد بخش‌های مختلف
        data.sections.forEach(section => {
            const sectionDiv = document.createElement("div");
            sectionDiv.className = "section";

            const h2 = document.createElement("h2");
            h2.innerHTML = '<i class="fas fa-' + getSectionIcon(section.title) + '"></i> ' + (section.title || "بخش بدون عنوان");
            sectionDiv.appendChild(h2);

            // بررسی نوع محتوا
            if (Array.isArray(section.content)) {
                section.content.forEach(sub => {
                    if (sub && sub.subtitle) {
                        const h3 = document.createElement("h3");
                        h3.textContent = sub.subtitle;
                        sectionDiv.appendChild(h3);
                    }

                    if (sub && sub.type === "list" && sub.items) {
                        const ul = document.createElement("ul");
                        sub.items.forEach(item => {
                            const li = document.createElement("li");
                            if (item && item.label && item.value) {
                                li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                            } else if (typeof item === "string") {
                                li.textContent = item;
                            }
                            ul.appendChild(li);
                        });
                        sectionDiv.appendChild(ul);
                    }
                    
                    if (sub && sub.type === "table" && sub.headers && sub.rows) {
                        // ایجاد جدول برای دسکتاپ
                        const table = document.createElement("table");
                        const thead = document.createElement("thead");
                        const headRow = document.createElement("tr");
                        
                        sub.headers.forEach(h => {
                            const th = document.createElement("th");
                            th.textContent = h;
                            headRow.appendChild(th);
                        });
                        
                        thead.appendChild(headRow);
                        table.appendChild(thead);

                        const tbody = document.createElement("tbody");
                        sub.rows.forEach(row => {
                            const tr = document.createElement("tr");
                            row.forEach(cell => {
                                const td = document.createElement("td");
                                td.textContent = cell;
                                tr.appendChild(td);
                            });
                            tbody.appendChild(tr);
                        });
                        
                        table.appendChild(tbody);
                        sectionDiv.appendChild(table);
                        
                        // ایجاد کارت‌های موبایل
                        const mobileCards = document.createElement("div");
                        mobileCards.className = "mobile-cards";
                        
                        sub.rows.forEach((row, index) => {
                            const dayCard = document.createElement("div");
                            dayCard.className = "day-card";
                            
                            const dayHeader = document.createElement("div");
                            dayHeader.className = "day-header";
                            dayHeader.innerHTML = '<i class="fas fa-calendar-day"></i> <span>' + row[0] + '</span>';
                            dayCard.appendChild(dayHeader);
                            
                            // اضافه کردن وعده‌های غذایی
                            for (let i = 1; i < row.length; i++) {
                                if (row[i]) {
                                    const mealSection = document.createElement("div");
                                    mealSection.className = "meal-section";
                                    
                                    const mealTitle = document.createElement("div");
                                    mealTitle.className = "meal-title";
                                    mealTitle.innerHTML = '<i class="fas fa-' + getMealIcon(i) + '"></i> <span>' + sub.headers[i] + '</span>';
                                    
                                    const mealContent = document.createElement("div");
                                    mealContent.textContent = row[i];
                                    
                                    mealSection.appendChild(mealTitle);
                                    mealSection.appendChild(mealContent);
                                    dayCard.appendChild(mealSection);
                                }
                            }
                            
                            mobileCards.appendChild(dayCard);
                        });
                        
                        sectionDiv.appendChild(mobileCards);
                    }
                });
            } else if (section.content && typeof section.content === "object") {
                // پردازش محتوای ساده
                if (section.content.type === "list" && section.content.items) {
                    const ul = document.createElement("ul");
                    section.content.items.forEach(item => {
                        const li = document.createElement("li");
                        if (typeof item === "string") {
                            li.textContent = item;
                        } else if (item && item.label && item.value) {
                            li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                        }
                        ul.appendChild(li);
                    });
                    sectionDiv.appendChild(ul);
                }
                
                if (section.content.type === "paragraph" && section.content.text) {
                    const p = document.createElement("p");
                    p.textContent = section.content.text;
                    sectionDiv.appendChild(p);
                }
                
                if (section.content.type === "nested_list" && section.content.items) {
                    const ul = document.createElement("ul");
                    section.content.items.forEach(item => {
                        const li = document.createElement("li");
                        if (item && item.label && item.value) {
                            li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                        }
                        ul.appendChild(li);
                    });
                    sectionDiv.appendChild(ul);
                }
            }

            container.appendChild(sectionDiv);
        });

        // ایجاد فوتر
        if (data.footer) {
            const footer = document.createElement("div");
            footer.className = "footer";
            footer.textContent = data.footer;
            container.appendChild(footer);
        }
    }

    function getSectionIcon(sectionTitle) {
        const icons = {
            'اطلاعات کاربر': 'user',
            'اطلاعات تغذیه‌ای': 'chart-pie',
            'برنامه هفتگی': 'calendar-alt',
            'توصیه‌های تکمیلی': 'lightbulb',
            'نتایج برنامه غذایی': 'utensils'
        };
        
        return icons[sectionTitle] || 'file-alt';
    }

    function getMealIcon(mealIndex) {
        const icons = {
            1: 'sun', // صبحانه
            2: 'sun', // ناهار
            3: 'moon', // شام
            4: 'apple-alt' // میان وعده
        };
        
        return icons[mealIndex] || 'utensils';
    }
    </script>
</body>
</html>