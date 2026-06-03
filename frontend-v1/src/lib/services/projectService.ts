import axiosClient from '$lib/axiosClient';
import type { PaginatedResponse, Project, ProjectFilterParams, ProjectForm } from '$lib/types';

type ResourceResponse<T> = {
	data: T;
	message?: string;
	form_dependencies?: Record<string, unknown>;
};

/** Fetches the paginated project list with filters and sorting. */
export async function fetchProjects(
	params: ProjectFilterParams
): Promise<PaginatedResponse<Project>> {
	const response = await axiosClient.get<PaginatedResponse<Project>>('/projects', { params });
	return response.data;
}

/** Fetches a single project and its form dependency payload. */
export async function fetchProject(
	id: string | number
): Promise<{ project: Project; formDeps: Record<string, unknown> }> {
	const response = await axiosClient.get<ResourceResponse<Project>>(`/projects/${id}`);

	return {
		project: response.data.data,
		formDeps: response.data.form_dependencies ?? {}
	};
}

/** Creates a project through the Laravel API. */
export async function createProject(form: ProjectForm): Promise<Project> {
	const response = await axiosClient.post<ResourceResponse<Project>>('/projects', form);
	return response.data.data;
}

/** Updates a project through the Laravel API. */
export async function updateProject(id: number, form: ProjectForm): Promise<Project> {
	const response = await axiosClient.put<ResourceResponse<Project>>(`/projects/${id}`, form);
	return response.data.data;
}

/** Deletes a project by id. */
export async function deleteProject(id: number): Promise<void> {
	await axiosClient.delete(`/projects/${id}`);
}
