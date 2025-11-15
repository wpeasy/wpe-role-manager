/**
 * Application State Store (Svelte 5 Runes)
 *
 * @package WP_Easy\RoleManager
 */

/**
 * Create the application state using Svelte 5 runes
 */
export function createAppStore(wpData = {}) {
  // Current active tab
  let currentTab = $state('roles');

  // Status indicator: '', 'saving', 'saved', 'error'
  let status = $state('');

  // Data stores
  let roles = $state([]);
  let capabilities = $state([]);
  let capabilityMatrix = $state([]);
  let users = $state([]);
  let settings = $state({});
  let logs = $state([]);

  // WordPress data
  const restUrl = wpData.restUrl || '';
  const nonce = wpData.nonce || '';
  const i18n = wpData.i18n || {};

  // Status management
  let statusTimeout = null;

  function setStatus(newStatus, duration = 3000) {
    status = newStatus;

    if (statusTimeout) {
      clearTimeout(statusTimeout);
    }

    if (newStatus && duration > 0) {
      statusTimeout = setTimeout(() => {
        status = '';
      }, duration);
    }
  }

  function showSaving() {
    setStatus('saving', 0);
  }

  function showSaved() {
    setStatus('saved', 3000);
  }

  function showError() {
    setStatus('error', 5000);
  }

  // API helper
  async function apiRequest(endpoint, options = {}) {
    const url = `${restUrl}${endpoint}`;
    const defaultOptions = {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
    };

    try {
      const response = await fetch(url, { ...defaultOptions, ...options });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'API request failed');
      }

      return await response.json();
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }

  // Data fetching methods
  async function fetchRoles() {
    try {
      const data = await apiRequest('/roles');
      roles = data.roles || [];
    } catch (error) {
      console.error('Error fetching roles:', error);
      showError();
    }
  }

  async function fetchUsers() {
    try {
      const data = await apiRequest('/users');
      users = data.users || [];
    } catch (error) {
      console.error('Error fetching users:', error);
      showError();
    }
  }

  async function fetchCapabilities() {
    try {
      const data = await apiRequest('/capabilities');
      capabilities = data.capabilities || [];
    } catch (error) {
      console.error('Error fetching capabilities:', error);
      showError();
    }
  }

  async function fetchCapabilityMatrix() {
    try {
      const data = await apiRequest('/capabilities/matrix');
      capabilityMatrix = data.matrix || [];
    } catch (error) {
      console.error('Error fetching capabilities:', error);
      showError();
    }
  }

  async function fetchSettings() {
    try {
      const data = await apiRequest('/settings');
      settings = data.settings || {};
    } catch (error) {
      console.error('Error fetching settings:', error);
    }
  }

  // Initialize data
  async function init() {
    console.log('WP Easy Role Manager - Svelte 5 App Initialized', wpData);
    await Promise.all([fetchRoles(), fetchUsers(), fetchCapabilityMatrix(), fetchSettings()]);
  }

  // Return the store interface
  return {
    // State (read-only from outside)
    get currentTab() {
      return currentTab;
    },
    set currentTab(value) {
      currentTab = value;
    },

    get status() {
      return status;
    },

    get roles() {
      return roles;
    },
    set roles(value) {
      roles = value;
    },

    get capabilities() {
      return capabilities;
    },
    set capabilities(value) {
      capabilities = value;
    },

    get capabilityMatrix() {
      return capabilityMatrix;
    },
    set capabilityMatrix(value) {
      capabilityMatrix = value;
    },

    get users() {
      return users;
    },
    set users(value) {
      users = value;
    },

    get settings() {
      return settings;
    },
    set settings(value) {
      settings = value;
    },

    get logs() {
      return logs;
    },
    set logs(value) {
      logs = value;
    },

    // Methods
    showSaving,
    showSaved,
    showError,
    apiRequest,
    fetchRoles,
    fetchUsers,
    fetchCapabilities,
    fetchCapabilityMatrix,
    fetchSettings,
    init,

    // WordPress data
    restUrl,
    nonce,
    i18n,
  };
}
