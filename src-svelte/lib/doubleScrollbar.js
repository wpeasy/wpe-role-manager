/**
 * Double Scrollbar Svelte Action
 *
 * Creates a synchronized top scrollbar for horizontally scrollable elements.
 *
 * Usage:
 * <div use:doubleScrollbar class="wpea-table-wrapper">
 *   <table>...</table>
 * </div>
 */

export function doubleScrollbar(node) {
  // Only add top scrollbar if element has horizontal overflow
  function checkOverflow() {
    return node.scrollWidth > node.clientWidth;
  }

  // Create top scrollbar container
  const topScrollbar = document.createElement('div');
  topScrollbar.className = 'wpea-top-scrollbar';
  topScrollbar.style.overflowX = 'auto';
  topScrollbar.style.overflowY = 'hidden';
  topScrollbar.style.height = '12px';
  topScrollbar.style.marginBottom = '0';

  // Create inner div to create scrollable width
  const topScrollbarInner = document.createElement('div');
  topScrollbar.appendChild(topScrollbarInner);

  // Function to update top scrollbar width
  function updateTopScrollbar() {
    if (checkOverflow()) {
      // Show top scrollbar
      if (!topScrollbar.parentNode) {
        node.parentNode.insertBefore(topScrollbar, node);
      }
      // Set inner width to match the scrollable content
      topScrollbarInner.style.width = `${node.scrollWidth}px`;
      topScrollbarInner.style.height = '1px';
    } else {
      // Hide top scrollbar if no overflow
      if (topScrollbar.parentNode) {
        topScrollbar.parentNode.removeChild(topScrollbar);
      }
    }
  }

  // Sync scroll positions
  function syncTopToBottom() {
    node.scrollLeft = topScrollbar.scrollLeft;
  }

  function syncBottomToTop() {
    topScrollbar.scrollLeft = node.scrollLeft;
  }

  // Add event listeners
  topScrollbar.addEventListener('scroll', syncTopToBottom);
  node.addEventListener('scroll', syncBottomToTop);

  // Initialize
  updateTopScrollbar();

  // Create ResizeObserver to update on size changes
  const resizeObserver = new ResizeObserver(() => {
    updateTopScrollbar();
  });
  resizeObserver.observe(node);

  // Cleanup
  return {
    destroy() {
      topScrollbar.removeEventListener('scroll', syncTopToBottom);
      node.removeEventListener('scroll', syncBottomToTop);
      resizeObserver.disconnect();
      if (topScrollbar.parentNode) {
        topScrollbar.parentNode.removeChild(topScrollbar);
      }
    }
  };
}
