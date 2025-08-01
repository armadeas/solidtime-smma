<script setup lang="ts">
import type { ProjectMember } from '@/packages/api/src';
import { computed, ref, inject, type ComputedRef } from 'vue';
import { storeToRefs } from 'pinia';
import TableRow from '@/Components/TableRow.vue';
import { useMembersStore } from '@/utils/useMembers';
import { useProjectMembersStore } from '@/utils/useProjectMembers';
import ProjectMemberMoreOptionsDropdown from '@/Components/Common/ProjectMember/ProjectMemberMoreOptionsDropdown.vue';
import { formatCents } from '@/packages/ui/src/utils/money';
import { capitalizeFirstLetter } from '@/utils/format';
import ProjectMemberEditModal from '@/Components/Common/ProjectMember/ProjectMemberEditModal.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import type { Organization } from '@/packages/api/src';

const props = defineProps<{
    projectMember: ProjectMember;
}>();

const organization = inject<ComputedRef<Organization>>('organization');

function deleteProjectMember() {
    useProjectMembersStore().deleteProjectMember(
        props.projectMember.project_id,
        props.projectMember.id
    );
}

function editProjectMember() {
    showEditModal.value = true;
}

const { members } = storeToRefs(useMembersStore());
const member = computed(() => {
    return members.value.find((member) => member.id === props.projectMember.member_id);
});
const showEditModal = ref(false);
</script>

<template>
    <TableRow>
        <ProjectMemberEditModal
            v-model:show="showEditModal"
            :name="member?.name"
            :project-member="projectMember"></ProjectMemberEditModal>
        <div
            class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span>
                {{ member?.name }}
            </span>
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
            {{
                projectMember.billable_rate
                    ? formatCents(
                          projectMember.billable_rate,
                          getOrganizationCurrencyString(),
                          organization?.currency_format,
                          organization?.currency_symbol,
                          organization?.number_format
                      )
                    : '--'
            }}
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
            {{ capitalizeFirstLetter(member?.role ?? '') }}
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <ProjectMemberMoreOptionsDropdown
                :project-member="projectMember"
                @delete="deleteProjectMember"
                @edit="editProjectMember"></ProjectMemberMoreOptionsDropdown>
        </div>
    </TableRow>
</template>

<style scoped></style>
