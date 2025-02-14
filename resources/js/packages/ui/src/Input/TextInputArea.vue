<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    name?: string;
    class?: string;
}>();

const input = ref<HTMLTextAreaElement | null>(null);

// Define the type of the model variable
const model = ref<string | number | readonly string[] | null | undefined>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <textarea
        ref="input"
        v-model="model"
        :class="
            twMerge(
                'border-input-border border bg-input-background text-white focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent rounded-md shadow-sm',
                props.class
            )
        "
        :name="name"></textarea>
</template>
