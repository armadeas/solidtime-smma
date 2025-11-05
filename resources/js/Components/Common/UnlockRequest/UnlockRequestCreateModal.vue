<script setup lang="ts">
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { ref, watch } from 'vue';
import type { CreateUnlockRequestBody, UnlockRequest } from '@/packages/api/src';
import { storeToRefs } from 'pinia';
import { useProjectsStore } from '@/utils/useProjects';

const show = defineModel('show', { default: false });
const saving = ref(false);

const unlockRequest = ref<CreateUnlockRequestBody>({
    project_id: '',
    reason: '',
});

const props = defineProps<{
    createUnlockRequest: (data: CreateUnlockRequestBody) => Promise<UnlockRequest | undefined>;
}>();

const { projects } = storeToRefs(useProjectsStore());

async function submit() {
    if (!unlockRequest.value.project_id) {
        alert('Please select a project');
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
        }
    } finally {
        saving.value = false;
    }
}

const reasonInput = ref<HTMLTextAreaElement | null>(null);

watch(show, (newValue) => {
    if (newValue) {
        // Load projects if not loaded
        if (projects.value.length === 0) {
            useProjectsStore().fetchProjects();
        }
    }
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
                    <label for="project" class="block text-sm font-medium text-text-primary mb-2">
                        Project <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="project"
                        v-model="unlockRequest.project_id"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-text-primary shadow-sm focus:border-accent-500 focus:ring-1 focus:ring-accent-500 sm:text-sm"
                        required>
                        <option value="">Select a project</option>
                        <option
                            v-for="project in projects"
                            :key="project.id"
                            :value="project.id">
                            {{ project.name }}
                        </option>
                    </select>
                </div>

                <!-- Reason -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-text-primary mb-2">
                        Reason (optional)
                    </label>
                    <textarea
                        id="reason"
                        ref="reasonInput"
                        v-model="unlockRequest.reason"
                        rows="4"
                        placeholder="Why do you need to unlock time entries for this project?"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-accent-500 focus:ring-1 focus:ring-accent-500 sm:text-sm resize-none"
                    />
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-800">
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
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Request Unlock
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

