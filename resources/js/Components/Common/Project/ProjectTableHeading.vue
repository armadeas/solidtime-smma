<script setup lang="ts">
import TableHeading from '@/Components/Common/TableHeading.vue';
import { defineEmits, defineProps } from 'vue';

const emit = defineEmits(['sort']);

const props = defineProps<{
    showBillableRate: boolean,
    sortOrder: 'asc' | 'desc',
}>();
</script>

<template>
    <TableHeading>
        <div
            class="py-1.5 pr-3 text-left font-semibold text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            Name
            <a href="#"
               class="ml-1 text-text-secondary hover:text-text-primary transition duration-200"
               title="Sort by name ascending"
               @click.prevent="emit('sort', 'asc')"
               :aria-pressed="props.sortOrder === 'asc'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </a>
            <a href="#"
               class="ml-1 text-text-secondary hover:text-text-primary transition duration-200"
               title="Sort by name descending"
               @click.prevent="emit('sort', 'desc')"
               :aria-pressed="props.sortOrder === 'desc'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 12.707a1 1 0 011.414 0L10 9.414l3.293 3.293a1 1 0 001.414-1.414l-4-4a1 1 0 00-1.414 0l-4 4a1 1 0 000 1.414z" clip-rule="evenodd"/>
                </svg>
            </a>
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Client</div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">
            Total Time
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">
            Progress
        </div>
        <div
            v-if="showBillableRate"
            class="px-3 py-1.5 text-left font-semibold text-text-primary">
            Billable Rate
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Status</div>
        <div class="relative py-1.5 pl-3 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <span class="sr-only">Edit</span>
        </div>
    </TableHeading>
</template>

<style scoped></style>
