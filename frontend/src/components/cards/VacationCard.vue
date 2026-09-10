<template>
    <v-col
      cols="12" sm="6" md="4" lg="3" xl="3"
      v-if="
        (!showRecentReturns && !showRecentVacations && currentVacation) ||
        (showRecentReturns && recentlyReturnedFromVacation.length > 0 && lastVacation && !currentVacation) ||
        (showRecentVacations &&
          upcomingVacation.length > 0 &&
          !currentVacation &&
          !showRecentReturns)
      "
    >
      <v-card
        class="vacation-card elevation-3"
        :class="{
          'terminated-card': member.is_terminated == true && !showIsTerminated,
          'current-vacation-card': currentVacation,
          'upcoming-vacation-card': showRecentVacations && upcomingVacation.length > 0 && !currentVacation,
          'recent-return-card': showRecentReturns && recentlyReturnedFromVacation.length > 0 && !currentVacation
        }"
      >
        <!-- Status Banner -->
        <div class="status-banner">
          <div class="status-icon">
            <v-icon v-if="member.is_terminated" color="error" size="small">mdi-account-cancel</v-icon>
            <v-icon v-else-if="currentVacation && currentVacation.reason.toLowerCase().includes('krank')" color="warning" size="small">mdi-medical-bag</v-icon>
            <v-icon v-else-if="currentVacation" color="info" size="small">mdi-beach</v-icon>
            <v-icon v-else-if="showRecentReturns" color="success" size="small">mdi-account-arrow-left</v-icon>
            <v-icon v-else-if="showRecentVacations" color="primary" size="small">mdi-calendar-clock</v-icon>
          </div>
          
          <div class="status-text">
            <template v-if="member.is_terminated">
              <span class="status-label">{{ $t('vacationCard.leftAt') }}</span>
              <span class="status-value">{{ formatDate(member.leavedate) }}</span>
            </template>
            
            <template v-else-if="currentVacation">
              <span :class="{'status-sick': currentVacation.reason.toLowerCase().includes('krank')}">
                {{ currentVacation.reason }}
              </span>
              <span class="status-date">
                {{ formatDate(currentVacation.start) }} - {{ formatDate(currentVacation.end) }}
              </span>
            </template>
            
            <template v-else-if="showRecentReturns && lastVacation">
              <span class="status-label">{{ $t('vacationCard.returnedSince') }}</span>
              <span class="status-value">{{ formatDate(lastVacation.end) }}</span>
            </template>
            
            <template v-else-if="showRecentVacations && upcomingVacation.length > 0">
              <span class="status-label">{{ $t('vacationCard.absentFrom') }}</span>
              <span class="status-value">{{ formatDate(upcomingVacation[0].start) }}</span>
            </template>
          </div>
        </div>
        
        <!-- Card Content -->
        <div class="card-content">
          <div class="employee-info">
            <div class="employee-name">
              <span class="name-text">
                [{{ member.servicenumber }}] {{ member.name }}
              </span>
            </div>
            
            <div class="employee-rank">{{ member.rank }}</div>
            
            <div class="employee-department">
              <template v-for="(company, index) in member.companies" :key="index">
                <span class="department-item">{{ company.name }}</span>
              </template>
              <template v-for="(department, index) in member.departments" :key="index">
                <span class="department-item">| {{ department.name }}</span>
              </template>
            </div>
            
            <div class="vacation-details" v-if="currentVacation || (showRecentReturns && lastVacation) || (showRecentVacations && upcomingVacation.length > 0)">
              <v-chip
                v-if="currentVacation"
                size="small"
                :color="currentVacation.reason.toLowerCase().includes('krank') ? 'warning' : 'info'"
                variant="tonal"
                class="mt-2"
              >
                <v-icon size="14" start>
                  {{ currentVacation.reason.toLowerCase().includes('krank') ? 'mdi-medical-bag' : 'mdi-beach' }}
                </v-icon>
                {{ formatDate(currentVacation.start) }} - {{ formatDate(currentVacation.end) }}
              </v-chip>
              
              <v-chip
                v-else-if="showRecentReturns && lastVacation"
                size="small"
                color="success"
                variant="tonal"
                class="mt-2"
              >
                <v-icon size="14" start>mdi-account-arrow-left</v-icon>
                Zurück seit {{ formatDate(lastVacation.end) }}
              </v-chip>
              
              <v-chip
                v-else-if="showRecentVacations && upcomingVacation.length > 0"
                size="small"
                color="primary"
                variant="tonal"
                class="mt-2"
              >
                <v-icon size="14" start>mdi-calendar-clock</v-icon>
                Ab {{ formatDate(upcomingVacation[0].start) }}
              </v-chip>
            </div>
          </div>
          
          <div class="logo-container">
            <v-img
              :src="getImage(logoURL)"
              width="64"
              height="64"
              class="rank-logo"
            ></v-img>
          </div>
        </div>
        
        <!-- Additional Info -->
        <div class="vacation-footer" v-if="currentVacation || (showRecentReturns && lastVacation) || (showRecentVacations && upcomingVacation.length > 0)">
          <v-divider class="mb-2"></v-divider>
          
          <div class="vacation-reason">
            <v-icon size="small" class="mr-1" :color="getReasonColor()">{{ getReasonIcon() }}</v-icon>
            <span>
              <template v-if="currentVacation">{{ currentVacation.reason }}</template>
              <template v-else-if="showRecentReturns && lastVacation">{{ lastVacation.reason }}</template>
              <template v-else-if="showRecentVacations && upcomingVacation.length > 0">{{ upcomingVacation[0].reason }}</template>
            </span>
          </div>
        </div>
      </v-card>
    </v-col>
  </template>
  
  <script setup lang="ts">
  import { defineComponent, ref, computed, type PropType } from 'vue';
  import { useToast } from 'vue-toastification';
  import type { License, Employee, Company, Department, Rank, Promotion, Vacation } from '@/types/Members'; // Adjust path
  import api from '@/api';
  
  const toast = useToast();
  
  // --- Props ---
  interface Props {
      member: Employee;
      logoURL?: string; // Rank Image URL
      // Pass necessary lookup data if needed for display (e.g., for rank name if not in member object)
      companies?: Company[];
      departments?: Department[];
      // Filter props from parent
      showRecentReturns?: boolean;
      showRecentVacations?: boolean;
      filterdays?: number; // Days for return/vacation filter
      showIsTerminated?: boolean;
      // Removed unused props: ranks, licenses, trainings, trainingassigns, showPreview
  }

  const props = withDefaults(defineProps<Props>(), {
      logoURL: '',
      companies: () => [],
      departments: () => [],
      showRecentReturns: false,
      showRecentVacations: false,
      filterdays: 0,
      showIsTerminated: false,
  });
  
  // --- Emits ---
  const emit = defineEmits(['edit', 'updateNotes', 'refreshData']);
  
  const expandedIndex = ref(-1);
  const editMode = ref(false);
  
  const licensesArray = computed(() => {
      if (props.member.licenses && typeof props.member.licenses === 'object') {
          return Object.values(props.member.licenses);
      }
      return [];
  });
  
  const getImage = (path: string | null) => {
      try {
          if (path) {
              return new URL(`../../assets/ranks/${path}`, import.meta.url).href;
          } else {
              return new URL(`../../assets/logo.png`, import.meta.url).href;
          }
      } catch {
          return new URL(`@/assets/logo.png`, import.meta.url).href;
      }
  };
  
  const getReasonIcon = () => {
      if (currentVacation.value) {
          if (currentVacation.value.reason.toLowerCase().includes('krank')) {
              return 'mdi-medical-bag';
          } else if (currentVacation.value.reason.toLowerCase().includes('urlaub')) {
              return 'mdi-beach';
          } else {
              return 'mdi-calendar-blank';
          }
      } else if (showRecentReturns.value && lastVacation.value) {
          return 'mdi-account-arrow-left';
      } else if (showRecentVacations.value && upcomingVacation.value.length > 0) {
          return 'mdi-calendar-clock';
      }
      return 'mdi-calendar-blank';
  };
  
  const getReasonColor = () => {
      if (currentVacation.value) {
          if (currentVacation.value.reason.toLowerCase().includes('krank')) {
              return 'warning';
          } else if (currentVacation.value.reason.toLowerCase().includes('urlaub')) {
              return 'info';
          } else {
              return 'grey';
          }
      } else if (showRecentReturns.value && lastVacation.value) {
          return 'success';
      } else if (showRecentVacations.value && upcomingVacation.value.length > 0) {
          return 'primary';
      }
      return 'grey';
  };
  
  const newVacation = ref({ type: '', start: '', end: '', reported: false, other: '' });
  
  const promotionDialog = ref(false);
  const promotionsArray = computed(() => {
      if (props.member.promotions && typeof props.member.promotions === 'object') {
          return Object.values(props.member.promotions);
      }
      return [];
  });
  const showPromotionsDialog = () => {
      promotionDialog.value = true;
  };
  
  const vacationsArray = computed<Vacation[]>(() => {
      if (props.member.vacations && typeof props.member.vacations === 'object') {
          return Object.values(props.member.vacations) as Vacation[];
      }
      return [];
  });
  
  const currentVacation = computed<Vacation | undefined>(() => {
      const today = new Date();
      return vacationsArray.value.find(vacation => {
          const startDate = new Date(vacation.start);
          const endDate = new Date(vacation.end);
          return today >= startDate && today <= endDate;
      });
  });
  
  const lastVacation = computed<Vacation | undefined>(() => {
      return getLastVacation(vacationsArray.value);
  });
  
  function getLastVacation(vacations: Vacation[]): Vacation | undefined {
      const sortedVacations = [...vacations].sort(
          (a, b) => new Date(b.end).getTime() - new Date(a.end).getTime()
      );
      return sortedVacations[0];
  }
  
  const recentlyReturnedFromVacation = computed(() => {
      const today = new Date();
      const sevenDaysAgo = new Date(today);
      sevenDaysAgo.setDate(today.getDate() - props.filterdays);
  
      return vacationsArray.value.filter(vacation => {
          const endDate = new Date(vacation.end);
          return endDate >= sevenDaysAgo && endDate <= today;
      });
  });
  
  function formatDate(date: any) {
      if (!date) return '';
      const [datePart] = date.split(' ');
      const [year, month, day] = datePart.split('-');
      return `${day}.${month}.${year}`;
  }
  
  const upcomingVacation = computed(() => {
      const today = new Date();
      const sevenDaysLater = new Date(today);
      sevenDaysLater.setDate(today.getDate() + props.filterdays);
  
      return vacationsArray.value.filter(vacation => {
          const startDate = new Date(vacation.start);
          return startDate >= today && startDate <= sevenDaysLater;
      });
  });
  </script>
  
  <style scoped>

  /* Card Styling */
  .vacation-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    transition: transform var(--transition-timing), box-shadow var(--transition-timing);
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  
  .vacation-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-elevation);
  }
  
  .terminated-card {
    background: var(--terminated-bg) !important;
    border: 1px solid rgba(244, 67, 54, 0.3);
    position: relative;
  }
  
  .terminated-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #b91c1c);
    z-index: 5;
  }
  
  .current-vacation-card {
    background: var(--vacation-bg) !important;
    border: 1px solid rgba(76, 175, 80, 0.3);
  }
  
  .upcoming-vacation-card {
    background: var(--upcoming-bg) !important;
    border: 1px solid var(--k-accent-line);
  }
  
  .recent-return-card {
    background: var(--recent-return-bg) !important;
    border: 1px solid rgba(22, 163, 74, 0.3);
  }
  
  /* Status Banner */
  .status-banner {
    display: flex;
    align-items: center;
    padding: 6px 12px;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid var(--k-line);
    position: relative;
    z-index: 3;
  }
  
  .terminated-card .status-banner {
    background: rgba(239, 68, 68, 0.2);
  }
  
  .current-vacation-card .status-banner {
    background: var(--k-accent-weak);
  }
  
  .upcoming-vacation-card .status-banner {
    background: var(--k-accent-weak);
  }
  
  .recent-return-card .status-banner {
    background: rgba(22, 163, 74, 0.15);
  }
  
  .status-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
  }
  
  .status-text {
    flex: 1;
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    flex-direction: column;
  }
  
  .status-label {
    font-size: 0.75rem;
    opacity: 0.8;
  }
  
  .status-value {
    font-weight: 600;
  }
  
  .status-date {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 2px;
  }
  
  .status-sick {
    color: var(--k-warning);
  }
  
  /* Card Content */
  .card-content {
    display: flex;
    padding: 16px;
    align-items: flex-start;
    position: relative;
    z-index: 2;
    flex: 1;
  }
  
  .employee-info {
    flex: 1;
  }
  
  .employee-name {
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
  }
  
  .name-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .employee-rank {
    font-size: 0.9rem;
    color: var(--k-ink-muted);
    margin-bottom: 4px;
  }
  
  .employee-department {
    font-size: 0.85rem;
    color: var(--k-ink-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .department-item {
    margin-right: 4px;
  }
  
  .logo-container {
    margin-left: 12px;
  }
  
  .rank-logo {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }
  
  /* Vacation Details */
  .vacation-details {
    margin-top: 6px;
  }
  
  /* Vacation Footer */
  .vacation-footer {
    padding: 8px 16px 16px;
  }
  
  .vacation-reason {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: var(--k-ink-muted);
  }
  
  /* Responsive Adjustments */
  @media (max-width: 600px) {
    .card-content {
      flex-direction: column;
    }
    
    .logo-container {
      margin: 12px 0 0 0;
      align-self: center;
    }
    
    .employee-department {
      margin-bottom: 8px;
    }
    
    .status-banner {
      flex-direction: column;
      text-align: center;
      padding: 10px;
    }
    
    .status-icon {
      margin-right: 0;
      margin-bottom: 4px;
    }
  }
  </style>