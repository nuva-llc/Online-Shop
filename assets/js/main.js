/**
 * Weapons Store - الملف البرمجي الرئيسي (Main JS)
 * مسؤول عن التنبيهات (Toasts)، نظام السمات (Light/Dark Mode)، والوظائف العامة
 */

document.addEventListener('DOMContentLoaded', () => {

    // 1. إعداد حاوية التنبيهات (Toast Container)
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);

    /**
     * دالة عرض التنبيهات المنبثقة
     */
    /**
     * دالة عرض التنبيهات المنبثقة (Premium Design)
     */
    window.showToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `toast alert alert-${type === 'error' ? 'error' : type}`;

        let iconColor = 'text-green-600';
        if (type === 'error') iconColor = 'text-red-600';
        if (type === 'warning') iconColor = 'text-yellow-600';
        if (type === 'info') iconColor = 'text-blue-600';

        toast.innerHTML = `
            <svg stroke="currentColor" viewBox="0 0 24 24" fill="none" class="${iconColor}" xmlns="http://www.w3.org/2000/svg">
                <path d="M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path>
            </svg>
            <p>${message}</p>
        `;

        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 600);
        }, 5000);
    };

    /**
     * التحقق من رسائل Flash
     */
    const flashMsg = document.getElementById('flash-message');
    if (flashMsg) {
        const msg = flashMsg.getAttribute('data-message');
        const type = flashMsg.getAttribute('data-type');
        if (msg) window.showToast(msg, type);
    }

    /**
     * تعريف عناصر واجهة المستخدم المشتركة
     */
    const body = document.body;
    const html = document.documentElement;
    const langToggle = document.getElementById('lang-toggle');
    const themeCheckbox = document.getElementById('theme-checkbox');

    function updateThemeSwitch(isLight) {
        if (!themeCheckbox) return;
        themeCheckbox.checked = isLight;
    }

    /**
     * نظام مزامنة الإعدادات (Language & Theme Sync)
     * يضمن تطبيق الإعدادات الصحيحة حتى عند التنقل للخلف/الأمام
     */
    function applyAllSettings() {
        const lang = localStorage.getItem('lang') || 'ar';
        const theme = localStorage.getItem('theme') || 'dark';

        // 1. تطبيق اللغة
        if (window.translations && window.translations[lang]) {
            const dict = window.translations[lang];
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (dict[key]) {
                    if (el.tagName === 'INPUT' && el.getAttribute('placeholder')) {
                        el.setAttribute('placeholder', dict[key]);
                    } else if (el.tagName === 'TEXTAREA' && el.getAttribute('placeholder')) {
                        el.setAttribute('placeholder', dict[key]);
                    } else if (el.children.length === 0) {
                        // Safe to use innerText if no children (like icons)
                        el.innerText = dict[key];
                    } else {
                        // If has children, find the text node to replace or append carefully
                        // For simplicity in this project, we assume icons are first/last siblings
                        // and we use a more robust way to replace only text content
                        Array.from(el.childNodes).forEach(node => {
                            if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '') {
                                node.textContent = dict[key];
                            }
                        });
                    }
                }
            });

            if (lang === 'en') {
                html.setAttribute('dir', 'ltr');
                html.setAttribute('lang', 'en');
            } else {
                html.setAttribute('dir', 'rtl');
                html.setAttribute('lang', 'ar');
            }

            if (langToggle) {
                const span = langToggle.querySelector('span');
                if (span) span.innerText = lang === 'ar' ? 'EN' : 'AR';
            }
        }

        // 2. تطبيق الثيم
        if (theme === 'light') {
            body.setAttribute('data-theme', 'light');
            updateThemeSwitch(true);
        } else {
            body.removeAttribute('data-theme');
            updateThemeSwitch(false);
        }
    }

    /**
     * نظام تعدد اللغات (Multi-language System)
     */
    if (langToggle) {
        langToggle.addEventListener('click', () => {
            const currentLang = localStorage.getItem('lang') || 'ar';
            const newLang = currentLang === 'ar' ? 'en' : 'ar';
            localStorage.setItem('lang', newLang);

            document.body.style.opacity = '0';
            setTimeout(() => location.reload(), 150);
        });
    }

    /**
     * نظام تحويل السمة (Theme Switcher) - Modern Switch
     */
    if (themeCheckbox) {
        themeCheckbox.addEventListener('change', () => {
            if (themeCheckbox.checked) {
                body.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            } else {
                body.removeAttribute('data-theme');
                localStorage.setItem('theme', 'dark');
            }
        });
    }

    /**
     * Mobile Menu Toggle
     */
    const menuToggle = document.getElementById('menu-toggle');
    const navMenu = document.getElementById('nav-menu');

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = menuToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!menuToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            }
        });
    }

    // التنفيذ عند تحميل الصفحة أو العودة إليها
    applyAllSettings();
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) applyAllSettings();
    });
});
