<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import { computed, ref } from 'vue';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import ProjectTableHeading from '@/Components/Common/Project/ProjectTableHeading.vue';
import ProjectTableRow from '@/Components/Common/Project/ProjectTableRow.vue';

export type SortColumn =
    | 'name'
    | 'client_name'
    | 'spent_time'
    | 'progress'
    | 'billable_rate'
    | 'status'
    | 'visibility';
export type SortDirection = 'asc' | 'desc';

import { canCreateProjects } from '@/utils/permissions';
import type { CreateProjectBody, Project, Client, CreateClientBody } from '@/packages/api/src';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useMembersQuery } from '@/utils/useMembersQuery';
import { getOrganizationCurrencyString } from '@/utils/money';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import ClientMultiselectDropdown from '@/Components/Common/Client/ClientMultiselectDropdown.vue';
import { UserGroupIcon } from '@heroicons/vue/20/solid';
import MemberMultiselectDropdown from '@/Components/Common/Member/MemberMultiselectDropdown.vue';
import {
    useVueTable,
    getCoreRowModel,
    getSortedRowModel,
    type SortingState,
} from '@tanstack/vue-table';

const props = defineProps<{
    projects: Project[];
    showBillableRate: boolean;
    sortColumn: SortColumn;
    sortDirection: SortDirection;
    searchQuery: string;
    selectedClients: string[];
    selectedMembers: string[];
}>();

const emit = defineEmits<{
    sort: [column: SortColumn, direction: SortDirection];
    'update:searchQuery': [value: string];
    'update:selectedClients': [value: string[]];
    'update:selectedMembers': [value: string[]];
    search: [];
}>();

const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);
const { clients } = useClientsQuery();
const { members } = useMembersQuery();

const searchVal = computed({
    get: () => props.searchQuery,
    set: (v) => emit('update:searchQuery', v),
});

const clientsVal = computed({
    get: () => props.selectedClients,
    set: (v) => {
        emit('update:selectedClients', v);
        emit('search');
    },
});

const membersVal = computed({
    get: () => props.selectedMembers,
    set: (v) => {
        emit('update:selectedMembers', v);
        emit('search');
    },
});

// Create a map of client names for sorting
const clientNameMap = computed(() => {
    const map = new Map<string, string>();
    clients.value.forEach((client) => {
        map.set(client.id, client.name);
    });
    return map;
});

// Convert sort props to TanStack Table format
const sorting = computed<SortingState>(() => [
    {
        id: props.sortColumn,
        desc: props.sortDirection === 'desc',
    },
]);

// Define column accessors for sorting.
const columns = computed(() => [
    {
        id: 'name',
        accessorFn: (row: Project) => row.name.toLowerCase(),
    },
    {
        id: 'client_name',
        sortUndefined: 'last' as const,
        accessorFn: (row: Project) => {
            if (!row.client_id) return undefined;
            return (clientNameMap.value.get(row.client_id) ?? '').toLowerCase();
        },
    },
    {
        id: 'spent_time',
        sortDescFirst: true,
        accessorFn: (row: Project) => row.spent_time ?? 0,
    },
    {
        id: 'progress',
        sortDescFirst: true,
        sortUndefined: 'last' as const,
        accessorFn: (row: Project) => {
            if (!row.estimated_time) return undefined;
            return (row.spent_time / row.estimated_time) * 100;
        },
    },
    {
        id: 'billable_rate',
        sortDescFirst: true,
        accessorFn: (row: Project) => row.billable_rate ?? 0,
    },
    {
        id: 'status',
        accessorFn: (row: Project) => (row.is_archived ? 1 : 0),
    },
    {
        id: 'visibility',
        accessorFn: (row: Project) => (row.is_public ? 1 : 0),
    },
]);

// Columns with sortDescFirst get desc as default direction on first click.
const descFirstColumns = new Set<SortColumn>(
    columns.value.filter((c) => c.sortDescFirst).map((c) => c.id as SortColumn)
);

