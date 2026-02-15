#!/bin/bash

# List of widgets to create
widgets=(
  "MyVacationsWidget"
  "WeatherWidget"
  "ActiveUsersWidget"
  "MessagesFeedWidget"
  "BlackboardFeedWidget"
  "CalendarWidget"
  "TodoListWidget"
  "RecentDocumentsWidget"
  "EmployeeOverviewWidget"
  "ReportAnalyticsWidget"
  "OpenReportsWidget"
  "VacationCalendarWidget"
  "QuickDispatchWidget"
  "VehicleStatusWidget"
  "SystemHealthWidget"
  "UserActivityWidget"
  "RecentLogsWidget"
)

# Create each widget file
for widget in "${widgets[@]}"; do
  if [ ! -f "${widget}.vue" ]; then
    cat > "${widget}.vue" << 'WIDGET_EOF'
<template>
  <WidgetPlaceholder :widget-id="widgetId" :config="config" />
</template>

<script setup lang="ts">
import WidgetPlaceholder from './WidgetPlaceholder.vue'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()
</script>
WIDGET_EOF
    echo "Created ${widget}.vue"
  fi
done

echo "All widgets created!"
