/**
 * Komponenten-Registry
 * Enthält alle Komponenten, die in Desktop-Fenstern geladen werden können
 */

// Typ für die Komponenten-Map
export type ComponentsMap = Record<string, () => Promise<any>>;

// Map mit allen Komponenten, die in Desktop-Fenstern geladen werden können
export const componentsMap: ComponentsMap = {
  // Eingebaute Desktop-Apps
  'calculator': () => import('@/components/desktop/Calculator.vue'),
  'minesweeper': () => import('@/components/desktop/Minesweeper.vue'),
  'solitaire': () => import('@/components/desktop/Solitaire.vue'),
  'sudoku': () => import('@/components/desktop/Sudoku.vue'),
  'quacklejump': () => import('@/components/desktop/apps/QuackleJump/QuackleJumpApp.vue'),
  'weather': () => import('@/components/desktop/WeatherApp.vue'),
  'whiteboard': () => import('@/components/desktop/WhiteboardApp.vue'),
  'waterduck': () => import('@/components/desktop/WaterDuck.vue'),
  
  // Routen-basierte Anwendungen
  'dashboard': () => import('@/views/DashboardViewNew.vue'),
  'document': () => import('@/views/DocumentView.vue'),
  'documentarea': () => import('@/views/DocumentView.vue'), // Für Dokumentenbereiche
  'employee': () => import('@/views/EmployeeView.vue'),
  'vacation': () => import('@/views/VacationView.vue'),
  'application': () => import('@/views/ApplicationView.vue'),
  'calendar': () => import('@/views/CalendarView.vue'),
  'fireprotection': () => import('@/views/FireprotectionView.vue'),
  'template': () => import('@/views/TemplateView.vue'),
  'person': () => import('@/views/PersonView.vue'),
  'vehicleFile': () => import('@/views/VehicleFileView.vue'),
  'apartmentFile': () => import('@/views/ApartmentView.vue'),
  'todo': () => import('@/views/TodoView.vue'),
  'profile': () => import('@/views/ProfileView.vue'),
  'training': () => import('@/views/DocumentView.vue'), // Sonderfall: DocumentView mit Param
  'administration': () => import('@/views/DocumentView.vue'), // Sonderfall: DocumentView mit Param
  'department': () => import('@/views/DocumentView.vue'), // Sonderfall: DocumentView mit Param
  'map': () => import('@/views/MapView.vue'),
  'company': () => import('@/views/CompanyView.vue'),
  'companytype': () => import('@/views/CompanyTypeView.vue'),
  'dispatch': () => import('@/views/DispatchView.vue'),
  'vehicle': () => import('@/views/VehicleView.vue'),
  'crew': () => import('@/views/CrewView.vue'),
  'report': () => import('@/views/ReportView.vue'),
  'reportcategory': () => import('@/views/ReportCategorieView.vue'),
  'reporttemplate': () => import('@/views/ReportTemplateView.vue'),
  'reportstatus': () => import('@/views/ReportStatusView.vue'),
  'reportadditional': () => import('@/views/ReportAdditionalView.vue'),
  'reportcode': () => import('@/views/ReportCodeView.vue'),
  'message': () => import('@/views/MessageView.vue'),
  'invoice': () => import('@/views/InvoiceView.vue'),
  'invoiceitems': () => import('@/views/InvoiceItemsView.vue'),
  'filemanager': () => import('@/views/FileManagerView.vue'),
  
  // Blackboard-Komponenten
  'blackboard': () => import('@/views/BlackboardView.vue'),
  'blackboardadmin': () => import('@/views/BlackboardView.vue'),
  'blackboardemployee': () => import('@/views/BlackboardView.vue'),
  'blackboardglobal': () => import('@/views/BlackboardView.vue'),
  'blackboard-admin': () => import('@/views/BlackboardView.vue'),
  'blackboard-employee': () => import('@/views/BlackboardView.vue'),
  'blackboard-global': () => import('@/views/BlackboardView.vue'),
  
  // Weitere Komponenten, die zuvor gefehlt haben könnten
  'cheatsheet': () => import('@/views/CheatsheetView.vue'),
  'desktopDebug': () => import('@/views/DesktopDebug.vue'),
  'mapglobal': () => import('@/views/MapView.vue'),
  'mapOnly': () => import('@/views/MapOnlyView.vue'),
  'website': () => import('@/views/WebsiteView.vue'),
  'website_preview': () => import('@/views/WebsitePreviewView.vue'),
  'companywebsite': () => import('@/components/WebsiteManager.vue'),
  
  // Admin-Bereich
  'admin_users': () => import('@/views/admin/UserView.vue'),
  'admin_applicationquestions': () => import('@/views/admin/ApplicationQuestionsView.vue'),
  'admin_roles': () => import('@/views/admin/RoleView.vue'),
  'admin_settings': () => import('@/views/admin/SettingsView.vue'),
  'admin_weather': () => import('@/views/admin/WeatherView.vue'),
  'admin_employee': () => import('@/views/admin/EmployeeView.vue'),
  'admin_training': () => import('@/views/admin/TrainingView.vue'),
  'admin_map': () => import('@/views/admin/MapView.vue'),
  'admin_messages': () => import('@/views/admin/MessagesView.vue'),
  'admin_authorities': () => import('@/views/admin/AuthorityView.vue'),
  'admin_authorityfields': () => import('@/components/Admin/AuthorityFieldManager.vue'),
  'admin_reportfields': () => import('@/views/admin/ReportFieldsView.vue'),
  'admin_logs': () => import('@/views/admin/LogsView.vue'),
  'admin_documentareas': () => import('@/views/admin/DocumentAreasView.vue'),
  'admin_authority_branding': () => import('@/views/admin/AuthorityBrandingView.vue'),
  'admin_cheatsheet': () => import('@/views/admin/CheatsheetView.vue'),

  // Add missing components
  'trainingassign': () => import('@/views/TrainingView.vue'),
  'test': () => import('@/views/TestView.vue'),
  'admin_employees': () => import('@/views/admin/EmployeeView.vue'),
  'admin_trainings': () => import('@/views/admin/TrainingView.vue'),
  'admin-employees': () => import('@/views/admin/EmployeeView.vue'),
  'admin-trainings': () => import('@/views/admin/TrainingView.vue'),
  
  // Fallback für unbekannte Komponenten
  'default': () => import('@/components/DefaultWindowContent.vue')
}; 