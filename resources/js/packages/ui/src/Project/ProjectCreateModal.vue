<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, ref } from 'vue';
import type { CreateClientBody, CreateProjectBody, Project } from '@/packages/api/src';
import { getRandomColor } from '@/packages/ui/src/utils/color';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import ClientDropdown from '@/packages/ui/src/Client/ClientDropdown.vue';
import Badge from '@/packages/ui/src/Badge.vue';
import ProjectColorSelector from '@/packages/ui/src/Project/ProjectColorSelector.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import EstimatedTimeSection from '@/packages/ui/src/EstimatedTimeSection.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import ProjectEditBillableSection from '@/packages/ui/src/Project/ProjectEditBillableSection.vue';
import type { Client } from '@/packages/api/src';

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    clients: Client[];
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    currency: string;
    enableEstimatedTime: boolean;
}>();

const activeClients = computed(() => {
    return props.clients.filter((client) => !client.is_archived);
});

const project = ref<CreateProjectBody>({
    name: '',
    color: getRandomColor(),
    client_id: null,
    billable_rate: null,
    is_billable: false,
    estimated_time: null,
});

async function submit() {
    await props.createProject(project.value);
    show.value = false;
    project.value = {
        name: '',
        color: getRandomColor(),
        client_id: null,
        billable_rate: null,
        is_billable: false,
        estimated_time: null,
    };
}

const projectNameInput = ref<HTMLInputElement | null>(null);

useFocus(projectNameInput, { initialValue: true });

const currentClientName = computed(() => {
    if (project.value.client_id) {
        return props.clients.find((client) => client.id === project.value.client_id)?.name;
    }
    return 'No Client';
});
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create Project </span>
            </div>
        </template>

        <template #content>
            <div class="sm:flex items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="flex-1 flex items-center">
                    <div class="text-center pr-5">
                        <InputLabel for="color" value="Color" />
                        <ProjectColorSelector
                            v-model="project.color"
                            class="mt-2.5"></ProjectColorSelector>
                    </div>
                    <div class="w-full">
                        <InputLabel for="projectName" value="Project name" />
                        <TextInput
                            id="projectName"
                            ref="projectNameInput"
                            v-model="project.name"
                            name="projectName"
                            type="text"
                            placeholder="The next big thing"
                            class="mt-2 block w-full"
                            required
                            autocomplete="projectName"
                            @keydown.enter="submit()" />
                    </div>
                </div>
                <div>
                    <InputLabel for="client" value="Client" />
                    <ClientDropdown
                        v-model="project.client_id"
                        :create-client="createClient"
                        :clients="activeClients"
                        class="mt-2">
                        <template #trigger>
                            <Badge
                                tag="button"
                                class="bg-input-background cursor-pointer hover:bg-tertiary"
                                size="xlarge">
                                <div class="flex items-center space-x-2">
                                    <UserCircleIcon class="w-5 text-icon-default"></UserCircleIcon>
                                    <span>
                                        {{ currentClientName }}
                                    </span>
                                </div>
                            </Badge>
                        </template>
                    </ClientDropdown>
                </div>
            </div>
            <div>
                <div>
                    <ProjectEditBillableSection
                        v-model:is-billable="project.is_billable"
                        v-model:billable-rate="project.billable_rate"
                        :currency="currency"></ProjectEditBillableSection>
                </div>
                <div>
                    <EstimatedTimeSection
                        v-if="enableEstimatedTime"
                        v-model="project.estimated_time"
                        @submit="submit()"></EstimatedTimeSection>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Create Project
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
