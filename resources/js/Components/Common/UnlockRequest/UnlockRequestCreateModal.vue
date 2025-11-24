<script setup lang="ts">
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import { computed, nextTick, ref, watch } from 'vue';
import type { CreateUnlockRequestBody, UnlockRequest, CreateClientBody, CreateProjectBody, Project, Client, Task } from '@/packages/api/src';
import { getOrganizationCurrencyString } from '@/utils/money';
import { canCreateProjects } from '@/utils/permissions';
import { storeToRefs } from 'pinia';
import { useProjectsStore } from '@/utils/useProjects';

const show = defineModel('show', { default: false });
const saving = ref(false);
const reasonError = ref('');

const unlockRequest = ref<CreateUnlockRequestBody>({
    project_id: '',
    reason: '',
});

const props = defineProps<{
    createUnlockRequest: (data: CreateUnlockRequestBody) => Promise<UnlockRequest | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    clients: Client[];
    tasks: Task[];
    enableEstimatedTime: boolean;
}>();

const { projects } = storeToRefs(useProjectsStore());

const taskId = ref<string | null>(null);

function validateReason(reason: string): boolean {
    reasonError.value = '';
    
    if (!reason || reason.trim().length === 0) {
        reasonError.value = 'Reason is required';
        return false;
    }
    
    const trimmedReason = reason.trim();
    const wordCount = trimmedReason.split(/\s+/).length;
    
    if (trimmedReason.length < 10) {
        reasonError.value = 'Reason must be at least 10 characters';
        return false;
    }
    
    if (wordCount < 3) {
        reasonError.value = 'Reason must be at least 3 words';
        return false;
    }
    
    return true;
}

async function submit() {
    if (!unlockRequest.value.project_id) {
        alert('Please select a project');
        return;
    }

    if (!validateReason(unlockRequest.value.reason || '')) {
        return;
    }

    saving.value = true;
    try {
        const result = await props.createUnlockRequest(unlockRequest.value);
        if (result) {
            show.value = false;
            unlockRequest.value = {
                project_id: '',
                reason: '',
            };
            taskId.value = null;
            reasonError.value = '';
        }
    } finally {
        saving.value = false;
    }
}

const reasonInput = ref<HTMLTextAreaElement | null>(null);

watch(show, (value) => {
    if (value) {
        nextTick(() => {
            reasonInput.value?.focus();
        });
        reasonError.value = '';
    }
});

const isSubmitDisabled = computed(() => {
    return saving.value || !unlockRequest.value.project_id || !unlockRequest.value.reason || unlockRequest.value.reason.trim().length < 10;
});
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span>Request Unlock</span>
            </div>
        </template>
        <template #content>
            <div class="space-y-5">
                <!-- Project Selection -->
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">
                        Project <span class="text-red-500">*</span>
                    </label>
                    <TimeTrackerProjectTaskDropdown
                        v-model:project="unlockRequest.project_id"
                        v-model:task="taskId"
                        :clients="clients"
                        :create-project="createProject"
                        :create-client="createClient"
                        :can-create-project="canCreateProjects()"
                        :currency="getOrganizationCurrencyString()"
                        size="xlarge"
                        class="bg-input-background"
                        :projects="projects"
                        :tasks="tasks"
                        :enable-estimated-time="enableEstimatedTime"
                    />
                </div>

                <!-- Reason -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-text-primary mb-2">
                        Reason <span class="text-red-500">*</span>
                        <span class="text-xs text-text-tertiary font-normal">(min. 10 characters or 3 words)</span>
                    </label>
                    <textarea
                        id="reason"
                        ref="reasonInput"
                        v-model="unlockRequest.reason"
                        rows="4"
                        placeholder="Why do you need to unlock time entries for this project? (e.g., 'Forgot to log yesterday meeting')"
                        class="mt-1 block w-full rounded-md border transition-colors px-3 py-2 shadow-sm sm:text-sm resize-none placeholder:text-muted bg-input-background text-text-primary"
                        :class="[
                            reasonError 
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500' 
                                : 'border-input-border focus:border-input-border-active focus:ring-accent-500',
                            'focus:ring-1'
                        ]"
                        @blur="validateReason(unlockRequest.reason || '')"
                    />
                    <p v-if="reasonError" class="mt-1 text-sm text-red-600 dark:text-red-400">
                        {{ reasonError }}
                    </p>
                    <p v-else-if="unlockRequest.reason" class="mt-1 text-xs text-text-tertiary">
                        {{ unlockRequest.reason.trim().length }} characters, {{ unlockRequest.reason.trim().split(/\s+/).length }} words
                    </p>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400 dark:text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>Note:</strong> Once approved, you will have 30 minutes to edit locked time entries for the selected project.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false">Cancel</SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': isSubmitDisabled }"
                :disabled="isSubmitDisabled"
                @click="submit">
                Request Unlock
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

