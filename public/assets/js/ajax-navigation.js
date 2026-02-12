/**
 * AJAX Navigation System for Smooth Page Transitions
 * Provides smooth fade-out/fade-in transitions between pages:
 * - Intercepts link clicks
 * - Fades out current content with opacity transition (100ms)
 * - Scrolls to top smoothly (100ms)
 * - Fetches pages via AJAX
 * - Swaps only main content (not header/footer)
 * - Fades in new content with opacity transition (100ms)
 * - Updates URL without page reload
 * - Blocks user interaction during transitions
 * 
 * Activates on ALL environments (web browser and Capacitor)
 */

class AjaxNavigation {
    constructor() {
        this.contentSelector = '#main-content';
        this.overlay = null;
        this.init();
        this.createOverlay();
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
            const authPages = ['/logout.php', '/logout', '/login.php', '/login'];
            if (authPages.includes(path)) {
                return false;
            }
            
            // File operations - actual download/export handlers (not list pages)
            // Note: /modules/reports/exports.php (plural) is a list page and will use AJAX
            // Only block the actual export handlers in the reports module and download directory
            if (path === '/modules/reports/export.php' ||
                path === '/modules/reports/export_pdf.php' ||
                path.startsWith('/download/')) {         // Download directory and its subdirectories
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
            // Show loading state and block interaction
            this.showLoading();

            // Fade out current content
            await this.fadeOut();

            // Scroll to top smoothly
            await this.scrollToTop();

            // ✅ ADD CACHE-BUSTER: Unique timestamp every request
            // Using URL constructor properly handles existing query params and hash fragments
            const urlObj = new URL(url, window.location.origin);
            urlObj.searchParams.set('t', Date.now().toString());
            const freshUrl = urlObj.href;

            // ✅ FORCE NO-CACHE in fetch API
            const response = await fetch(freshUrl, {
                method: 'GET',
                cache: 'no-store',  // ✅ CRITICAL: Bypass browser memory cache
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error('Page load failed');
            
            const html = await response.text();
            
            // ✅ CLEAR old content before inserting new
            const mainContent = document.querySelector(this.contentSelector);
            if (mainContent) {
                mainContent.innerHTML = '';
            }
            
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

            // Replace content safely
            const container = document.querySelector(this.contentSelector);
            // Clone and append new content nodes
            Array.from(newContent.childNodes).forEach(node => {
                container.appendChild(node.cloneNode(true));
            });
            
            document.title = newTitle;

            // Update URL
            if (updateHistory) {
                history.pushState({ url }, newTitle, url);
            }

            // ✅ CRITICAL FIX: Force CSS stylesheets to re-apply to new content
            // This ensures all CSS rules are re-evaluated for the new DOM elements
            await this.forceStylesheetRefresh();

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
        content.style.opacity = '0';
        return new Promise(resolve => setTimeout(resolve, 100));
    }

    fadeIn() {
        const content = document.querySelector(this.contentSelector);
        content.style.opacity = '1';
        return new Promise(resolve => setTimeout(resolve, 100));
    }

    scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        // Wait 100ms for scroll - optimized for speed per requirements
        // Note: May overlap with scroll on slower devices, but creates snappier UX
        return new Promise(resolve => setTimeout(resolve, 100));
    }

    createOverlay() {
        // Create overlay element for blocking interaction during transitions
        this.overlay = document.createElement('div');
        this.overlay.className = 'ajax-transition-overlay';
        document.body.appendChild(this.overlay);
    }

    showLoading() {
        // Block page interaction during transition
        if (this.overlay) {
            this.overlay.classList.add('active');
        }
    }

    hideLoading() {
        // Re-enable page interaction after transition
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }
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

    async forceStylesheetRefresh() {
        console.log('🔄 Refreshing stylesheets for new content...');
        
        // Why this is necessary:
        // In iOS WebView/Capacitor, especially with Service Workers, inserting new DOM 
        // via AJAX doesn't reliably trigger CSS re-application for new elements.
        // The browser may keep using the old stylesheet object, leaving new elements 
        // unstyled until a full document navigation. We must aggressively clone and 
        // replace each <link> node to force CSSOM rebuild.
        
        // Query all stylesheet link elements
        const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
        
        // Helper function to wait for load event or timeout
        const waitFor = (el, evt, ms = 2000) => new Promise(res => {
            let done = false;
            const t = setTimeout(() => { 
                if (!done) { 
                    done = true; 
                    res(); 
                }
            }, ms);
            el.addEventListener(evt, () => { 
                if (!done) { 
                    done = true; 
                    clearTimeout(t); 
                    res(); 
                }
            }, { once: true });
        });
        
        // Replace each stylesheet link with a fresh clone
        await Promise.all(links.map(async (oldLink) => {
            try {
                // Construct clean URL with single cache-busting param
                const url = new URL(oldLink.href, window.location.origin);
                
                // Remove any existing cache-busting params to avoid query growth
                url.searchParams.delete('css-refresh');
                url.searchParams.delete('v');
                
                // Add fresh timestamp cache-buster
                url.searchParams.set('v', Date.now().toString());
                
                // Clone the node and update href
                const newLink = oldLink.cloneNode(true);
                newLink.href = url.toString();
                
                console.log('📝 Replacing stylesheet:', oldLink.href.split('?')[0]);
                
                // Replace in DOM - preserves order
                oldLink.parentNode.replaceChild(newLink, oldLink);
                
                // Wait for load (best-effort) to minimize FOUC
                await waitFor(newLink, 'load');
            } catch (e) {
                // Ignore cross-origin or access errors
                console.log('⚠️ Could not refresh stylesheet (may be cross-origin):', e.message);
            }
        }));
        
        // CSSOM nudge (best-effort) - poke each accessible stylesheet
        Array.from(document.styleSheets).forEach(sheet => {
            try {
                sheet.insertRule('/*refresh*/', 0);
                sheet.deleteRule(0);
            } catch (e) {
                // Ignore CORS errors from external stylesheets
            }
        });
        
        // Force reflow to ensure styles are recalculated
        void document.documentElement.offsetHeight;
        
        console.log('✅ Stylesheet refresh complete');
    }
}

// Initialize on page load
// Activate on ALL environments for smooth page transitions
document.addEventListener('DOMContentLoaded', () => {
    if (window.Capacitor) {
        console.log('📱 AJAX Navigation: Initializing for Capacitor');
    } else {
        console.log('🌐 AJAX Navigation: Initializing for web browser');
    }
    new AjaxNavigation();
});
