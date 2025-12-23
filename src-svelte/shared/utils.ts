/**
 * Utility functions for WPE Role Manager
 *
 * @package WP_Easy\RoleManager
 */

/**
 * WordPress slug constraints
 */
export const SLUG_CONSTRAINTS = {
  ROLE_MIN_LENGTH: 1,
  ROLE_MAX_LENGTH: 20, // WordPress database column constraint
  CAPABILITY_MIN_LENGTH: 1,
  CAPABILITY_MAX_LENGTH: 191, // WordPress meta key limit
} as const;

export type SlugType = 'role' | 'capability';

export interface ValidationResult {
  valid: boolean;
  error: string | null;
}

/**
 * Sanitize a string to be a valid WordPress slug
 * Only allows lowercase letters, numbers, hyphens, and underscores
 */
export function sanitizeSlug(
  value: string,
  type: SlugType = 'role',
  maxLength?: number
): string {
  const max =
    maxLength ||
    (type === 'capability'
      ? SLUG_CONSTRAINTS.CAPABILITY_MAX_LENGTH
      : SLUG_CONSTRAINTS.ROLE_MAX_LENGTH);

  return value
    .toLowerCase()
    .replace(/[^a-z0-9_-]/g, '')
    .slice(0, max);
}

/**
 * Validate a slug meets WordPress requirements
 */
export function validateSlug(slug: string, type: SlugType = 'role'): ValidationResult {
  const minLength =
    type === 'capability'
      ? SLUG_CONSTRAINTS.CAPABILITY_MIN_LENGTH
      : SLUG_CONSTRAINTS.ROLE_MIN_LENGTH;
  const maxLength =
    type === 'capability'
      ? SLUG_CONSTRAINTS.CAPABILITY_MAX_LENGTH
      : SLUG_CONSTRAINTS.ROLE_MAX_LENGTH;

  const typeLabel = type === 'role' ? 'Role' : 'Capability';

  if (!slug || slug.length < minLength) {
    return {
      valid: false,
      error: `${typeLabel} slug must be at least ${minLength} character${minLength > 1 ? 's' : ''} long.`,
    };
  }

  if (slug.length > maxLength) {
    return {
      valid: false,
      error: `${typeLabel} slug cannot exceed ${maxLength} characters.`,
    };
  }

  if (!/^[a-z0-9_-]+$/.test(slug)) {
    return {
      valid: false,
      error: `${typeLabel} slug can only contain lowercase letters, numbers, hyphens, and underscores.`,
    };
  }

  return { valid: true, error: null };
}

/**
 * Generate a capability name based on a pattern and slug
 */
export function generateCapabilityName(pattern: string, slug: string): string {
  if (pattern === 'read' || pattern === 'edit' || pattern === 'delete') {
    return `${pattern}_${slug}`;
  }

  const plural = pluralizeSlug(slug);

  const capabilityMap: Record<string, string> = {
    read_private: `read_private_${plural}`,
    edit_others: `edit_others_${plural}`,
    edit_published: `edit_published_${plural}`,
    edit_private: `edit_private_${plural}`,
    publish: `publish_${plural}`,
    delete_others: `delete_others_${plural}`,
    delete_published: `delete_published_${plural}`,
    delete_private: `delete_private_${plural}`,
  };

  return capabilityMap[pattern] || '';
}

/**
 * Pluralize a slug for WordPress capability naming
 */
export function pluralizeSlug(slug: string): string {
  return slug.endsWith('s') ? slug : `${slug}s`;
}

/**
 * Debounce a function
 */
export function debounce<T extends (...args: unknown[]) => unknown>(
  fn: T,
  delay: number
): (...args: Parameters<T>) => void {
  let timeoutId: ReturnType<typeof setTimeout>;

  return (...args: Parameters<T>) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
}

/**
 * Deep merge two objects
 */
export function deepMerge<T extends Record<string, unknown>>(
  target: T,
  source: Partial<T>
): T {
  const result = { ...target };

  for (const key in source) {
    if (Object.prototype.hasOwnProperty.call(source, key)) {
      const sourceValue = source[key];
      const targetValue = result[key];

      if (
        sourceValue &&
        typeof sourceValue === 'object' &&
        !Array.isArray(sourceValue) &&
        targetValue &&
        typeof targetValue === 'object' &&
        !Array.isArray(targetValue)
      ) {
        result[key] = deepMerge(
          targetValue as Record<string, unknown>,
          sourceValue as Record<string, unknown>
        ) as T[Extract<keyof T, string>];
      } else {
        result[key] = sourceValue as T[Extract<keyof T, string>];
      }
    }
  }

  return result;
}
