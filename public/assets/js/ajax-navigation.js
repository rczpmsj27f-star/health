/**
 * AJAX Navigation System for Smooth Page Transitions
 * Prevents screen flicker in iOS Capacitor app by:
 * - Intercepting link clicks
 * - Fetching pages via AJAX
 * - Swapping only main content (not header/footer)
 * - Updating URL without page reload
 * 
 * Only activates when running in Capacitor environment
 */

class AjaxNavigation {
    constructor() {
        this.contentSelector = '#main-content';
        this.init();
    }

    init() {
        // Intercept all link clicks
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && this.shouldIntercept(link)) {
                e.preventDefault();
                this.loadPage(link.href);
            }
        });

        // Handle browser back/forward
        window.addEventListener('popstate', () => {
            this.loadPage(window.location.href, false);
        });
    }

    shouldIntercept(link) {
        const href = link.getAttribute('href');
        
        // Block dangerous URL schemes and special links
        if (!href || 
            href.startsWith('#') || 
            href.startsWith('javascript:') ||
            href.startsWith('data:') ||
            href.startsWith('vbscript:') ||
            href.startsWith('mailto:') ||              // Email links
            href.startsWith('tel:')) {                 // Phone links
            return false;
        }
        
        if (link.target === '_blank') return false;
        if (link.hasAttribute('data-no-ajax')) return false;
        
        // Parse URL to check path and origin
        try {
            const url = new URL(link.href, window.location.origin);
            
            // Only handle internal links
            if (url.origin !== window.location.origin) return false;
            
            const path = url.pathname;
            
            // Block specific pages/patterns that need full page reload
            // Authentication pages - critical security flows
            // Note: App uses URL rewriting (.htaccess) so both clean URLs and .php extensions are in use
            if (path === '/logout.php' || path === '/logout' ||
                path === '/login.php' || path === '/login') {
                return false;
            }
            
            // File operations - actual download/export handlers (not list pages)
            // Note: /modules/reports/exports.php (plural) is a list page and will use AJAX
            // Only block the actual export handlers in the reports module
            if (path === '/modules/reports/export.php' ||
                path === '/modules/reports/export_pdf.php' ||
                path.includes('/download/')) {            // Download directory
                return false;
            }
            
            // API endpoints
            if (path.includes('/api/')) {
                return false;
            }
            
            // Intercept all other internal links for smooth AJAX navigation
            return true;
        } catch (e) {
            return false;
        }
    }

    async loadPage(url, updateHistory = true) {
        try {
            // Show loading state
            this.showLoading();

            // Fetch new page
            const response = await fetch(url);
            if (!response.ok) throw new Error('Page load failed');
            
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Extract content
            const newContent = doc.querySelector(this.contentSelector);
            const newTitle = doc.querySelector('title')?.textContent || document.title;

            if (!newContent) {
                // Fallback to normal navigation if no content wrapper found
                window.location.href = url;
                return;
            }

            // Fade out current content
            await this.fadeOut();

            // Replace content safely
            const container = document.querySelector(this.contentSelector);
            // Clear existing content first
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
            // Clone and append new content nodes
            Array.from(newContent.childNodes).forEach(node => {
                container.appendChild(node.cloneNode(true));
            });
            
            document.title = newTitle;

            // Update URL
            if (updateHistory) {
                history.pushState({ url }, newTitle, url);
            }

            // Reinitialize page scripts
            this.reinitializeScripts();

            // Fade in new content
            await this.fadeIn();

        } catch (error) {
            console.error('AJAX navigation error:', error);
            // Fallback to normal navigation
            window.location.href = url;
        } finally {
            this.hideLoading();
        }
    }

    fadeOut() {
        const content = document.querySelector(this.contentSelector);
        content.style.transition = 'opacity 0.15s ease-out';
        content.style.opacity = '0';
        return new Promise(resolve => setTimeout(resolve, 150));
    }

    fadeIn() {
        const content = document.querySelector(this.contentSelector);
        content.style.opacity = '1';
        return new Promise(resolve => setTimeout(resolve, 150));
    }

    showLoading() {
        // Optional: show loading spinner
        // Could add a small loading indicator here if desired
    }

    hideLoading() {
        // Optional: hide loading spinner
    }

    reinitializeScripts() {
        // Re-run any page-specific JavaScript initialization function
        if (typeof initPageScripts === 'function') {
            initPageScripts();
        }
        
        // Execute inline scripts from the new content
        // Note: Scripts with src attributes won't re-execute automatically
        const container = document.querySelector(this.contentSelector);
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            // Copy attributes
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            // Copy inline script content
            if (!oldScript.src) {
                newScript.textContent = oldScript.textContent;
            }
            // Replace old script with new one to trigger execution
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }
}

// Initialize on page load
// Only activate when running in Capacitor environment
if (window.Capacitor) {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('📱 AJAX Navigation: Initializing for Capacitor');
        new AjaxNavigation();
    });
} else {
    console.log('🌐 AJAX Navigation: Disabled (not running in Capacitor)');
}
