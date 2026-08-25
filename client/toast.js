/**
 * Leezofood Toast Notification Module
 */
(function () {
    // Ensure CSS is loaded dynamically if not already linked
    function ensureStylesheetLoaded() {
        const existingLink = document.querySelector('link[href*="toast.css"]');
        if (!existingLink) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'toast.css';
            document.head.appendChild(link);
        }
    }

    // Get or create container element
    function getContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    const SVG_ICONS = {
        success: `<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>`,
        error: `<svg viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>`,
        warning: `<svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`,
        info: `<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>`
    };

    const CLOSE_ICON = `<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`;

    /**
     * Display Toast Notification
     * @param {string} message - Main notification text
     * @param {string} type - 'success' | 'error' | 'warning' | 'info'
     * @param {string|null} title - Optional title header
     * @param {number} duration - Auto dismiss delay in ms (default 4500ms)
     */
    function showToast(message, type = 'info', title = null, duration = 4500) {
        if (!document.body) {
            window.addEventListener('DOMContentLoaded', () => showToast(message, type, title, duration));
            return;
        }

        ensureStylesheetLoaded();
        const container = getContainer();

        const validTypes = ['success', 'error', 'warning', 'info'];
        if (!validTypes.includes(type)) {
            type = 'info';
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-item toast-${type}`;

        // Toast Icon
        const iconDiv = document.createElement('div');
        iconDiv.className = 'toast-icon';
        iconDiv.innerHTML = SVG_ICONS[type];
        toast.appendChild(iconDiv);

        // Toast Content
        const contentDiv = document.createElement('div');
        contentDiv.className = 'toast-content';

        if (title) {
            const titleEl = document.createElement('div');
            titleEl.className = 'toast-title';
            titleEl.textContent = title;
            contentDiv.appendChild(titleEl);
        }

        const msgEl = document.createElement('div');
        msgEl.className = 'toast-message';
        msgEl.textContent = message;
        contentDiv.appendChild(msgEl);

        toast.appendChild(contentDiv);

        // Close Button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close-btn';
        closeBtn.setAttribute('aria-label', 'Close toast notification');
        closeBtn.innerHTML = CLOSE_ICON;
        toast.appendChild(closeBtn);

        // Progress Bar
        let progressBar = null;
        let animationFrame = null;
        let startTime = null;
        let remainingTime = duration;
        let isPaused = false;

        if (duration > 0) {
            progressBar = document.createElement('div');
            progressBar.className = 'toast-progress-bar';
            toast.appendChild(progressBar);
        }

        const closeToast = () => {
            if (toast.classList.contains('toast-closing')) return;
            toast.classList.add('toast-closing');
            if (animationFrame) cancelAnimationFrame(animationFrame);
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        };

        closeBtn.addEventListener('click', closeToast);

        // Progress bar and auto close logic
        if (duration > 0) {
            const animateProgress = (timestamp) => {
                if (!startTime) startTime = timestamp;
                if (!isPaused) {
                    const elapsed = timestamp - startTime;
                    remainingTime = Math.max(0, duration - elapsed);
                    const progress = remainingTime / duration;
                    if (progressBar) {
                        progressBar.style.transform = `scaleX(${progress})`;
                    }
                    if (remainingTime <= 0) {
                        closeToast();
                        return;
                    }
                } else {
                    startTime = timestamp - (duration - remainingTime);
                }
                animationFrame = requestAnimationFrame(animateProgress);
            };

            animationFrame = requestAnimationFrame(animateProgress);

            toast.addEventListener('mouseenter', () => {
                isPaused = true;
            });

            toast.addEventListener('mouseleave', () => {
                isPaused = false;
            });
        }

        container.appendChild(toast);
        return toast;
    }

    // Helper methods
    showToast.success = (msg, title, duration) => showToast(msg, 'success', title, duration);
    showToast.error = (msg, title, duration) => showToast(msg, 'error', title, duration);
    showToast.warning = (msg, title, duration) => showToast(msg, 'warning', title, duration);
    showToast.info = (msg, title, duration) => showToast(msg, 'info', title, duration);

    // Export globally
    window.showToast = showToast;

    /**
     * Button Loading State Controller
     * @param {HTMLElement|string} button - Button element or selector
     * @param {boolean} isLoading - Loading state true/false
     * @param {string} loadingText - Text to show next to spinner
     */
    function setButtonLoading(button, isLoading, loadingText = 'Loading...') {
        const btnElement = typeof button === 'string' ? document.querySelector(button) : button;
        if (!btnElement) return;

        if (isLoading) {
            if (btnElement.dataset.originalContent === undefined) {
                btnElement.dataset.originalContent = btnElement.innerHTML;
            }
            
            // Maintain geometry to prevent button layout jitter
            const currentWidth = btnElement.offsetWidth;
            if (currentWidth && !btnElement.style.minWidth) {
                btnElement.style.minWidth = `${currentWidth}px`;
            }

            btnElement.disabled = true;
            btnElement.classList.add('btn-loading');
            btnElement.innerHTML = `<span class="btn-spinner"></span><span>${loadingText}</span>`;
        } else {
            if (btnElement.dataset.originalContent !== undefined) {
                btnElement.innerHTML = btnElement.dataset.originalContent;
                delete btnElement.dataset.originalContent;
            }
            btnElement.disabled = false;
            btnElement.classList.remove('btn-loading');
            btnElement.style.minWidth = '';
        }
    }

    window.setButtonLoading = setButtonLoading;

    // Replace native browser alert with non-blocking modern toast notification fallback
    window.alert = function (message) {
        if (!message) return;
        const msgStr = String(message);
        const lower = msgStr.toLowerCase();
        let type = 'info';

        if (lower.includes('success') || lower.includes('created') || lower.includes('added') || lower.includes('updated')) {
            type = 'success';
        } else if (lower.includes('failed') || lower.includes('error') || lower.includes('invalid') || lower.includes('unable') || lower.includes('denied')) {
            type = 'error';
        } else if (lower.includes('please') || lower.includes('check') || lower.includes('enter') || lower.includes('warning') || lower.includes('must be')) {
            type = 'warning';
        }

        showToast(msgStr, type);
    };

    // Auto load stylesheet
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureStylesheetLoaded);
    } else {
        ensureStylesheetLoaded();
    }
})();
