/**
 * AJAX Router for SchoolDynamics SIMS
 * Handles client-side navigation without full page reloads
 */

(function() {
    'use strict';

    const AjaxRouter = {
        // Configuration
        config: {
            contentSelector: '.page-content-area',  // Custom wrapper that includes page-header and page-body
            linkSelector: 'a:not([target="_blank"]):not([data-no-ajax]):not([href^="#"]):not([href^="javascript:"]):not([download]):not([data-bs-toggle]):not([role="tab"]):not(.nav-link[data-bs-toggle])',
            formSelector: 'form:not([data-no-ajax])',
            loadingClass: 'ajax-loading',
            excludePatterns: [
                /^mailto:/,
                /^tel:/,
                /\.(pdf|zip|doc|docx|xls|xlsx)$/i,
                /^http/  // External links
            ]
        },

        // State management
        state: {
            isLoading: false,
            currentUrl: window.location.href
        },

        /**
         * Initialize the router
         */
        init() {
            console.log('Initializing AJAX Router...');

            // Set up event listeners
            this.bindEvents();

            // Handle browser back/forward buttons
            this.handlePopState();

            // Mark initial page load
            this.replaceState(window.location.href, document.title);

            console.log('AJAX Router initialized successfully');
        },

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Intercept link clicks - use bubble phase to allow Bootstrap to handle its components first
            document.addEventListener('click', (e) => {
                // Skip if event was already handled by Bootstrap or other components
                if (e.defaultPrevented) return;

                // Early exit - if click is inside a form, skip unless it's a link
                if (e.target.closest('form') && !e.target.closest('a')) return;

                const link = e.target.closest('a');
                if (!link) return;

                // Skip Bootstrap components entirely
                if (link.hasAttribute('data-bs-toggle')) return;
                if (link.classList.contains('dropdown-toggle')) return;
                if (link.closest('.dropdown-menu') && !link.getAttribute('href')?.startsWith('/')) return;
                if (link.getAttribute('role') === 'tab') return;

                // Check if this link should be intercepted for AJAX navigation
                if (this.shouldInterceptLink(link)) {
                    e.preventDefault();
                    this.navigate(link.href);
                }
            }, false); // FALSE = bubble phase, allows other handlers to run first

            // Intercept form submissions
            document.addEventListener('submit', (e) => {
                const form = e.target.closest(this.config.formSelector);
                if (form && this.shouldInterceptForm(form)) {
                    e.preventDefault();
                    this.submitForm(form);
                }
            });
        },

        /**
         * Check if link should be intercepted
         */
        shouldInterceptLink(link) {
            const href = link.getAttribute('href');

            // Don't intercept if no href
            if (!href) return false;

            // Don't intercept Bootstrap components (dropdowns, tabs, modals, etc.)
            if (link.hasAttribute('data-bs-toggle')) return false;
            if (link.hasAttribute('role') && link.getAttribute('role') === 'tab') return false;
            if (link.classList.contains('dropdown-toggle')) return false;

            // Don't intercept if href matches exclude patterns
            for (let pattern of this.config.excludePatterns) {
                if (pattern.test(href)) return false;
            }

            // Don't intercept if different origin
            try {
                const linkUrl = new URL(href, window.location.origin);
                if (linkUrl.origin !== window.location.origin) return false;
            } catch (e) {
                return false;
            }

            // Don't intercept campus switcher (causes full reload)
            if (href.includes('/switch-campus')) return false;

            return true;
        },

        /**
         * Check if form should be intercepted
         */
        shouldInterceptForm(form) {
            // Don't intercept file upload forms
            if (form.enctype === 'multipart/form-data') return false;

            // Don't intercept if method is GET and action is external
            const action = form.action;
            try {
                const actionUrl = new URL(action, window.location.origin);
                if (actionUrl.origin !== window.location.origin) return false;
            } catch (e) {
                return false;
            }

            return true;
        },

        /**
         * Navigate to a URL
         */
        async navigate(url, pushState = true) {
            // Prevent concurrent requests
            if (this.state.isLoading) {
                console.log('Navigation already in progress, skipping...');
                return;
            }

            // Don't navigate to same URL
            if (url === this.state.currentUrl) {
                console.log('Already on this page, skipping...');
                return;
            }

            console.log('Navigating to:', url);

            this.state.isLoading = true;
            this.showLoading();

            try {
                // Fetch the page with AJAX header
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-AJAX-Navigation': '1'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                // Get the HTML content
                const html = await response.text();

                // Update the content
                this.updateContent(html);

                // Update browser history
                if (pushState) {
                    this.pushState(url, this.extractTitle(html));
                }

                // Update current URL
                this.state.currentUrl = url;

                // Scroll to top
                window.scrollTo(0, 0);

                // Reinitialize any JavaScript components
                this.reinitializeComponents();

                console.log('Navigation completed successfully');

            } catch (error) {
                console.error('Navigation error:', error);
                this.showError('Failed to load page. Please try again.');

                // Fall back to full page load on error
                setTimeout(() => {
                    window.location.href = url;
                }, 2000);
            } finally {
                this.state.isLoading = false;
                this.hideLoading();
            }
        },

        /**
         * Submit form via AJAX
         */
        async submitForm(form) {
            if (this.state.isLoading) return;

            console.log('Submitting form via AJAX:', form.action);

            // Clean up any open modals before submitting
            this.cleanupModals();

            this.state.isLoading = true;
            this.showLoading();

            try {
                const formData = new FormData(form);
                const method = form.method.toUpperCase() || 'POST';

                let url = form.action;
                let fetchOptions = {
                    method: method,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-AJAX-Navigation': '1'
                    },
                    credentials: 'same-origin'
                };

                // For GET requests, append form data to URL as query parameters
                if (method === 'GET') {
                    const params = new URLSearchParams(formData);
                    url = form.action + (form.action.includes('?') ? '&' : '?') + params.toString();
                } else {
                    // For POST requests, include form data in body
                    fetchOptions.body = formData;
                }

                const response = await fetch(url, fetchOptions);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                // Check if response is a redirect
                const finalUrl = response.url;
                const html = await response.text();

                // Update content
                this.updateContent(html);

                // Update URL - for GET forms, use the URL we built; for POST, use the response URL
                const newUrl = method === 'GET' ? url : finalUrl;
                if (newUrl !== window.location.href) {
                    this.pushState(newUrl, this.extractTitle(html));
                    this.state.currentUrl = newUrl;
                }

                // Scroll to top
                window.scrollTo(0, 0);

                // Reinitialize components
                this.reinitializeComponents();

                console.log('Form submitted successfully');

            } catch (error) {
                console.error('Form submission error:', error);
                this.showError('Failed to submit form. Please try again.');

                // Fall back to normal form submission
                setTimeout(() => {
                    form.submit();
                }, 2000);
            } finally {
                this.state.isLoading = false;
                this.hideLoading();
            }
        },

        /**
         * Update page content
         */
        updateContent(html) {
            const contentWrapper = document.querySelector(this.config.contentSelector);
            if (!contentWrapper) {
                console.error('Content wrapper not found');
                return;
            }

            // Parse the HTML response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector(this.config.contentSelector);

            const self = this;

            if (newContent) {
                // Replace content with fade effect
                contentWrapper.style.opacity = '0';

                setTimeout(() => {
                    contentWrapper.innerHTML = newContent.innerHTML;
                    contentWrapper.style.opacity = '1';
                    // Execute scripts from new content
                    self.executeScripts(contentWrapper);
                }, 150);
            } else {
                // If no content wrapper found, it might be the content-only response
                contentWrapper.style.opacity = '0';

                setTimeout(() => {
                    contentWrapper.innerHTML = html;
                    contentWrapper.style.opacity = '1';
                    // Execute scripts from new content
                    self.executeScripts(contentWrapper);
                }, 150);
            }

            // Update page title if present
            const newTitle = doc.querySelector('title');
            if (newTitle) {
                document.title = newTitle.textContent;
            }

            // Update active nav items
            this.updateActiveNav();
        },

        /**
         * Execute scripts found in the updated content
         * Wraps inline scripts in IIFE to prevent variable redeclaration errors
         */
        executeScripts(container) {
            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                try {
                    // For external scripts, create a new script element
                    if (oldScript.src) {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    } else if (oldScript.textContent) {
                        // For inline scripts, wrap in IIFE to create new scope
                        // This prevents "Identifier has already been declared" errors
                        const wrappedCode = `(function() { ${oldScript.textContent} })();`;
                        const newScript = document.createElement('script');
                        newScript.textContent = wrappedCode;
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    }
                } catch (e) {
                    console.error('Error executing script:', e);
                }
            });

            console.log('Executed', scripts.length, 'scripts from loaded content');
        },

        /**
         * Extract title from HTML
         */
        extractTitle(html) {
            const match = html.match(/<title>(.*?)<\/title>/i);
            return match ? match[1] : document.title;
        },

        /**
         * Update active navigation items
         * Disabled for now - navigation highlighting was causing issues
         */
        updateActiveNav() {
            // Navigation active state highlighting disabled
            // The page content updates correctly, menu highlighting was unreliable
        },

        /**
         * Show loading indicator
         */
        showLoading() {
            const contentWrapper = document.querySelector(this.config.contentSelector);
            if (contentWrapper) {
                contentWrapper.classList.add(this.config.loadingClass);
            }

            // Show loading overlay
            let overlay = document.getElementById('ajax-loading-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'ajax-loading-overlay';
                overlay.innerHTML = `
                    <div class="ajax-spinner">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Loading...</p>
                    </div>
                `;
                document.body.appendChild(overlay);
            }
            overlay.style.display = 'flex';
        },

        /**
         * Hide loading indicator
         */
        hideLoading() {
            const contentWrapper = document.querySelector(this.config.contentSelector);
            if (contentWrapper) {
                contentWrapper.classList.remove(this.config.loadingClass);
            }

            const overlay = document.getElementById('ajax-loading-overlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        },

        /**
         * Show error message
         */
        showError(message) {
            // Use AdminLTE toast if available
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                alert(message);
            }
        },

        /**
         * Handle browser back/forward buttons
         */
        handlePopState() {
            window.addEventListener('popstate', (e) => {
                console.log('Popstate event:', e.state);

                if (e.state && e.state.url) {
                    // Navigate without pushing new state
                    this.navigate(e.state.url, false);
                } else {
                    // Reload page if no state
                    window.location.reload();
                }
            });
        },

        /**
         * Push state to history
         */
        pushState(url, title) {
            window.history.pushState({ url: url, title: title }, title, url);
        },

        /**
         * Replace state in history
         */
        replaceState(url, title) {
            window.history.replaceState({ url: url, title: title }, title, url);
        },

        /**
         * Clean up Bootstrap modals and backdrops
         * This prevents modal backdrop from staying stuck after AJAX navigation
         */
        cleanupModals() {
            // First, move focus out of any modal to avoid aria-hidden warning
            if (document.activeElement && document.activeElement.closest('.modal')) {
                document.activeElement.blur();
            }

            // Dispose of all modal instances properly
            document.querySelectorAll('.modal').forEach(modalEl => {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    try {
                        modalInstance.dispose();
                    } catch (e) {
                        // Ignore dispose errors
                    }
                }
                // Reset modal state
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.removeAttribute('aria-modal');
                modalEl.removeAttribute('role');
                modalEl.setAttribute('aria-hidden', 'true');
            });

            // Remove all modal backdrops
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

            // Reset body styles
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        },

        /**
         * Reinitialize JavaScript components after content update
         */
        reinitializeComponents() {
            // Clean up any leftover modal state
            this.cleanupModals();

            // Reinitialize Bootstrap components
            if (typeof $().tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip();
            }

            if (typeof $().popover === 'function') {
                $('[data-toggle="popover"]').popover();
            }

            if (typeof $().modal === 'function') {
                // Ensure modals are properly initialized
                $('.modal').modal({ show: false });
            }

            // Reinitialize Select2 if present
            if (typeof $().select2 === 'function') {
                $('.select2').select2();
            }

            // Reinitialize DataTables if present
            if (typeof $().DataTable === 'function') {
                $('[data-table="true"]').DataTable();
            }

            // Reinitialize any custom components
            if (window.initializePageComponents) {
                window.initializePageComponents();
            }

            // Trigger custom event for other scripts
            document.dispatchEvent(new CustomEvent('ajax-content-loaded'));
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => AjaxRouter.init());
    } else {
        AjaxRouter.init();
    }

    // Expose to global scope for external access
    window.AjaxRouter = AjaxRouter;

})();
