/**
 * PDF Processor - استخراج FBS و CBC
 * @file pdf-processor.js
 */

window.PDFProcessor = {
    validRanges: {
        // قند و متابولیسم
        'FBS': { min: 50, max: 400, unit: 'mg/dL' },
        'HbA1c': { min: 3, max: 20, unit: '%' },
        'Insulin': { min: 1, max: 100, unit: 'µIU/mL' },
        
        // چربی‌های خون
        'Cholesterol': { min: 100, max: 500, unit: 'mg/dL' },
        'Triglyceride': { min: 30, max: 1000, unit: 'mg/dL' },
        'LDL': { min: 30, max: 300, unit: 'mg/dL' },
        'HDL': { min: 20, max: 150, unit: 'mg/dL' },
        'VLDL': { min: 5, max: 100, unit: 'mg/dL' },
        
        // عملکرد کبد
        'SGOT': { min: 5, max: 500, unit: 'U/L' },
        'SGPT': { min: 5, max: 500, unit: 'U/L' },
        'ALP': { min: 30, max: 1000, unit: 'U/L' },
        
        // عملکرد کلیه
        'UricAcid': { min: 2, max: 15, unit: 'mg/dL' },
        'Creatinine': { min: 0.3, max: 15, unit: 'mg/dL' },
        'BUN': { min: 5, max: 200, unit: 'mg/dL' },  // ✅ افزایش از 50 به 200
        
        // ویتامین‌ها و مواد معدنی
        'Magnesium': { min: 1.5, max: 4, unit: 'mg/dL' },
        'Zinc': { min: 50, max: 300, unit: 'µg/dL' },
        'VitaminB12': { min: 100, max: 2000, unit: 'pg/mL' },
        'VitaminD': { min: 5, max: 200, unit: 'ng/mL' },
        'Ferritin': { min: 5, max: 1500, unit: 'ng/mL' },
        'Copper': { min: 50, max: 300, unit: 'µg/dL' },
        
        // هورمون‌های تیروئید
        'T3': { min: 50, max: 300, unit: 'ng/dL' },
        'T4': { min: 3, max: 25, unit: 'µg/dL' },
        'TSH': { min: 0.1, max: 50, unit: 'µIU/mL' },
        
        // التهاب
        'CRP': { min: 0, max: 200, unit: 'mg/L' },  // ✅ تکراری حذف شد
        'ESR': { min: 0, max: 150, unit: 'mm/hr' },
        
        // شمارش کامل خون (CBC)
        'WBC': { min: 2.0, max: 20.0, unit: '×10³/µL' },
        'RBC': { min: 3.0, max: 7.0, unit: 'million/µL' },
        'HGB': { min: 8, max: 20, unit: 'g/dL' },
        'HCT': { min: 25, max: 60, unit: '%' },  // ✅ اضافه شد
        'MCV': { min: 60, max: 120, unit: 'fL' },
        'MCH': { min: 20, max: 40, unit: 'pg' },  // ✅ اضافه شد
        'MCHC': { min: 28, max: 38, unit: 'g/dL' },  // ✅ اضافه شد
        'PLT': { min: 100, max: 600, unit: '×10³/µL' },  // ✅ اضافه شد
        'RDW': { min: 10, max: 20, unit: '%' }  // ✅ اضافه شد
    },


    /**
     * اعتبارسنجی مقدار استخراج شده
     * @param {string} testName - نام آزمایش
     * @param {number} value - مقدار استخراج شده
     * @returns {Object} - {isValid: boolean, reason: string}
     */
    validateValue(testName, value) {
        if (value === null || value === undefined || isNaN(value)) {
            return { isValid: false, reason: 'مقدار معتبر نیست' };
        }

        const range = this.validRanges[testName];
        if (!range) {
            // اگه رنجی تعریف نشده، بپذیر
            return { isValid: true, reason: '' };
        }

        if (value < range.min || value > range.max) {
            console.warn(`⚠️ ${testName}: مقدار ${value} خارج از رنج معتبر (${range.min}-${range.max} ${range.unit})`);
            return { 
                isValid: false, 
                reason: `خارج از رنج معتبر (${range.min}-${range.max} ${range.unit})`
            };
        }

        return { isValid: true, reason: '' };
    },

    async processPDF(file) {
        try {
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
            const pdf = await loadingTask.promise;
            const totalPages = pdf.numPages;
    
            let fullText = '';
    
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                // ✅ به‌روزرسانی لودر قبل از پردازش صفحه
                if (window.aidastyarLoader && typeof window.aidastyarLoader.update === 'function') {
                    window.aidastyarLoader.update(
                        `در حال خواندن صفحه ${pageNum} از ${totalPages}...`
                    );
                }
    
                const page = await pdf.getPage(pageNum);
                const textContent = await page.getTextContent();
                const pageText = textContent.items
                    .map(item => item.str)
                    .join(' ');
    
                // بررسی نیاز به OCR
                if (pageText.trim().length < 50) { // threshold برای OCR
    
                    // ✅ به‌روزرسانی لودر برای OCR
                    if (window.aidastyarLoader && typeof window.aidastyarLoader.update === 'function') {
                        window.aidastyarLoader.update(
                            `در حال خواندن صفحه ${pageNum} از ${totalPages}...<br><small style="color:#ff9800">⚠️ استفاده از OCR برای این صفحه از PDF</small>`
                        );
                    }
    
                    // رندر صفحه به canvas برای OCR
                    const viewport = page.getViewport({ scale: 2.0 }); // افزایش scale برای دقت بهتر
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
    
                    await page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;
    
                    // اجرای OCR با Tesseract
                    const worker = await Tesseract.createWorker('eng+fas');
                    const { data: { text } } = await worker.recognize(canvas);
                    await worker.terminate();
    
                    fullText += text;
                } else {
                    fullText += pageText;
                    console.log(`✅ صفحه ${pageNum}: متن استخراج شد (${pageText.trim().length} کاراکتر)`);
                }
    
                // ✅ به‌روزرسانی لودر بعد از پردازش هر صفحه
                if (window.aidastyarLoader && typeof window.aidastyarLoader.update === 'function') {
                    window.aidastyarLoader.update(
                        `صفحه ${pageNum} از ${totalPages} پردازش شد...`
                    );
                }
    
                // ✅ اضافه کردن تأخیر کوتاه برای نمایش لودر
                await new Promise(resolve => setTimeout(resolve, 100)); // 100ms delay
            }
    
            console.log('📝 متن استخراج شده:', fullText);
    
            // استخراج آزمایشات
            const results = [
                this.extractFBS(fullText),
                this.extractInsulin(fullText),
                this.extractHbA1c(fullText),
                this.extractCholesterol(fullText),
                this.extractTriglyceride(fullText),
                this.extractLDL(fullText),
                this.extractHDL(fullText),
                this.extractVLDL(fullText),
                this.extractSGOT(fullText),
                this.extractSGPT(fullText),
                this.extractALP(fullText),
                this.extractUricAcid(fullText),
                this.extractCreatinine(fullText),
                this.extractMagnesium(fullText),
                this.extractZinc(fullText),
                this.extractVitaminB12(fullText),
                this.extractVitaminD(fullText),
                this.extractFerritin(fullText),
                this.extractT3(fullText),
                this.extractT4(fullText),
                this.extractTSH(fullText),
                this.extractCRP(fullText),
                this.extractESR(fullText),
                this.extractCopper(fullText),
                this.extractBUN(fullText),
                ...this.extractCBC(fullText)
            ];
    
            const foundCount = results.filter(r => r.found && r.value !== null).length;
    
            return results;
        } catch (error) {
            console.error('❌ خطا در پردازش PDF:', error);
            throw error;
        }
    },
    /**
     * استخراج قند خون ناشتا (FBS)
     * بهینه شده برای فرمت‌های جدولی و متنوع PDF
     */
    extractFBS(text) {
        const fbsPatterns = [
            // ✅ 1. فرمت جدولی با واحد (اولویت اول - دقیق‌ترین)
            /Fasting\s+Serum\s+Glucose\s*\(\s*FBS\s*\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
            /Fasting\s+Blood\s+Sugar\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
            /\bFBS\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
            /Fasting\s+Glucose\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
            /Glucose\s*[,\s]*\(?Fasting\)?\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
            
            // ✅ 2. Fasting Serum Glucose (با علامت اجباری)
            /Fasting\s+Serum\s+Glucose\s*\(\s*FBS\s*\)\s*[:\-]\s*(\d+\.?\d*)/gi,
            /Fasting\s+Serum\s+Glucose\s*[:\-]\s*(\d+\.?\d*)/gi,
            /Serum\s+Glucose\s*[,\s]*\(?Fasting\)?\s*[:\-]\s*(\d+\.?\d*)/gi,
            
            // ✅ 3. FBS اصلی (با Word Boundary و علامت اجباری)
            /\bFBS\b\s*[:\-]\s*(\d+\.?\d*)/gi,
            /F\.?\s*B\.?\s*S\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
            
            // 4. Fasting Blood Sugar
            /Fasting\s+Blood\s+Sugar\s*[:\-]\s*(\d+\.?\d*)/gi,
            /Blood\s+Sugar\s*[,\s]*\(?Fasting\)?\s*[:\-]\s*(\d+\.?\d*)/gi,
            
            // 5. Fasting Glucose
            /Fasting\s+Glucose\s*[:\-]\s*(\d+\.?\d*)/gi,
            /Glucose\s*[,\s]*\(?Fasting\)?\s*[:\-]\s*(\d+\.?\d*)/gi,
            /Glucose\s+\(?\s*Fasting\s*\)?\s*[:\-]\s*(\d+\.?\d*)/gi,
            
            // 6. Fasting Blood/Plasma Glucose
            /Fasting\s+Blood\s+Glucose\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Fasting\s+Plasma\s+Glucose\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /FPG\s*[:\-]\s*(\d+\.?\d*)/gi,
            
            // 7. BS (Fasting)
            /BS\s*\(\s*Fasting\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /BS\s*\(\s*F\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Sugar\s*\(\s*F\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // 8. GLU-F (اختصار آزمایشگاهی)
            /GLU-F\s*[:\-]\s*(\d+\.?\d*)/gi,
            /GLU\s*\(\s*F\s*\)\s*[:\-]\s*(\d+\.?\d*)/gi,
            
            // 9. فارسی
            /قند\s+خون\s+ناشتا\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /گلوکز\s+ناشتا\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let fbsValue = null;
        let matchedPattern = null;
        let patternIndex = -1;
    
        for (let i = 0; i < fbsPatterns.length; i++) {
            const pattern = fbsPatterns[i];
            pattern.lastIndex = 0;
            const match = pattern.exec(text);  // ✅ استفاده از exec به جای match
            
            if (match && match[1]) {  // ✅ مستقیماً match[1] را چک می‌کنیم
                const tempValue = parseFloat(match[1]);
                
                const validation = this.validateValue('FBS', tempValue);
                if (validation.isValid) {
                    fbsValue = tempValue;
                    matchedPattern = match[0];
                    patternIndex = i + 1;
                    console.log(`✅ FBS استخراج شد: ${fbsValue} mg/dL` + `   📝 متن مطابقت: "${matchedPattern}"`);
                    break;
                } else {
                    console.warn(`❌ FBS رد شد: ${tempValue} - ${validation.reason}`);
                }
            }
        }
    
        if (fbsValue === null) {
            console.warn('⚠️ FBS معتبر پیدا نشد.');
            
            // 🔍 بررسی وجود کلمات کلیدی
            const keywords = [
                { name: 'FBS', pattern: /\bFBS\b/i },
                { name: 'Fasting', pattern: /Fasting/i },
                { name: 'Glucose', pattern: /Glucose/i },
                { name: 'Serum', pattern: /Serum/i },
                { name: 'Blood Sugar', pattern: /Blood\s+Sugar/i }
            ];
            
            keywords.forEach(kw => {
                if (kw.pattern.test(text)) {
                    console.log(`  ✓ "${kw.name}" یافت شد`);
                    
                    // نمایش متن اطراف کلمه کلیدی
                    const kwIndex = text.search(kw.pattern);
                    const contextStart = Math.max(0, kwIndex - 50);
                    const contextEnd = Math.min(text.length, kwIndex + 100);
                    console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
                }
            });
            
            // 🔍 بررسی احتمال فرمت جدولی
            if (/mg\/d[lL]/i.test(text)) {
                console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
            }
        }
    
        return {
            name: 'Fasting Blood Sugar (FBS)',
            found: fbsValue !== null,
            value: fbsValue,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },
extractHbA1c(text) {
    const patterns = [
        // ✅ 1. الگوی دقیق برای PDF شما: Glycated Hb. (HbA1c) 5.8 %
        /Glycated\s+Hb\.\s*\(\s*HbA[1Il]c\s*\)\s+(\d+\.?\d*)\s+%/gi,
        /Glycated\s+Hb\.\s*\(\s*HbA[1Il]c\s*\)\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // ✅ 2. فرمت جدولی با فاصله‌های زیاد
        /HbA[1Il]c\s+(\d+\.?\d*)\s+%/gi,
        /HbA[1Il]c\s{2,}(\d+\.?\d*)/gi,
        
        // 3. Glycated Hb بدون نقطه (با علامت اجباری)
        /Glycated\s+Hb\s*\(?\s*HbA[1Il]c\s*\)?\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Glycated\s+Hemoglobin\s*\(?\s*HbA[1Il]c\s*\)?\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Glycosylated\s+Hemoglobin\s*\(?\s*HbA[1Il]c\s*\)?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. HbA1c ساده (با علامت اجباری)
        /HbA[1Il]c\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Hb\s*A[1Il]c\s*[:\-]\s*(\d+\.?\d*)/gi,
        /H\.?b\.?A\.?[1Il]\.?c\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. Hemoglobin A1c
        /Hemoglobin\s+A[1Il]c\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Haemoglobin\s+A[1Il]c\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. A1c ساده
        /\bA[1Il]c\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 7. فارسی
        /هموگلوبین\s+گلیکوزیله\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /هموگلوبین\s+گلیکه\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 8. با واحد mmol/mol
        /HbA[1Il]c\s+(\d+\.?\d*)\s+mmol\/mol/gi,
        /HbA[1Il]c\s*[:\-]\s*(\d+\.?\d*)\s*mmol\/mol/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('HbA1c', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ HbA1c استخراج شد: ${value}% - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ HbA1c رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ HbA1c معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'HbA1c', pattern: /HbA[1Il]c/i },
            { name: 'Glycated', pattern: /Glycated/i },
            { name: 'Glycosylated', pattern: /Glycosylated/i },
            { name: 'Hemoglobin A1c', pattern: /Hemoglobin\s+A[1Il]c/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/% total Hb/i.test(text)) {
            console.log('  ℹ️ عبارت "% total Hb" در متن دیده شد - احتمال فرمت جدولی');
        } else if (/%/i.test(text)) {
            console.log('  ℹ️ واحد % در متن دیده شد');
        }
    }

    return {
        name: 'HbA1c',
        found: value !== null,
        value: value,
        unit: '%',
        matchedText: matchedPattern
    };
},
extractCholesterol(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول - دقیق‌ترین)
        /Cholestrol-Total\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,  // املای غلط (Cholestrol)
        /Cholesterol-Total\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Total\s+Cholesterol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bCholesterol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bChol\.?\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 2. Cholesterol/Total با علامت اجباری
        /Cholestrol-Total\s*[:\-]\s*(\d+\.?\d*)/gi,  // املای غلط
        /Cholesterol-Total\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Total\s+Cholesterol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Cholesterol\s*\(\s*Total\s*\)\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Cholesterol\s*,?\s*Total\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // ✅ 3. Cholesterol ساده (با Word Boundary و علامت اجباری)
        /\bCholesterol\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /\bChol\.\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. T-Chol یا TC
        /T-Chol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /T-Chol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /T\.?\s*Chol\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        /\bTC\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. فارسی
        /کلسترول\s+تام\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /کلسترول\s+کل\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /کلسترول\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. پترن‌های آزمایشگاهی
        /Serum\s+Cholesterol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Serum\s+Cholesterol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Cholesterol\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('Cholesterol', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Cholesterol استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Cholesterol رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Cholesterol معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Cholesterol', pattern: /Choles?trol/i },  // هم Cholesterol و هم Cholestrol
            { name: 'Total Cholesterol', pattern: /Total\s+Choles?trol/i },
            { name: 'Chol', pattern: /\bChol\b/i },
            { name: 'T-Chol', pattern: /T-Chol/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/d[lL]/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Cholesterol (Total)',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedText: matchedPattern
    };
},
extractTriglyceride(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول - دقیق‌ترین)
        /Triglycerides?\s+\(\s*Tg\s*\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Triglycerides?\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bTG\b\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\(Tg\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,  // از PDF شما
        
        // ✅ 2. Triglyceride با علامت اجباری
        /Triglycerides?\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Tri\s*glycerides?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // ✅ 3. TG اختصاری (با Word Boundary و علامت اجباری)
        /\bTG\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /T\.?\s*G\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. Serum/Blood Triglyceride
        /Serum\s+Triglycerides?\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Serum\s+Triglycerides?\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Triglycerides?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. فارسی
        /تری\s*گلیسرید\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /تری\s*گلیسیرید\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /تری\s+گلیسرید\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 6. پترن‌های آزمایشگاهی
        /Triglyceride\s+Level\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Triglyceride\s+Level\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Fasting\s+Triglycerides?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        /Triglycerides?\s+(\d+\.?\d*)\s+[HL]?\s*mg\/d[lL]/gi,
        /Triglycerides?\s+(\d+\.?\d*)\s+[HL]\s+mg\/d[lL]/gi,

        
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('Triglyceride', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Triglyceride استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Triglyceride رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Triglyceride معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Triglyceride', pattern: /Triglycerides?/i },
            { name: 'TG', pattern: /\bTG\b/i },
            { name: 'Tg (پرانتز)', pattern: /\(Tg\)/i },
            { name: 'Serum Triglyceride', pattern: /Serum\s+Triglycerides?/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/d[lL]/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Triglyceride (TG)',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedPattern: matchedPattern
    };
},
extractLDL(text) {
    const patterns = [
        // ✅ 1. الگوی دقیق برای PDF شما: LDL-Cholestrol (Direct) 105 mg/dL
        /LDL-Choles?trol\s*\(\s*Direct\s*\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /LDL-Choles?terol\s*\(\s*Direct\s*\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 2. فرمت جدولی با املای متنوع
        /LDL-Choles?trol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /LDL-Choles?terol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /LDL\s+Choles?trol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /LDL\s+Choles?terol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 3. LDL Cholesterol کامل (با علامت اجباری)
        /LDL\s*Choles?terol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /LDL\s*Choles?trol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /LDL\s*Chol\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. LDL-C
        /LDL-C\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /LDL-C\s*[:\-]\s*(\d+\.?\d*)/gi,
        /LDL\s*-\s*C\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. LDL ساده (با Word Boundary و علامت اجباری)
        /\bLDL\b\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bLDL\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. با Direct/Calculated
        /LDL\s*Choles?terol\s*,?\s*Direct\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /LDL\s*Choles?terol\s*,?\s*Direct\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Direct\s+LDL\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Calculated\s+LDL\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 7. Low Density Lipoprotein
        /Low\s+Density\s+Lipoprotein\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Low\s+Density\s+Lipoprotein\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Low\s*-?\s*Density\s+Lipoprotein\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 8. فارسی
        /لیپوپروتئین\s+کم\s+چگال\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /ال\s*دی\s*ال\s*[:\-]?\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('LDL', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ LDL استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ LDL رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ LDL معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'LDL', pattern: /\bLDL\b/i },
            { name: 'LDL-Cholesterol', pattern: /LDL-Choles?trol/i },
            { name: 'LDL (Direct)', pattern: /LDL.*Direct/i },
            { name: 'LDL-C', pattern: /LDL-C/i },
            { name: 'Low Density Lipoprotein', pattern: /Low\s+Density\s+Lipoprotein/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 150);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/d[lL]/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'LDL Cholesterol',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedPattern: matchedPattern
    };
},
extractHDL(text) {
    const patterns = [
        // ✅ 1. الگوی دقیق برای PDF: HDL-Cholestrol (Direct) 41 mg/dL
        /HDL-Choles?trol\s*\(\s*Direct\s*\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /HDL-Choles?terol\s*\(\s*Direct\s*\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 2. فرمت جدولی با املای متنوع
        /HDL-Choles?trol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /HDL-Choles?terol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /HDL\s+Choles?trol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /HDL\s+Choles?terol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 3. HDL Cholesterol کامل (با علامت اجباری)
        /HDL\s*Choles?terol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /HDL\s*Choles?trol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /HDL\s*Chol\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. HDL-C
        /HDL-C\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /HDL-C\s*[:\-]\s*(\d+\.?\d*)/gi,
        /HDL\s*-\s*C\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. HDL ساده (با Word Boundary و علامت اجباری)
        /\bHDL\b\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bHDL\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. با Direct
        /HDL\s*Choles?terol\s*,?\s*Direct\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /HDL\s*Choles?terol\s*,?\s*Direct\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Direct\s+HDL\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 7. High Density Lipoprotein
        /High\s+Density\s+Lipoprotein\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /High\s+Density\s+Lipoprotein\s*[:\-]\s*(\d+\.?\d*)/gi,
        /High\s*-?\s*Density\s+Lipoprotein\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 8. فارسی
        /لیپوپروتئین\s+پرچگال\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /اچ\s*دی\s*ال\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        /\bHDL\b\s+(\d+\.?\d*)\s+[HL]o?\s+mg\/d[lL]/gi,
        /HDL\s+(\d+\.?\d*)\s+Lo\s+med/gi,  // خاص این PDF        
        /\bHDL\b\s+(\d+\.?\d*)\s+Lo\s+med/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('HDL', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ HDL استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ HDL رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ HDL معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'HDL', pattern: /\bHDL\b/i },
            { name: 'HDL-Cholesterol', pattern: /HDL-Choles?trol/i },
            { name: 'HDL (Direct)', pattern: /HDL.*Direct/i },
            { name: 'HDL-C', pattern: /HDL-C/i },
            { name: 'High Density Lipoprotein', pattern: /High\s+Density\s+Lipoprotein/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 150);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/d[lL]/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'HDL Cholesterol',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedPattern: matchedPattern
    };
},
extractVLDL(text) {
    const patterns = [
        // ✅ 1. الگوی دقیق برای PDF: VLDL-Cholestrol (Calculated) 31 mg/dL
        /VLDL-Choles?trol\s*\(\s*Calculated\s*\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /VLDL-Choles?terol\s*\(\s*Calculated\s*\)\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 2. فرمت جدولی با املای متنوع
        /VLDL-Choles?trol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /VLDL-Choles?terol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /VLDL\s+Choles?trol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /VLDL\s+Choles?terol\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 3. VLDL Cholesterol کامل (با علامت اجباری)
        /VLDL\s*Choles?terol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /VLDL\s*Choles?trol\s*[:\-]\s*(\d+\.?\d*)/gi,
        /VLDL\s*Chol\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. VLDL-C
        /VLDL-C\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /VLDL-C\s*[:\-]\s*(\d+\.?\d*)/gi,
        /VLDL\s*-\s*C\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. VLDL ساده (با Word Boundary و علامت اجباری)
        /\bVLDL\b\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bVLDL\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. با Calculated
        /VLDL\s*Choles?terol\s*,?\s*Calculated\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /VLDL\s*Choles?terol\s*,?\s*Calculated\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Calculated\s+VLDL\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 7. Very Low Density Lipoprotein
        /Very\s+Low\s+Density\s+Lipoprotein\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Very\s+Low\s+Density\s+Lipoprotein\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Very\s*-?\s*Low\s*-?\s*Density\s+Lipoprotein\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 8. فارسی
        /لیپوپروتئین\s+بسیار\s+کم\s+چگال\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /وی\s*ال\s*دی\s*ال\s*[:\-]?\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('VLDL', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ VLDL استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ VLDL رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ VLDL معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'VLDL', pattern: /\bVLDL\b/i },
            { name: 'VLDL-Cholesterol', pattern: /VLDL-Choles?trol/i },
            { name: 'VLDL (Calculated)', pattern: /VLDL.*Calculated/i },
            { name: 'VLDL-C', pattern: /VLDL-C/i },
            { name: 'Very Low Density Lipoprotein', pattern: /Very\s+Low\s+Density\s+Lipoprotein/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 150);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/d[lL]/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'VLDL Cholesterol',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedPattern: matchedPattern
    };
},
extractSGOT(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /SGOT\s*\(\s*AST\s*\)\s+(\d+\.?\d*)\s+U\/L/gi,
        /AST\s*\(\s*SGOT\s*\)\s+(\d+\.?\d*)\s+U\/L/gi,
        /S\.?G\.?O\.?T\.?\s+(\d+\.?\d*)\s+U\/L/gi,
        /\bAST\b\s+(\d+\.?\d*)\s+U\/L/gi,
        /SGOT\s+(\d+\.?\d*)\s+U\/L/gi,
        
        // ✅ 2. SGOT/AST با علامت اجباری
        /SGOT\s*[:\-]\s*(\d+\.?\d*)/gi,
        /S\.?\s*G\.?\s*O\.?\s*T\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        /\bAST\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /A\.?\s*S\.?\s*T\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. SGOT (AST) ترکیبی
        /SGOT\s*\(\s*AST\s*\)\s*[:\-]\s*(\d+\.?\d*)/gi,
        /AST\s*\(\s*SGOT\s*\)\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. Aspartate Aminotransferase
        /Aspartate\s+Aminotransferase\s+(\d+\.?\d*)\s+U\/L/gi,
        /Aspartate\s+Aminotransferase\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Aspartate\s+Transaminase\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. فارسی
        /آسپارتات\s+آمینوترانسفراز\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /\bآ\.س\.ت\b\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 6. پترن‌های آزمایشگاهی
        /Serum\s+SGOT\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /Serum\s+AST\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        /S\.G\.O\.T\.\s*\(\s*AST\s*\)\s+(\d+\.?\d*)\s+U\/L/gi,  // نقطه اجباری بعد T
        
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('SGOT', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ SGOT (AST) استخراج شد: ${value} U/L - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ SGOT (AST) رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ SGOT (AST) معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'SGOT', pattern: /SGOT/i },
            { name: 'AST', pattern: /\bAST\b/i },
            { name: 'S.G.O.T', pattern: /S\.G\.O\.T/i },
            { name: 'Aspartate Aminotransferase', pattern: /Aspartate\s+Aminotransferase/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/U\/L/i.test(text)) {
            console.log('  ℹ️ واحد U/L در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'SGOT (AST)',
        found: value !== null,
        value: value,
        unit: 'U/L',
        matchedPattern: matchedPattern
    };
},

extractSGPT(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /SGPT\s*\(\s*ALT\s*\)\s+(\d+\.?\d*)\s+U\/L/gi,
        /ALT\s*\(\s*SGPT\s*\)\s+(\d+\.?\d*)\s+U\/L/gi,
        /SGPT\s+(\d+\.?\d*)\s+U\/L/gi,
        /\bALT\b\s+(\d+\.?\d*)\s+U\/L/gi,
        
        // 2. S.G.P.T با نقطه
        /S\.?\s*G\.?\s*P\.?\s*T\.?\s+(\d+\.?\d*)\s+U\/L/gi,
        /S\.?\s*G\.?\s*P\.?\s*T\.?\s*[:\-()]\s*(\d+\.?\d*)/gi,
        
        // 3. SGPT/ALT ساده (با علامت اجباری)
        /SGPT\s*[:\-(]\s*(\d+\.?\d*)/gi,  // ✅ علامت اجباری
        /\bALT\b\s*[:\-(]\s*(\d+\.?\d*)/gi,
        
        // 4. Alanine Aminotransferase
        /Alanine\s+Aminotransferase\s+(\d+\.?\d*)\s+U\/L/gi,
        /Alanine\s+Aminotransferase\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /Alanine\s+Transaminase\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        
        // 5. فارسی
        /آلانین\s+آمینوترانسفراز\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /\bآ\.ل\.ت\b\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 6. پترن‌های آزمایشگاهی
        /Serum\s+SGPT\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /Serum\s+ALT\s*[:\-]?\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('SGPT', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ SGPT (ALT) استخراج شد: ${value} U/L - الگو: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ SGPT (ALT) رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ SGPT (ALT) در این PDF یافت نشد - احتمالاً آزمایش نشده است');
        
        // 🔍 فقط برای دیباگ
        if (/SGPT/i.test(text) || /\bALT\b/i.test(text)) {
            console.log('ℹ️ کلمه SGPT/ALT در متن وجود دارد اما مقدار معتبری استخراج نشد');
        }
    }

    return {
        name: 'SGPT (ALT)',
        found: value !== null,
        value: value,
        unit: 'U/L',
        matchedText: matchedPattern
    };
},
extractALP(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /Alkaline\s+Phosphatase\s+(\d+\.?\d*)\s+U\/L/gi,
        /\bALP\b\s+(\d+\.?\d*)\s+U\/L/gi,
        /Alk\.\s+Phos\.\s+(\d+\.?\d*)\s+U\/L/gi,
        
        // ✅ 2. ALP با علامت اجباری
        /\bALP\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /A\.?\s*L\.?\s*P\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. Alkaline Phosphatase کامل
        /Alkaline\s+Phosphatase\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Alk\.?\s+Phos\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Alk\s+Phosphatase\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. فارسی
        /آلکالین\s+فسفاتاز\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /فسفاتاز\s+قلیایی\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 5. پترن‌های آزمایشگاهی
        /Serum\s+ALP\s+(\d+\.?\d*)\s+U\/L/gi,
        /Serum\s+ALP\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Total\s+ALP\s*[:\-]\s*(\d+\.?\d*)/gi,
        /ALP\s*,?\s*Total\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('ALP', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ ALP استخراج شد: ${value} U/L - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ ALP رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ ALP معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'ALP', pattern: /\bALP\b/i },
            { name: 'Alkaline Phosphatase', pattern: /Alkaline\s+Phosphatase/i },
            { name: 'Alk Phos', pattern: /Alk\.?\s+Phos/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/U\/L/i.test(text)) {
            console.log('  ℹ️ واحد U/L در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Alkaline Phosphatase (ALP)',
        found: value !== null,
        value: value,
        unit: 'U/L',
        matchedPattern: matchedPattern
    };
},
extractUricAcid(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /Uric\s+Acid\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Uric\s*-?\s*Acid\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bUA\b\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 2. Uric Acid با علامت اجباری
        /Uric\s+Acid\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Uric\s*-?\s*Acid\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. Uric Acid با پرانتز
        /Uric\s+Acid\s*\(\s*Serum\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /Uric\s+Acid\s*\(\s*Blood\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /Uric\s+Acid\s*\(\s*Plasma\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 4. Uric ساده
        /\bUric\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. UA اختصاری (با Word Boundary و علامت اجباری)
        /\bUA\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /U\.?\s*A\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. فارسی
        /اسید\s+اوریک\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /اسید\s*اوریک\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 7. پترن‌های آزمایشگاهی
        /Serum\s+Uric\s+Acid\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Serum\s+Uric\s+Acid\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Uric\s+Acid\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Urate\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('UricAcid', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Uric Acid استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Uric Acid رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Uric Acid معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Uric Acid', pattern: /Uric\s+Acid/i },
            { name: 'Uric', pattern: /\bUric\b/i },
            { name: 'UA', pattern: /\bUA\b/i },
            { name: 'Urate', pattern: /Urate/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/d[lL]/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Uric Acid',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedPattern: matchedPattern
    };
},
extractCreatinine(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /Creatinine\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Serum\s+Creatinine\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Blood\s+Creatinine\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bCr\b\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 2. Creatinine با علامت اجباری
        /Creatinine\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Serum\s+Creatinine\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Creatinine\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. اختصاری Cr (با Word Boundary و علامت اجباری)
        /\bCr\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /S\.?\s*Cr\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. فارسی
        /کراتینین\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /کراتی\s+نین\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 5. پترن‌های آزمایشگاهی
        /Creatinine\s*,?\s*Serum\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Creatinine\s*,?\s*Serum\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Serum\s+Cr\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('Creatinine', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Creatinine استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Creatinine رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Creatinine معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Creatinine', pattern: /Creatinine/i },
            { name: 'Serum Creatinine', pattern: /Serum\s+Creatinine/i },
            { name: 'Cr', pattern: /\bCr\b/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/d[lL]/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Creatinine',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedPattern: matchedPattern
    };
},
extractMagnesium(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی (با Negative Lookahead برای جلوگیری از mg/dL)
        /Magnesium\s+(\d+\.?\d*)\s+(?:mg\/dL|mmol\/L|mEq\/L)/gi,
        /\bMg\b(?!\/)\s+(\d+\.?\d*)\s+(?:mg\/dL|mmol\/L|mEq\/L)/gi,
        /Magnesium\s{2,}(\d+\.?\d*)/gi,
        /\bMg\b(?!\/)\s{2,}(\d+\.?\d*)/gi,
        
        // ✅ 2. فرمت معمولی (با علامت اجباری)
        /Magnesium\s*[:\-]\s*(\d+\.?\d*)/gi,
        /\bMg\b(?!\/)\s*[:\-]\s*(\d+\.?\d*)/gi,
        /M\.?\s*g\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. پترن‌های آزمایشگاهی
        /Serum\s+Magnesium\s+(\d+\.?\d*)\s+(?:mg\/dL|mmol\/L)/gi,
        /Serum\s+Magnesium\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Magnesium\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Total\s+Magnesium\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Magnesium\s*Level\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. فارسی
        /منیزیم\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /منیزیوم\s*[:\-]?\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('Magnesium', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Magnesium استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Magnesium رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Magnesium معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Magnesium', pattern: /Magnesium/i },
            { name: 'Mg (به عنوان نام آزمایش)', pattern: /\bMg\b(?!\/)/ },
            { name: 'Serum Magnesium', pattern: /Serum\s+Magnesium/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/dL/i.test(text) || /mmol\/L/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL یا mmol/L در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Magnesium (Mg)',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedPattern: matchedPattern
    };
},
extractZinc(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /Zinc\s+(\d+\.?\d*)\s+(?:µg\/dL|ug\/dL|mcg\/dL)/gi,
        /\bZn\b\s+(\d+\.?\d*)\s+(?:µg\/dL|ug\/dL|mcg\/dL)/gi,
        
        // ✅ 2. Zinc با علامت اجباری
        /Zinc\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Zinc\s*,?\s*Serum\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. Zn اختصاری (با Word Boundary و علامت اجباری)
        /\bZn\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Z\.?\s*n\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. فارسی
        /روی\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /زینک\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 5. پترن‌های آزمایشگاهی
        /Serum\s+Zinc\s+(\d+\.?\d*)\s+(?:µg\/dL|ug\/dL)/gi,
        /Serum\s+Zinc\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Plasma\s+Zinc\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Zinc\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Zinc\s*Level\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('Zinc', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Zinc استخراج شد: ${value} µg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Zinc رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Zinc معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Zinc', pattern: /Zinc/i },
            { name: 'Zn', pattern: /\bZn\b/i },
            { name: 'Serum Zinc', pattern: /Serum\s+Zinc/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/µg\/dL|ug\/dL|mcg\/dL/i.test(text)) {
            console.log('  ℹ️ واحد µg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Zinc (Zn)',
        found: value !== null,
        value: value,
        unit: 'µg/dL',
        matchedPattern: matchedPattern
    };
},
extractVitaminB12(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /Vitamin\s+B-?12\s+(\d+\.?\d*)\s+pg\/mL/gi,
        /Vitamin\s+B\s*-?\s*12\s+(\d+\.?\d*)\s+pg\/mL/gi,
        /\bB-?12\b\s+(\d+\.?\d*)\s+pg\/mL/gi,
        
        // ✅ 2. Vitamin B12 با علامت اجباری
        /Vitamin\s+B-?12\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Vitamin\s+B\s*-?\s*12\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. Vit B12 اختصاری
        /Vit\.?\s+B-?12\s+(\d+\.?\d*)\s+pg\/mL/gi,
        /Vit\.?\s+B-?12\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Vit\s+B\s*-?\s*12\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. B12 ساده (با Word Boundary و علامت اجباری)
        /\bB-?12\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /\bB\s*-?\s*12\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. Cobalamin
        /Cobalamin\s+(\d+\.?\d*)\s+pg\/mL/gi,
        /Cobalamin\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Cyanocobalamin\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. فارسی
        /ویتامین\s+B-?12\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /ویتامین\s+بی\s*12\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /بی\s*12\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 7. پترن‌های آزمایشگاهی
        /Serum\s+Vitamin\s+B12\s+(\d+\.?\d*)\s+pg\/mL/gi,
        /Serum\s+Vitamin\s+B12\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Serum\s+B12\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Total\s+B12\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('VitaminB12', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Vitamin B12 استخراج شد: ${value} pg/mL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Vitamin B12 رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Vitamin B12 معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Vitamin B12', pattern: /Vitamin\s+B-?12/i },
            { name: 'B12', pattern: /\bB-?12\b/i },
            { name: 'Cobalamin', pattern: /Cobalamin/i },
            { name: 'Vit B12', pattern: /Vit\.?\s+B-?12/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/pg\/mL|pmol\/L/i.test(text)) {
            console.log('  ℹ️ واحد pg/mL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Vitamin B12',
        found: value !== null,
        value: value,
        unit: 'pg/mL',
        matchedPattern: matchedPattern
    };
},
extractVitaminD(text) {
    const patterns = [
        // ✅ 1. الگوی دقیق برای PDF شما: Vitamin D total (25OH) 32.0 ng/ml
        /Vitamin\s+D\s+[Tt]otal\s*\(\s*25\s*OH\s*\)\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /Vitamin\s+D\s*\(\s*25\s*OH\s*\)\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /Total\s+Vitamin\s+D\s*\(\s*25\s*OH\s*\)\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        
        // ✅ 2. فرمت جدولی با 25(OH)
        /25\s*\(\s*OH\s*\)\s+vit\.\s+D\s*\(\s*D[23]\s*\)\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /25\s*\(\s*OH\s*\)\s+vit\.\s+D\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /25\s*\(\s*OH\s*\)\s*D3?\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        
        // ✅ 3. Vitamin D ساده با واحد
        /Vitamin\s+D\s+[Tt]otal\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /Vitamin\s+D\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /Vit\.?\s+D\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        
        // 4. 25-OH Vitamin D
        /25-OH\s+Vitamin\s+D\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /25\s*OH\s+Vitamin\s+D\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        
        // 5. فرمت معمولی (با علامت اجباری)
        /Vitamin\s+D\s+Total\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Total\s+Vitamin\s+D\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Vitamin\s+D\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Vit\.?\s+D\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. 25(OH)D بدون کلمه Vitamin
        /25\s*\(\s*OH\s*\)\s*D3?\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /25\s*\(\s*OH\s*\)\s*D3?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 7. Hydroxyvitamin D
        /25-Hydroxyvitamin\s+D\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /25-Hydroxyvitamin\s+D\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Hydroxyvitamin\s+D\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 8. فارسی
        /ویتامین\s+D\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /ویتامین\s+دی\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 9. Serum
        /Serum\s+Vitamin\s+D\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /Serum\s+Vitamin\s+D\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Serum\s+25\s*\(\s*OH\s*\)\s*D\s+(\d+\.?\d*)\s+ng\/m[lL]/gi,
        /Serum\s+25\s*\(\s*OH\s*\)\s*D\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            // 🔥 فیلتر اضافی: اگر مقدار 25 است، رد کن (احتمالاً از 25OH گرفته)
            if (tempValue === 25) {
                console.warn(`⚠️ Vitamin D: مقدار ${tempValue} احتمالاً از "25OH" است - رد شد`);
                continue;
            }
            
            const validation = this.validateValue('VitaminD', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Vitamin D استخراج شد: ${value} ng/mL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Vitamin D رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Vitamin D معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Vitamin D', pattern: /Vitamin\s+D/i },
            { name: 'Vit D', pattern: /Vit\.?\s+D/i },
            { name: '25(OH)D', pattern: /25\s*\(\s*OH\s*\)\s*D/i },
            { name: '25-OH Vitamin D', pattern: /25-?OH\s+Vitamin\s+D/i },
            { name: 'Hydroxyvitamin D', pattern: /Hydroxyvitamin\s+D/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 150);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/ng\/m[lL]|nmol\/L/i.test(text)) {
            console.log('  ℹ️ واحد ng/mL یا nmol/L در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Vitamin D (25-OH-D)',
        found: value !== null,
        value: value,
        unit: 'ng/mL',
        matchedText: matchedPattern
    };
},
extractFerritin(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /Ferritin\s+(\d+\.?\d*)\s+ng\/mL/gi,
        /Ferritin\s+(\d+\.?\d*)\s+µg\/L/gi,
        
        // 2. Ferritin استاندارد
        /Ferritin\s*[:\-(]\s*(\d+\.?\d*)/gi,  // ✅ علامت اجباری
        /Serum\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        
        // 3. Ferritin ECL
        /Ferritin\s+ECL\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /Ferritin\s*\(\s*ECL\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 4. Ferritin با روش‌های مختلف
        /Ferritin\s*\(\s*CMIA\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /Ferritin\s*\(\s*CLIA\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /Ferritin\s*\(\s*ELISA\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 5. Ferritin با کاما
        /Ferritin\s*,?\s+Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 6. فارسی
        /فریتین\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 7. Intact/Total
        /Intact\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /Total\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        
        // 8. Blood/Plasma
        /Blood\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /Plasma\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);  // ✅ تغییر به exec
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('Ferritin', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Ferritin استخراج شد: ${value} ng/mL - الگو: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Ferritin رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        if (/Ferritin/i.test(text)) {
            console.log('✓ "Ferritin" یافت شد');
            const ferritinIndex = text.search(/Ferritin/i);
            const contextStart = Math.max(0, ferritinIndex - 50);
            const contextEnd = Math.min(text.length, ferritinIndex + 150);
            console.log('📄 متن اطراف Ferritin:', text.substring(contextStart, contextEnd));
        }
    }


    return {
        name: 'Ferritin',
        found: value !== null,
        value: value,
        unit: 'ng/mL',
        matchedText: matchedPattern
    };
},
extractT3(text) {
    const patterns = [
        // ✅ 1. الگوی دقیق برای PDF: Triiodothyronine:T3 1.47 nmol/L
        /Triiodothyronine\s*:\s*T3\s+(\d+\.?\d*)\s+nmol\/L/gi,
        /Tri-?iodothyronine\s*:\s*T3\s+(\d+\.?\d*)\s+nmol\/L/gi,
        /Triiodothyronine:?\s*T3\s+(\d+\.?\d*)\s+nmol\/[lL]/gi,

        // ✅ 2. فرمت جدولی با واحدهای مختلف
        /Triiodothyronine\s*:\s*T3\s+(\d+\.?\d*)\s+(?:nmol\/L|ng\/dL|ng\/mL)/gi,
        /\bT3\s+(\d+\.?\d*)\s+(?:nmol\/L|ng\/dL|ng\/mL)/gi,
        
        // 3. T3 ساده
        /\bT3\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /T\.?\s*3\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        
        // 4. Total T3
        /Total\s+T3\s+(\d+\.?\d*)\s+(?:nmol\/L|ng\/dL)/gi,
        /Total\s+T3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /T3\s+Total\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /TT3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        
        // 5. Free T3
        /Free\s+T3\s+(\d+\.?\d*)\s+(?:pmol\/L|pg\/mL)/gi,
        /Free\s+T3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /T3\s+Free\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /FT3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        
        // 6. Triiodothyronine ساده
        /Triiodothyronine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /Tri-iodothyronine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        
        // 7. فارسی
        /تری\s*یدوتیرونین\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /تی\s*3\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 8. پترن‌های آزمایشگاهی
        /Serum\s+T3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        /T3\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /T3\s+Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        /Triiodothyronine\s*[:=]?\s*T3\s*[:=]?\s*(\d+\.?\d*)\s*nmol\/L/gi,
        /T3\s*[:=]?\s*(\d+\.?\d*)\s*nmol\/L/gi,        
    ];

    let value = null;
    let matchedPattern = null;
    let detectedUnit = 'ng/dL';  // ✅ واحد پیش‌فرض

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            // 🔍 تشخیص واحد از متن
            if (/nmol\/L/i.test(match[0])) {
                detectedUnit = 'nmol/L';
            } else if (/ng\/dL/i.test(match[0])) {
                detectedUnit = 'ng/dL';
            } else if (/ng\/mL/i.test(match[0])) {
                detectedUnit = 'ng/mL';
            } else if (/pmol\/L/i.test(match[0])) {
                detectedUnit = 'pmol/L';  // برای Free T3
            } else if (/pg\/mL/i.test(match[0])) {
                detectedUnit = 'pg/mL';  // برای Free T3
            }
            
            // ✅ اعتبارسنجی با توجه به واحد
            let validation;
            if (detectedUnit === 'nmol/L') {
                // محدوده برای nmol/L: 1.2-2.6 (مطابق با PDF شما)
                if (tempValue >= 0.5 && tempValue <= 5.0) {
                    validation = { isValid: true };
                } else {
                    validation = { isValid: false, reason: `خارج از محدوده معقول برای ${detectedUnit}` };
                }
            } else if (detectedUnit === 'pmol/L' || detectedUnit === 'pg/mL') {
                // محدوده برای Free T3 (pmol/L): 2.5-6.5 یا (pg/mL): 1.5-4.5
                if (tempValue >= 1.0 && tempValue <= 10.0) {
                    validation = { isValid: true };
                } else {
                    validation = { isValid: false, reason: `خارج از محدوده معقول برای Free T3 (${detectedUnit})` };
                }
            } else {
                // محدوده برای ng/dL یا ng/mL: 50-300
                validation = this.validateValue('T3', tempValue);
            }
            
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ T3 استخراج شد: ${value} ${detectedUnit} - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ T3 رد شد: ${tempValue} ${detectedUnit} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ T3 معتبر پیدا نشد.');
        
        if (/\bT3\b/i.test(text)) {
            console.log('✓ "T3" یافت شد');
            const t3Index = text.search(/\bT3\b/i);
            const contextStart = Math.max(0, t3Index - 50);
            const contextEnd = Math.min(text.length, t3Index + 100);
            console.log('📄 متن اطراف T3:', text.substring(contextStart, contextEnd));
        }
        
        if (/Triiodothyronine/i.test(text)) {
            console.log('✓ "Triiodothyronine" یافت شد');
        }
    }

    return {
        name: 'T3',
        found: value !== null,
        value: value,
        unit: detectedUnit,  // ✅ واحد تشخیص داده شده
        matchedText: matchedPattern
    };
},
extractT4(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی: Thyroxine: T4 140 nmol/L
        /Thyroxine\s*:\s*T4\s+(\d+\.?\d*)\s+nmol\/L/gi,
        /Thyroxine\s+T4\s+(\d+\.?\d*)\s+nmol\/L/gi,
        /T4\s+(\d+\.?\d*)\s+nmol\/L/gi,
        
        // ✅ 2. فرمت با µg/dL
        /Total\s+T4\s+(\d+\.?\d*)\s+[µu]g\/dL/gi,
        /T4\s+(\d+\.?\d*)\s+[µu]g\/dL/gi,
        
        // 3. T4 با علامت اجباری
        /\bT4\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /T\.?\s*4\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. Thyroxine
        /Thyroxine\s+(\d+\.?\d*)\s+(?:nmol\/L|[µu]g\/dL)/gi,
        /Thyroxine\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. Total T4
        /Total\s+T4\s*[:\-]\s*(\d+\.?\d*)/gi,
        /T4\s*\(\s*Total\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 6. Free T4 (FT4)
        /Free\s+T4\s+(\d+\.?\d*)\s+(?:pmol\/L|ng\/dL)/gi,
        /Free\s+T4\s*[:\-]\s*(\d+\.?\d*)/gi,
        /FT4\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 7. فارسی
        /تیروکسین\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /تی\s*4\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 8. پترن‌های آزمایشگاهی
        /Serum\s+T4\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /Serum\s+Thyroxine\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // ✅ اضافه کردن pmol/L برای Free T4
        /Free\s*T4\s*\(?FT4\)?\s*[:=]?\s*(\d+\.?\d*)\s*pmol\/L/gi,
        /FT4\s*[:=]?\s*(\d+\.?\d*)\s*pmol\/L/gi,        
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('T4', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ T4 استخراج شد: ${value} nmol/L - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ T4 رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ T4 معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'T4', pattern: /\bT4\b/i },
            { name: 'Thyroxine', pattern: /Thyroxine/i },
            { name: 'Total T4', pattern: /Total\s+T4/i },
            { name: 'Free T4', pattern: /Free\s+T4/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/nmol\/L|µg\/dL|ug\/dL/i.test(text)) {
            console.log('  ℹ️ واحد nmol/L یا µg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'T4',
        found: value !== null,
        value: value,
        unit: 'nmol/L',
        matchedPattern: matchedPattern
    };
},
extractTSH(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی: TSH 2.5 µIU/mL
        /\bTSH\b\s+(\d+\.?\d*)\s+[µu]IU\/m[lL]/gi,
        /Thyroid\s+Stimulating\s+Hormone\s+(\d+\.?\d*)\s+[µu]IU\/m[lL]/gi,
        /T\.?S\.?H\.?\s+(\d+\.?\d*)\s+[µu]IU\/m[lL]/gi,
        
        // ✅ 2. TSH با علامت اجباری
        /\bTSH\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /T\.?\s*S\.?\s*H\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. Thyroid Stimulating Hormone
        /Thyroid\s+Stimulating\s+Hormone\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Thyrotropin\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. Serum TSH
        /Serum\s+TSH\s+(\d+\.?\d*)\s+[µu]IU\/m[lL]/gi,
        /Serum\s+TSH\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. فارسی
        /هورمون\s+محرک\s+تیروئید\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /تی\s*اس\s*اچ\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 6. با پرانتز
        /TSH\s*\(\s*[µu]IU\/m[lL]\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        /TSH\s*[:=]?\s*(\d+\.?\d*)\s*p[IU]+\/m[lL]/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('TSH', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ TSH استخراج شد: ${value} µIU/mL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ TSH رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ TSH معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'TSH', pattern: /\bTSH\b/i },
            { name: 'Thyroid Stimulating Hormone', pattern: /Thyroid\s+Stimulating\s+Hormone/i },
            { name: 'Thyrotropin', pattern: /Thyrotropin/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/[µu]IU\/m[lL]/i.test(text)) {
            console.log('  ℹ️ واحد µIU/mL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'TSH',
        found: value !== null,
        value: value,
        unit: 'µIU/mL',
        matchedPattern: matchedPattern
    };
},
extractCRP(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی: CRP 5.2 mg/L
        /\bCRP\b\s+(\d+\.?\d*)\s+mg\/L/gi,
        /C-Reactive\s+Protein\s+(\d+\.?\d*)\s+mg\/L/gi,
        /C\.?\s*R\.?\s*P\.?\s+(\d+\.?\d*)\s+mg\/L/gi,
        
        // ✅ 2. CRP با علامت اجباری
        /\bCRP\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /C\.?\s*R\.?\s*P\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. C-Reactive Protein
        /C-Reactive\s+Protein\s*[:\-]\s*(\d+\.?\d*)/gi,
        /C\s+Reactive\s+Protein\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. hs-CRP (high sensitivity)
        /hs-CRP\s+(\d+\.?\d*)\s+mg\/L/gi,
        /hs-CRP\s*[:\-]\s*(\d+\.?\d*)/gi,
        /High\s+Sensitivity\s+CRP\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 5. Quantitative CRP
        /Quantitative\s+CRP\s+(\d+\.?\d*)\s+mg\/L/gi,
        /Quantitative\s+CRP\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 6. فارسی
        /پروتئین\s+واکنشگر\s+C\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /سی\s*آر\s*پی\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 7. پترن‌های آزمایشگاهی
        /Serum\s+CRP\s+(\d+\.?\d*)\s+mg\/L/gi,
        /Serum\s+CRP\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('CRP', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ CRP استخراج شد: ${value} mg/L - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ CRP رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ CRP معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'CRP', pattern: /\bCRP\b/i },
            { name: 'C-Reactive Protein', pattern: /C-Reactive\s+Protein/i },
            { name: 'hs-CRP', pattern: /hs-CRP/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/L/i.test(text)) {
            console.log('  ℹ️ واحد mg/L در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'C-Reactive Protein (CRP)',
        found: value !== null,
        value: value,
        unit: 'mg/L',
        matchedPattern: matchedPattern
    };
},
extractESR(text) {
    const patterns = [
        // ✅ الگوی 1: ESR + عدد (1, 2, ...) + hr/hour + مقدار + واحد
        /\bESR\s+\d+\s*h(?:ou)?r?\.?\s+(\d+\.?\d*)\s+mm[\s\/]*h(?:r|our)?/gi,
        
        // ✅ الگوی 2: ESR + 1st/first/2nd + hr + مقدار + واحد
        /\bESR\s+(?:1st|2nd|first|second)\s*h(?:ou)?r?\.?\s+(\d+\.?\d*)\s+mm[\s\/]*h(?:r|our)?/gi,
        
        // ✅ الگوی 3: فرمت جدولی ساده (ESR + فاصله زیاد + مقدار)
        /\bESR\s{2,}(\d+\.?\d*)/gi,
        
        // ✅ الگوی 4: ESR با واحد (بدون hr)
        /\bESR\s+(\d+\.?\d*)\s+mm[\s\/]*h(?:r|our)?/gi,
        
        // ✅ الگوهای معمولی (با علامت اجباری)
        /\bESR\s*[:\-]\s*(\d+\.?\d*)/gi,
        /E\.?\s*S\.?\s*R\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // Erythrocyte Sedimentation Rate
        /Erythrocyte\s+Sedimentation\s+Rate\s+(\d+\.?\d*)\s+mm[\s\/]*h(?:r|our)?/gi,
        /Erythrocyte\s+Sedimentation\s+Rate\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // Sed Rate
        /Sed\.?\s+Rate\s+(\d+\.?\d*)\s+mm[\s\/]*h(?:r|our)?/gi,
        /Sed\.?\s+Rate\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // فارسی
        /ای\s*اس\s*آر\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /سرعت\s+رسوب\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // Westergren
        /Westergren\s+ESR\s+(\d+\.?\d*)\s+mm[\s\/]*h(?:r|our)?/gi,
        /Westergren\s+ESR\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        /ESR\s+1st\s+hr\s+(\d+\.?\d*)\s+[HL]?\s*mm\/hr/gi,

    ];

    let value = null;
    let matchedPattern = null;
    
    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            // 🔥 فیلتر: حذف مقادیر < 2 (احتمالاً اشتباه)
            if (tempValue < 2) {
                console.warn(`⚠️ ESR: مقدار ${tempValue} خیلی کوچک (احتمالاً از "1st hour" گرفته شده) - رد شد`);
                continue;
            }
            
            const validation = this.validateValue('ESR', tempValue);
            
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ ESR استخراج شد: ${value} mm/hr - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ ESR رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }
    
    if (!value) {
        console.warn('⚠️ ESR معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'ESR', pattern: /\bESR\b/i },
            { name: 'E.S.R', pattern: /E\.S\.R/i },
            { name: 'Erythrocyte Sedimentation Rate', pattern: /Erythrocyte\s+Sedimentation\s+Rate/i },
            { name: 'Sed Rate', pattern: /Sed\.?\s+Rate/i },
            { name: 'Westergren', pattern: /Westergren/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 150);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mm[\s\/]*h(?:r|our)?/i.test(text)) {
            console.log('  ℹ️ واحد mm/hr در متن دیده شد - احتمال فرمت جدولی');
        }
        
        // 🔍 بررسی فرمت "1st hour" یا "2nd hour"
        if (/(?:1st|2nd|first|second)\s*h(?:ou)?r/i.test(text)) {
            console.log('  ℹ️ عبارت "1st hour" یا "2nd hour" در متن دیده شد - احتمال فرمت چند ساعته');
        }
    }
    
    return {
        name: 'ESR (Erythrocyte Sedimentation Rate)',
        found: value !== null,
        value: value,
        unit: 'mm/hr',
        matchedText: matchedPattern
    };
},
extractCopper(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی: Copper 95 µg/dL
        /Copper\s+(\d+\.?\d*)\s+[µu]g\/dL/gi,
        /\bCu\b\s+(\d+\.?\d*)\s+[µu]g\/dL/gi,
        
        // ✅ 2. Copper با علامت اجباری
        /Copper\s*[:\-]\s*(\d+\.?\d*)/gi,
        /\bCu\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. Serum Copper
        /Serum\s+Copper\s+(\d+\.?\d*)\s+[µu]g\/dL/gi,
        /Serum\s+Copper\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Copper\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. فارسی
        /مس\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /کوپر\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 5. پترن‌های آزمایشگاهی
        /Plasma\s+Copper\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Copper\s*Level\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Total\s+Copper\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('Copper', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Copper استخراج شد: ${value} µg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Copper رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Copper معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Copper', pattern: /Copper/i },
            { name: 'Cu', pattern: /\bCu\b/i },
            { name: 'Serum Copper', pattern: /Serum\s+Copper/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/[µu]g\/dL|[µu]mol\/L/i.test(text)) {
            console.log('  ℹ️ واحد µg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Copper (Cu)',
        found: value !== null,
        value: value,
        unit: 'µg/dL',
        matchedPattern: matchedPattern
    };
},
extractCBC(text) {
    const cbcTests = [
        {
            name: 'WBC',
            fullName: 'White Blood Cells',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد (اولویت اول)
                /\bWBC\b\s+(\d+\.?\d*)\s+[×xX]?10[³3]?\/[µuμ]?[lL]/gi,
                /W\.?\s*B\.?\s*C\.?\s+(\d+\.?\d*)\s+[×xX]?10[³3]?\/[µuμ]?[lL]/gi,  // ✅ W.B.C
                /White\s+Blood\s+Cells?\s+(\d+\.?\d*)\s+[×xX]?10[³3]?\/[µuμ]?[lL]/gi,
                /\bWBC\b\s+(\d+\.?\d*)\s+1000\/[µuμ]?[lL]/gi,

                // ✅ 2. WBC با علامت اجباری
                /\bWBC\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /W\.?\s*B\.?\s*C\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. White Blood Cells کامل
                /White\s+Blood\s+Cells?\s*[:\-]\s*(\d+\.?\d*)/gi,
                /White\s+Blood\s+Cell\s+Count\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. Leukocyte
                /Leukocytes?\s+(\d+\.?\d*)\s+[×xX]?10[³3]?\/[µuμ]?[lL]/gi,
                /Leukocytes?\s*[:\-]\s*(\d+\.?\d*)/gi,
                /Leukocytes?\s+Count\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 5. فارسی
                /گلبول\s+سفید\s*[:\-]?\s*(\d+\.?\d*)/gi,
                /گلبول\s*های\s*سفید\s*[:\-]?\s*(\d+\.?\d*)/gi,
                /تعداد\s+گلبول\s+سفید\s*[:\-]?\s*(\d+\.?\d*)/gi,
                
                // 6. پترن‌های آزمایشگاهی
                /Total\s+WBC\s*[:\-]\s*(\d+\.?\d*)/gi,
                /Total\s+Leukocyte\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                /WBC\s*[:=]?\s*(\d+\.?\d*)\s*(?:1000\/[۰-۹۳]+|×?10³?\/[μu]?[lL])/gi,
                /W\.?B\.?C\.?\s*[:=]?\s*(\d+\.?\d*)\s*(?:1000\/[۰-۹۳]+|×?10³?\/[μu]?[lL])/gi,                
            ],
            unit: '×10³/µL',
            validationKey: 'WBC'
        },
        {
            name: 'HGB',
            fullName: 'Hemoglobin',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد (اولویت اول)
                /\bHGB\b\s+(\d+\.?\d*)\s+g\/d[lL]/gi,
                /\bHb\.?\s+(\d+\.?\d*)\s+g\/d[lL]/gi, 
                /H\.?\s*[Gg]\.?\s*[Bb]\.?\s+(\d+\.?\d*)\s+g\/d[lL]/gi,  // ✅ H.G.B
                /Hemoglobin\s+(\d+\.?\d*)\s+g\/d[lL]/gi,
                
                // ✅ 2. HGB/Hb با علامت اجباری
                /\bHGB\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /\bHb\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /H\.?\s*G\.?\s*B\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. Hemoglobin کامل
                /Hemoglobin\s*[:\-]\s*(\d+\.?\d*)/gi,
                /Haemoglobin\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. فارسی
                /هموگلوبین\s*[:\-]?\s*(\d+\.?\d*)/gi,
                /هموگلوبن\s*[:\-]?\s*(\d+\.?\d*)/gi,
                
                // 5. پترن‌های آزمایشگاهی
                /Blood\s+Hemoglobin\s*[:\-]\s*(\d+\.?\d*)/gi,
                /Total\s+Hemoglobin\s*[:\-]\s*(\d+\.?\d*)/gi,
            ],
            unit: 'g/dL',
            validationKey: 'HGB'
        },
        {
            name: 'RBC',
            fullName: 'Red Blood Cells',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد (اولویت اول)
                /\bRBC\b\s+(\d+\.?\d*)\s+[mM]il(?:lion)?\/[µuμ]?[lL]/gi,
                /\bRBC\b\s+(\d+\.?\d*)\s+[×xX]?10[⁶6]?\/[µuμ]?[lL]/gi,
                /R\.?\s*B\.?\s*C\.?\s+(\d+\.?\d*)\s+[mM]il(?:lion)?\/[µuμ]?[lL]/gi,  // ✅ R.B.C
                /Red\s+Blood\s+Cells?\s+(\d+\.?\d*)\s+[mM]il(?:lion)?\/[µuμ]?[lL]/gi,
                /\bRBC\b\s+(\d+\.?\d*)\s+[mM۲]/gi, // M یا ۲ فارسی

                // ✅ 2. RBC با علامت اجباری
                /\bRBC\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /R\.?\s*B\.?\s*C\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. Red Blood Cells کامل
                /Red\s+Blood\s+Cells?\s*[:\-]\s*(\d+\.?\d*)/gi,
                /Red\s+Blood\s+Cell\s+Count\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. Erythrocyte
                /Erythrocytes?\s+(\d+\.?\d*)\s+[mM]il(?:lion)?\/[µuμ]?[lL]/gi,
                /Erythrocytes?\s+(\d+\.?\d*)\s+[×xX]?10[⁶6]?\/[µuμ]?[lL]/gi,
                /Erythrocytes?\s*[:\-]\s*(\d+\.?\d*)/gi,
                /Erythrocytes?\s+Count\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 5. فارسی
                /گلبول\s+قرمز\s*[:\-]?\s*(\d+\.?\d*)/gi,
                /گلبول\s*های\s*قرمز\s*[:\-]?\s*(\d+\.?\d*)/gi,
                /تعداد\s+گلبول\s+قرمز\s*[:\-]?\s*(\d+\.?\d*)/gi,
                
                // 6. پترن‌های آزمایشگاهی
                /Total\s+RBC\s*[:\-]\s*(\d+\.?\d*)/gi,
            ],
            unit: 'million/µL',
            validationKey: 'RBC'
        },
        {
            name: 'MCV',
            fullName: 'Mean Corpuscular Volume',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد (اولویت اول)
                /\bMCV\b\s+(\d+\.?\d*)\s+f[lL]/gi,
                /M\.?\s*C\.?\s*V\.?\s+(\d+\.?\d*)\s+f[lL]/gi,  // ✅ M.C.V
                /Mean\s+Corpuscular\s+Volume\s+(\d+\.?\d*)\s+f[lL]/gi,
                
                // ✅ 2. MCV با علامت اجباری
                /\bMCV\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /M\.?\s*C\.?\s*V\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. Mean Corpuscular Volume کامل
                /Mean\s+Corpuscular\s+Volume\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. فارسی
                /حجم\s+متوسط\s+گلبول\s*[:\-]?\s*(\d+\.?\d*)/gi,
                /حجم\s+متوسط\s+سلولی\s*[:\-]?\s*(\d+\.?\d*)/gi,
            ],
            unit: 'fL',
            validationKey: 'MCV'
        },
        {
            name: 'MCH',
            fullName: 'Mean Corpuscular Hemoglobin',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد
                /\bMCH\b\s+(\d+\.?\d*)\s+pg/gi,
                /M\.?\s*C\.?\s*H\.?\s+(\d+\.?\d*)\s+pg/gi,  // ✅ M.C.H
                /Mean\s+Corpuscular\s+Hemoglobin\s+(\d+\.?\d*)\s+pg/gi,
                
                // ✅ 2. MCH با علامت اجباری
                /\bMCH\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /M\.?\s*C\.?\s*H\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. Mean Corpuscular Hemoglobin کامل
                /Mean\s+Corpuscular\s+Hemoglobin\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. فارسی
                /هموگلوبین\s+متوسط\s+گلبول\s*[:\-]?\s*(\d+\.?\d*)/gi,
            ],
            unit: 'pg',
            validationKey: 'MCH'
        },
        {
            name: 'MCHC',
            fullName: 'Mean Corpuscular Hemoglobin Concentration',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد
                /\bMCHC\b\s+(\d+\.?\d*)\s+g\/d[lL]/gi,
                /M\.?\s*C\.?\s*H\.?\s*C\.?\s+(\d+\.?\d*)\s+g\/d[lL]/gi,  // ✅ M.C.H.C
                /Mean\s+Corpuscular\s+Hemoglobin\s+Concentration\s+(\d+\.?\d*)\s+g\/d[lL]/gi,
                
                // ✅ 2. MCHC با علامت اجباری
                /\bMCHC\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /M\.?\s*C\.?\s*H\.?\s*C\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. Mean Corpuscular Hemoglobin Concentration کامل
                /Mean\s+Corpuscular\s+Hemoglobin\s+Concentration\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. فارسی
                /غلظت\s+هموگلوبین\s+متوسط\s*[:\-]?\s*(\d+\.?\d*)/gi,
            ],
            unit: 'g/dL',
            validationKey: 'MCHC'
        },
        {
            name: 'PLT',
            fullName: 'Platelet Count',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد (اولویت اول)
                /\bPLT\b\s+(\d+\.?\d*)\s+[×xX]?10[³3]?\/[µuμ]?[lL]/gi,
                /P\.?\s*L\.?\s*T\.?\s+(\d+\.?\d*)\s+[×xX]?10[³3]?\/[µuμ]?[lL]/gi,  // ✅ P.L.T
                /Platelets?\s+(\d+\.?\d*)\s+[×xX]?10[³3]?\/[µuμ]?[lL]/gi,
                /Platelet\s+Count\s+(\d+\.?\d*)\s+[×xX]?10[³3]?\/[µuμ]?[lL]/gi,
                /Platelets?\s+(\d+\.?\d*)\s+1000\/[µuμ]?[lL]/gi,

                // ✅ 2. PLT با علامت اجباری
                /\bPLT\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /P\.?\s*L\.?\s*T\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. Platelet کامل
                /Platelets?\s*[:\-]\s*(\d+\.?\d*)/gi,
                /Platelet\s+Count\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. فارسی
                /پلاکت\s*[:\-]?\s*(\d+\.?\d*)/gi,
                /تعداد\s+پلاکت\s*[:\-]?\s*(\d+\.?\d*)/gi,
                
                // 5. پترن‌های آزمایشگاهی
                /Total\s+Platelet\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // ✅ پشتیبانی از فرمت فارسی
                /Platelets?\s*[:=]?\s*(\d+\.?\d*)\s*(?:1000\/[۰-۹]+|×?10³?\/[μu]?[lL])/gi,
                /Platelet\s*Count\s*[:=]?\s*(\d+\.?\d*)\s*(?:1000\/[۰-۹]+)/gi,                
            ],
            unit: '×10³/µL',
            validationKey: 'PLT'
        },
        {
            name: 'RDW',
            fullName: 'Red Cell Distribution Width',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد
                /\bRDW\b\s+(\d+\.?\d*)\s+%/gi,
                /R\.?\s*D\.?\s*W\.?\s+(\d+\.?\d*)\s+%/gi,  // ✅ R.D.W
                /Red\s+Cell\s+Distribution\s+Width\s+(\d+\.?\d*)\s+%/gi,
                
                // ✅ 2. RDW با علامت اجباری
                /\bRDW\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /R\.?\s*D\.?\s*W\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. Red Cell Distribution Width کامل
                /Red\s+Cell\s+Distribution\s+Width\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. فارسی
                /پراکندگی\s+گلبول\s+قرمز\s*[:\-]?\s*(\d+\.?\d*)/gi,
            ],
            unit: '%',
            validationKey: 'RDW'
        },
        {
            name: 'HCT',
            fullName: 'Hematocrit',
            patterns: [
                // ✅ 1. فرمت جدولی با واحد
                /\bHCT\b\s+(\d+\.?\d*)\s+%/gi,
                /H\.?\s*C\.?\s*T\.?\s+(\d+\.?\d*)\s+%/gi,  // ✅ H.C.T
                /Hematocrit\s+(\d+\.?\d*)\s+%/gi,
                /Het\.?\s+(\d+\.?\d*)\s+%/gi,

                // ✅ 2. HCT با علامت اجباری
                /\bHCT\b\s*[:\-]\s*(\d+\.?\d*)/gi,
                /H\.?\s*C\.?\s*T\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 3. Hematocrit کامل
                /Hematocrit\s*[:\-]\s*(\d+\.?\d*)/gi,
                /Haematocrit\s*[:\-]\s*(\d+\.?\d*)/gi,
                
                // 4. فارسی
                /هماتوکریت\s*[:\-]?\s*(\d+\.?\d*)/gi,
            ],
            unit: '%',
            validationKey: 'HCT'
        }
    ];

    const results = [];

    for (const test of cbcTests) {
        let value = null;
        let matchedPattern = null;

        for (const pattern of test.patterns) {
            pattern.lastIndex = 0;
            const match = pattern.exec(text);
            
            if (match && match[1]) {
                const tempValue = parseFloat(match[1]);
                
                const validation = this.validateValue(test.validationKey, tempValue);
                if (validation.isValid) {
                    value = tempValue;
                    matchedPattern = match[0];
                    console.log(`✅ ${test.name} استخراج شد: ${value} ${test.unit} - متن مطابقت: "${matchedPattern}"`);
                    break;
                } else {
                    console.warn(`❌ ${test.name} رد شد: ${tempValue} - ${validation.reason}`);
                }
            }
        }

        if (!value) {
            console.warn(`⚠️ ${test.name} معتبر پیدا نشد.`);
            
            // 🔍 بررسی کلمات کلیدی
            const keywords = [
                { name: test.name, pattern: new RegExp(`\\b${test.name}\\b`, 'i') },
                { name: test.name.replace(/([A-Z])/g, '\\.$1').substring(2), pattern: new RegExp(test.name.split('').join('\\.?\\s*'), 'i') },  // W.B.C
                { name: test.fullName, pattern: new RegExp(test.fullName.replace(/\s+/g, '\\s+'), 'i') }
            ];
            
            keywords.forEach(kw => {
                if (kw.pattern.test(text)) {
                    console.log(`  ✓ "${kw.name}" یافت شد`);
                    const kwIndex = text.search(kw.pattern);
                    const contextStart = Math.max(0, kwIndex - 50);
                    const contextEnd = Math.min(text.length, kwIndex + 100);
                    console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
                }
            });
            
            // 🔍 بررسی احتمال فرمت جدولی
            if (/[×xX]?10[³3⁶6]?\/[µuμ]?[lL]|g\/d[lL]|f[lL]|pg|%/i.test(text)) {
                console.log(`  ℹ️ واحد ${test.unit} احتمالاً در متن دیده شد`);
            }
        }

        results.push({
            name: `${test.fullName} (${test.name})`,
            found: value !== null,
            value: value,
            unit: test.unit,
            matchedText: matchedPattern
        });
    }

    return results;
},
extractInsulin(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی: Insulin 12.5 µIU/mL
        /\bInsulin\b\s+(\d+\.?\d*)\s+[µu]IU\/m[lL]/gi,
        /Fasting\s+Insulin\s+(\d+\.?\d*)\s+[µu]IU\/m[lL]/gi,
        
        // ✅ 2. Insulin با علامت اجباری
        /\bInsulin\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Fasting\s+Insulin\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. Serum Insulin
        /Serum\s+Insulin\s+(\d+\.?\d*)\s+[µu]IU\/m[lL]/gi,
        /Serum\s+Insulin\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. فارسی
        /انسولین\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /انسولین\s+ناشتا\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 5. پترن‌های آزمایشگاهی
        /Plasma\s+Insulin\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Insulin\s*Level\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Basal\s+Insulin\s*[:\-]\s*(\d+\.?\d*)/gi,
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('Insulin', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ Insulin استخراج شد: ${value} µIU/mL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ Insulin رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ Insulin معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'Insulin', pattern: /\bInsulin\b/i },
            { name: 'Fasting Insulin', pattern: /Fasting\s+Insulin/i },
            { name: 'Serum Insulin', pattern: /Serum\s+Insulin/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/[µu]IU\/m[lL]|pmol\/L/i.test(text)) {
            console.log('  ℹ️ واحد µIU/mL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Insulin',
        found: value !== null,
        value: value,
        unit: 'µIU/mL',
        matchedPattern: matchedPattern
    };
},

extractBUN(text) {
    const patterns = [
        // ✅ 1. فرمت جدولی با واحد (اولویت اول)
        /\bBUN\b\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Blood\s+Urea\s+Nitrogen\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Blood\s+Urea\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /\bUrea\b\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        
        // ✅ 2. BUN/Urea با علامت اجباری
        /\bBUN\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        /B\.?\s*U\.?\s*N\.?\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Urea\s+Nitrogen\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Blood\s+Urea\s*[:\-]\s*(\d+\.?\d*)/gi,
        /\bUrea\b\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 3. Serum Urea/BUN
        /Serum\s+Urea\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Serum\s+Urea\s*[:\-]\s*(\d+\.?\d*)/gi,
        /Serum\s+BUN\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Serum\s+BUN\s*[:\-]\s*(\d+\.?\d*)/gi,
        
        // 4. فارسی
        /اوره\s+خون\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /نیتروژن\s+اوره\s*[:\-]?\s*(\d+\.?\d*)/gi,
        /بی\s*یو\s*ان\s*[:\-]?\s*(\d+\.?\d*)/gi,
        
        // 5. پترن‌های آزمایشگاهی
        /Urea\s+Nitrogen\s+(\d+\.?\d*)\s+mg\/d[lL]/gi,
        /Urea\s+Nitrogen\s*[:\-]\s*(\d+\.?\d*)/gi,
                
        // اضافه کردن پترن برای واحد بدون اسلش
        /Blood\s+Urea\s+(\d+\.?\d*)\s+[HL]?\s*mg\s*d[lL]/gi,  // بدون اسلش
        /Blood\s+Urea\s+(\d+\.?\d*)\s+[HL]?\s*mg\/d[lL]/gi,   // با اسلش        
    ];

    let value = null;
    let matchedPattern = null;

    for (const pattern of patterns) {
        pattern.lastIndex = 0;
        const match = pattern.exec(text);
        
        if (match && match[1]) {
            const tempValue = parseFloat(match[1]);
            
            const validation = this.validateValue('BUN', tempValue);
            if (validation.isValid) {
                value = tempValue;
                matchedPattern = match[0];
                console.log(`✅ BUN استخراج شد: ${value} mg/dL - متن مطابقت: "${matchedPattern}"`);
                break;
            } else {
                console.warn(`❌ BUN رد شد: ${tempValue} - ${validation.reason}`);
            }
        }
    }

    if (!value) {
        console.warn('⚠️ BUN معتبر پیدا نشد.');
        
        // 🔍 بررسی کلمات کلیدی
        const keywords = [
            { name: 'BUN', pattern: /\bBUN\b/i },
            { name: 'Blood Urea', pattern: /Blood\s+Urea/i },
            { name: 'Urea', pattern: /\bUrea\b/i },
            { name: 'Blood Urea Nitrogen', pattern: /Blood\s+Urea\s+Nitrogen/i },
            { name: 'Serum Urea', pattern: /Serum\s+Urea/i }
        ];
        
        keywords.forEach(kw => {
            if (kw.pattern.test(text)) {
                console.log(`  ✓ "${kw.name}" یافت شد`);
                const kwIndex = text.search(kw.pattern);
                const contextStart = Math.max(0, kwIndex - 50);
                const contextEnd = Math.min(text.length, kwIndex + 100);
                console.log(`    📄 متن: "${text.substring(contextStart, contextEnd)}"`);
            }
        });
        
        // 🔍 بررسی احتمال فرمت جدولی
        if (/mg\/d[lL]/i.test(text)) {
            console.log('  ℹ️ واحد mg/dL در متن دیده شد - احتمال فرمت جدولی');
        }
    }

    return {
        name: 'Blood Urea Nitrogen (BUN)',
        found: value !== null,
        value: value,
        unit: 'mg/dL',
        matchedText: matchedPattern
    };
}

};
