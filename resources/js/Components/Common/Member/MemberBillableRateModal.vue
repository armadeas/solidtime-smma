<script setup lang="ts">
import { getOrganizationCurrencyString } from '@/utils/money';
import BillableRateModal from '@/packages/ui/src/BillableRateModal.vue';
import { formatCents } from '@/packages/ui/src/utils/money';
import { inject, type ComputedRef } from 'vue';
import type { Organization } from '@/packages/api/src';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

const organization = inject<ComputedRef<Organization>>('organization');

defineProps<{
    newBillableRate?: number | null;
    memberName: string;
}>();

defineEmits<{
    submit: [];
}>();
</script>

<template>
    <BillableRateModal
        v-model:show="show"
        v-model:saving="saving"
        title="Update Member Billable Rate"
        @submit="$emit('submit')">
        <p class="py-1 text-center">
            The billable rate of {{ memberName }} will be updated to
            <strong>{{
                newBillableRate
                    ? formatCents(
                          newBillableRate,
                          getOrganizationCurrencyString(),
                          organization?.currency_format,
                          organization?.currency_symbol,
                          organization?.number_format
                      )
                    : ' the default rate of the organization'
            }}</strong
            >.
        </p>
        <p class="py-1 text-center font-semibold max-w-md mx-auto">
            Do you want to update all existing time entries, where the member billable rate applies
            as well?
        </p>
    </BillableRateModal>
</template>

<style scoped></style>
