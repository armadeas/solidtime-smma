<script lang="ts" setup>
import { CalendarCell, type CalendarCellProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps<CalendarCellProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <CalendarCell
        :class="
            twMerge(
                'relative p-0 text-center text-sm focus-within:relative focus-within:z-20 [&:has([data-selected])]:rounded-md [&:has([data-selected])]:bg-accent [&:has([data-selected][data-outside-view])]:bg-accent/50',
                props.class
            )
        "
        v-bind="forwardedProps">
        <slot />
    </CalendarCell>
</template>
