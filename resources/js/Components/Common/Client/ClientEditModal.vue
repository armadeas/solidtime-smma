<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import type { Client, UpdateClientBody } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useClientsStore } from '@/utils/useClients';
import InputLabel from '../../../packages/ui/src/Input/InputLabel.vue';
import TextInputArea from '@/packages/ui/src/Input/TextInputArea.vue';

const { updateClient } = useClientsStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    client: Client;
}>();

const clientBody = ref<UpdateClientBody>({
    name: props.client.name,
    email: props.client.email,
    phone: props.client.phone,
    taxNumber: props.client.taxNumber,
    address: props.client.address,
    postal_code: props.client.postal_code,
    city: props.client.city,
    country: props.client.country,
});

async function submit() {
    await updateClient(props.client.id, clientBody.value);
    show.value = false;
}

const clientNameInput = ref<HTMLInputElement | null>(null);
useFocus(clientNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update Client </span>
            </div>
        </template>

        <template #content>
            <div class="flex items-center space-x-4 mb-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <TextInput
                        id="clientName"
                        ref="clientNameInput"
                        v-model="clientBody.name"
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
                        v-model="clientBody.email"
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
                        v-model="clientBody.phone"
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
                        v-model="clientBody.city"
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
                        v-model="clientBody.country"
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
                        v-model="clientBody.postal_code"
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
                        v-model="clientBody.taxNumber"
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
                        v-model="clientBody.address"
                        type="text"
                        placeholder="Client Address"
                        required
                        autocomplete="clientAddress"
                        class="mt-1 block w-full" />
<!--                    <textarea
                        id="clientAddress"
                        ref="clientAddressInput"
                        v-model="clientBody.address"
                        type="text"
                        placeholder="Client Address"
                        required
                        autocomplete="clientAddress"
                        class="border-input-border border bg-input-background text-white focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent rounded-md shadow-sm mt-1 block w-full" ></textarea>-->
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
                Update Client
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
