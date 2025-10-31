<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import { computed, ref } from 'vue';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import ProjectTableHeading from '@/Components/Common/Project/ProjectTableHeading.vue';
import ProjectTableRow from '@/Components/Common/Project/ProjectTableRow.vue';
import { canCreateProjects } from '@/utils/permissions';
import type { Client, CreateClientBody, CreateProjectBody, Project } from '@/packages/api/src';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import { getOrganizationCurrencyString } from '@/utils/money';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TextInput from '../../../packages/ui/src/Input/TextInput.vue';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import ClientMultiselectDropdown from '@/Components/Common/Client/ClientMultiselectDropdown.vue';
import { UserGroupIcon } from '@heroicons/vue/20/solid';
import MemberMultiselectDropdown from '@/Components/Common/Member/MemberMultiselectDropdown.vue';

const props = defineProps<{
    projects: Project[];
    showBillableRate: boolean;
}>();

const showCreateProjectModal = ref(false);
async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

const searchQuery = ref('');

const sortOrder = ref<'asc' | 'desc'>('asc');

const projectsStore = useProjectsStore();

const selectedClients = ref<string[]>([]);

const selectedMembers = ref<string[]>([]);


async function createClient(
    client: CreateClientBody
): Promise<Client | undefined> {
    return await useClientsStore().createClient(client);
}
const { clients } = storeToRefs(useClientsStore());
const gridTemplate = computed(() => {
    return `grid-template-columns: minmax(300px, 1fr) minmax(150px, auto) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} minmax(120px, auto) 80px;`;
});

const sortedProjects = computed(() => {
    return projectsStore.projects;
});

function handleSort(order: 'asc' | 'desc') {
    sortOrder.value = order;
    projectsStore.fetchProjects(searchQuery.value, order, selectedClients.value, selectedMembers.value);
}
function handleSearch() {
    projectsStore.fetchProjects(searchQuery.value, sortOrder.value, selectedClients.value, selectedMembers.value);
}
</script>

<template>
    <ProjectCreateModal
        v-model:show="showCreateProjectModal"
        :create-project
        :create-client
        :currency="getOrganizationCurrencyString()"
        :clients="clients"
        :enable-estimated-time="true"></ProjectCreateModal>
    <div class="py-2.5 w-full border-b border-default-background-separator">
        <MainContainer
            class="sm:flex space-y-4 sm:space-y-0 justify-between">
            <div
                class="flex flex-wrap items-center space-y-2 sm:space-y-0 space-x-4">
                <div class="text-sm font-medium">Filters</div>
                <TextInput
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search Project name"
                    class="m-1 "
                    style="min-width: 180px;"
                    @keyup.enter="handleSearch"
                />
                <SecondaryButton @click="handleSearch">Search</SecondaryButton>
                <ClientMultiselectDropdown
                    v-model="selectedClients"
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
                    v-model="selectedMembers"
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
                    :show-billable-rate="
                        props.showBillableRate
                    "
                    :sortOrder="sortOrder" @sort="handleSort"
                ></ProjectTableHeading>
                <div
                    v-if="projects.length === 0"
                    class="col-span-5 py-24 text-center">
                    <FolderPlusIcon
                        class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
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
