/**
 * PDF Processor - استخراج FBS و CBC
 * @file pdf-processor.js
 */

window.PDFProcessor = {
    /**
     * رنج‌های معتبر برای هر آزمایش
     */
    validRanges: {
        'FBS': { min: 50, max: 400, unit: 'mg/dL' },
        'HbA1c': { min: 3, max: 20, unit: '%' },
        'Insulin': { min: 1, max: 100, unit: 'µIU/mL' },
        'Cholesterol': { min: 100, max: 500, unit: 'mg/dL' },
        'Triglyceride': { min: 30, max: 1000, unit: 'mg/dL' },
        'LDL': { min: 30, max: 300, unit: 'mg/dL' },
        'HDL': { min: 20, max: 150, unit: 'mg/dL' },
        'VLDL': { min: 5, max: 100, unit: 'mg/dL' },
        'SGOT': { min: 5, max: 500, unit: 'U/L' },
        'SGPT': { min: 5, max: 500, unit: 'U/L' },
        'ALP': { min: 30, max: 1000, unit: 'U/L' },
        'UricAcid': { min: 2, max: 15, unit: 'mg/dL' },
        'Creatinine': { min: 0.3, max: 15, unit: 'mg/dL' },
        'Magnesium': { min: 1.5, max: 4, unit: 'mg/dL' },
        'Zinc': { min: 50, max: 300, unit: 'µg/dL' },
        'VitaminB12': { min: 100, max: 2000, unit: 'pg/mL' },
        'VitaminD': { min: 5, max: 200, unit: 'ng/mL' },
        'Ferritin': { min: 5, max: 1500, unit: 'ng/mL' },
        'T3': { min: 50, max: 300, unit: 'ng/dL' },
        'T4': { min: 3, max: 25, unit: 'µg/dL' },
        'TSH': { min: 0.1, max: 50, unit: 'µIU/mL' },
        'CRP': { min: 0, max: 100, unit: 'mg/L' },
        'ESR': { min: 0, max: 150, unit: 'mm/hr' },
        'Copper': { min: 50, max: 300, unit: 'µg/dL' },
        'WBC': { min: 2.0, max: 20.0, unit: '10³/µL' },
        'HGB': { min: 8, max: 20, unit: 'g/dL' },
        'RBC': { min: 3.0, max: 7.0, unit: 'million/µL' },
        'MCV': { min: 60, max: 120, unit: 'fL' },
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
            console.log('🔄 شروع پردازش PDF:', file.name);
            
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
            const pdf = await loadingTask.promise;
            const totalPages = pdf.numPages; // ✅ اضافه کردن این خط
            console.log(`📄 تعداد صفحات PDF: ${totalPages}`);
            
            let fullText = '';
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
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
      
                // اگر متنی پیدا نشد، از OCR استفاده کن
                if (pageText.trim().length < 50) { // threshold قابل تنظیم
                    console.log(`صفحه ${pageNum} احتمالاً تصویره، OCR فعال شد`);
                    
                    // ===== 🔥 پیام OCR =====
                    if (window.aidastyarLoader && typeof window.aidastyarLoader.update === 'function') {
                        window.aidastyarLoader.update(
                            `پردازش تصویری صفحه ${pageNum} از ${totalPages}...<br><small style="color:#ff9800;">⚠️ این ممکن است کمی طول بکشد</small>`
                        );
                    }                    
                    // تبدیل صفحه PDF به تصویر
                    const viewport = page.getViewport({scale: 2.0}); // scale بالاتر = دقت بهتر
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    
                    await page.render({canvasContext: context, viewport: viewport}).promise;
                    
                    // OCR با Tesseract
                    const worker = await Tesseract.createWorker('eng+fas'); // فارسی و انگلیسی
                    const { data: { text } } = await worker.recognize(canvas);
                    await worker.terminate();
                    
                    fullText += text + ' ';
                } else                    
                    fullText += pageText + '\n';
                
            }
            
            console.log('📝 متن استخراج شده:', fullText.substring(0, 200));
            
            const results = [
                this.extractFBS(fullText),
                this.extractInsulin(fullText),
                this.extractHbA1c(fullText),
                this.extractCholesterol(fullText),
                this.extractTriglyceride(fullText),
                this.extractLDL(fullText),
                this.extractHDL(fullText),
                this.extractVLDL(fullText),           // ✅ جدید
                this.extractSGOT(fullText),           // ✅ جدید
                this.extractSGPT(fullText),           // ✅ جدید
                this.extractALP(fullText),            // ✅ جدید
                this.extractUricAcid(fullText),       // ✅ جدید
                this.extractCreatinine(fullText),     // ✅ جدید
                this.extractMagnesium(fullText),      // ✅ جدید
                this.extractZinc(fullText),           // ✅ جدید
                this.extractVitaminB12(fullText),     // ✅ جدید
                this.extractVitaminD(fullText),       // ✅ جدید
                this.extractFerritin(fullText),       // ✅ جدید
                this.extractT3(fullText),             // ✅ جدید
                this.extractT4(fullText),             // ✅ جدید
                this.extractTSH(fullText),            // ✅ جدید
                this.extractCRP(fullText),            // ✅ جدید
                this.extractESR(fullText),            // ✅ جدید
                this.extractCopper(fullText),         // ✅ جدید
                ...this.extractCBC(fullText)
            ];

            // ❌ فیلتر حذف شد - همه رو برمی‌گردونه (حتی اگه پیدا نشده باشن)
            console.log(`📋 ${results.length} آزمایش در لیست:`, results);
            
            // نمایش تعداد پیدا شده
            const foundCount = results.filter(r => r.found && r.value !== null).length;
            console.log(`✅ ${foundCount} آزمایش پیدا شد از ${results.length}`);
            
            return results;  // 👈 همه رو برمی‌گردونه
            
        } catch (error) {
            console.error('❌ خطا در پردازش PDF:', error);
            throw error;
        }
    },
    /**
     * استخراج قند خون ناشتا (FBS)
     */
    extractFBS(text) {
        const fbsPatterns = [
            // === 1. Fasting Serum Glucose - با فاصله ===
            /Fasting\s+Serum\s+Glucose\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Serum\s+Glucose\s*[,\s]*\(?Fasting\)?\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Glucose\s*,?\s*Serum\s*[,\s]*Fasting\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 2. FBS اصلی ===
            /\bFBS\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /F\.?B\.?S\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Fasting Blood Sugar ===
            /Fasting\s+Blood\s+Sugar\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Sugar\s*[,\s]*Fasting\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Fasting Glucose ===
            /Fasting\s+Glucose\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Glucose\s*[,\s]*Fasting\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Glucose\s+\(?\s*Fasting\s*\)?\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 5. Fasting Blood Glucose ===
            /Fasting\s+Blood\s+Glucose\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 6. Fasting Plasma Glucose (FPG) ===
            /Fasting\s+Plasma\s+Glucose\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /FPG\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 7. پترن‌های فارسی ===
            /قند\s+خون\s+ناشتا\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /گلوکز\s+ناشتا\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /قند\s+خون\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 8. با واحد mg/dL یا mmol/L ===
            /FBS\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|mmol\/L)?/gi,
            /Glucose\s*[,\s]*Fasting\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|mmol\/L)?/gi,
            
            // === 9. BS (Fasting) ===
            /BS\s*\(?\s*F(?:asting)?\s*\)?\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Sugar\s*\(?\s*F\s*\)?\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 10. GLU-F (اختصار آزمایشگاهی) ===
            /GLU-F\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /GLU\s*\(\s*F\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let fbsValue = null;
        let matchedPattern = null;
    
        for (const pattern of fbsPatterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                // استخراج عدد از match
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('FBS', tempValue);
                    if (validation.isValid) {                    
                        fbsValue = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ FBS یافت شد: ${fbsValue} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ FBS رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن دیگر
                    }                        
                }
            }
        }
    
        if (fbsValue === null) {
            console.warn('⚠️ FBS معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن برای کلمات کلیدی:');
            if (text.toLowerCase().includes('fasting')) console.log('  ✓ "Fasting" یافت شد');
            if (text.toLowerCase().includes('glucose')) console.log('  ✓ "Glucose" یافت شد');
            if (text.toLowerCase().includes('serum')) console.log('  ✓ "Serum" یافت شد');
            if (text.toLowerCase().includes('fbs')) console.log('  ✓ "FBS" یافت شد');
        }
    
        return {
            name: 'Fasting Blood Sugar (FBS)',
            found: fbsValue !== null,
            value: fbsValue,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج HbA1c (Glycated Hemoglobin)
     */
    extractHbA1c(text) {
        const patterns = [
            // === 1. Glycated Hb با هر نوع املا ===
            /Glycated\s+Hb\.?\s*\(?\s*HbA[1Il]c\s*\)?\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Glycated\s+Hemoglobin\s*\(?\s*HbA[1Il]c\s*\)?\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Glycosylated\s+Hemoglobin\s*\(?\s*HbA[1Il]c\s*\)?\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 2. HbA1c اصلی (با تمام حالات املایی) ===
            /HbA[1Il]c\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Hb\s*A[1Il]c\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /H\.?b\.?A\.?[1Il]\.?c\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Hemoglobin A1c ===
            /Hemoglobin\s+A[1Il]c\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Haemoglobin\s+A[1Il]c\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. A1c ساده ===
            /\bA[1Il]c\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /هموگلوبین\s+گلیکوزیله\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /هموگلوبین\s+گلیکه\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /اچ\s*بی\s*ای\s*وان\s*سی\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد % ===
            /HbA[1Il]c\s*[:\-]?\s*(\d+\.?\d*)\s*%/gi,
            /HbA[1Il]c\s*\(\s*%\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 7. HbA1c با mmol/mol (واحد IFCC) ===
            /HbA[1Il]c\s*[:\-]?\s*(\d+\.?\d*)\s*(?:%|mmol\/mol)?/gi,
            
            // === 8. Glycated Hb بدون پرانتز ===
            /Glycated\s+Hb\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Glycosylated\s+Hb\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0;
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('HbA1c', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ HbA1c یافت شد: ${value}% | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ HbA1c رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ HbA1c معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/HbA[1Il]c/i.test(text)) console.log('  ✓ "HbA1c" یافت شد');
            if (/Glycated/i.test(text)) console.log('  ✓ "Glycated" یافت شد');
            if (/Glycosylated/i.test(text)) console.log('  ✓ "Glycosylated" یافت شد');
            if (/Hemoglobin/i.test(text)) console.log('  ✓ "Hemoglobin" یافت شد');
        }
    
        return {
            name: 'HbA1c',
            found: value !== null,
            value: value,
            unit: '%',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج Cholesterol
     */
    extractCholesterol(text) {
        const patterns = [
            // === 1. Total Cholesterol ===
            /Total\s+Cholesterol\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Cholesterol\s*\(\s*Total\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Cholesterol\s*,?\s*Total\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Cholesterol ساده ===
            /\bCholesterol\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Chol\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. T-Chol یا TC ===
            /T-Chol\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T\.?\s*Chol\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /\bTC\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. فارسی ===
            /کلسترول\s+تام\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /کلسترول\s+کل\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /کلسترول\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 5. با واحد ===
            /Cholesterol\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|mmol\/L)?/gi,
            /Total\s+Cholesterol\s*[:\-]?\s*(\d+\.?\d*)\s*mg\/dL/gi,
            
            // === 6. پترن‌های آزمایشگاهی ===
            /Serum\s+Cholesterol\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Cholesterol\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('Cholesterol', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Cholesterol یافت شد: ${value} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Cholesterol رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Cholesterol معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/cholesterol/i.test(text)) console.log('  ✓ "Cholesterol" یافت شد');
            if (/chol\b/i.test(text)) console.log('  ✓ "Chol" یافت شد');
        }
    
        return {
            name: 'Cholesterol',
            found: value !== null,
            value: value,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },

    /**
     * استخراج Triglyceride
     */
    extractTriglyceride(text) {
        const patterns = [
            // === 1. Triglyceride کامل ===
            /Triglycerides?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Tri\s*glycerides?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. TG اختصاری ===
            /\bTG\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T\.?\s*G\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Serum Triglyceride ===
            /Serum\s+Triglycerides?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Triglycerides?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. فارسی ===
            /تری\s*گلیسرید\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /تری\s*گلیسیرید\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /تری\s+گلیسرید\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 5. با واحد ===
            /Triglycerides?\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|mmol\/L)?/gi,
            /TG\s*[:\-]?\s*(\d+\.?\d*)\s*mg\/dL/gi,
            
            // === 6. پترن‌های آزمایشگاهی ===
            /Triglyceride\s*Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Fasting\s+Triglycerides?\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('Triglyceride', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Triglyceride یافت شد: ${value} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Triglyceride رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Triglyceride معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/triglyceride/i.test(text)) console.log('  ✓ "Triglyceride" یافت شد');
            if (/\bTG\b/i.test(text)) console.log('  ✓ "TG" یافت شد');
        }
    
        return {
            name: 'Triglyceride (TG)',
            found: value !== null,
            value: value,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },

    /**
     * استخراج LDL
     */
    extractLDL(text) {
        const patterns = [
            // === 1. LDL Cholesterol کامل ===
            /LDL\s*Cholesterol\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /LDL\s*Chol\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Cholesterol-Total (با خط فاصله) ===
            /Cholesterol\s*-\s*Total\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Total\s*-\s*Cholesterol\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. LDL-C ===
            /LDL-C\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /LDL\s*-\s*C\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. LDL ساده ===
            /\bLDL\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Low Density Lipoprotein ===
            /Low\s+Density\s+Lipoprotein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Low\s*-?\s*Density\s+Lipoprotein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /لیپوپروتئین\s+کم\s+چگال\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /ال\s*دی\s*ال\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /LDL\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|mmol\/L)?/gi,
            /LDL-C\s*[:\-]?\s*(\d+\.?\d*)\s*mg\/dL/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /LDL\s*Cholesterol\s*,?\s*Direct\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Direct\s+LDL\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Calculated\s+LDL\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('LDL', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ LDL یافت شد: ${value} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ LDL رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ LDL معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/LDL/i.test(text)) console.log('  ✓ "LDL" یافت شد');
            if (/Low\s+Density/i.test(text)) console.log('  ✓ "Low Density" یافت شد');
        }
    
        return {
            name: 'LDL Cholesterol',
            found: value !== null,
            value: value,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },

    /**
     * استخراج HDL
     */
    extractHDL(text) {
        const patterns = [
            // === 1. HDL Cholesterol کامل ===
            /HDL\s*Cholesterol\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /HDL\s*Chol\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. HDL-C ===
            /HDL-C\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /HDL\s*-\s*C\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. HDL ساده ===
            /\bHDL\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. High Density Lipoprotein ===
            /High\s+Density\s+Lipoprotein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /High\s*-?\s*Density\s+Lipoprotein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /لیپوپروتئین\s+پرچگال\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /اچ\s*دی\s*ال\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /HDL\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|mmol\/L)?/gi,
            /HDL-C\s*[:\-]?\s*(\d+\.?\d*)\s*mg\/dL/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /HDL\s*Cholesterol\s*,?\s*Direct\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Direct\s+HDL\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('HDL', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ HDL یافت شد: ${value} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ HDL رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ HDL معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/HDL/i.test(text)) console.log('  ✓ "HDL" یافت شد');
            if (/High\s+Density/i.test(text)) console.log('  ✓ "High Density" یافت شد');
        }
    
        return {
            name: 'HDL Cholesterol',
            found: value !== null,
            value: value,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج VLDL
     */
    extractVLDL(text) {
        const patterns = [
            // === 1. VLDL Cholesterol کامل ===
            /VLDL\s*Cholesterol\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /VLDL\s*Chol\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. VLDL-C ===
            /VLDL-C\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /VLDL\s*-\s*C\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. VLDL ساده ===
            /\bVLDL\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Very Low Density Lipoprotein ===
            /Very\s+Low\s+Density\s+Lipoprotein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Very\s*-?\s*Low\s*-?\s*Density\s+Lipoprotein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /لیپوپروتئین\s+بسیار\s+کم\s+چگال\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /وی\s*ال\s*دی\s*ال\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /VLDL\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|mmol\/L)?/gi,
            /VLDL-C\s*[:\-]?\s*(\d+\.?\d*)\s*mg\/dL/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /VLDL\s*Cholesterol\s*,?\s*Calculated\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Calculated\s+VLDL\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('VLDL', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ VLDL یافت شد: ${value} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ VLDL رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ VLDL معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/VLDL/i.test(text)) console.log('  ✓ "VLDL" یافت شد');
            if (/Very\s+Low\s+Density/i.test(text)) console.log('  ✓ "Very Low Density" یافت شد');
        }
    
        return {
            name: 'VLDL',
            found: value !== null,
            value: value,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج SGOT (AST)
     */
    extractSGOT(text) {
        const patterns = [
            // === 1. SGOT ===
            /SGOT\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /S\.?\s*G\.?\s*O\.?\s*T\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. AST ===
            /\bAST\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /A\.?\s*S\.?\s*T\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. SGOT (AST) ترکیبی ===
            /SGOT\s*\(\s*AST\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /AST\s*\(\s*SGOT\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Aspartate Aminotransferase ===
            /Aspartate\s+Aminotransferase\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Aspartate\s+Transaminase\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /آسپارتات\s+آمینوترانسفراز\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /\bآ\.س\.ت\b\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /SGOT\s*[:\-]?\s*(\d+\.?\d*)\s*(?:U\/L|IU\/L)?/gi,
            /AST\s*[:\-]?\s*(\d+\.?\d*)\s*U\/L/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /Serum\s+SGOT\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Serum\s+AST\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('SGOT', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ SGOT (AST) یافت شد: ${value} U/L | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ SGOT (AST) رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ SGOT (AST) معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/SGOT/i.test(text)) console.log('  ✓ "SGOT" یافت شد');
            if (/\bAST\b/i.test(text)) console.log('  ✓ "AST" یافت شد');
        }
    
        return {
            name: 'SGOT (AST)',
            found: value !== null,
            value: value,
            unit: 'U/L',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج SGPT (ALT)
     */
    extractSGPT(text) {
        const patterns = [
            // === 1. SGPT ===
            /SGPT\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /S\.?\s*G\.?\s*P\.?\s*T\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. ALT ===
            /\bALT\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /A\.?\s*L\.?\s*T\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. SGPT (ALT) ترکیبی ===
            /SGPT\s*\(\s*ALT\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /ALT\s*\(\s*SGPT\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Alanine Aminotransferase ===
            /Alanine\s+Aminotransferase\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Alanine\s+Transaminase\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /آلانین\s+آمینوترانسفراز\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /\bآ\.ل\.ت\b\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /SGPT\s*[:\-]?\s*(\d+\.?\d*)\s*(?:U\/L|IU\/L)?/gi,
            /ALT\s*[:\-]?\s*(\d+\.?\d*)\s*U\/L/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /Serum\s+SGPT\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Serum\s+ALT\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('SGPT', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ SGPT (ALT) یافت شد: ${value} U/L | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ SGPT (ALT) رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ SGPT (ALT) معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/SGPT/i.test(text)) console.log('  ✓ "SGPT" یافت شد');
            if (/\bALT\b/i.test(text)) console.log('  ✓ "ALT" یافت شد');
        }
    
        return {
            name: 'SGPT (ALT)',
            found: value !== null,
            value: value,
            unit: 'U/L',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج ALP
     */
    extractALP(text) {
        const patterns = [
            // === 1. ALP ===
            /\bALP\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /A\.?\s*L\.?\s*P\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Alkaline Phosphatase کامل ===
            /Alkaline\s+Phosphatase\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Alk\.?\s+Phos\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Alk\s+Phosphatase\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. فارسی ===
            /آلکالین\s+فسفاتاز\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /فسفاتاز\s+قلیایی\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. با واحد ===
            /ALP\s*[:\-]?\s*(\d+\.?\d*)\s*(?:U\/L|IU\/L)?/gi,
            /Alkaline\s+Phosphatase\s*[:\-]?\s*(\d+\.?\d*)\s*U\/L/gi,
            
            // === 5. پترن‌های آزمایشگاهی ===
            /Serum\s+ALP\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Total\s+ALP\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /ALP\s*,?\s*Total\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('ALP', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ ALP یافت شد: ${value} U/L | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ ALP رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ ALP معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/\bALP\b/i.test(text)) console.log('  ✓ "ALP" یافت شد');
            if (/Alkaline\s+Phosphatase/i.test(text)) console.log('  ✓ "Alkaline Phosphatase" یافت شد');
        }
    
        return {
            name: 'Alkaline Phosphatase (ALP)',
            found: value !== null,
            value: value,
            unit: 'U/L',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج Uric Acid
     */
    extractUricAcid(text) {
        const patterns = [
            // === 1. Uric Acid کامل ===
            /Uric\s+Acid\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Uric\s*-?\s*Acid\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            /Uric\s+Acid\s*\(\s*Serum\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Uric\s+Acid\s*\(\s*Blood\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Uric\s+Acid\s*\(\s*Plasma\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Uric ساده ===
            /\bUric\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. UA اختصاری ===
            /\bUA\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /U\.?\s*A\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. فارسی ===
            /اسید\s+اوریک\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /اسید\s*اوریک\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 5. با واحد ===
            /Uric\s+Acid\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|µmol\/L)?/gi,
            /Uric\s*[:\-]?\s*(\d+\.?\d*)\s*mg\/dL/gi,
            
            // === 6. پترن‌های آزمایشگاهی ===
            /Serum\s+Uric\s+Acid\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Uric\s+Acid\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Urate\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('UricAcid', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Uric Acid یافت شد: ${value} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Uric Acid رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Uric Acid معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/Uric/i.test(text)) console.log('  ✓ "Uric" یافت شد');
            if (/Uric\s+Acid/i.test(text)) console.log('  ✓ "Uric Acid" یافت شد');
        }
    
        return {
            name: 'Uric Acid',
            found: value !== null,
            value: value,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },

    /**
     * استخراج Creatinine
     */
    extractCreatinine(text) {
        const patterns = [
            // === 1. Creatinine کامل ===
            /Creatinine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Serum\s+Creatinine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Creatinine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. اختصاری Cr ===
            /\bCr\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /S\.?\s*Cr\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. فارسی ===
            /کراتینین\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /کراتی\s+نین\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. با واحد ===
            /Creatinine\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|µmol\/L)?/gi,
            /Cr\s*[:\-]?\s*(\d+\.?\d*)\s*mg\/dL/gi,
            
            // === 5. پترن‌های آزمایشگاهی ===
            /Creatinine\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Serum\s+Cr\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('Creatinine', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Creatinine یافت شد: ${value} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Creatinine رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Creatinine معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/creatinine/i.test(text)) console.log('  ✓ "Creatinine" یافت شد');
            if (/\bcr\b/i.test(text)) console.log('  ✓ "Cr" یافت شد');
        }
    
        return {
            name: 'Creatinine',
            found: value !== null,
            value: value,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج Magnesium
     */
    extractMagnesium(text) {
        const patterns = [
            // === 1. Magnesium کامل ===
            /Magnesium\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Magnesium\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Mg اختصاری ===
            /\bMg\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /M\.?\s*g\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. فارسی ===
            /منیزیم\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /منیزیوم\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. با واحد ===
            /Magnesium\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/dL|mmol\/L|mEq\/L)?/gi,
            /Mg\s*[:\-]?\s*(\d+\.?\d*)\s*mg\/dL/gi,
            
            // === 5. پترن‌های آزمایشگاهی ===
            /Serum\s+Magnesium\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Magnesium\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Total\s+Magnesium\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Magnesium\s*Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('Magnesium', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Magnesium یافت شد: ${value} mg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Magnesium رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Magnesium معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/Magnesium/i.test(text)) console.log('  ✓ "Magnesium" یافت شد');
            if (/\bMg\b/i.test(text)) console.log('  ✓ "Mg" یافت شد');
        }
    
        return {
            name: 'Magnesium (Mg)',
            found: value !== null,
            value: value,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج Zinc
     */
    extractZinc(text) {
        const patterns = [
            // === 1. Zinc کامل ===
            /Zinc\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Zinc\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Zn اختصاری ===
            /\bZn\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Z\.?\s*n\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. فارسی ===
            /روی\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /زینک\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. با واحد ===
            /Zinc\s*[:\-]?\s*(\d+\.?\d*)\s*(?:µg\/dL|ug\/dL|mcg\/dL|µmol\/L)?/gi,
            /Zn\s*[:\-]?\s*(\d+\.?\d*)\s*(?:µg\/dL|ug\/dL)/gi,
            
            // === 5. پترن‌های آزمایشگاهی ===
            /Serum\s+Zinc\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Plasma\s+Zinc\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Zinc\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Zinc\s*Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('Zinc', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Zinc یافت شد: ${value} µg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Zinc رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Zinc معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/Zinc/i.test(text)) console.log('  ✓ "Zinc" یافت شد');
            if (/\bZn\b/i.test(text)) console.log('  ✓ "Zn" یافت شد');
        }
    
        return {
            name: 'Zinc (Zn)',
            found: value !== null,
            value: value,
            unit: 'µg/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج Vitamin B12
     */
    extractVitaminB12(text) {
        const patterns = [
            // === 1. Vitamin B12 کامل ===
            /Vitamin\s+B-?12\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Vitamin\s+B\s*-?\s*12\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Vit B12 اختصاری ===
            /Vit\.?\s+B-?12\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Vit\s+B\s*-?\s*12\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. B12 ساده ===
            /\bB-?12\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /\bB\s*-?\s*12\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Cobalamin ===
            /Cobalamin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Cyanocobalamin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /ویتامین\s+B-?12\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /ویتامین\s+بی\s*12\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /بی\s*12\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /Vitamin\s+B12\s*[:\-]?\s*(\d+\.?\d*)\s*(?:pg\/mL|pmol\/L|ng\/L)?/gi,
            /B12\s*[:\-]?\s*(\d+\.?\d*)\s*pg\/mL/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /Serum\s+Vitamin\s+B12\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Serum\s+B12\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Total\s+B12\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('VitaminB12', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Vitamin B12 یافت شد: ${value} pg/mL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Vitamin B12 رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Vitamin B12 معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/Vitamin\s+B12/i.test(text)) console.log('  ✓ "Vitamin B12" یافت شد');
            if (/\bB12\b/i.test(text)) console.log('  ✓ "B12" یافت شد');
            if (/Cobalamin/i.test(text)) console.log('  ✓ "Cobalamin" یافت شد');
        }
    
        return {
            name: 'Vitamin B12',
            found: value !== null,
            value: value,
            unit: 'pg/mL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج Vitamin D
     */
    extractVitaminD(text) {
        const patterns = [
            // === 1. Vitamin D Total ===
            /Vitamin\s+D\s+Total\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Total\s+Vitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Vitamin D ساده ===
            /Vitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Vit\.?\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. 25-OH Vitamin D ===
            /25-OH\s+Vitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /25-OH-Vitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /25\s*OH\s+Vitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /25\s*-\s*OH\s*-?\s*D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. 25(OH)D با پرانتز ===
            /25\s*\(\s*OH\s*\)\s*D3?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /25\s*\(\s*OH\s*\)\s*D2?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /25\s*\(\s*OH\s*\)\s*Vitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. Hydroxyvitamin D ===
            /25-Hydroxyvitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /25\s+Hydroxyvitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Hydroxyvitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 6. 25-Hydroxy D2/D3 ===
            /25-Hydroxy\s+D3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /25-Hydroxy\s+D2\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /25\s+Hydroxy\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 7. Calcidiol ===
            /Calcidiol\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 8. فارسی ===
            /ویتامین\s+D\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /ویتامین\s+دی\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 9. با واحد ===
            /Vitamin\s+D\s*[:\-]?\s*(\d+\.?\d*)\s*(?:ng\/mL|nmol\/L)?/gi,
            /25-OH\s+Vitamin\s+D\s*[:\-]?\s*(\d+\.?\d*)\s*ng\/mL/gi,
            /25\s*\(\s*OH\s*\)\s*D\s*[:\-]?\s*(\d+\.?\d*)\s*ng\/mL/gi,
            
            // === 10. Total 25(OH)D ===
            /Total\s+25\s*\(\s*OH\s*\)\s*D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 11. Serum Vitamin D ===
            /Serum\s+Vitamin\s+D\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Serum\s+25\s*\(\s*OH\s*\)\s*D\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('VitaminD', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Vitamin D یافت شد: ${value} ng/mL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Vitamin D رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Vitamin D معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/Vitamin\s+D/i.test(text)) console.log('  ✓ "Vitamin D" یافت شد');
            if (/25.*OH.*D/i.test(text)) console.log('  ✓ "25-OH-D" یافت شد');
            if (/Hydroxyvitamin/i.test(text)) console.log('  ✓ "Hydroxyvitamin" یافت شد');
        }
    
        return {
            name: 'Vitamin D (25-OH-D)',
            found: value !== null,
            value: value,
            unit: 'ng/mL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج Ferritin
     */
    extractFerritin(text) {
        const patterns = [
            // === 1. Ferritin استاندارد ===
            /Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Serum\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Ferritin ECL (الکتروکمی‌لومینسانس) ===
            /Ferritin\s*ECL\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /FerritinECL\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Ferritin\s*\(\s*ECL\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Ferritin با روش‌های مختلف اندازه‌گیری ===
            /Ferritin\s*\(\s*CMIA\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Ferritin\s*\(\s*CLIA\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Ferritin\s*\(\s*ELISA\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Ferritin\s*\(\s*RIA\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Ferritin با واحد ===
            /Ferritin\s*[:\-]?\s*(\d+\.?\d*)\s*(?:ng\/mL|µg\/L|ug\/L)?/gi,
            /Ferritin\s*\(\s*ng\/mL\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Ferritin\s*\(\s*µg\/L\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 5. Ferritin با کاما ===
            /Ferritin\s*,?\s+Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. فارسی ===
            /فریتین\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 7. Intact/Total Ferritin ===
            /Intact\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Total\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 8. با Low/High/Normal ===
            /Ferritin\s*[:\-]?\s*(\d+\.?\d*)\s*(?:Low|High|Normal)?/gi,
            
            // === 9. Blood/Plasma Ferritin ===
            /Blood\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Plasma\s+Ferritin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('Ferritin', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Ferritin یافت شد: ${value} ng/mL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Ferritin رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Ferritin معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/Ferritin/i.test(text)) console.log('  ✓ "Ferritin" یافت شد');
            if (/ECL/i.test(text)) console.log('  ✓ "ECL" یافت شد');
        }
    
        return {
            name: 'Ferritin',
            found: value !== null,
            value: value,
            unit: 'ng/mL',
            matchedText: matchedPattern
        };
    },
     /**
     * استخراج T3
     */
    extractT3(text) {
        const patterns = [
            // === 1. T3 ساده ===
            /\bT3\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T\.?\s*3\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Total T3 ===
            /Total\s+T3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T3\s+Total\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /TT3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Free T3 ===
            /Free\s+T3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T3\s+Free\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /FT3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Triiodothyronine ===
            /Triiodothyronine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Tri-iodothyronine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Triodothyronine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /تری\s*یدوتیرونین\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /تی\s*3\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /T3\s*[:\-]?\s*(\d+\.?\d*)\s*(?:ng\/dL|ng\/mL|nmol\/L)?/gi,
            /Total\s+T3\s*[:\-]?\s*(\d+\.?\d*)\s*ng\/dL/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /Serum\s+T3\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T3\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /T3\s+Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('T3', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ T3 یافت شد: ${value} ng/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ T3 رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ T3 معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/\bT3\b/i.test(text)) console.log('  ✓ "T3" یافت شد');
            if (/Triiodothyronine/i.test(text)) console.log('  ✓ "Triiodothyronine" یافت شد');
        }
    
        return {
            name: 'T3',
            found: value !== null,
            value: value,
            unit: 'ng/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج T4
     */
    extractT4(text) {
        const patterns = [
            // === 1. T4 ساده ===
            /\bT4\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T\.?\s*4\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Total T4 ===
            /Total\s+T4\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T4\s+Total\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /TT4\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Free T4 ===
            /Free\s+T4\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T4\s+Free\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /FT4\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Thyroxine ===
            /Thyroxine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Tetraiodothyronine\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /تیروکسین\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /تی\s*4\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /T4\s*[:\-]?\s*(\d+\.?\d*)\s*(?:µg\/dL|ug\/dL|mcg\/dL|nmol\/L)?/gi,
            /Total\s+T4\s*[:\-]?\s*(\d+\.?\d*)\s*µg\/dL/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /Serum\s+T4\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T4\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /T4\s+Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('T4', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ T4 یافت شد: ${value} µg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ T4 رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ T4 معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/\bT4\b/i.test(text)) console.log('  ✓ "T4" یافت شد');
            if (/Thyroxine/i.test(text)) console.log('  ✓ "Thyroxine" یافت شد');
        }
    
        return {
            name: 'T4',
            found: value !== null,
            value: value,
            unit: 'µg/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج TSH
     */
    extractTSH(text) {
        const patterns = [
            // === 1. TSH ساده ===
            /\bTSH\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /T\.?\s*S\.?\s*H\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Thyroid Stimulating Hormone ===
            /Thyroid\s+Stimulating\s+Hormone\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Thyroid-Stimulating\s+Hormone\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Thyrotropin ===
            /Thyrotropin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Thyrotrophin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. فارسی ===
            /تی\s*اس\s*اچ\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /هورمون\s+محرک\s+تیروئید\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 5. با واحد ===
            /TSH\s*[:\-]?\s*(\d+\.?\d*)\s*(?:µIU\/mL|uIU\/mL|mIU\/L|µU\/mL)?/gi,
            /TSH\s*\(\s*µIU\/mL\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. پترن‌های آزمایشگاهی ===
            /Serum\s+TSH\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /TSH\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /TSH\s+Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /s-TSH\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 7. Ultrasensitive TSH ===
            /Ultrasensitive\s+TSH\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Ultra-sensitive\s+TSH\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Sensitive\s+TSH\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('TSH', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ TSH یافت شد: ${value} µIU/mL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ TSH رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ TSH معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/\bTSH\b/i.test(text)) console.log('  ✓ "TSH" یافت شد');
            if (/Thyroid\s+Stimulating/i.test(text)) console.log('  ✓ "Thyroid Stimulating" یافت شد');
            if (/Thyrotropin/i.test(text)) console.log('  ✓ "Thyrotropin" یافت شد');
        }
    
        return {
            name: 'TSH',
            found: value !== null,
            value: value,
            unit: 'µIU/mL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج CRP
     */
    extractCRP(text) {
        const patterns = [
            // === 1. CRP ساده ===
            /\bCRP\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /C\.?\s*R\.?\s*P\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. C-Reactive Protein کامل ===
            /C-Reactive\s+Protein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /C\s+Reactive\s+Protein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /C\s*-?\s*Reactive\s+Protein\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. hs-CRP (High Sensitivity) ===
            /hs-CRP\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /hsCRP\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /High\s+Sensitivity\s+CRP\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /HS-CRP\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Quantitative CRP ===
            /Quantitative\s+CRP\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /CRP\s+Quantitative\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /سی\s*آر\s*پی\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /پروتئین\s+واکنشی\s+C\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /CRP\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mg\/L|mg\/dL)?/gi,
            /CRP\s*\(\s*mg\/L\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /Serum\s+CRP\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /CRP\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /CRP\s+Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('CRP', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ CRP یافت شد: ${value} mg/L | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ CRP رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ CRP معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/\bCRP\b/i.test(text)) console.log('  ✓ "CRP" یافت شد');
            if (/C.*Reactive.*Protein/i.test(text)) console.log('  ✓ "C-Reactive Protein" یافت شد');
            if (/hs-CRP/i.test(text)) console.log('  ✓ "hs-CRP" یافت شد');
        }
    
        return {
            name: 'CRP',
            found: value !== null,
            value: value,
            unit: 'mg/L',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج ESR
     */
    extractESR(text) {
        const patterns = [
            // === 1. ESR ساده ===
            /\bESR\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /E\.?\s*S\.?\s*R\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Erythrocyte Sedimentation Rate کامل ===
            /Erythrocyte\s+Sedimentation\s+Rate\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Erythrocyte\s+Sed\.?\s+Rate\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Sed Rate اختصاری ===
            /Sed\.?\s+Rate\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Sedimentation\s+Rate\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 4. فارسی ===
            /ای\s*اس\s*آر\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /سرعت\s+رسوب\s+گلبول\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /رسوب\s+گلبول\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 5. با واحد ===
            /ESR\s*[:\-]?\s*(\d+\.?\d*)\s*(?:mm\/hr|mm\/h)?/gi,
            /ESR\s*\(\s*mm\/hr\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. پترن‌های آزمایشگاهی ===
            /ESR\s+Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /ESR\s+Test\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 7. Westergren Method ===
            /ESR\s*\(\s*Westergren\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Westergren\s+ESR\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('ESR', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ ESR یافت شد: ${value} mm/hr | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ ESR رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ ESR معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/\bESR\b/i.test(text)) console.log('  ✓ "ESR" یافت شد');
            if (/Erythrocyte\s+Sedimentation/i.test(text)) console.log('  ✓ "Erythrocyte Sedimentation" یافت شد');
            if (/Sed.*Rate/i.test(text)) console.log('  ✓ "Sed Rate" یافت شد');
        }
    
        return {
            name: 'ESR',
            found: value !== null,
            value: value,
            unit: 'mm/hr',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج Copper
     */
    extractCopper(text) {
        const patterns = [
            // === 1. Copper کامل ===
            /Copper\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Copper\s*,?\s*Serum\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Cu اختصاری ===
            /\bCu\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /C\.?\s*u\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. فارسی ===
            /مس\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. با واحد ===
            /Copper\s*[:\-]?\s*(\d+\.?\d*)\s*(?:µg\/dL|ug\/dL|mcg\/dL|µmol\/L)?/gi,
            /Cu\s*[:\-]?\s*(\d+\.?\d*)\s*(?:µg\/dL|ug\/dL)/gi,
            
            // === 5. پترن‌های آزمایشگاهی ===
            /Serum\s+Copper\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Plasma\s+Copper\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Copper\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Copper\s*Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Total\s+Copper\s*[:\-]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('Copper', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Copper یافت شد: ${value} µg/dL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Copper رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Copper معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/Copper/i.test(text)) console.log('  ✓ "Copper" یافت شد');
            if (/\bCu\b/i.test(text)) console.log('  ✓ "Cu" یافت شد');
        }
    
        return {
            name: 'Copper (Cu)',
            found: value !== null,
            value: value,
            unit: 'µg/dL',
            matchedText: matchedPattern
        };
    },
    /**
     * استخراج CBC (Complete Blood Count)
     */
    extractCBC(text) {
        const cbcTests = [
            {
                name: 'WBC',
                fullName: 'White Blood Cells',
                patterns: [
                    // === 1. WBC استاندارد ===
                    /\bWBC\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /W\.?\s*B\.?\s*C\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 2. White Blood Cells کامل ===
                    /White\s+Blood\s+Cells?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /White\s+Blood\s+Cell\s+Count\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 3. Leukocyte ===
                    /Leukocytes?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /Leukocytes?\s+Count\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 4. فارسی ===
                    /گلبول\s+سفید\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    /گلبول\s*های\s*سفید\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    /تعداد\s+گلبول\s+سفید\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    
                    // === 5. با واحد ===
                    /WBC\s*[:\-]?\s*(\d+\.?\d*)\s*(?:[xX×]10[³3]?\/[μµu]?[lL]|cells?\/[μµu]?[lL])?/gi,
                    /WBC\s*\(\s*[xX×]?10[³3]\s*\/\s*[μµu]?[lL]\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
                ],
                unit: '×10³/µL',
                validationKey: 'WBC'
            },
            {
                name: 'HGB',
                fullName: 'Hemoglobin',
                patterns: [
                    // === 1. HGB/Hb ===
                    /\bHGB\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /\bHb\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /H\.?\s*G\.?\s*B\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 2. Hemoglobin کامل ===
                    /Hemoglobin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /Haemoglobin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 3. فارسی ===
                    /هموگلوبین\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    /هموگلوبن\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    
                    // === 4. با واحد ===
                    /HGB\s*[:\-]?\s*(\d+\.?\d*)\s*(?:[gG]\/[dD][lL])?/gi,
                    /Hemoglobin\s*\(\s*[gG]\/[dD][lL]\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
                ],
                unit: 'g/dL',
                validationKey: 'HGB'
            },
            {
                name: 'RBC',
                fullName: 'Red Blood Cells',
                patterns: [
                    // === 1. RBC ===
                    /\bRBC\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /R\.?\s*B\.?\s*C\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 2. Red Blood Cells کامل ===
                    /Red\s+Blood\s+Cells?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /Red\s+Blood\s+Cell\s+Count\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 3. Erythrocyte ===
                    /Erythrocytes?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /Erythrocytes?\s+Count\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 4. فارسی ===
                    /گلبول\s+قرمز\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    /گلبول\s*های\s*قرمز\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    /تعداد\s+گلبول\s+قرمز\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    
                    // === 5. با واحد ===
                    /RBC\s*[:\-]?\s*(\d+\.?\d*)\s*(?:[mM]illion\/[μµu]?[lL]|[mM]\/[μµu]?[lL])?/gi,
                    /RBC\s*\(\s*[mM]illion\/[μµu]?[lL]\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
                ],
                unit: 'million/µL',
                validationKey: 'RBC'
            },
            {
                name: 'MCV',
                fullName: 'Mean Corpuscular Volume',
                patterns: [
                    // === 1. MCV ===
                    /\bMCV\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    /M\.?\s*C\.?\s*V\.?\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 2. Mean Corpuscular Volume ===
                    /Mean\s+Corpuscular\s+Volume\s*[:\-()]?\s*(\d+\.?\d*)/gi,
                    
                    // === 3. فارسی ===
                    /حجم\s+متوسط\s+گلبول\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    /حجم\s+متوسط\s+سلولی\s*[:\-]?\s*(\d+\.?\d*)/gi,
                    
                    // === 4. با واحد ===
                    /MCV\s*[:\-]?\s*(\d+\.?\d*)\s*(?:[fF][lL])?/gi,
                    /MCV\s*\(\s*[fF][lL]\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
                ],
                unit: 'fL',
                validationKey: 'MCV'
            }
        ];
    
        const results = [];
    
        for (const test of cbcTests) {
            let value = null;
            let matchedPattern = null;
    
            for (const pattern of test.patterns) {
                pattern.lastIndex = 0; // Reset regex
                const match = text.match(pattern);
                if (match && match[0]) {
                    const numberMatch = match[0].match(/(\d+\.?\d*)/);
                    if (numberMatch && numberMatch[1]) {
                        const tempValue = parseFloat(numberMatch[1]);
                        
                        // ✅ اعتبارسنجی
                        const validation = this.validateValue(test.validationKey, tempValue);
                        if (validation.isValid) {
                            value = tempValue;
                            matchedPattern = match[0];
                            console.log(`✅ ${test.name} یافت شد: ${value} ${test.unit} | پترن: "${matchedPattern}"`);
                            break;
                        } else {
                            console.warn(`❌ ${test.name} رد شد: ${tempValue} - ${validation.reason}`);
                            // ادامه جستجو برای پترن بعدی
                        }
                    }
                }
            }
    
            if (!value) {
                console.warn(`⚠️ ${test.name} معتبر پیدا نشد.`);
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
    /**
     * استخراج انسولین ناشتا (Fasting Insulin)
     */
    extractInsulin(text) {
        const patterns = [
            // === 1. Fasting Insulin ===
            /Fasting\s+Insulin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Insulin\s*\(\s*Fasting\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Insulin\s*,?\s*Fasting\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 2. Insulin ساده ===
            /\bInsulin\b\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            
            // === 3. Serum Insulin ===
            /Serum\s+Insulin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Serum\s+Insulin\s*\(\s*Fasting\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 4. Insulin (F) اختصاری ===
            /Insulin\s*\(\s*F\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 5. فارسی ===
            /انسولین\s+ناشتا\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /انسولین\s+سرم\s+ناشتا\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /انسولین\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 6. با واحد ===
            /Fasting\s+Insulin\s*[:\-]?\s*(\d+\.?\d*)\s*(?:[μµu]IU\/[mM][lL]|[μµu]U\/[mM][lL])?/gi,
            /Insulin\s*\(\s*[μµu]IU\/[mM][lL]\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            /Insulin\s*\(\s*[μµu]U\/[mM][lL]\s*\)\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 7. پترن‌های آزمایشگاهی ===
            /Plasma\s+Insulin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Blood\s+Insulin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
            /Insulin\s+Level\s*[:\-]?\s*(\d+\.?\d*)/gi,
            
            // === 8. Basal Insulin ===
            /Basal\s+Insulin\s*[:\-()]?\s*(\d+\.?\d*)/gi,
        ];
    
        let value = null;
        let matchedPattern = null;
    
        for (const pattern of patterns) {
            pattern.lastIndex = 0; // Reset regex
            const match = text.match(pattern);
            if (match && match[0]) {
                const numberMatch = match[0].match(/(\d+\.?\d*)/);
                if (numberMatch && numberMatch[1]) {
                    const tempValue = parseFloat(numberMatch[1]);
                    
                    // ✅ اعتبارسنجی
                    const validation = this.validateValue('Insulin', tempValue);
                    if (validation.isValid) {
                        value = tempValue;
                        matchedPattern = match[0];
                        console.log(`✅ Fasting Insulin یافت شد: ${value} µIU/mL | پترن: "${matchedPattern}"`);
                        break;
                    } else {
                        console.warn(`❌ Fasting Insulin رد شد: ${tempValue} - ${validation.reason}`);
                        // ادامه جستجو برای پترن بعدی
                    }
                }
            }
        }
    
        if (!value) {
            console.warn('⚠️ Fasting Insulin معتبر پیدا نشد.');
            console.log('🔍 جستجو در متن:');
            if (/Insulin/i.test(text)) console.log('  ✓ "Insulin" یافت شد');
            if (/Fasting.*Insulin/i.test(text)) console.log('  ✓ "Fasting Insulin" یافت شد');
        }
    
        return {
            name: 'Fasting Insulin',
            found: value !== null,
            value: value,
            unit: 'µIU/mL',
            matchedText: matchedPattern
        };
    }
};
