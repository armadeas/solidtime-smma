<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { SelectLabel, type SelectLabelProps } from 'reka-ui';

const props = defineProps<SelectLabelProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
    <SelectLabel :class="cn('px-2 py-1.5 text-sm font-semibold', props.class)">
        <slot />
    </SelectLabel>
</template>
