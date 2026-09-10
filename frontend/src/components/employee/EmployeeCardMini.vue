<script setup lang="ts">
import { computed } from 'vue';
import type { Rank, Company, Department } from '@/types/Members';

interface Employee {
    id: number;
    name: string;
    servicenumber?: string;
    rank_id?: number;
    rankId?: number;
    companies?: Company[] | Record<string, any>;
    departments?: Department[] | Record<string, any>;
    is_terminated?: boolean;
    leavedate?: string;
    vacations?: any;
    [key: string]: any;
}

interface Props {
    employee: Employee;
    rank: Rank;
    isSelected?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    click: [employee: Employee, rank: Rank];
}>();

// Get avatar image
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

// Get department name
const departmentName = computed(() => {
    if (!props.employee.departments) return '';
    const depts = Object.values(props.employee.departments);
    if (depts.length === 0) return '';
    return (depts[0] as Department).name || '';
});

// Get current vacation for employee
const getCurrentVacation = () => {
    if (!props.employee.vacations) return null;
    const today = new Date();
    const vacations = Object.values(props.employee.vacations);
    return vacations.find((vacation: any) => {
        const startDate = new Date(vacation.start);
        const endDate = new Date(vacation.end);
        return today >= startDate && today <= endDate;
    });
};

// Get status info
const statusInfo = computed(() => {
    if (props.employee.is_terminated) {
        return {
            class: 'terminated',
            color: '#ef4444'
        };
    }

    const currentVacation = getCurrentVacation();
    if (currentVacation) {
        const isSick = currentVacation.reason.toLowerCase().includes('krank');
        return {
            class: isSick ? 'sick' : 'vacation',
            color: isSick ? '#f59e0b' : 'var(--k-accent)'
        };
    }

    return {
        class: 'active',
        color: '#22c55e'
    };
});

const handleClick = () => {
    emit('click', props.employee, props.rank);
};
</script>

<template>
    <div
        class="employee-card-mini"
        :class="{ selected: isSelected }"
        @click="handleClick"
    >
        <div class="card-mini-header">
            <v-avatar size="52" class="card-mini-avatar" rounded="lg">
                <v-img :src="getImage(rank.rankImage)" />
            </v-avatar>

            <div class="card-mini-info">
                <div class="card-mini-name">{{ employee.name }}</div>
                <div class="card-mini-id">#{{ employee.servicenumber }}</div>
                <div class="card-mini-rank">
                    {{ rank.name }}<span v-if="departmentName"> • {{ departmentName }}</span>
                </div>
            </div>

            <div
                class="card-mini-status"
                :class="statusInfo.class"
                :style="{ backgroundColor: statusInfo.color }"
            ></div>
        </div>
    </div>
</template>

<style scoped>
.employee-card-mini {
    background: var(--k-surface);
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    transition: all 0.3s;
    cursor: pointer;
}

.employee-card-mini:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    border-color: var(--k-accent-line);
}

.employee-card-mini.selected {
    border-color: var(--k-accent);
    box-shadow: 0 0 0 2px var(--k-accent-line);
    transform: scale(1.02);
}

.card-mini-header {
    padding: 14px;
    display: flex;
    gap: 12px;
    align-items: center;
}

.card-mini-avatar {
    flex-shrink: 0;
    box-shadow: 0 2px 8px var(--k-accent-weak);
    background: linear-gradient(135deg, var(--k-accent-hover), var(--k-accent));
}

.card-mini-info {
    flex: 1;
    min-width: 0;
}

.card-mini-name {
    font-weight: 600;
    font-size: 1rem;
    color: #e2e8f0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.card-mini-id {
    color: var(--k-accent);
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 2px;
}

.card-mini-rank {
    color: var(--k-ink-muted);
    font-size: 0.8rem;
}

.card-mini-status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-left: 8px;
    transition: all 0.2s;
}

.card-mini-status.active {
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.5);
}

.card-mini-status.vacation {
    box-shadow: 0 0 8px var(--k-accent-line);
}

.card-mini-status.sick {
    box-shadow: 0 0 8px rgba(245, 158, 11, 0.5);
}

.card-mini-status.terminated {
    box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
}
</style>
