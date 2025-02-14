<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import type { CreateClientBody } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useClientsStore } from '@/utils/useClients';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import TextInputArea from '@/packages/ui/src/Input/TextInputArea.vue';

const { createClient } = useClientsStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const client = ref<CreateClientBody>({
    name: '',
    email: '',
    phone: '', // Add phone field
    taxNumber: '', // Add taxNumber field
    address: '', // Add address field
    postalCOde: '', //Add postalCode field
    city: '', // Add city field
    country: '', // Add country field
});

async function submit() {
    await createClient(client.value);
    client.value.name = '';
    client.value.email = '';
    client.value.phone = '';
    client.value.city = ''; // Reset city field
    client.value.country = ''; // Reset country field
    client.value.postal_code = ''; // Reset postal_code field
    client.value.taxNumber = '';
    client.value.address = ''; // Reset address field
    show.value = false;
}

const clientNameInput = ref<HTMLInputElement | null>(null);
useFocus(clientNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create Client </span>
            </div>
        </template>

        <template #content>
            <div class="flex items-center space-x-4 mb-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="clientName" value="Client Name" />
                    <TextInput
                        id="clientName"
                        ref="clientNameInput"
                        v-model="client.name"
                        type="text"
                        placeholder="Client Name"
                        class="mt-1 block w-full"
                        required
                        autocomplete="clientName"
                        @keydown.enter="submit" />
                </div>
            </div>
            <div class="flex items-center space-x-4 mb-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="clientEmail" value="Client Email" />
                    <TextInput
                        id="clientEmail"
                        ref="clientEmailInput"
                        v-model="client.email"
                        type="text"
                        placeholder="Client Email"
                        class="mt-1 block w-full"
                        required
                        autocomplete="clientEmail"
                        @keydown.enter="submit" />
                </div>
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="clientPhone" value="Client Phone" />
                    <TextInput
                        id="clientPhone"
                        ref="clientPhoneInput"
                        v-model="client.phone"
                        type="text"
                        placeholder="Client Phone"
                        class="mt-1 block w-full"
                        required
                        autocomplete="clientPhone"
                        @keydown.enter="submit" />
                </div>
            </div>
            <div class="flex items-center space-x-4 mb-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="clientCity" value="Client City" />
                    <TextInput
                        id="clientCity"
                        ref="clientCityInput"
                        v-model="client.city"
                        type="text"
                        placeholder="Client City"
                        class="mt-1 block w-full"
                        required
                        autocomplete="clientCity"
                        @keydown.enter="submit" />
                </div>
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="clientCountry" value="Client Country" />
                    <TextInput
                        id="clientCountry"
                        ref="clientCountryInput"
                        v-model="client.country"
                        type="text"
                        placeholder="Client Country"
                        class="mt-1 block w-full"
                        required
                        autocomplete="clientCountry"
                        @keydown.enter="submit" />
                </div>
            </div>
            <div class="flex items-center space-x-4 mb-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="clientPostalCode" value="Postal Code" />
                    <TextInput
                        id="clientPostalCode"
                        ref="clientPostalCodeInput"
                        v-model="client.postal_code"
                        type="text"
                        placeholder="Postal Code"
                        class="mt-1 block w-full"
                        required
                        autocomplete="clientPostalCode"
                        @keydown.enter="submit" />
                </div>
            </div>
            <div class="flex items-center space-x-4 mb-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="clientTaxNumber" value="Tax Number" />
                    <TextInput
                        id="clientTaxNumber"
                        ref="clientTaxNumberInput"
                        v-model="client.taxNumber"
                        type="text"
                        placeholder="Tax Number"
                        class="mt-1 block w-full"
                        required
                        autocomplete="clientTaxNumber"
                        @keydown.enter="submit" />
                </div>
            </div>
            <div class="flex items-center space-x-4 mb-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="clientAddress" value="Client Address" />
                    <TextInputArea
                        id="clientAddress"
                        ref="clientAddressInput"
                        v-model="client.address"
                        placeholder="Client Address"
                        required
                        autocomplete="clientAddress"
                        @keydown.enter="submit"
                        class="mt-1 block w-full" />
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Create Client
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
