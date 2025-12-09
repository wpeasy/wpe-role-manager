<script lang="ts">
  import type { ColorVariant } from './types';

  type ToastPosition =
    | 'top-left'
    | 'top-center'
    | 'top-right'
    | 'bottom-left'
    | 'bottom-center'
    | 'bottom-right'
    | 'center';

  export type ToastItem = {
    id: string;
    title?: string;
    message: string;
    variant?: ColorVariant;
    duration?: number;
  };

  type Props = {
    toasts?: ToastItem[];
    position?: ToastPosition;
    class?: string;
    style?: string;
    onClose?: (id: string) => void;
  };

  let {
    toasts = $bindable([]),
    position = 'top-right',
    class: className = '',
    style,
    onClose
  }: Props = $props();

  let closingToasts = $state<Set<string>>(new Set());

  function closeToast(id: string) {
    closingToasts.add(id);
    setTimeout(() => {
      toasts = toasts.filter(t => t.id !== id);
      closingToasts.delete(id);
      onClose?.(id);
    }, 300);
  }

  // Track active timeouts to prevent duplicates
  let activeTimeouts = new Map<string, ReturnType<typeof setTimeout>>();

  $effect(() => {
    // Get current toast IDs
    const currentIds = new Set(toasts.map(t => t.id));

    // Clear timeouts for removed toasts
    for (const [id, timeout] of activeTimeouts) {
      if (!currentIds.has(id)) {
        clearTimeout(timeout);
        activeTimeouts.delete(id);
      }
    }

    // Set timeouts for new toasts with duration
    toasts.forEach(toast => {
      if (toast.duration && toast.duration > 0 && !activeTimeouts.has(toast.id) && !closingToasts.has(toast.id)) {
        const timeout = setTimeout(() => {
          activeTimeouts.delete(toast.id);
          closeToast(toast.id);
        }, toast.duration);
        activeTimeouts.set(toast.id, timeout);
      }
    });
  });

  let positionClass = $derived(`wpea-toast-container--${position}`);
</script>

<div class="wpea-toast-container {positionClass} {className}" {style}>
  {#each toasts as toast (toast.id)}
    {@const isClosing = closingToasts.has(toast.id)}
    {@const variantClass = toast.variant ? `wpea-toast--${toast.variant}` : ''}
    <div class="wpea-toast {variantClass}" class:wpea-toast--closing={isClosing}>
      <div class="wpea-toast__content">
        {#if toast.title}
          <div class="wpea-toast__title">{toast.title}</div>
        {/if}
        <div class="wpea-toast__message">{toast.message}</div>
      </div>
      <button
        class="wpea-toast__close"
        onclick={() => closeToast(toast.id)}
      >
        ×
      </button>
    </div>
  {/each}
</div>
