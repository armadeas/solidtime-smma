<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import type { CreateClientBody } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useClientsStore } from '@/utils/useClients';
import TextInputArea from '@/packages/ui/src/Input/TextInputArea.vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';

const { createClient } = useClientsStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const client = ref<CreateClientBody>({
    name: '',
    email: '',
    phone: '', // Add phone field
    taxNumber: '', // Add taxNumber field
    address: '', // Add address field
    postal_code: '', //Add postalCode field
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
            <div class="flex flex-col space-y-4">
                <div class="flex items-center space-x-4">
                    <Field class="col-span-6 sm:col-span-4 flex-1">
                        <FieldLabel for="clientName">Client Name</FieldLabel>
                        <TextInput
                            id="clientName"
                            ref="clientNameInput"
                            v-model="client.name"
                            type="text"
                            placeholder="Client Name"
                            class="block w-full"
                            required
                            autocomplete="clientName"
                            @keydown.enter="submit" />
                    </Field>
                </div>
                <div class="flex items-center space-x-4">
                    <Field class="col-span-6 sm:col-span-4 flex-1">
                        <FieldLabel for="clientEmail">Client Email</FieldLabel>
                        <TextInput
                            id="clientEmail"
                            ref="clientEmailInput"
                            v-model="client.email"
                            type="text"
                            placeholder="Client Email"
                            class="block w-full"
                            required
                            autocomplete="clientEmail"
                            @keydown.enter="submit" />
                    </Field>
                    <Field class="col-span-6 sm:col-span-4 flex-1">
                        <FieldLabel for="clientPhone">Client Phone</FieldLabel>
                        <TextInput
                            id="clientPhone"
                            ref="clientPhoneInput"
                            v-model="client.phone"
                            type="text"
                            placeholder="Client Phone"
                            class="block w-full"
                            required
                            autocomplete="clientPhone"
                            @keydown.enter="submit" />
                    </Field>
                </div>
                <div class="flex items-center space-x-4">
                    <Field class="col-span-6 sm:col-span-4 flex-1">
                        <FieldLabel for="clientCity">Client City</FieldLabel>
                        <TextInput
                            id="clientCity"
                            ref="clientCityInput"
                            v-model="client.city"
                            type="text"
                            placeholder="Client City"
                            class="block w-full"
                            required
                            autocomplete="clientCity"
                            @keydown.enter="submit" />
                    </Field>
                    <Field class="col-span-6 sm:col-span-4 flex-1">
                        <FieldLabel for="clientCountry">Client Country</FieldLabel>
                        <TextInput
                            id="clientCountry"
                            ref="clientCountryInput"
                            v-model="client.country"
                            type="text"
                            placeholder="Client Country"
                            class="block w-full"
                            required
                            autocomplete="clientCountry"
                            @keydown.enter="submit" />
                    </Field>
                </div>
                <div class="flex items-center space-x-4">
                    <Field class="col-span-6 sm:col-span-4 flex-1">
                        <FieldLabel for="clientPostalCode">Postal Code</FieldLabel>
                        <TextInput
                            id="clientPostalCode"
                            ref="clientPostalCodeInput"
                            v-model="client.postal_code"
                            type="text"
                            placeholder="Postal Code"
                            class="block w-full"
                            required
                            autocomplete="clientPostalCode"
                            @keydown.enter="submit" />
                    </Field>
                    <Field class="col-span-6 sm:col-span-4 flex-1">
                        <FieldLabel for="clientTaxNumber">Tax Number</FieldLabel>
                        <TextInput
                            id="clientTaxNumber"
                            ref="clientTaxNumberInput"
                            v-model="client.taxNumber"
                            type="text"
                            placeholder="Tax Number"
                            class="block w-full"
                            required
                            autocomplete="clientTaxNumber"
                            @keydown.enter="submit" />
                    </Field>
                </div>
                <div class="flex items-center space-x-4">
                    <Field class="col-span-6 sm:col-span-4 flex-1">
                        <FieldLabel for="clientAddress">Client Address</FieldLabel>
                        <TextInputArea
                            id="clientAddress"
                            ref="clientAddressInput"
                            v-model="client.address"
                            type="text"
                            placeholder="Client Address"
                            required
                            autocomplete="clientAddress"
                            @keydown.enter="submit"
                            class="block w-full" />
                    </Field>
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
