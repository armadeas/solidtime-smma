<script setup lang="ts">
import { FolderPlusIcon, PlusIcon } from '@heroicons/vue/24/solid';
import { ref } from 'vue';
import UnlockRequestTableRow from '@/Components/Common/UnlockRequest/UnlockRequestTableRow.vue';
import UnlockRequestTableHeading from '@/Components/Common/UnlockRequest/UnlockRequestTableHeading.vue';
import UnlockRequestCreateModal from '@/Components/Common/UnlockRequest/UnlockRequestCreateModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { useUnlockRequestsStore } from '@/utils/useUnlockRequests';

const props = defineProps<{
    unlockRequests: any[];
    isManager: boolean;
}>();

const showCreateModal = ref(false);

async function createUnlockRequest(data: any) {
    return await useUnlockRequestsStore().createUnlockRequest(data);
}
</script>

<template>
    <UnlockRequestCreateModal
        v-model:show="showCreateModal"
        :create-unlock-request="createUnlockRequest"
    />
    <div class="flow-root">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="unlock_request_table"
                class="grid min-w-full"
                style="grid-template-columns: minmax(200px, 2fr) minmax(150px, 1fr) minmax(150px, 1fr) minmax(100px, 120px) minmax(150px, 180px) minmax(150px, 180px) minmax(120px, 150px)">
                <UnlockRequestTableHeading :is-manager="isManager" />

                <div v-if="unlockRequests.length === 0" class="col-span-7 py-24 text-center">
                    <FolderPlusIcon class="w-8 text-icon-default inline pb-2" />
                    <h3 class="text-text-primary font-semibold">No unlock requests found</h3>
                    <p class="pb-5">Create your first unlock request now!</p>
                    <SecondaryButton
                        :icon="PlusIcon"
                        @click="showCreateModal = true"
                        >Request Unlock</SecondaryButton>
                </div>

                <template v-for="unlockRequest in unlockRequests" :key="unlockRequest.id">
                    <UnlockRequestTableRow
                        :unlock-request="unlockRequest"
                        :is-manager="isManager"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

