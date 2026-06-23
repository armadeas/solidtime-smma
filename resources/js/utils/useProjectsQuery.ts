import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Project } from '@/packages/api/src';
import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';

export interface ProjectFilters {
    search?: string;
    clients?: string[];
    members?: string[];
}

export async function fetchAllProjects(
    organizationId: string,
    filters?: ProjectFilters
): Promise<Project[]> {
    return fetchAllPages((page) =>
        api.getProjects({
            params: { organization: organizationId },
            queries: {
                archived: 'all',
                page,
                ...(filters?.search ? { search: filters.search } : {}),
                ...(filters?.clients && filters.clients.length > 0 ? { 'clients[]': filters.clients } : {}),
                ...(filters?.members && filters.members.length > 0 ? { 'members[]': filters.members } : {}),
            },
        })
    );
}

export function useProjectsQuery(filters?: MaybeRefOrGetter<ProjectFilters | undefined>) {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => [
            'projects',
            getCurrentOrganizationId(),
            filters ? toValue(filters) : undefined,
        ]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllProjects(organizationId, filters ? toValue(filters) : undefined);
            return { data };
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const projects = computed<Project[]>(() => query.data.value?.data ?? []);

    const invalidateProjects = () => {
        queryClient.invalidateQueries({ queryKey: ['projects'] });
    };

    return {
        ...query,
        projects,
        invalidateProjects,
    };
}
