<script setup lang="ts">
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import Badge from '@/packages/ui/src/Badge.vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import type { Role } from '@/types/jetstream';
import { usePage } from '@inertiajs/vue3';

const model = defineModel<string>({
    default: 'employee',
});

const page = usePage<{
    availableRoles: Role[];
}>();

function getKeyFromItem(item: Role) {
    return item.key;
}

function getNameFromItem(item: Role) {
    return item.name;
}

function getNameForKey(key: string | undefined) {
    const item = page.props.availableRoles.find((item) => getKeyFromItem(item) === key);
    if (item) {
        return getNameFromItem(item);
    }
    return '';
}
</script>

<template>
    <SelectDropdown
        v-model="model"
        :get-key-from-item="getKeyFromItem"
        :get-name-for-item="getNameFromItem"
        :items="page.props.availableRoles">
        <template #trigger>
            <Badge size="xlarge" class="bg-input-background cursor-pointer">
                <span>
                    {{ getNameForKey(model) }}
                </span>
                <ChevronDownIcon class="text-text-secondary w-5"></ChevronDownIcon>
            </Badge>
        </template>
    </SelectDropdown>
</template>

<style scoped></style>
