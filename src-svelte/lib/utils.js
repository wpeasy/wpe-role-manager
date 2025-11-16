/**
 * Centralized utility functions for WP Easy Role Manager
 *
 * @package WP_Easy\RoleManager
 */

/**
 * WordPress slug constraints
 */
const SLUG_CONSTRAINTS = {
  ROLE_MIN_LENGTH: 1,
  ROLE_MAX_LENGTH: 20, // WordPress database column constraint
  CAPABILITY_MIN_LENGTH: 1,
  CAPABILITY_MAX_LENGTH: 191, // WordPress meta key limit
};

/**
 * Sanitize a string to be a valid WordPress slug
 * Only allows lowercase letters, numbers, hyphens, and underscores
 * Enforces maximum length based on type
 *
 * @param {string} value - The string to sanitize
 * @param {string} type - The type of slug ('role' or 'capability')
 * @param {number} maxLength - Optional custom max length override
 * @returns {string} The sanitized slug
 */
export function sanitizeSlug(value, type = 'role', maxLength = null) {
  // Determine max length based on type
  const max = maxLength || (type === 'capability' ? SLUG_CONSTRAINTS.CAPABILITY_MAX_LENGTH : SLUG_CONSTRAINTS.ROLE_MAX_LENGTH);

  // Sanitize and enforce max length
  return value
    .toLowerCase()
    .replace(/[^a-z0-9_-]/g, '')
    .slice(0, max);
}

/**
 * Validate a slug meets WordPress requirements
 *
 * @param {string} slug - The slug to validate
 * @param {string} type - The type of slug ('role' or 'capability')
 * @returns {Object} { valid: boolean, error: string|null }
 */
export function validateSlug(slug, type = 'role') {
  const minLength = type === 'capability' ? SLUG_CONSTRAINTS.CAPABILITY_MIN_LENGTH : SLUG_CONSTRAINTS.ROLE_MIN_LENGTH;
  const maxLength = type === 'capability' ? SLUG_CONSTRAINTS.CAPABILITY_MAX_LENGTH : SLUG_CONSTRAINTS.ROLE_MAX_LENGTH;

  if (!slug || slug.length < minLength) {
    return {
      valid: false,
      error: `${type === 'role' ? 'Role' : 'Capability'} slug must be at least ${minLength} character${minLength > 1 ? 's' : ''} long.`
    };
  }

  if (slug.length > maxLength) {
    return {
      valid: false,
      error: `${type === 'role' ? 'Role' : 'Capability'} slug cannot exceed ${maxLength} characters.`
    };
  }

  // Check if contains only valid characters
  if (!/^[a-z0-9_-]+$/.test(slug)) {
    return {
      valid: false,
      error: `${type === 'role' ? 'Role' : 'Capability'} slug can only contain lowercase letters, numbers, hyphens, and underscores.`
    };
  }

  return { valid: true, error: null };
}

/**
 * Generate a capability name based on a pattern and slug
 * Follows WordPress naming conventions for post type capabilities
 *
 * @param {string} pattern - The capability pattern (read, edit, delete, etc.)
 * @param {string} slug - The role/post type slug
 * @returns {string} The generated capability name
 */
export function generateCapabilityName(pattern, slug) {
  // For singular capabilities
  if (pattern === 'read' || pattern === 'edit' || pattern === 'delete') {
    return `${pattern}_${slug}`;
  }

  // For plural capabilities (assume slug + 's' for plural)
  const plural = slug.endsWith('s') ? slug : `${slug}s`;

  const capabilityMap = {
    'read_private': `read_private_${plural}`,
    'edit_others': `edit_others_${plural}`,
    'edit_published': `edit_published_${plural}`,
    'edit_private': `edit_private_${plural}`,
    'publish': `publish_${plural}`,
    'delete_others': `delete_others_${plural}`,
    'delete_published': `delete_published_${plural}`,
    'delete_private': `delete_private_${plural}`,
  };

  return capabilityMap[pattern] || '';
}

/**
 * Pluralize a slug for WordPress capability naming
 *
 * @param {string} slug - The slug to pluralize
 * @returns {string} The pluralized slug
 */
export function pluralizeSlug(slug) {
  return slug.endsWith('s') ? slug : `${slug}s`;
}
