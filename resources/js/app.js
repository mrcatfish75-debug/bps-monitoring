import './bootstrap';

import Alpine from 'alpinejs';
import { createApp, nextTick } from 'vue/dist/vue.esm-bundler.js';
import { createIcons, icons } from 'lucide';

window.Alpine = Alpine;
Alpine.start();

function refreshLucideIcons() {
    nextTick(() => {
        createIcons({
            icons,
            attrs: {
                'stroke-width': 2,
            },
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const sidebarShell = document.getElementById('sidebar-shell');

    if (!sidebarShell) {
        refreshLucideIcons();
        return;
    }

    if (sidebarShell.dataset.vueMounted === 'true') {
        refreshLucideIcons();
        return;
    }

    const sidebarApp = createApp({
        data() {
            return {
                sidebarExpanded: false,
                sidebarTheme: 'dark',
                sidebarThemeKey: 'bps-sidebar-theme',
                mobileBreakpoint: 1024,
            };
        },

        mounted() {
            this.sidebarTheme = this.getSavedSidebarTheme();
            this.sidebarExpanded = false;

            this.applySidebarTheme();
            refreshLucideIcons();

            window.addEventListener('resize', this.handleResize);
            window.addEventListener('keydown', this.handleKeydown);
        },

        updated() {
            this.applySidebarTheme();
            refreshLucideIcons();
        },

        beforeUnmount() {
            window.removeEventListener('resize', this.handleResize);
            window.removeEventListener('keydown', this.handleKeydown);
        },

        methods: {
            isMobile() {
                return window.innerWidth < this.mobileBreakpoint;
            },

            getSavedSidebarTheme() {
                try {
                    const savedTheme = localStorage.getItem(this.sidebarThemeKey);

                    if (savedTheme === 'dark' || savedTheme === 'light') {
                        return savedTheme;
                    }

                    return 'dark';
                } catch (error) {
                    return 'dark';
                }
            },

            applySidebarTheme() {
                const root = document.getElementById('sidebar-shell');

                if (!root) {
                    return;
                }

                root.setAttribute('data-sidebar-theme', this.sidebarTheme);

                root.classList.remove('sidebar-theme-dark', 'sidebar-theme-light');
                root.classList.add(
                    this.sidebarTheme === 'dark'
                        ? 'sidebar-theme-dark'
                        : 'sidebar-theme-light'
                );
            },

            setSidebarTheme(theme) {
                if (theme !== 'dark' && theme !== 'light') {
                    return;
                }

                this.sidebarTheme = theme;

                try {
                    localStorage.setItem(this.sidebarThemeKey, theme);
                } catch (error) {
                    //
                }

                this.applySidebarTheme();
                refreshLucideIcons();
            },

            toggleSidebarTheme() {
                this.setSidebarTheme(this.sidebarTheme === 'dark' ? 'light' : 'dark');
            },

            toggleSidebar() {
                this.sidebarExpanded = !this.sidebarExpanded;
                refreshLucideIcons();
            },

            openSidebar() {
                this.sidebarExpanded = true;
                refreshLucideIcons();
            },

            closeSidebar() {
                this.sidebarExpanded = false;
                refreshLucideIcons();
            },

            handleMenuClick() {
                if (this.isMobile()) {
                    this.closeSidebar();
                }
            },

            handleResize() {
                if (this.isMobile()) {
                    this.sidebarExpanded = false;
                }

                refreshLucideIcons();
            },

            handleKeydown(event) {
                if (event.key === 'Escape') {
                    this.closeSidebar();
                }
            },
        },
    });

    sidebarApp.mount('#sidebar-shell');

    sidebarShell.dataset.vueMounted = 'true';

    refreshLucideIcons();
});