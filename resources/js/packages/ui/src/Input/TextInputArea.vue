<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    name?: string;
    class?: string;
    modelValue?: string | number | readonly string[] | null | undefined;
}>();

const emit = defineEmits(['update:modelValue']);

const input = ref<HTMLTextAreaElement | null>(null);
const model = ref(props.modelValue);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

watch(model, (newValue) => {
    emit('update:modelValue', newValue);
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <textarea
        ref="input"
        v-model="model"
        :class="
            twMerge(
                'border-input-border border bg-input-background text-text-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent rounded-md shadow-sm',
                props.class
            )
        "
        :name="name"></textarea>
</template>
