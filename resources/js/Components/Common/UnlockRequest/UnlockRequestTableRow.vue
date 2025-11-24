<script setup lang="ts">
import TableRow from '@/Components/TableRow.vue';
import { useUnlockRequestsStore } from '@/utils/useUnlockRequests';
import { CheckCircleIcon, XCircleIcon, TrashIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import { formatDateTime } from '@/utils/format';
import { computed, ref } from 'vue';
import UnlockRequestAuditModal from './UnlockRequestAuditModal.vue';

interface UnlockRequest {
    id: string;
    organization_id: string;
    project_id: string;
    requester_member_id: string;
    approver_member_id?: string | null;
    reason?: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'expired';
    approved_at?: string | null;
    rejected_at?: string | null;
    expires_at?: string | null;
    created_at: string;
    updated_at: string;
    project?: {
        id: string;
        name: string;
        client?: {
            id: string;
            name: string;
        };
    };
    requester?: {
        id: string;
        user?: {
            id: string;
            name: string;
        };
    };
    approver?: {
        id: string;
        user?: {
            id: string;
            name: string;
        };
    };
}

const props = defineProps<{
    unlockRequest: UnlockRequest;
    isManager: boolean;
}>();

const isPending = computed(() => props.unlockRequest.status === 'pending');
const isApproved = computed(() => props.unlockRequest.status === 'approved');
const isExpired = computed(() => props.unlockRequest.status === 'expired');
const isRejected = computed(() => props.unlockRequest.status === 'rejected');

const statusColor = computed(() => {
    if (isPending.value) return 'text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20';
    if (isApproved.value) return 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20';
    if (isRejected.value) return 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20';
    if (isExpired.value) return 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
    return 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
});

const statusText = computed(() => {
    return props.unlockRequest.status.charAt(0).toUpperCase() + props.unlockRequest.status.slice(1);
});

async function approveRequest() {
    if (confirm('Are you sure you want to approve this unlock request?')) {
        await useUnlockRequestsStore().approveUnlockRequest(props.unlockRequest.id);
    }
}

async function rejectRequest() {
    if (confirm('Are you sure you want to reject this unlock request?')) {
        await useUnlockRequestsStore().rejectUnlockRequest(props.unlockRequest.id);
    }
}

async function deleteRequest() {
    if (confirm('Are you sure you want to delete this unlock request?')) {
        await useUnlockRequestsStore().deleteUnlockRequest(props.unlockRequest.id);
    }
}

const showAuditModal = ref(false);
</script>

<template>
    <TableRow>
        <!-- Project -->
        <div class="py-4 pr-3 text-sm text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <div class="font-medium break-words">{{ unlockRequest.project?.name ?? 'N/A' }}</div>
            <div v-if="unlockRequest.project?.client" class="text-xs text-text-tertiary mt-1 break-words">
                Client: {{ unlockRequest.project.client.name }}
            </div>
        </div>

        <!-- Reason -->
        <div class="py-4 pr-3 text-sm text-text-tertiary break-words max-w-md">
            {{ unlockRequest.reason ?? '-' }}
        </div>

        <!-- Requester -->
        <div class="py-4 pr-3 text-sm text-text-primary break-words">
            {{ unlockRequest.requester?.user?.name ?? 'Unknown' }}
        </div>

        <!-- Status -->
        <div class="py-4 pr-3 text-sm">
            <span
                :class="statusColor"
                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold leading-5 whitespace-nowrap">
                {{ statusText }}
            </span>
        </div>

        <!-- Created -->
        <div class="py-4 pr-3 text-sm text-text-tertiary">
            <div class="whitespace-nowrap">{{ formatDateTime(unlockRequest.created_at) }}</div>
        </div>

        <!-- Expires -->
        <div class="py-4 pr-3 text-sm text-text-tertiary">
            <span v-if="unlockRequest.expires_at" class="whitespace-nowrap">
                {{ formatDateTime(unlockRequest.expires_at) }}
            </span>
            <span v-else class="text-text-quaternary">-</span>
        </div>

        <!-- Actions -->
        <div class="relative py-4 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12 text-right text-sm font-medium">
            <div class="flex items-center justify-end space-x-2">
                <!-- View Audit Button (for approved/expired requests) -->
                <button
                    v-if="isApproved || isExpired"
                    @click="showAuditModal = true"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-1.5 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                    title="View Audit Logs">
                    <ClipboardDocumentListIcon class="h-5 w-5" />
                </button>

                <!-- Approve Button (for managers on pending requests) -->
                <button
                    v-if="isManager && isPending"
                    @click="approveRequest"
                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 p-1.5 rounded hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                    title="Approve">
                    <CheckCircleIcon class="h-5 w-5" />
                </button>

                <!-- Reject Button (for managers on pending requests) -->
                <button
                    v-if="isManager && isPending"
                    @click="rejectRequest"
                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    title="Reject">
                    <XCircleIcon class="h-5 w-5" />
                </button>

                <!-- Delete Button (for own pending requests) -->
                <button
                    v-if="isPending"
                    @click="deleteRequest"
                    class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-900/20 transition-colors"
                    title="Delete">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>
        </div>
    </TableRow>

    <!-- Audit Modal -->
    <UnlockRequestAuditModal
        v-model:show="showAuditModal"
        :unlock-request-id="unlockRequest.id"
    />
</template>

