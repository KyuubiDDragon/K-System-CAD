// src/components/desktop/index.ts
// Export desktop components for easy registration

// Note: To avoid circular dependencies, import components directly 
// instead of through this index file when needed inside other desktop components

// Export these components for use outside the desktop folder
export { default as DesktopIcon } from './DesktopIcon.vue';
export { default as AppWindow } from './AppWindow.vue';
export { default as DesktopTaskbar } from './DesktopTaskbar.vue';
export { default as StartMenu } from './StartMenu.vue';

// Export DesktopView separately to avoid circular dependencies
// DO NOT import DesktopView within any of the above components
export { default as DesktopView } from './DesktopView.vue';