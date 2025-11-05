<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { LockOpenIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { computed, onMounted, ref } from 'vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import UnlockRequestTable from '@/Components/Common/UnlockRequest/UnlockRequestTable.vue';
import UnlockRequestCreateModal from '@/Components/Common/UnlockRequest/UnlockRequestCreateModal.vue';
import { useUnlockRequestsStore } from '@/utils/useUnlockRequests';
import { storeToRefs } from 'pinia';
import { getCurrentRole } from '@/utils/useUser';
import type { CreateUnlockRequestBody, UnlockRequest } from '@/packages/api/src';

onMounted(() => {
    useUnlockRequestsStore().fetchUnlockRequests();
});

const showCreateModal = ref(false);
const activeTab = ref<'all' | 'my_requests' | 'pending_approvals'>('all');

const { unlockRequests } = storeToRefs(useUnlockRequestsStore());

const isManager = computed(() => {
    const role = getCurrentRole();
    return role === 'owner' || role === 'admin' || role === 'manager';
});

const filteredUnlockRequests = computed(() => {
    if (activeTab.value === 'my_requests') {
        return unlockRequests.value; // Will be filtered by API
    }
    if (activeTab.value === 'pending_approvals') {
        return unlockRequests.value; // Will be filtered by API
    }
    return unlockRequests.value;
});

async function createUnlockRequest(data: CreateUnlockRequestBody): Promise<UnlockRequest | undefined> {
    return await useUnlockRequestsStore().createUnlockRequest(data);
}

function handleTabChange(tab: 'all' | 'my_requests' | 'pending_approvals') {
    activeTab.value = tab;
    const filters: any = {};

    if (tab === 'my_requests') {
        filters.my_requests = 'true';
    } else if (tab === 'pending_approvals') {
        filters.pending_approvals = 'true';
    }

    useUnlockRequestsStore().fetchUnlockRequests(filters);
}
</script>

<template>
    <AppLayout title="Unlock Requests" data-testid="unlock_requests_view">
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="LockOpenIcon" title="Unlock Requests"></PageTitle>
                <TabBar v-model="activeTab" @update:model-value="handleTabChange">
                    <TabBarItem value="all">All Requests</TabBarItem>
                    <TabBarItem value="my_requests">My Requests</TabBarItem>
                    <TabBarItem v-if="isManager" value="pending_approvals">Pending Approvals</TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton
                :icon="PlusIcon"
                @click="showCreateModal = true"
                >Request Unlock
            </SecondaryButton>
            <UnlockRequestCreateModal
                v-model:show="showCreateModal"
                :create-unlock-request="createUnlockRequest"
            />
        </MainContainer>
        <UnlockRequestTable
            :unlock-requests="filteredUnlockRequests"
            :is-manager="isManager"
        />
    </AppLayout>
</template>

