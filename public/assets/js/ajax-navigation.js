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
                    'Expires': '0',
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error(`Page load failed: ${response.status}`);
            
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newContent = doc.querySelector(this.contentSelector);
            const newTitle = doc.querySelector('title')?.textContent || document.title;

            if (!newContent) {
                // Fallback to normal navigation if no content wrapper found
                window.location.href = url;
                return;
            }

            const container = document.querySelector(this.contentSelector);

            // ✅ Replace content in one step
            container.innerHTML = newContent.innerHTML;
            
            document.title = newTitle;

            // Update URL
            if (updateHistory) {
                history.pushState({ url }, newTitle, url);
            }

            // ✅ Synchronize page-specific head assets (inline styles, meta tags, scripts)
            this.synchronizeHeadAssets(doc);

            // ✅ Robust stylesheet refresh
            await this.refreshStylesheets();

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

    // Replaces each stylesheet <link> with a cloned node using a cache-busting v=timestamp,
    // awaits load to ensure CSSOM is rebuilt, then forces a reflow.
    async refreshStylesheets(timeoutMs = 4000) {
        const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
        const waitForLoad = (link) =>
            new Promise((resolve, reject) => {
                const timer = setTimeout(() => reject(new Error('Stylesheet load timeout')), timeoutMs);
                link.addEventListener('load', () => { clearTimeout(timer); resolve(); });
                link.addEventListener('error', () => { clearTimeout(timer); reject(new Error(`Stylesheet load error: ${link.href}`)); });
            });

        const tasks = links.map(async (oldLink) => {
            try {
                const href = oldLink.getAttribute('href');
                if (!href) return;

                const url = new URL(href, window.location.origin);
                url.searchParams.set('v', Date.now().toString());

                const newLink = oldLink.cloneNode(true);
                newLink.href = url.toString();

                oldLink.parentNode.insertBefore(newLink, oldLink.nextSibling);
                await waitForLoad(newLink);
                oldLink.remove();
            } catch (e) {
                console.warn('Stylesheet refresh warning:', e.message || e);
            }
        });

        await Promise.allSettled(tasks);

        // Nudge CSSOM and force reflow
        try {
            const sheet = document.styleSheets[0];
            if (sheet?.insertRule) {
                sheet.insertRule(':root { --css-refresh: 1 }', sheet.cssRules.length);
                sheet.deleteRule(sheet.cssRules.length - 1);
            }
        } catch (_) {}
        void document.body.offsetHeight;
    }

    /**
     * Synchronize page-specific head assets from the fetched document.
     * This includes:
     * - Inline <style> blocks
     * - Page-specific <link> stylesheets (e.g., external libraries like cropperjs)
     * - Page-specific <meta> tags (e.g., viewport-fit=cover)
     * - Page-specific <script> tags in the head (e.g., page-specific libraries)
     * 
     * @param {Document} newDoc - The parsed document from the AJAX response
     */
    synchronizeHeadAssets(newDoc) {
        const currentHead = document.head;
        const newHead = newDoc.head;

        // Define global assets that should not be synchronized (to avoid duplication)
        const globalStylesheets = ['/assets/css/app.css'];
        const globalScripts = ['/assets/js/menu.js', '/assets/js/ajax-navigation.js'];

        // Remove existing page-specific inline styles (marked with data-page-specific)
        // This ensures we don't accumulate styles from multiple pages
        const existingPageStyles = currentHead.querySelectorAll('style[data-page-specific]');
        existingPageStyles.forEach(style => style.remove());

        // Remove existing page-specific link stylesheets
        const existingPageLinks = currentHead.querySelectorAll('link[rel="stylesheet"][data-page-specific]');
        existingPageLinks.forEach(link => link.remove());

        // Remove existing page-specific scripts
        const existingPageScripts = currentHead.querySelectorAll('script[data-page-specific]');
        existingPageScripts.forEach(script => script.remove());

        // Add new page-specific inline styles
        // Note: In this codebase, inline styles in <head> are page-specific by convention
        // Each page defines its own layout styles, so we swap them during navigation
        const newStyles = newHead.querySelectorAll('style');
        newStyles.forEach(style => {
            const clonedStyle = style.cloneNode(true);
            clonedStyle.setAttribute('data-page-specific', 'true');
            currentHead.appendChild(clonedStyle);
        });

        // Add new page-specific link stylesheets (exclude global stylesheets)
        const newLinks = newHead.querySelectorAll('link[rel="stylesheet"]');
        newLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (!href) return;

            // Check if this is a global stylesheet
            const isGlobal = globalStylesheets.some(globalPath => {
                // Handle both absolute paths and URLs with query params
                return href.includes(globalPath);
            });

            if (!isGlobal) {
                const clonedLink = link.cloneNode(true);
                clonedLink.setAttribute('data-page-specific', 'true');
                currentHead.appendChild(clonedLink);
            }
        });

        // Synchronize page-specific meta tags (e.g., viewport-fit=cover)
        // Only update if the new page has a different viewport meta tag
        const newViewport = newHead.querySelector('meta[name="viewport"]');
        const currentViewport = currentHead.querySelector('meta[name="viewport"]');
        if (newViewport && currentViewport) {
            const newContent = newViewport.getAttribute('content');
            const currentContent = currentViewport.getAttribute('content');
            if (newContent !== currentContent) {
                currentViewport.setAttribute('content', newContent);
            }
        }

        // Add new page-specific scripts from head (exclude global scripts)
        // This handles page-specific libraries like cropperjs
        const newScripts = newHead.querySelectorAll('script[src]');
        newScripts.forEach(script => {
            const src = script.getAttribute('src');
            if (!src) return;

            // Check if this is a global script
            const isGlobal = globalScripts.some(globalPath => {
                return src.includes(globalPath);
            });

            if (!isGlobal) {
                // Check if this script is already loaded (compare src values programmatically)
                const existingScript = Array.from(currentHead.querySelectorAll('script[src]')).find(
                    s => s.getAttribute('src') === src
                );

                if (!existingScript) {
                    const clonedScript = document.createElement('script');
                    clonedScript.src = src;
                    clonedScript.setAttribute('data-page-specific', 'true');
                    // Copy other attributes
                    Array.from(script.attributes).forEach(attr => {
                        if (attr.name !== 'src') {
                            clonedScript.setAttribute(attr.name, attr.value);
                        }
                    });
                    currentHead.appendChild(clonedScript);
                }
            }
        });
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
