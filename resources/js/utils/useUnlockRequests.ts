import { defineStore } from 'pinia';
import { ref } from 'vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import { useNotificationsStore } from '@/utils/notification';

export const useUnlockRequestsStore = defineStore('unlockRequests', () => {
    const unlockRequests = ref<any[]>([]);
    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchUnlockRequests(filters?: Record<string, any>) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.getUnlockRequests({
                        params: {
                            organization: organizationId,
                        },
                        queries: {
                            ...filters,
                        },
                    }),
                undefined,
                'Failed to fetch unlock requests'
            );
            if (response?.data) {
                unlockRequests.value = response.data;
            }
        } else {
            throw new Error('Failed to fetch unlock requests because organization ID is missing.');
        }
    }

    async function createUnlockRequest(data: any): Promise<any> {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.createUnlockRequest(data, {
                        params: {
                            organization: organizationId,
                        },
                    }),
                'Unlock request created successfully',
                'Failed to create unlock request'
            );
            if (response?.data) {
                unlockRequests.value.unshift(response.data);
                return response.data;
            }
        } else {
            throw new Error('Failed to create unlock request because organization ID is missing.');
        }
    }

    async function approveUnlockRequest(unlockRequestId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.approveUnlockRequest(undefined, {
                        params: {
                            organization: organizationId,
                            unlockRequest: unlockRequestId,
                        },
                    }),
                'Unlock request approved successfully',
                'Failed to approve unlock request'
            );
            if (response?.data) {
                const index = unlockRequests.value.findIndex((ur) => ur.id === unlockRequestId);
                if (index !== -1) {
                    unlockRequests.value[index] = response.data;
                }
            }
        }
    }

    async function rejectUnlockRequest(unlockRequestId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.rejectUnlockRequest(undefined, {
                        params: {
                            organization: organizationId,
                            unlockRequest: unlockRequestId,
                        },
                    }),
                'Unlock request rejected',
                'Failed to reject unlock request'
            );
            if (response?.data) {
                const index = unlockRequests.value.findIndex((ur) => ur.id === unlockRequestId);
                if (index !== -1) {
                    unlockRequests.value[index] = response.data;
                }
            }
        }
    }

    async function deleteUnlockRequest(unlockRequestId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteUnlockRequest(undefined, {
                        params: {
                            organization: organizationId,
                            unlockRequest: unlockRequestId,
                        },
                    }),
                'Unlock request deleted',
                'Failed to delete unlock request'
            );
            unlockRequests.value = unlockRequests.value.filter((ur) => ur.id !== unlockRequestId);
        }
    }

    return {
        unlockRequests,
        fetchUnlockRequests,
        createUnlockRequest,
        approveUnlockRequest,
        rejectUnlockRequest,
        deleteUnlockRequest,
    };
});

