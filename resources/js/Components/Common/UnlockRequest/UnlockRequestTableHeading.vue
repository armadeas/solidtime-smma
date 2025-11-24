<script setup lang="ts">
import TableHeading from '@/Components/Common/TableHeading.vue';

defineProps<{
    isManager: boolean;
}>();
</script>

<template>
    <TableHeading>
        <div class="py-1.5 pr-3 text-left font-semibold text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            Project
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">
            Reason
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">
            Requester
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">
            Status
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">
            Created
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">
            Expires
        </div>
        <div class="relative py-1.5 pl-3 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <span class="sr-only">Actions</span>
        </div>
    </TableHeading>
</template>

