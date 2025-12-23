/**
 * Application State Store (Svelte 5 Runes)
 *
 * Uses window.WPE_RM shared module for API requests.
 *
 * @package WP_Easy\RoleManager
 */

/**
 * Get the API client from the shared module
 * @returns {Object} API client
 */
function getApi() {
  if (!window.WPE_RM?.api) {
    console.error('[WPE_RM Store] Shared module not loaded');
    return null;
  }
  return window.WPE_RM.api;
}

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

  // Loading states
  let loadingRoles = $state(false);
  let loadingUsers = $state(false);
  let loadingCapabilities = $state(false);
  let loadingLogs = $state(false);
  let loadingRevisions = $state(false);

  // i18n from shared module or fallback
  const i18n = window.WPE_RM?.i18n || wpData.i18n || {};

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

  // API helper - uses shared module
  async function apiRequest(endpoint, options = {}) {
    const api = getApi();
    if (!api) {
      throw new Error('API not available');
    }

    const method = (options.method || 'GET').toUpperCase();

    try {
      if (method === 'GET') {
        return await api.get(endpoint);
      } else if (method === 'POST') {
        return await api.post(endpoint, options.body ? JSON.parse(options.body) : {});
      } else if (method === 'PUT') {
        return await api.put(endpoint, options.body ? JSON.parse(options.body) : {});
      } else if (method === 'PATCH') {
        return await api.patch(endpoint, options.body ? JSON.parse(options.body) : {});
      } else if (method === 'DELETE') {
        return await api.delete(endpoint);
      }
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }

  // Data fetching methods
  async function fetchRoles() {
    try {
      loadingRoles = true;
      const api = getApi();
      if (!api) return;
      const data = await api.get('/roles');
      roles = data.roles || [];
    } catch (error) {
      console.error('Error fetching roles:', error);
      showError();
    } finally {
      loadingRoles = false;
    }
  }

  async function fetchUsers() {
    try {
      loadingUsers = true;
      const api = getApi();
      if (!api) return;
      const data = await api.get('/users');
      users = data.users || [];
    } catch (error) {
      console.error('Error fetching users:', error);
      showError();
    } finally {
      loadingUsers = false;
    }
  }

  async function fetchCapabilities() {
    try {
      loadingCapabilities = true;
      const api = getApi();
      if (!api) return;
      const data = await api.get('/capabilities');
      capabilities = data.capabilities || [];
    } catch (error) {
      console.error('Error fetching capabilities:', error);
      showError();
    } finally {
      loadingCapabilities = false;
    }
  }

  async function fetchCapabilityMatrix() {
    try {
      loadingCapabilities = true;
      const api = getApi();
      if (!api) return;
      const data = await api.get('/capabilities/matrix');
      capabilityMatrix = data.matrix || [];
    } catch (error) {
      console.error('Error fetching capabilities:', error);
      showError();
    } finally {
      loadingCapabilities = false;
    }
  }

  async function fetchSettings() {
    try {
      const api = getApi();
      if (!api) return;
      const data = await api.get('/settings');
      settings = data.settings || {};
    } catch (error) {
      console.error('Error fetching settings:', error);
    }
  }

  async function saveSettings(newSettings) {
    try {
      showSaving();
      const api = getApi();
      if (!api) throw new Error('API not available');

      const data = await api.post('/settings', newSettings);
      settings = data.settings || newSettings;
      showSaved();

      // Emit event for other modules
      window.WPE_RM?.emit?.('settings:changed', settings);

      return data;
    } catch (error) {
      console.error('Error saving settings:', error);
      showError();
      throw error;
    }
  }

  async function fetchLogs() {
    try {
      loadingLogs = true;
      const api = getApi();
      if (!api) return;
      const data = await api.get('/logs');
      logs = data.logs || [];
    } catch (error) {
      console.error('Error fetching logs:', error);
      showError();
    } finally {
      loadingLogs = false;
    }
  }

  // Initialize data
  async function init() {
    console.log('[WPE_RM] Svelte App Store Initialized');
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

    // Loading states
    get loadingRoles() {
      return loadingRoles;
    },
    get loadingUsers() {
      return loadingUsers;
    },
    get loadingCapabilities() {
      return loadingCapabilities;
    },
    get loadingLogs() {
      return loadingLogs;
    },
    get loadingRevisions() {
      return loadingRevisions;
    },
    set loadingRevisions(value) {
      loadingRevisions = value;
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
    saveSettings,
    fetchLogs,
    init,

    // i18n
    i18n,
  };
}
