/**
 * PDF Processor - استخراج قند خون ناشتا (FBS)
 * @file pdf-processor.js
 */

window.PDFProcessor = {
    /**
     * پردازش فایل PDF و استخراج FBS
     * @param {File} file - فایل PDF
     * @returns {Promise<Object>} - JSON حاوی FBS
     */
    async processPDF(file) {
        try {
            console.log('🔄 شروع پردازش PDF:', file.name);
            
            // تبدیل فایل به ArrayBuffer
            const arrayBuffer = await file.arrayBuffer();
            
            // بارگذاری PDF با استفاده از PDF.js
            const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
            const pdf = await loadingTask.promise;
            
            console.log(`📄 تعداد صفحات PDF: ${pdf.numPages}`);
            
            // استخراج متن از تمام صفحات
            let fullText = '';
            
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const textContent = await page.getTextContent();
                
                const pageText = textContent.items
                    .map(item => item.str)
                    .join(' ');
                
                fullText += pageText + '\n';
            }
            
            // 🎯 استخراج فقط FBS
            const fbsResult = this.extractFBS(fullText);
            
            return fbsResult;
            
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
        // الگوهای مختلف برای FBS
        const fbsPatterns = [
            /FBS[:\s]*([0-9.]+)/i,
            /Fasting Blood Sugar[:\s]*([0-9.]+)/i,
            /قند خون ناشتا[:\s]*([0-9.]+)/i,
            /Glucose[\s,]*Fasting[:\s]*([0-9.]+)/i,
            /BS[:\s]*\(F\)[:\s]*([0-9.]+)/i
        ];
        
        let fbsValue = null;
        let matchedPattern = null;
        
        // جستجو با الگوهای مختلف
        for (const pattern of fbsPatterns) {
            const match = text.match(pattern);
            if (match && match[1]) {
                fbsValue = parseFloat(match[1]);
                matchedPattern = match[0];
                break;
            }
        }
        
        // ساخت JSON خروجی
        const result = {
            name: 'Fasting Blood Sugar (FBS)',
            found: fbsValue !== null,
            value: fbsValue,
            unit: 'mg/dL',
            matchedText: matchedPattern
        };
        
        return result;
    }
};
