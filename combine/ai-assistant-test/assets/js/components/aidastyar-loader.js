// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/diet/aidastyar-loader.js

class AiDastyarLoader {
  constructor(options = {}) {
    this.options = {
      message: 'در حال انتقال به صفحه ورود',
      duration: 3000,
      ...options
    };
    
    this.init();
  }
  
  init() {
    this.createLoaderElement();
    this.injectStyles();
  }
  
  createLoaderElement() {
    this.loader = document.createElement('div');
    this.loader.id = 'aidastyar-loading-overlay';
    this.loader.style.display = 'none';
    
    this.loader.innerHTML = `
      <div class="aidastyar-loader">
        <div class="logo-animation">
          <div class="logo-particle"></div>
          <div class="logo-particle"></div>
          <div class="logo-particle"></div>
          <div class="logo-particle"></div>
          <div class="logo-center">
            <svg viewBox="0 0 100 100" width="60" height="60">
              <circle cx="50" cy="55" r="20" fill="#00857a"/>
              <text x="50" y="60" text-anchor="middle" font-family="Arial" font-size="14" font-weight="bold" fill="white">AI</text>
            </svg>
          </div>
        </div>
        <div class="brand-text">
          <span class="letter">A</span>
          <span class="letter">i</span>
          <span class="letter">D</span>
          <span class="letter">A</span>
          <span class="letter">S</span>
          <span class="letter">T</span>
          <span class="letter">Y</span>
          <span class="letter">A</span>
          <span class="letter">R</span>
        </div>
        <p class="loading-message">${this.options.message}</p>
        <div class="progress-bar">
          <div class="progress-fill"></div>
        </div>
      </div>
    `;
    
    document.body.appendChild(this.loader);
  }
  
  injectStyles() {
    if (document.getElementById('aidastyar-loader-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'aidastyar-loader-styles';
    style.textContent = `
      #aidastyar-loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.5s ease;
      }
      
      #aidastyar-loading-overlay.show {
        opacity: 1;
      }
      
      .aidastyar-loader {
        text-align: center;
        max-width: 400px;
        padding: 30px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 133, 122, 0.15);
      }
      
      /* انیمیشن لوگو */
      .logo-animation {
        position: relative;
        width: 140px;
        height: 140px;
        margin: 0 auto 25px;
      }
      
      .logo-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 3;
      }
      
      .logo-particle {
        position: absolute;
        width: 30px;
        height: 30px;
        background: #00857a;
        border-radius: 50%;
        top: 50%;
        left: 50%;
        margin-top: -15px;
        margin-left: -15px;
        opacity: 0.7;
        z-index: 2;
      }
      
      .logo-particle:nth-child(1) {
        animation: particle-1 2.5s infinite ease-in-out;
      }
      
      .logo-particle:nth-child(2) {
        animation: particle-2 2.5s infinite ease-in-out;
      }
      
      .logo-particle:nth-child(3) {
        animation: particle-3 2.5s infinite ease-in-out;
      }
      
      .logo-particle:nth-child(4) {
        animation: particle-4 2.5s infinite ease-in-out;
      }
      
      @keyframes particle-1 {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-40px, -40px); }
      }
      
      @keyframes particle-2 {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(40px, -40px); }
      }
      
      @keyframes particle-3 {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-40px, 40px); }
      }
      
      @keyframes particle-4 {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(40px, 40px); }
      }
      
      /* انیمیشن تایپ نام برند */
      .brand-text {
        font-size: 2.8rem;
        color: #212529;
        margin-bottom: 25px;
        letter-spacing: -5px;
        direction: ltr;
        font-family: BordeauxBlack, sans-serif;
      }
      
      .letter {
        display: inline-block;
        opacity: 0;
        transform: translateY(20px);
        animation: letter-appear 0.4s forwards;
      }
      
      .letter:nth-child(1) { animation-delay: 0.1s; color: #00857a; }
      .letter:nth-child(2) { animation-delay: 0.2s; }
      .letter:nth-child(3) { animation-delay: 0.3s; color: #00857a; }
      .letter:nth-child(4) { animation-delay: 0.4s; }
      .letter:nth-child(5) { animation-delay: 0.5s; }
      .letter:nth-child(6) { animation-delay: 0.6s; }
      .letter:nth-child(7) { animation-delay: 0.7s; color: #00857a; }
      .letter:nth-child(8) { animation-delay: 0.8s; }
      .letter:nth-child(9) { animation-delay: 0.9s; }
      
      @keyframes letter-appear {
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      /* نوار پیشرفت */
      .progress-bar {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 20px;
      }
      
      .progress-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #00857a, #00c9b7);
        border-radius: 4px;
        animation: progress-loading 2s infinite;
      }
      
      @keyframes progress-loading {
        0% { width: 0%; }
        50% { width: 80%; }
        100% { width: 100%; }
      }
      
      .loading-message {
        color: #6c757d;
        font-size: 1.1rem;
        margin-top: 10px;
      }
    `;
    
    document.head.appendChild(style);
  }
  
  show() {
    this.loader.style.display = 'flex';
    setTimeout(() => this.loader.classList.add('show'), 10);
    
    // غیرفعال کردن تعاملات صفحه
    document.body.style.pointerEvents = 'none';
    
    // شروع انیمیشن پیشرفت
    const progressFill = this.loader.querySelector('.progress-fill');
    if (progressFill) {
      progressFill.style.animation = 'progress-loading 2s infinite';
    }
  }
  
  hide() {
    this.loader.classList.remove('show');
    setTimeout(() => {
      this.loader.style.display = 'none';
      document.body.style.pointerEvents = 'auto';
    }, 500);
  }
  
  redirect(url, delay = this.options.duration) {
    this.show();
    
    setTimeout(() => {
      window.location.href = url;
    }, delay);
  }
}

// ایجاد نمونه گلوبال برای دسترسی آسان
window.AiDastyarLoader = new AiDastyarLoader();