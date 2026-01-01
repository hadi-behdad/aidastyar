/**
 * PDF Processor - استخراج FBS و CBC
 * @file pdf-processor.js
 */

window.PDFProcessor = {
    /**
     * پردازش فایل PDF و استخراج FBS و CBC
     * @param {File} file - فایل PDF
     * @returns {Promise} - آرایه حاوی FBS و پارامترهای CBC
     */
    async processPDF(file) {
        try {
            console.log('🔄 شروع پردازش PDF:', file.name);
            
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
            const pdf = await loadingTask.promise;
            
            console.log(`📄 تعداد صفحات PDF: ${pdf.numPages}`);
            
            let fullText = '';
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const textContent = await page.getTextContent();
                const pageText = textContent.items
                    .map(item => item.str)
                    .join(' ');
                fullText += pageText + '\n';
            }
            
            console.log('📝 متن استخراج شده:', fullText.substring(0, 200));
            
            // 🎯 استخراج همه آزمایش‌ها
            const results = [
                this.extractFBS(fullText),
                this.extractInsulin(fullText),
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
     * @param {string} text - متن کامل PDF
     * @returns {Object} - نتیجه FBS
     */
    extractFBS(text) {
        const fbsPatterns = [
            // فرمت‌های استاندارد
            /FBS[:\s=\-]*([0-9.]+)/i,
            /Fasting\s+Blood\s+Sugar[:\s=\-]*([0-9.]+)/i,
            /Fasting\s+Glucose[:\s=\-]*([0-9.]+)/i,
            /Fasting\s+Blood\s+Glucose[:\s=\-]*([0-9.]+)/i,
            
            // فرمت‌های فارسی
            /قند\s*خون\s*ناشتا[:\s=\-]*([0-9.]+)/i,
            /گلوکز\s*ناشتا[:\s=\-]*([0-9.]+)/i,
            /قند\s*ناشتا[:\s=\-]*([0-9.]+)/i,
            
            // با واحد
            /FBS[:\s]*\([mM][gG]\/[dD][lL]\)[:\s]*([0-9.]+)/i,
            /Fasting\s+Blood\s+Sugar[:\s]*\([mM][gG]\/[dD][lL]\)[:\s]*([0-9.]+)/i,
            /Glucose[,\s]*Fasting[:\s]*\([mM][gG]\/[dD][lL]\)[:\s]*([0-9.]+)/i,
            
            // با نقطه
            /F\.B\.S[:\s=\-]*([0-9.]+)/i,
            /F\.B\.G[:\s=\-]*([0-9.]+)/i,  // Fasting Blood Glucose
            
            // فرمت جدول
            /Fasting\s+Blood\s+Sugar\s+([0-9.]+)\s+[mM][gG]/i,
            /FBS\s+([0-9.]+)\s+[mM][gG]\/[dD][lL]/i,
            /Fasting\s+Glucose\s+([0-9.]+)\s+[mM][gG]/i,
            
            // BS (F) format
            /BS[:\s]*\(F\)[:\s]*([0-9.]+)/i,
            /BS[:\s]*\(Fasting\)[:\s]*([0-9.]+)/i,
            /Blood\s+Sugar[:\s]*\(F\)[:\s]*([0-9.]+)/i,
            /Blood\s+Sugar[:\s]*\(Fasting\)[:\s]*([0-9.]+)/i,
            
            // Glucose Fasting variations
            /Glucose[\s,]*Fasting[:\s=\-]*([0-9.]+)/i,
            /Glucose[\s,]*\(F\)[:\s]*([0-9.]+)/i,
            /Glucose[\s,]*\(Fasting\)[:\s]*([0-9.]+)/i,
            
            // فرمت با خط فاصله
            /Fasting\s+Blood\s+Sugar\s*-\s*([0-9.]+)/i,
            /FBS\s*-\s*([0-9.]+)/i,
            /Fasting\s+Glucose\s*-\s*([0-9.]+)/i,
            
            // فرمت ساده‌تر (بدون علامت)
            /\bFBS\s+([0-9.]+)\b/i,
            /\bF\.B\.S\s+([0-9.]+)\b/i,
            
            // فرمت Lab معمول
            /Fasting\s+Sugar[:\s]*([0-9.]+)/i,
            /Sugar[:\s]*\(Fasting\)[:\s]*([0-9.]+)/i,
            
            // فرمت با Result
            /FBS[:\s]*Result[:\s]*([0-9.]+)/i,
            /Fasting\s+Blood\s+Sugar[:\s]*Result[:\s]*([0-9.]+)/i
        ];
    
        let fbsValue = null;
        let matchedPattern = null;
    
        // جستجو با الگوهای مختلف
        for (const pattern of fbsPatterns) {
            const match = text.match(pattern);
            if (match && match[1]) {
                fbsValue = parseFloat(match[1]);
                matchedPattern = match[0];
                console.log(`✅ FBS پیدا شد: ${fbsValue} mg/dL (الگو: ${matchedPattern})`);
                break;
            }
        }
    
        // اگه پیدا نشد، لاگ بگیر
        if (fbsValue === null) {
            console.warn('⚠️ FBS در متن پیدا نشد. نمونه‌ای از متن:');
            console.log(text.substring(0, 300));
        }
    
        return {
            name: 'Fasting Blood Sugar (FBS)',
            found: fbsValue !== null,
            value: fbsValue,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
    },

    extractCBC(text) {
        const cbcTests = [
            {
                name: 'WBC',
                fullName: 'White Blood Cells',
                patterns: [
                    // فرمت‌های استاندارد
                    /WBC[:\s=\-]*([0-9.]+)/i,
                    /White\s+Blood\s+Cells?[:\s=\-]*([0-9.]+)/i,
                    /White\s+Blood\s+Cell\s+Count[:\s=\-]*([0-9.]+)/i,
                    
                    // فرمت‌های فارسی
                    /گلبول\s*سفید[:\s=\-]*([0-9.]+)/i,
                    /گلبول\s*های\s*سفید[:\s=\-]*([0-9.]+)/i,
                    /تعداد\s*گلبول\s*سفید[:\s=\-]*([0-9.]+)/i,
                    
                    // با واحد
                    /WBC[:\s]*\([xX]10[³3]\/?[μµu][lL]\)[:\s]*([0-9.]+)/i,
                    /WBC[:\s]*\(cells?\/[μµu][lL]\)[:\s]*([0-9.]+)/i,
                    /WBC[:\s]*\(10\^3\/[μµu][lL]\)[:\s]*([0-9.]+)/i,
                    
                    // با نقطه
                    /W\.B\.C[:\s=\-]*([0-9.]+)/i,
                    
                    // فرمت جدول
                    /White\s+Blood\s+Cells?\s+([0-9.]+)\s+[xX]?10/i,
                    /WBC\s+([0-9.]+)\s+[xX]?10[³3]/i,
                    /White\s+Blood\s+Cells?\s+([0-9.]+)\s+cells/i,
                    
                    // فرمت با خط فاصله
                    /White\s+Blood\s+Cells?\s*-\s*([0-9.]+)/i,
                    /WBC\s*-\s*([0-9.]+)/i,
                    
                    // فرمت ساده‌تر
                    /\bWBC\s+([0-9.]+)\b/i,
                    /\bW\.B\.C\s+([0-9.]+)\b/i,
                    
                    // Leukocyte (نام علمی)
                    /Leukocyte[s]?[:\s=\-]*([0-9.]+)/i,
                    /Leukocyte[s]?\s+Count[:\s]*([0-9.]+)/i
                ],
                unit: '×10³/µL',
                min: 4.0,
                max: 11.0
            },
            {
                name: 'HGB',
                fullName: 'Hemoglobin',
                patterns: [
                    /HGB[:\s=\-]*([0-9.]+)/i,
                    /Hemoglobin[:\s=\-]*([0-9.]+)/i,
                    /\bHb[:\s=\-]*([0-9.]+)/i,
                    /هموگلوبین[:\s=\-]*([0-9.]+)/i,
                    /هموگلوبن[:\s=\-]*([0-9.]+)/i,
                    /HGB[:\s]*\([gG]\/[dD][lL]\)[:\s]*([0-9.]+)/i,
                    /Hemoglobin[:\s]*\([gG]\/[dD][lL]\)[:\s]*([0-9.]+)/i,
                    /H\.G\.B[:\s=\-]*([0-9.]+)/i,
                    /Hemoglobin\s+([0-9.]+)\s+[gG]\/[dD][lL]/i,
                    /HGB\s+([0-9.]+)\s+[gG]\/[dD][lL]/i,
                    /Hemoglobin\s*-\s*([0-9.]+)/i,
                    /HGB\s*-\s*([0-9.]+)/i,
                    /\bHGB\s+([0-9.]+)\b/i
                ],
                unit: 'g/dL',
                min: 12,
                max: 18
            },
            {
                name: 'RBC',
                fullName: 'Red Blood Cells',
                patterns: [
                    /RBC[:\s=\-]*([0-9.]+)/i,
                    /Red\s+Blood\s+Cells?[:\s=\-]*([0-9.]+)/i,
                    /Red\s+Blood\s+Cell\s+Count[:\s=\-]*([0-9.]+)/i,
                    /گلبول\s*قرمز[:\s=\-]*([0-9.]+)/i,
                    /گلبول\s*های\s*قرمز[:\s=\-]*([0-9.]+)/i,
                    /تعداد\s*گلبول\s*قرمز[:\s=\-]*([0-9.]+)/i,
                    /RBC[:\s]*\([mM]illion\/[μµu][lL]\)[:\s]*([0-9.]+)/i,
                    /RBC[:\s]*\([mM]\/[μµu][lL]\)[:\s]*([0-9.]+)/i,
                    /R\.B\.C[:\s=\-]*([0-9.]+)/i,
                    /Red\s+Blood\s+Cells?\s+([0-9.]+)\s+[mM]/i,
                    /RBC\s+([0-9.]+)\s+[mM][iI][lL][lL][iI][oO][nN]/i,
                    /Red\s+Blood\s+Cells?\s*-\s*([0-9.]+)/i,
                    /RBC\s*-\s*([0-9.]+)/i,
                    /\bRBC\s+([0-9.]+)\b/i
                ],
                unit: 'million/µL',
                min: 4.0,
                max: 6.0
            },
            {
                name: 'MCV',
                fullName: 'Mean Corpuscular Volume',
                patterns: [
                    /MCV[:\s=\-]*([0-9.]+)/i,
                    /Mean\s+Corpuscular\s+Volume[:\s=\-]*([0-9.]+)/i,
                    /حجم\s*متوسط\s*گلبول[:\s=\-]*([0-9.]+)/i,
                    /حجم\s*متوسط\s*سلولی[:\s=\-]*([0-9.]+)/i,
                    /MCV[:\s]*\([fF][lL]\)[:\s]*([0-9.]+)/i,
                    /MCV[:\s]*\([fF][eE][mM][tT][oO][lL][iI][tT][eE][rR]\)[:\s]*([0-9.]+)/i,
                    /M\.C\.V[:\s=\-]*([0-9.]+)/i,
                    /Mean\s+Corpuscular\s+Volume\s+([0-9.]+)\s+[fF][lL]/i,
                    /MCV\s+([0-9.]+)\s+[fF][lL]/i,
                    /Mean\s+Corpuscular\s+Volume\s*-\s*([0-9.]+)/i,
                    /MCV\s*-\s*([0-9.]+)/i,
                    /\bMCV\s+([0-9.]+)\b/i
                ],
                unit: 'fL',
                min: 70,
                max: 110
            }
        ];
    
        const results = [];
    
        for (const test of cbcTests) {
            let value = null;
            let matchedPattern = null;
    
            for (const pattern of test.patterns) {
                const match = text.match(pattern);
                if (match && match[1]) {
                    value = parseFloat(match[1]);
                    matchedPattern = match[0];
                    console.log(`✅ ${test.name} پیدا شد: ${value} (${matchedPattern})`);
                    break;
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
    /**
     * استخراج انسولین ناشتا (Fasting Insulin)
     * @param {string} text - متن کامل PDF
     * @returns {Object} - نتیجه Fasting Insulin
     */
    extractInsulin(text) {
        const insulinPatterns = [
            // فرمت‌های استاندارد
            /Fasting\s+Insulin[:\s=\-]*([0-9.]+)/i,
            /Insulin[:\s]*\(Fasting\)[:\s]*([0-9.]+)/i,
            /Insulin[:\s]*Fasting[:\s=\-]*([0-9.]+)/i,
            
            // فرمت‌های فارسی
            /انسولین\s*ناشتا[:\s=\-]*([0-9.]+)/i,
            /انسولین\s*سرم\s*ناشتا[:\s=\-]*([0-9.]+)/i,
            
            // با واحد
            /Fasting\s+Insulin[:\s]*\([μµu]IU\/[mM][lL]\)[:\s]*([0-9.]+)/i,
            /Insulin[:\s]*\([μµu]U\/[mM][lL]\)[:\s]*([0-9.]+)/i,
            
            // فرمت جدول
            /Fasting\s+Insulin\s+([0-9.]+)\s+[μµu]IU/i,
            /Insulin\s+\(Fasting\)\s+([0-9.]+)/i,
            
            // فرمت با خط فاصله
            /Fasting\s+Insulin\s*-\s*([0-9.]+)/i,
            /Insulin\s*\(F\)[:\s]*([0-9.]+)/i,
            
            // فرمت ساده
            /\bInsulin[:\s]+([0-9.]+)\b/i,
            
            // Serum Insulin
            /Serum\s+Insulin[:\s]*\(Fasting\)[:\s]*([0-9.]+)/i,
            /Serum\s+Insulin[:\s=\-]*([0-9.]+)/i
        ];
    
        let insulinValue = null;
        let matchedPattern = null;
    
        for (const pattern of insulinPatterns) {
            const match = text.match(pattern);
            if (match && match[1]) {
                insulinValue = parseFloat(match[1]);
                matchedPattern = match[0];
                console.log(`✅ Fasting Insulin پیدا شد: ${insulinValue} µIU/mL (الگو: ${matchedPattern})`);
                break;
            }
        }
    
        if (insulinValue === null) {
            console.warn('⚠️ Fasting Insulin در متن پیدا نشد');
        }
    
        return {
            name: 'Fasting Insulin',
            found: insulinValue !== null,
            value: insulinValue,
            unit: 'µIU/mL',
            matchedText: matchedPattern
        };
    }
    

};