function handleSort(column: SortColumn) {
    if (props.sortColumn === column) {
        emit('sort', column, props.sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
        emit('sort', column, descFirstColumns.has(column) ? 'desc' : 'asc');
    }
}

const table = useVueTable({
    get data() {
        return props.projects;
    },
    get columns() {
        return columns.value;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    manualSorting: false,
});

const sortedProjects = computed(() => {
    return table.getRowModel().rows.map((row) => row.original);
});

const showCreateProjectModal = ref(false);

async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(client: CreateClientBody): Promise<Client | undefined> {
    return await useClientsStore().createClient(client);
}

const gridTemplate = computed(() => {
    return `grid-template-columns: minmax(300px, 1fr) minmax(150px, auto) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} minmax(120px, auto) minmax(120px, auto) 80px;`;
});

function handleSearch() {
    emit('search');
}
</script>

<template>
    <ProjectCreateModal
        v-model:show="showCreateProjectModal"
        :create-project
        :create-client
        :currency="getOrganizationCurrencyString()"
        :organization-billable-rate="organization?.billable_rate ?? null"
        :clients="clients"
        :enable-estimated-time="isAllowedToPerformPremiumAction()"></ProjectCreateModal>
    <div class="py-2.5 w-full border-b border-default-background-separator">
        <MainContainer
            class="sm:flex space-y-4 sm:space-y-0 justify-between">
            <div
                class="flex flex-wrap items-center space-y-2 sm:space-y-0 space-x-4">
                <div class="text-sm font-medium">Filters</div>
                <TextInput
                    v-model="searchVal"
                    type="text"
                    placeholder="Search Project name"
                    class="m-1"
                    style="min-width: 180px;"
                    @keyup.enter="handleSearch"
                />
                <SecondaryButton @click="handleSearch">Search</SecondaryButton>
                <ClientMultiselectDropdown
                    v-model="clientsVal"
                    @submit="handleSearch">
                    <template #trigger>
                        <ReportingFilterBadge
                            :count="selectedClients.length"
                            :active="selectedClients.length > 0"
                            title="Clients"
                            :icon="FolderIcon"></ReportingFilterBadge>
                    </template>
                </ClientMultiselectDropdown>
                <MemberMultiselectDropdown
                    v-model="membersVal"
                    @submit="handleSearch">
                    <template #trigger>
                        <ReportingFilterBadge
                            :count="selectedMembers.length"
                            :active="selectedMembers.length > 0"
                            title="Members"
                            :icon="UserGroupIcon"></ReportingFilterBadge>
                    </template>
                </MemberMultiselectDropdown>
            </div>
        </MainContainer>
    </div>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div data-testid="project_table" class="grid min-w-full" :style="gridTemplate">
                <ProjectTableHeading
                    :show-billable-rate="props.showBillableRate"
                    :sort-column="props.sortColumn"
                    :sort-direction="props.sortDirection"
                    :desc-first-columns="descFirstColumns"
                    @sort="handleSort"></ProjectTableHeading>
                <div v-if="sortedProjects.length === 0" class="col-span-full py-24 text-center">
                    <FolderPlusIcon class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-text-primary font-semibold">
                        {{
                            canCreateProjects()
                                ? 'No projects found'
                                : 'You are not a member of any projects'
                        }}
                    </h3>
                    <p class="pb-5 max-w-md mx-auto text-sm pt-1">
                        {{
                            canCreateProjects()
                                ? 'Create your first project now!'
                                : 'Ask your manager to add you to a project as a team member.'
                        }}
                    </p>
                    <SecondaryButton
                        v-if="canCreateProjects()"
                        :icon="PlusIcon"
                        @click="showCreateProjectModal = true"
                        >Create your First Project
                    </SecondaryButton>
                </div>
                <template v-for="project in sortedProjects" :key="project.id">
                    <ProjectTableRow
                        :show-billable-rate="props.showBillableRate"
                        :project="project"></ProjectTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
