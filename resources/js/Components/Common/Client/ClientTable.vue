<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { type Component, ref, computed } from 'vue';
import { type Client } from '@/packages/api/src';
import ClientTableRow from '@/Components/Common/Client/ClientTableRow.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import ClientTableHeading from '@/Components/Common/Client/ClientTableHeading.vue';
import { canCreateClients } from '@/utils/permissions';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TextInput from '../../../packages/ui/src/Input/TextInput.vue';
import { useClientsStore } from '@/utils/useClients';

const props = defineProps<{
    clients: Client[];
}>();
const createClient = ref(false);

const searchQuery = ref('');

const clientsStore = useClientsStore();

const sortOrder = ref<'asc' | 'desc'>('asc');

const sortedClients = computed(() => {
    return clientsStore.clients;
});

function handleSort(order: 'asc' | 'desc') {
    sortOrder.value = order;
    clientsStore.fetchClients(searchQuery.value, order);
}

function handleSearch() {
    clientsStore.fetchClients(searchQuery.value, sortOrder.value);
}
</script>

<template>
    <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
    <div class="py-2.5 w-full border-b border-default-background-separator">
        <MainContainer
            class="sm:flex space-y-4 sm:space-y-0 justify-between">
            <div
                class="flex flex-wrap items-center space-y-2 sm:space-y-0 space-x-4">
                <div class="text-sm font-medium">Filters</div>
                <TextInput
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search client name address or anything else"
                    class="m-1 "
                    style="min-width: 180px;"
                    @keyup.enter="handleSearch"
                />
                <SecondaryButton @click="handleSearch">Search</SecondaryButton>
            </div>
        </MainContainer>
    </div>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="client_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 150px 200px 80px">
                <ClientTableHeading :sortOrder="sortOrder" @sort="handleSort" />
                <div
                    v-if="clients.length === 0"
                    class="col-span-2 py-24 text-center">
                    <UserCircleIcon
                        class="w-8 text-icon-default inline pb-2"></UserCircleIcon>
                    <h3 class="text-text-primary font-semibold">No clients found</h3>
                    <p v-if="canCreateClients()" class="pb-5">
                        Create your first client now!
                    </p>
                    <SecondaryButton
                        v-if="canCreateClients()"
                        :icon="PlusIcon as Component"
                        @click="createClient = true"
                        >Create your First Client
                    </SecondaryButton>
                </div>
                <template v-for="client in sortedClients" :key="client.id">
                    <ClientTableRow :client="client"></ClientTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
