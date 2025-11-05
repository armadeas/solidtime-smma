<script setup lang="ts">
import TableRow from '@/Components/TableRow.vue';
import { useUnlockRequestsStore } from '@/utils/useUnlockRequests';
import { CheckCircleIcon, XCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { formatDateTime } from '@/utils/format';
import { computed } from 'vue';

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
    if (isPending.value) return 'text-yellow-600 bg-yellow-50';
    if (isApproved.value) return 'text-green-600 bg-green-50';
    if (isRejected.value) return 'text-red-600 bg-red-50';
    if (isExpired.value) return 'text-gray-600 bg-gray-50';
    return 'text-gray-600 bg-gray-50';
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
</script>

<template>
    <TableRow>
        <!-- Project -->
        <div class="py-4 pr-3 text-sm text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <div class="font-medium break-words">{{ unlockRequest.project?.name ?? 'N/A' }}</div>
            <div v-if="unlockRequest.reason" class="text-xs text-text-tertiary mt-1 break-words max-w-md">
                {{ unlockRequest.reason }}
            </div>
        </div>

        <!-- Client -->
        <div class="py-4 pr-3 text-sm text-text-primary break-words">
            {{ unlockRequest.project?.client?.name ?? '-' }}
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
                <!-- Approve Button (for managers on pending requests) -->
                <button
                    v-if="isManager && isPending"
                    @click="approveRequest"
                    class="text-green-600 hover:text-green-900 p-1.5 rounded hover:bg-green-50 transition-colors"
                    title="Approve">
                    <CheckCircleIcon class="h-5 w-5" />
                </button>

                <!-- Reject Button (for managers on pending requests) -->
                <button
                    v-if="isManager && isPending"
                    @click="rejectRequest"
                    class="text-red-600 hover:text-red-900 p-1.5 rounded hover:bg-red-50 transition-colors"
                    title="Reject">
                    <XCircleIcon class="h-5 w-5" />
                </button>

                <!-- Delete Button (for own pending requests) -->
                <button
                    v-if="isPending"
                    @click="deleteRequest"
                    class="text-gray-600 hover:text-gray-900 p-1.5 rounded hover:bg-gray-50 transition-colors"
                    title="Delete">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>
        </div>
    </TableRow>
</template>

