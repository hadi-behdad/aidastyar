class ConsultantDietEditor {
    constructor(containerId = 'consultation-editor') {
        this.container = document.getElementById(containerId);
        this.currentRequestId = null;
        this.originalData = null;
        this.isEditing = false;
        this.currentEditElement = null;
        
        if (!this.container) {
            console.error(`Container with ID '${containerId}' not found`);
            return;
        }
    }

    init(data, requestId) {
        this.originalData = data;
        this.currentRequestId = requestId;
        this.render();
    }

    render() {
        if (!this.originalData) {
            this.container.innerHTML = '<div class="consultant-loading">داده‌ها برای نمایش موجود نیست</div>';
            return;
        }

        const { original_data, consultation_data } = this.originalData;
        const dietData = consultation_data?.final_diet_data || original_data?.ai_response;
        
        let parsedData;
        try {
            parsedData = typeof dietData === 'string' ? JSON.parse(dietData) : dietData;
        } catch (e) {
            // اگر JSON نیست، نمایش متن ساده
            this.container.innerHTML = this.renderSimpleTextEditor(dietData);
            return;
        }

        this.container.innerHTML = this.renderStructuredEditor(parsedData);
        this.setupEditEvents();
    }

    renderStructuredEditor(data) {
        return `
            <div class="consultant-editor-tabs">
                <div class="consultant-editor-tab active" data-tab="preview">
                    <i class="fas fa-eye"></i> پیش‌نمایش
                </div>
                <div class="consultant-editor-tab" data-tab="json">
                    <i class="fas fa-code"></i> ویرایش JSON
                </div>
            </div>

            <div class="consultant-editor-content">
                <div class="consultant-editor-pane active" id="preview-pane">
                    <div class="consultant-editor-actions">
                        <button class="consultant-btn consultant-btn-primary" id="expand-all-btn">
                            <i class="fas fa-expand"></i> باز کردن همه بخش‌ها
                        </button>
                        <button class="consultant-btn consultant-btn-secondary" id="collapse-all-btn">
                            <i class="fas fa-compress"></i> بستن همه بخش‌ها
                        </button>
                    </div>
                    <div class="consultant-diet-preview" id="consultant-diet-preview">
                        ${this.renderDietPlan(data)}
                    </div>
                </div>

                <div class="consultant-editor-pane" id="json-pane">
                    <div class="consultant-json-editor">
                        <h4><i class="fas fa-edit"></i> ویرایش مستقیم JSON</h4>
                        <textarea id="diet-json-editor" style="width: 100%; height: 400px; font-family: monospace;">${JSON.stringify(data, null, 2)}</textarea>
                        <button class="consultant-btn consultant-btn-primary" id="update-from-json-btn">
                            <i class="fas fa-sync"></i> بروزرسانی از JSON
                        </button>
                    </div>
                </div>
            </div>

            <div class="consultant-notes-section">
                <label for="consultant-notes">
                    <i class="fas fa-sticky-note"></i> یادداشت‌های مشاور:
                </label>
                <textarea id="consultant-notes" style="width: 100%; height: 100px;" placeholder="یادداشت‌ها و توضیحات خود را اینجا بنویسید...">${this.escapeHtml(this.originalData.consultation_data?.consultant_notes || '')}</textarea>
            </div>
        `;
    }

    renderSimpleTextEditor(text) {
        return `
            <div class="consultant-simple-editor">
                <h4><i class="fas fa-edit"></i> ویرایش متن رژیم</h4>
                <textarea id="diet-text-editor" style="width: 100%; height: 400px; font-family: monospace;">${this.escapeHtml(text)}</textarea>
                
                <div class="consultant-notes-section">
                    <label for="consultant-notes">
                        <i class="fas fa-sticky-note"></i> یادداشت‌های مشاور:
                    </label>
                    <textarea id="consultant-notes" style="width: 100%; height: 100px;" placeholder="یادداشت‌ها و توضیحات خود را اینجا بنویسید...">${this.escapeHtml(this.originalData.consultation_data?.consultant_notes || '')}</textarea>
                </div>
            </div>
        `;
    }

    renderDietPlan(data) {
        if (!data.sections || !Array.isArray(data.sections)) {
            return `<div class="consultant-debug-info">
                <h3>داده‌های خام:</h3>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            </div>`;
        }

        let html = '<div class="consultant-diet-plan">';
        
        data.sections.forEach((section, sectionIndex) => {
            html += this.renderSection(section, sectionIndex);
        });

        if (data.footer) {
            html += `<div class="consultant-footer">${this.escapeHtml(data.footer)}</div>`;
        }

        html += '</div>';
        return html;
    }

    renderSection(section, sectionIndex) {
        return `
            <div class="consultant-section" data-section-index="${sectionIndex}">
                <div class="consultant-section-header">
                    <h2>
                        <i class="fas fa-${this.getSectionIcon(section.title)}"></i>
                        <span class="editable-text" data-path="sections.${sectionIndex}.title">${this.escapeHtml(section.title || 'بخش بدون عنوان')}</span>
                    </h2>
                    <i class="fas fa-chevron-down consultant-accordion-icon"></i>
                </div>
                <div class="consultant-section-content">
                    ${this.renderSectionContent(section.content, sectionIndex)}
                </div>
            </div>
        `;
    }

    renderSectionContent(content, sectionIndex) {
        if (!content) return '<p>محتوایی موجود نیست</p>';
        
        let html = '';
        
        if (Array.isArray(content)) {
            content.forEach((sub, subIndex) => {
                html += this.renderSubContent(sub, sectionIndex, subIndex);
            });
        } else if (typeof content === 'object') {
            html += this.renderSubContent(content, sectionIndex, 0);
        } else {
            html += `<p class="editable-text" data-path="sections.${sectionIndex}.content">${this.escapeHtml(content)}</p>`;
        }
        
        return html;
    }

    renderSubContent(sub, sectionIndex, subIndex) {
        let html = '';
        
        if (sub.subtitle) {
            html += `<h3 class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.subtitle">${this.escapeHtml(sub.subtitle)}</h3>`;
        }

        if (sub.type === 'list' && sub.items) {
            html += this.renderList(sub.items, sectionIndex, subIndex);
        } else if (sub.type === 'table' && sub.headers && sub.rows) {
            html += this.renderTable(sub, sectionIndex, subIndex);
        } else if (sub.type === 'paragraph' && sub.text) {
            html += `<p class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.text">${this.escapeHtml(sub.text)}</p>`;
        } else if (sub.text) {
            html += `<p class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.text">${this.escapeHtml(sub.text)}</p>`;
        }

        return html;
    }

    renderList(items, sectionIndex, subIndex) {
        let html = '<ul>';
        items.forEach((item, itemIndex) => {
            if (typeof item === 'string') {
                html += `<li class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.items.${itemIndex}">${this.escapeHtml(item)}</li>`;
            } else if (item.label && item.value) {
                html += `
                    <li>
                        <span class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.items.${itemIndex}.label">${this.escapeHtml(item.label)}</span>: 
                        <span class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.items.${itemIndex}.value">${this.escapeHtml(item.value)}</span>
                    </li>
                `;
            }
        });
        html += '</ul>';
        return html;
    }

    renderTable(tableData, sectionIndex, subIndex) {
        let html = `
            <div class="consultant-table-container">
                <table class="consultant-table">
                    <thead>
                        <tr>
        `;
        
        tableData.headers.forEach((header, headerIndex) => {
            html += `<th class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.headers.${headerIndex}">${this.escapeHtml(header)}</th>`;
        });
        
        html += '</tr></thead><tbody>';
        
        tableData.rows.forEach((row, rowIndex) => {
            html += '<tr>';
            row.forEach((cell, cellIndex) => {
                html += `<td class="editable-text" data-path="sections.${sectionIndex}.content.${subIndex}.rows.${rowIndex}.${cellIndex}">${this.escapeHtml(cell)}</td>`;
            });
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        
        return html;
    }

    setupEditEvents() {
        // تب‌ها
        this.setupTabs();
        
        // آکاردئون‌ها
        this.setupAccordions();
        
        // دکمه‌های اکشن
        this.setupActionButtons();
        
        // ویرایش دبل کلیک
        this.setupDoubleClickEdit();
    }

    setupTabs() {
        const tabs = this.container.querySelectorAll('.consultant-editor-tab');
        const panes = this.container.querySelectorAll('.consultant-editor-pane');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                panes.forEach(p => p.classList.remove('active'));

                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                const pane = document.getElementById(`${tabId}-pane`);
                if (pane) {
                    pane.classList.add('active');
                }
            });
        });
    }

    setupAccordions() {
        const headers = this.container.querySelectorAll('.consultant-section-header');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const isActive = this.classList.contains('active');
                const content = this.nextElementSibling;
                
                if (isActive) {
                    this.classList.remove('active');
                    content.style.maxHeight = '0';
                } else {
                    this.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });

        // باز کردن اولین بخش
        if (headers[0]) {
            headers[0].classList.add('active');
            const firstContent = headers[0].nextElementSibling;
            firstContent.style.maxHeight = firstContent.scrollHeight + 'px';
        }
    }

    setupActionButtons() {
        // باز کردن همه بخش‌ها
        const expandBtn = document.getElementById('expand-all-btn');
        if (expandBtn) {
            expandBtn.addEventListener('click', () => {
                const contents = this.container.querySelectorAll('.consultant-section-content');
                const headers = this.container.querySelectorAll('.consultant-section-header');
                
                headers.forEach(header => header.classList.add('active'));
                contents.forEach(content => {
                    content.style.maxHeight = content.scrollHeight + 'px';
                });
            });
        }

        // بستن همه بخش‌ها
        const collapseBtn = document.getElementById('collapse-all-btn');
        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                const contents = this.container.querySelectorAll('.consultant-section-content');
                const headers = this.container.querySelectorAll('.consultant-section-header');
                
                headers.forEach(header => header.classList.remove('active'));
                contents.forEach(content => {
                    content.style.maxHeight = '0';
                });
            });
        }

        // بروزرسانی از JSON
        const updateJsonBtn = document.getElementById('update-from-json-btn');
        if (updateJsonBtn) {
            updateJsonBtn.addEventListener('click', () => {
                const jsonEditor = document.getElementById('diet-json-editor');
                if (jsonEditor) {
                    try {
                        const newData = JSON.parse(jsonEditor.value);
                        this.updatePreviewFromJSON(newData);
                    } catch (e) {
                        alert('خطا در پارس کردن JSON: ' + e.message);
                    }
                }
            });
        }
    }

    setupDoubleClickEdit() {
        const editableElements = this.container.querySelectorAll('.editable-text');
        
        editableElements.forEach(element => {
            element.addEventListener('dblclick', (e) => {
                e.stopPropagation();
                this.startEditing(element);
            });
        });

        // کلیک خارج از حالت ویرایش
        document.addEventListener('click', (e) => {
            if (this.isEditing && !e.target.closest('.editable-text')) {
                this.finishEditing();
            }
        });

        // کلید Escape
        document.addEventListener('keydown', (e) => {
            if (this.isEditing && e.key === 'Escape') {
                this.cancelEditing();
            }
        });
    }

    startEditing(element) {
        if (this.isEditing) return;
        
        this.isEditing = true;
        this.currentEditElement = element;
        
        const originalText = element.textContent;
        const path = element.dataset.path;
        
        // ایجاد input
        const input = document.createElement('input');
        input.type = 'text';
        input.value = originalText;
        input.className = 'consultant-edit-input';
        
        // جایگزینی element با input
        element.style.display = 'none';
        element.parentNode.insertBefore(input, element);
        
        // فوکوس و انتخاب متن
        input.focus();
        input.select();
        
        // ذخیره با Enter
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                this.saveEdit(input, element, path);
            }
        });
        
        // از دست دادن فوکوس
        input.addEventListener('blur', () => {
            this.saveEdit(input, element, path);
        });
    }

    saveEdit(input, element, path) {
        const newValue = input.value.trim();
        element.textContent = newValue;
        element.style.display = '';
        input.remove();
        
        this.isEditing = false;
        this.currentEditElement = null;
        
        // آپدیت داده‌ها
        this.updateData(path, newValue);
    }

    cancelEditing() {
        if (!this.isEditing || !this.currentEditElement) return;
        
        const input = this.container.querySelector('.consultant-edit-input');
        if (input) {
            this.currentEditElement.style.display = '';
            input.remove();
        }
        
        this.isEditing = false;
        this.currentEditElement = null;
    }

    finishEditing() {
        if (!this.isEditing) return;
        
        const input = this.container.querySelector('.consultant-edit-input');
        if (input && this.currentEditElement) {
            this.saveEdit(input, this.currentEditElement, this.currentEditElement.dataset.path);
        }
    }

    updateData(path, value) {
        // در اینجا می‌توانید داده‌ها را آپدیت کنید
        console.log('Updating data:', path, 'to:', value);
        // اینجا می‌توانید داده‌های اصلی را آپدیت کنید
    }

    updatePreviewFromJSON(newData) {
        const previewContainer = document.getElementById('consultant-diet-preview');
        if (previewContainer) {
            previewContainer.innerHTML = this.renderDietPlan(newData);
            this.setupDoubleClickEdit();
            this.setupAccordions();
        }
    }

    getEditedData() {
        // این متد داده‌های ویرایش شده را برمی‌گرداند
        const jsonEditor = document.getElementById('diet-json-editor');
        if (jsonEditor) {
            try {
                return JSON.parse(jsonEditor.value);
            } catch (e) {
                console.error('Error parsing edited JSON:', e);
            }
        }
        
        return this.originalData;
    }

    getSectionIcon(sectionTitle) {
        const icons = {
            'اطلاعات کاربر': 'user',
            'اطلاعات تغذیه‌ای': 'chart-pie',
            'برنامه هفتگی': 'calendar-alt',
            'توصیه‌های تکمیلی': 'lightbulb',
            'نتایج برنامه غذایی': 'utensils'
        };
        
        return icons[sectionTitle] || 'file-alt';
    }

    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}