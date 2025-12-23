/**
 * Component exports for WPE Role Manager
 *
 * Re-exports all components from lib/ for the new modular architecture.
 * This allows imports like: import { Button, Card } from '@components';
 *
 * @package WP_Easy\RoleManager
 */

// Components
export { default as Accordion } from '../lib/Accordion.svelte';
export { default as AdvancedSelect } from '../lib/AdvancedSelect.svelte';
export { default as Alert } from '../lib/Alert.svelte';
export { default as Badge } from '../lib/Badge.svelte';
export { default as Button } from '../lib/Button.svelte';
export { default as Card } from '../lib/Card.svelte';
export { default as Cluster } from '../lib/Cluster.svelte';
export { default as DoubleOptInButton } from '../lib/DoubleOptInButton.svelte';
export { default as Input } from '../lib/Input.svelte';
export { default as Modal } from '../lib/Modal.svelte';
export { default as MultiSelect } from '../lib/MultiSelect.svelte';
export { default as Panel } from '../lib/Panel.svelte';
export { default as Popover } from '../lib/Popover.svelte';
export { default as Radio } from '../lib/Radio.svelte';
export { default as Range } from '../lib/Range.svelte';
export { default as Select } from '../lib/Select.svelte';
export { default as Stack } from '../lib/Stack.svelte';
export { default as Switch } from '../lib/Switch.svelte';
export { default as Table } from '../lib/Table.svelte';
export { default as Tabs } from '../lib/Tabs.svelte';
export { default as Textarea } from '../lib/Textarea.svelte';
export { default as Toast } from '../lib/Toast.svelte';
export { default as Toggle3State } from '../lib/Toggle3State.svelte';
export { default as VerticalTabs } from '../lib/VerticalTabs.svelte';

// Utilities
export * from '../lib/transitions';
export * from '../lib/icons.svelte';

// Types
export type { Size, ColorVariant, ButtonVariant } from '../lib/types';
export type { ToastItem } from '../lib/Toast.svelte';
