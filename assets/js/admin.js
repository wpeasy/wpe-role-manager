/**
 * WP Easy Role Manager - Admin JavaScript
 *
 * @package WP_Easy\RoleManager
 * @version 0.0.1-alpha
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('roleManagerApp', () => ({
        // State
        currentTab: 'roles',
        status: '', // '', 'saving', 'saved', 'error'

        // Data
        roles: [],
        capabilities: [],
        users: [],
        settings: {},
        logs: [],

        // Initialize
        init() {
            console.log('WP Easy Role Manager initialized', wpeRmAdmin);
            this.loadData();
        },

        // Load initial data
        async loadData() {
            try {
                await this.fetchRoles();
                await this.fetchUsers();
            } catch (error) {
                console.error('Error loading data:', error);
                this.showError();
            }
        },

        // Fetch roles from REST API
        async fetchRoles() {
            const response = await fetch(`${wpeRmAdmin.restUrl}/roles`, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': wpeRmAdmin.nonce,
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch roles');
            }

            const data = await response.json();
            this.roles = data.roles || [];
        },

        // Fetch users from REST API
        async fetchUsers() {
            const response = await fetch(`${wpeRmAdmin.restUrl}/users`, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': wpeRmAdmin.nonce,
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch users');
            }

            const data = await response.json();
            this.users = data.users || [];
        },

        // Status indicators
        showSaving() {
            this.status = 'saving';
        },

        showSaved() {
            this.status = 'saved';
            setTimeout(() => {
                this.status = '';
            }, 3000);
        },

        showError() {
            this.status = 'error';
            setTimeout(() => {
                this.status = '';
            }, 5000);
        },

        // Autosave with debounce
        autosave: null,
        debounceAutosave(callback, delay = 500) {
            clearTimeout(this.autosave);
            this.showSaving();
            this.autosave = setTimeout(async () => {
                try {
                    await callback();
                    this.showSaved();
                } catch (error) {
                    console.error('Autosave error:', error);
                    this.showError();
                }
            }, delay);
        },

        // Tab switching
        switchTab(tab) {
            this.currentTab = tab;
        },

        // Confirm dialog
        confirm(message) {
            return window.confirm(message || wpeRmAdmin.i18n.confirmDelete);
        },
    }));
});

// Global utility functions
window.wpeRmUtils = {
    /**
     * Make REST API request
     */
    async request(endpoint, options = {}) {
        const url = `${wpeRmAdmin.restUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wpeRmAdmin.nonce,
            },
        };

        const response = await fetch(url, { ...defaultOptions, ...options });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'API request failed');
        }

        return await response.json();
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
};
