/**
 * Event Bus for cross-module communication
 *
 * @package WP_Easy\RoleManager
 */

/**
 * Event types and their payloads
 */
export interface EventMap {
  // Settings events
  'settings:changed': { key: string; value: unknown };
  'settings:saved': { success: boolean };

  // Display settings events
  'displaySettings:changed': Record<string, unknown>;

  // Theme and density events
  'theme:changed': { mode: 'light' | 'dark' | 'auto' };
  'density:changed': { compact: boolean };

  // Status indicator events
  'status:saving': void;
  'status:saved': void;
  'status:error': { message?: string };

  // Role events
  'role:created': { slug: string; name: string; copyFrom?: string };
  'role:updated': { slug: string; changes: Record<string, unknown> };
  'role:deleted': { slug: string; name?: string };
  'role:disabled': { slug: string };
  'role:enabled': { slug: string };
  'roles:updated': void;

  // Capability events
  'capability:added': { role: string; capability: string; granted: boolean };
  'capability:removed': { role: string; capability: string };
  'capability:toggled': { role: string; capability: string; action: 'grant' | 'deny' | 'unset' };
  'capabilities:updated': void;

  // User events
  'user:rolesUpdated': { userId: number; roles: string[]; previousRoles?: string[] };
  'users:updated': void;

  // Import/Export events
  'import:started': { type: 'full' | 'roles' };
  'import:completed': { type: 'full' | 'roles'; count: number; errors: number };
  'export:completed': { type: 'full' | 'roles'; count: number };

  // Revision events
  'revision:restored': { revisionId: number };
  'revision:deleted': { revisionId: number };

  // Notification events
  'notification:show': {
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    duration?: number;
  };
}

type EventHandler<K extends keyof EventMap> = EventMap[K] extends void
  ? () => void
  : (detail: EventMap[K]) => void;

export interface Subscription {
  unsubscribe: () => void;
}

const PREFIX = 'wpe-rm';

/**
 * Emit an event
 */
export function emit<K extends keyof EventMap>(
  event: K,
  ...args: EventMap[K] extends void ? [] : [EventMap[K]]
): void {
  const detail = args[0];
  const customEvent = new CustomEvent(`${PREFIX}:${event}`, {
    detail,
    bubbles: true,
  });
  document.dispatchEvent(customEvent);
}

/**
 * Subscribe to an event
 */
export function on<K extends keyof EventMap>(
  event: K,
  handler: EventHandler<K>
): Subscription {
  const eventName = `${PREFIX}:${event}`;

  const wrappedHandler = ((e: CustomEvent) => {
    (handler as (detail: unknown) => void)(e.detail);
  }) as EventListener;

  document.addEventListener(eventName, wrappedHandler);

  return {
    unsubscribe: () => {
      document.removeEventListener(eventName, wrappedHandler);
    },
  };
}

/**
 * Subscribe to an event once (auto-unsubscribes after first call)
 */
export function once<K extends keyof EventMap>(
  event: K,
  handler: EventHandler<K>
): Subscription {
  const subscription = on(event, ((...args: unknown[]) => {
    subscription.unsubscribe();
    (handler as (...args: unknown[]) => void)(...args);
  }) as EventHandler<K>);

  return subscription;
}
