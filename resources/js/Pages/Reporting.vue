<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import ReportingOverview from '@/Components/Common/Reporting/ReportingOverview.vue';
</script>

<template>
    <AppLayout
        title="Reporting"
        data-testid="reporting_view"
        class="overflow-hidden">
        <ReportingExportModal
            v-model:show="showExportModal"
            :export-url="exportUrl"></ReportingExportModal>
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
                <ReportingTabNavbar active="reporting"></ReportingTabNavbar>
            </div>
            <div class="flex space-x-2">
                <ReportingExportButton
                    :download="downloadExport"></ReportingExportButton>
                <ReportSaveButton
                    :report-properties="reportProperties"></ReportSaveButton>
            </div>
        </MainContainer>
        <div class="py-2.5 w-full border-b border-default-background-separator">
            <MainContainer
                class="sm:flex space-y-4 sm:space-y-0 justify-between">
                <div
                    class="flex flex-wrap items-center space-y-2 sm:space-y-0 space-x-4">
                    <div class="text-sm font-medium">Filters</div>
                    <MemberMultiselectDropdown
                        v-model="selectedMembers"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedMembers.length"
                                :active="selectedMembers.length > 0"
                                title="Members"
                                :icon="UserGroupIcon"></ReportingFilterBadge>
                        </template>
                    </MemberMultiselectDropdown>
                    <ProjectMultiselectDropdown
                        v-model="selectedProjects"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedProjects.length"
                                :active="selectedProjects.length > 0"
                                title="Projects"
                                :icon="FolderIcon"></ReportingFilterBadge>
                        </template>
                    </ProjectMultiselectDropdown>
                    <TaskMultiselectDropdown
                        v-model="selectedTasks"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedTasks.length"
                                :active="selectedTasks.length > 0"
                                title="Tasks"
                                :icon="CheckCircleIcon"></ReportingFilterBadge>
                        </template>
                    </TaskMultiselectDropdown>
                    <ClientMultiselectDropdown
                        v-model="selectedClients"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedClients.length"
                                :active="selectedClients.length > 0"
                                title="Clients"
                                :icon="FolderIcon"></ReportingFilterBadge>
                        </template>
                    </ClientMultiselectDropdown>
                    <TagDropdown
                        v-model="selectedTags"
                        :create-tag
                        :tags="tags"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedTags.length"
                                :active="selectedTags.length > 0"
                                title="Tags"
                                :icon="TagIcon"></ReportingFilterBadge>
                        </template>
                    </TagDropdown>

                    <SelectDropdown
                        v-model="billable"
                        :get-key-from-item="(item) => item.value"
                        :get-name-for-item="(item) => item.label"
                        :items="[
                            {
                                label: 'Both',
                                value: null,
                            },
                            {
                                label: 'Billable',
                                value: 'true',
                            },
                            {
                                label: 'Non Billable',
                                value: 'false',
                            },
                        ]"
                        @changed="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :active="billable !== null"
                                :title="
                                    billable === 'false'
                                        ? 'Non Billable'
                                        : 'Billable'
                                "
                                :icon="BillableIcon"></ReportingFilterBadge>
                        </template>
                    </SelectDropdown>
                </div>
                <div>
                    <DateRangePicker
                        v-model:start="startDate"
                        v-model:end="endDate"
                        @submit="updateReporting"></DateRangePicker>
                </div>
            </MainContainer>
        </div>
        <MainContainer>
            <div class="pt-10 w-full px-3 relative">
                <ReportingChart
                    :grouped-type="aggregatedGraphTimeEntries?.grouped_type"
                    :grouped-data="
                        aggregatedGraphTimeEntries?.grouped_data
                    "></ReportingChart>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="sm:grid grid-cols-4 pt-6 items-start">
                <div
                    class="col-span-3 bg-card-background rounded-lg border border-card-border pt-3">
                    <div
                        class="text-sm flex text-text-primary items-center space-x-3 font-medium px-6 border-b border-card-background-separator pb-3">
                        <span>Group by</span>
                        <ReportingGroupBySelect
                            v-model="group"
                            :group-by-options="groupByOptions"
                            @changed="updateTableReporting"></ReportingGroupBySelect>
                        <span>and</span>
                        <ReportingGroupBySelect
                            v-model="subGroup"
                            :group-by-options="
                                groupByOptions.filter(
                                    (el) => el.value !== group
                                )
                            "
                            @changed="updateTableReporting"></ReportingGroupBySelect>
                    </div>
                    <div
                        class="grid items-center"
                        style="grid-template-columns: 1fr 100px 210px">
                        <div
                            class="contents [&>*]:border-card-background-separator [&>*]:border-b [&>*]:bg-tertiary [&>*]:pb-1.5 [&>*]:pt-1 text-text-secondary text-sm">
                            <div class="pl-6">Name</div>
                            <div class="text-right">Duration</div>
                            <div class="text-right pr-6">Cost</div>
                        </div>
                        <template
                            v-if="
                                aggregatedTableTimeEntries?.grouped_data &&
                                aggregatedTableTimeEntries.grouped_data
                                    ?.length > 0
                            ">
                            <ReportingRow
                                v-for="entry in tableData"
                                :key="entry.description ?? 'none'"
                                :currency="getOrganizationCurrencyString()"
                                :entry="entry"
                                :type="
                                    aggregatedTableTimeEntries.grouped_type
                                "></ReportingRow>
                            <div
                                class="contents [&>*]:transition text-text-tertiary [&>*]:h-[50px]">
                                <div class="flex items-center pl-6 font-medium">
                                    <span>Total</span>
                                </div>
                                <div
                                    class="justify-end flex items-center font-medium">
                                    {{
                                        formatHumanReadableDuration(
                                            aggregatedTableTimeEntries.seconds
                                        )
                                    }}
                                </div>
                                <div
                                    class="justify-end pr-6 flex items-center font-medium">
                                    {{
                                        aggregatedTableTimeEntries.cost ?
                                        formatCents(
                                            aggregatedTableTimeEntries.cost,
                                            getOrganizationCurrencyString()
                                        ) : '--'
                                    }}
                                </div>
                            </div>
                        </template>
                        <div
                            v-else
                            class="chart flex flex-col items-center justify-center py-12 col-span-3">
                            <p class="text-lg text-text-primary font-semibold">
                                No time entries found
                            </p>
                            <p>Try to change the filters and time range</p>
                        </div>
                    </div>
                </div>
                <div class="px-2 lg:px-4">
                    <ReportingPieChart
                        :data="groupedPieChartData"></ReportingPieChart>
                </div>
            </div>
        </MainContainer>
    </AppLayout>
</template>
