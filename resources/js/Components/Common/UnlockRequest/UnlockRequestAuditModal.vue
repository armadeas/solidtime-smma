<script setup lang="ts">
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { computed, watch } from 'vue';
import { formatDateTime } from '@/utils/format';
import { api } from '@/packages/api/src';
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const show = defineModel('show', { default: false });

const props = defineProps<{
    unlockRequestId: string;
}>();

const audits = ref<any[]>([]);
const loading = ref(false);
const unlockRequest = ref<any>(null);

const page = usePage<{
    auth: {
        user: {
            current_team_id: string;
        };
    };
}>();

watch(show, async (newValue) => {
    if (newValue && props.unlockRequestId) {
        loading.value = true;
        try {
            const response = await api.getUnlockRequest({
                params: { 
                    organization: page.props.auth.user.current_team_id,
                    unlockRequest: props.unlockRequestId
                }
            });
            unlockRequest.value = response.data;
            audits.value = Array.isArray(response.data.unlock_audits) 
                ? response.data.unlock_audits 
                : [];
        } catch (error) {
            console.error('Failed to load audit logs:', error);
        } finally {
            loading.value = false;
        }
    }
});

const hasAudits = computed(() => audits.value && audits.value.length > 0);

function getEventIcon(event: string) {
    switch (event) {
        case 'created': return '➕';
        case 'updated': return '✏️';
        case 'deleted': return '🗑️';
        default: return '📝';
    }
}

function getEventColor(event: string) {
    switch (event) {
        case 'created': return 'text-green-600 bg-green-50';
        case 'updated': return 'text-blue-600 bg-blue-50';
        case 'deleted': return 'text-red-600 bg-red-50';
        default: return 'text-gray-600 bg-gray-50';
    }
}

function formatChanges(changes: any): string {
    if (!changes) return '';
    
    const formatted = Object.entries(changes)
        .map(([key, value]: [string, any]) => {
            const oldVal = value.old !== null && value.old !== undefined ? value.old : 'null';
            const newVal = value.new !== null && value.new !== undefined ? value.new : 'null';
            return `${key}: ${oldVal} → ${newVal}`;
        })
        .join(', ');
    
    return formatted;
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false" max-width="4xl">
        <template #title>
            <div class="flex items-center space-x-2">
                <span>📊 Audit Logs</span>
                <span v-if="unlockRequest" class="text-sm font-normal text-text-tertiary">
                    - {{ unlockRequest.project?.name }}
                </span>
            </div>
        </template>

        <template #content>
            <div v-if="loading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-accent-500"></div>
                <p class="mt-2 text-text-tertiary">Loading audit logs...</p>
            </div>

            <div v-else-if="!hasAudits" class="text-center py-8">
                <p class="text-text-tertiary">No audit logs found.</p>
                <p class="text-sm text-text-quaternary mt-1">
                    Audit logs will appear here when time entries are modified using this unlock request.
                </p>
            </div>

            <div v-else class="space-y-4">
                <!-- Unlock Request Info -->
                <div class="bg-card-background border border-card-background-separator rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-text-tertiary">Project:</span>
                            <span class="ml-2 text-text-primary font-medium">{{ unlockRequest.project?.name }}</span>
                        </div>
                        <div>
                            <span class="text-text-tertiary">Requester:</span>
                            <span class="ml-2 text-text-primary">{{ unlockRequest.requester?.user?.name }}</span>
                        </div>
                        <div>
                            <span class="text-text-tertiary">Total Changes:</span>
                            <span class="ml-2 text-text-primary font-medium">{{ audits.length }}</span>
                        </div>
                        <div>
                            <span class="text-text-tertiary">Status:</span>
                            <span class="ml-2 text-text-primary capitalize">{{ unlockRequest.status }}</span>
                        </div>
                    </div>
                </div>

                <!-- Audit Timeline -->
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div
                        v-for="audit in audits"
                        :key="audit.id"
                        class="bg-card-background border border-card-background-separator rounded-lg p-4 hover:bg-card-background-active transition-colors">
                        <div class="flex items-start space-x-3">
                            <!-- Event Badge -->
                            <div class="flex-shrink-0">
                                <span
                                    :class="getEventColor(audit.event)"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold">
                                    {{ getEventIcon(audit.event) }}
                                </span>
                            </div>

                            <!-- Audit Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-text-primary">
                                        {{ audit.event_label }} {{ audit.auditable_name }}
                                    </h4>
                                    <span class="text-xs text-text-tertiary whitespace-nowrap ml-2">
                                        {{ formatDateTime(audit.created_at) }}
                                    </span>
                                </div>

                                <!-- User Info -->
                                <div v-if="audit.user" class="text-xs text-text-tertiary mt-1">
                                    by {{ audit.user.name }}
                                </div>

                                <!-- Changes -->
                                <div v-if="audit.changes && Object.keys(audit.changes).length > 0" class="mt-2">
                                    <div class="text-xs text-text-tertiary mb-1">Changes:</div>
                                    <div class="bg-input-background rounded border border-input-border p-2 text-xs font-mono">
                                        <div
                                            v-for="(change, field) in audit.changes"
                                            :key="field"
                                            class="py-1">
                                            <span class="text-text-tertiary">{{ field }}:</span>
                                            <div class="ml-4 mt-1 space-y-1">
                                                <div class="text-red-600 dark:text-red-400">
                                                    - {{ change.old !== null ? change.old : 'null' }}
                                                </div>
                                                <div class="text-green-600 dark:text-green-400">
                                                    + {{ change.new !== null ? change.new : 'null' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- IP & User Agent -->
                                <div class="mt-2 text-xs text-text-quaternary flex items-center space-x-4">
                                    <span v-if="audit.ip_address">IP: {{ audit.ip_address }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="show = false">Close</SecondaryButton>
        </template>
    </DialogModal>
</template>
