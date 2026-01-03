/**
 * Persistent Bootstrap Tabs
 * Maintains active tab state across page reloads using URL hash
 */

(function() {
    'use strict';

    const PersistentTabs = {
        /**
         * Initialize persistent tabs functionality
         */
        init() {
            console.log('Initializing Persistent Tabs...');

            // Restore active tab on page load
            this.restoreActiveTab();

            // Save active tab when tab is clicked
            this.bindTabEvents();

            console.log('Persistent Tabs initialized');
        },

        /**
         * Restore the active tab from URL hash
         */
        restoreActiveTab() {
            // Check if there's a hash in the URL
            const hash = window.location.hash;

            if (hash) {
                console.log('Found hash:', hash);

                // Try to find a tab with this ID
                const tabElement = document.querySelector(`a[href="${hash}"][data-toggle="pill"], a[href="${hash}"][data-toggle="tab"]`);

                if (tabElement) {
                    console.log('Activating tab from hash:', hash);

                    // Use Bootstrap's tab show method
                    if (typeof $(tabElement).tab === 'function') {
                        $(tabElement).tab('show');
                    } else {
                        // Fallback if jQuery/Bootstrap not loaded yet
                        tabElement.click();
                    }
                } else {
                    console.log('No tab found for hash:', hash);
                }
            }
        },

        /**
         * Bind events to save tab state when clicked
         */
        bindTabEvents() {
            // Listen for Bootstrap tab show events
            document.addEventListener('click', (e) => {
                // Early exit for form elements
                const tagName = e.target.tagName;
                if (tagName === 'INPUT' || tagName === 'SELECT' || tagName === 'TEXTAREA' ||
                    tagName === 'BUTTON' || tagName === 'LABEL' || tagName === 'OPTION') {
                    return;
                }

                const tabLink = e.target.closest('[data-toggle="pill"], [data-toggle="tab"]');

                if (tabLink) {
                    const href = tabLink.getAttribute('href');

                    if (href && href.startsWith('#')) {
                        console.log('Tab clicked, updating hash to:', href);

                        // Update URL hash without jumping
                        history.replaceState(null, null, href);
                    }
                }
            });

            // Also listen for Bootstrap's shown.bs.tab event for programmatic tab changes
            $('a[data-toggle="pill"], a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                const href = $(this).attr('href');

                if (href && href.startsWith('#')) {
                    console.log('Tab shown event, updating hash to:', href);
                    history.replaceState(null, null, href);
                }
            });
        },

        /**
         * Reinitialize after AJAX content load
         */
        reinitialize() {
            console.log('Reinitializing persistent tabs after AJAX load');
            this.restoreActiveTab();
            this.bindTabEvents();
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => PersistentTabs.init());
    } else {
        PersistentTabs.init();
    }

    // Reinitialize after AJAX content loads
    document.addEventListener('ajax-content-loaded', () => {
        PersistentTabs.reinitialize();
    });

    // Expose to global scope
    window.PersistentTabs = PersistentTabs;

})();
