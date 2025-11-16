/**
 * Centralized utility functions for WP Easy Role Manager
 *
 * @package WP_Easy\RoleManager
 */

/**
 * Sanitize a string to be a valid WordPress slug
 * Only allows lowercase letters, numbers, hyphens, and underscores
 *
 * @param {string} value - The string to sanitize
 * @returns {string} The sanitized slug
 */
export function sanitizeSlug(value) {
  return value
    .toLowerCase()
    .replace(/[^a-z0-9_-]/g, '');
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
