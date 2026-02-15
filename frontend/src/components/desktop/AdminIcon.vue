// Admin menu items
const menuItems = ref([
  // ... existing menu items ...
  {
    title: 'System Logs',
    icon: 'mdi-database-search',
    route: '/admin/logs',
    permissions: ['SYSTEM_ADMIN', 'ALL_PERMISSIONS', 'LOG_ACCESS']
  },
  // ... existing code ...
]); 