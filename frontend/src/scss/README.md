# CSS Variables and Styling Guidelines

This document outlines how to use the CSS variables and styling system in the project.

## Overview

The project uses a CSS variables system defined in `main.scss` and implemented through several files:

- `main.scss`: Defines all the CSS variables
- `global.scss`: Applies variables to common elements globally
- `common.scss`: Defines reusable CSS classes that leverage the variables

## Using the CSS Variables

### Directly in Component Styles

You can use the CSS variables directly in your component's scoped styles:

```css
.my-element {
  background-color: var(--background);
  color: var(--on-background);
  border: 1px solid var(--card-border);
  border-radius: var(--border-radius-md);
}
```

### Using Common Classes

Import the common.scss file in your component for access to pre-defined classes:

```vue
<style scoped>
@import '@/scss/common.scss';

/* Now you can extend these classes */
.my-container {
  @extend .page-container;
}
</style>
```

### Using Standardized Components

We've created several standardized components that use the CSS variables consistently:

- `PageContainer.vue` - For page layouts
- `DataTableCard.vue` - For data tables with consistent styling
- `StandardDialog.vue` - For dialogs with consistent styling

## Available Variables

### Color Variables

- `--primary`: Main primary color
- `--secondary`: Secondary color
- `--accent`: Accent color
- `--success`: Success state color
- `--info`: Information state color
- `--warning`: Warning state color
- `--error`: Error state color
- `--background`: Main background color
- `--surface`: Surface color for cards and elements
- `--on-primary`: Text color on primary backgrounds
- `--on-secondary`: Text color on secondary backgrounds
- `--on-background`: Text color on main background
- `--on-surface`: Text color on surface elements

### Layout Variables

- `--desktop-padding`: Standard padding for desktop elements
- `--desktop-icon-gap`: Gap between desktop icons
- `--desktop-widget-width`: Width of desktop widgets
- `--folder-width`: Width of folder popups
- `--taskbar-height`: Height of the desktop taskbar
- `--border-radius-sm`: Small border radius
- `--border-radius-md`: Medium border radius

### Effect Variables

- `--glass-blur`: Blur amount for glass effects
- `--glass-bg-opacity`: Background opacity for glass elements
- `--glass-border-opacity`: Border opacity for glass elements
- `--shadow-small`: Small shadow effect
- `--shadow-medium`: Medium shadow effect
- `--shadow-large`: Large shadow effect
- `--shadow-accent`: Accent shadow effect
- `--button-hover-translate`: Transform value for button hover effects

### Desktop Variables

- `--desktop-bg-dark-1`: Dark background color for desktop elements
- `--desktop-bg-dark-2`: Secondary dark background for desktop elements
- `--desktop-accent-blue`: Blue accent color for desktop elements
- `--desktop-accent-purple`: Purple accent color for desktop elements
- `--desktop-text`: Text color for desktop elements
- `--desktop-text-secondary`: Secondary text color for desktop elements
- `--desktop-text-tertiary`: Tertiary text color for desktop elements

## Utility Classes

The `common.scss` file defines several utility classes:

### Container Classes

- `.page-container`: Standard page container with background
- `.section-header`: Standard section header with border
- `.app-card`: Standard card styling with hover effects
- `.empty-state`: Standard empty state styling

### Text Colors

- `.text-primary`: Primary text color
- `.text-secondary`: Secondary text color
- `.text-accent`: Accent text color
- `.text-success`: Success text color
- `.text-info`: Info text color
- `.text-warning`: Warning text color
- `.text-error`: Error text color
- `.text-muted`: Muted text color

### Background Colors

- `.bg-primary`: Primary background color
- `.bg-secondary`: Secondary background color
- `.bg-accent`: Accent background color
- `.bg-success`: Success background color
- `.bg-info`: Info background color
- `.bg-warning`: Warning background color
- `.bg-error`: Error background color

## Migration Guide

When updating existing components:

1. Replace hardcoded colors with CSS variables
2. Use the appropriate utility classes where possible
3. Consider using the standardized components for new features
4. Import `common.scss` for component-specific styling

For new components, use the standardized components and CSS variables from the start. 