import axiosClient from '$lib/axiosClient';
import { appendAttachments, appendScalar } from '$lib/utils/form-data';
import type {
  Activity,
  ActivityEditForm,
  ActivityFilterParams,
  ActivityFormDependencies,
  ActivityForm,
  ActivityJenis,
  ActivityKategori,
  ActivityPaginatedResponse,
  Option,
  PaginationMeta
} from '$lib/types';

export type ActivityListFormDeps = {
  projects: ActivityFormDependencies['projects'];
  vendors: Option[];
  customers: Option[];
  kategoriList: ActivityKategori[];
  jenisList: ActivityJenis[];
};

export type ActivityListResult = {
  data: Activity[];
  meta: PaginationMeta;
  vendorOptions: Option[];
  formDeps: ActivityListFormDeps;
};

export type ExtractedActivity = Partial<
  Pick<
    ActivityForm,
    'name' | 'short_desc' | 'description' | 'kategori' | 'jenis' | 'activity_date' | 'from' | 'to'
  >
> & {
  value?: number | string | null;
  mitra_id?: number | string | null;
};

type ResourceResponse<T> = {
  data: T;
  message?: string;
  form_dependencies?: ActivityFormDependencies;
};

const MULTIPART_HEADERS = {
  'Content-Type': 'multipart/form-data'
} as const;

/** Returns empty dependencies when the backend omits optional metadata. */
function getDefaultFormDeps(): ActivityListFormDeps {
  return {
    projects: [],
    vendors: [],
    customers: [],
    kategoriList: [],
    jenisList: []
  };
}

/** Fetches the paginated activity list and normalizes dependency payloads. */
export async function fetchActivities(params: ActivityFilterParams): Promise<ActivityListResult> {
  const response = await axiosClient.get<ActivityPaginatedResponse>('/activities', { params });
  const deps = response.data.form_dependencies;

  return {
    data: response.data.data,
    meta: response.data.meta,
    vendorOptions: response.data.vendor_options ?? [],
    formDeps: deps
      ? {
          projects: deps.projects,
          vendors: deps.vendors,
          customers: deps.customers,
          kategoriList: deps.kategori_list,
          jenisList: deps.jenis_list
        }
      : getDefaultFormDeps()
  };
}

/** Fetches a single activity and its form dependencies. */
export async function fetchActivity(
  id: string | number
): Promise<{ activity: Activity; formDeps: ActivityListFormDeps }> {
  const response = await axiosClient.get<ResourceResponse<Activity>>(`/activities/${id}`);
  const deps = response.data.form_dependencies;

  return {
    activity: response.data.data,
    formDeps: deps
      ? {
          projects: deps.projects,
          vendors: deps.vendors,
          customers: deps.customers,
          kategoriList: deps.kategori_list,
          jenisList: deps.jenis_list
        }
      : getDefaultFormDeps()
  };
}

/** Builds multipart activity payloads including mitra rules and attachments. */
export function buildActivityFormData(
  data: ActivityForm | ActivityEditForm,
  projectMitraId?: number
): FormData {
  const fd = new FormData();

  appendScalar(fd, 'action', data.action);
  appendScalar(fd, 'name', data.name);
  appendScalar(fd, 'short_desc', data.short_desc);
  appendScalar(fd, 'description', data.description);
  appendScalar(fd, 'project_id', data.project_id);
  appendScalar(fd, 'kategori', data.kategori);
  appendScalar(fd, 'value', data.value);
  appendScalar(fd, 'activity_date', data.activity_date);
  appendScalar(fd, 'jenis', data.jenis);
  appendScalar(fd, 'from', data.from);
  appendScalar(fd, 'to', data.to);

  if (data.jenis === 'Internal') {
    fd.set('mitra_id', '1');
  } else if (data.jenis === 'Customer' && projectMitraId) {
    fd.set('mitra_id', String(projectMitraId));
  } else if (data.mitra_id) {
    fd.set('mitra_id', String(data.mitra_id));
  }

  appendAttachments(fd, data);

  return fd;
}

/** Creates an activity through the Laravel API. */
export async function createActivity(
  data: ActivityForm,
  projectMitraId?: number
): Promise<Activity> {
  const formData = buildActivityFormData(data, projectMitraId);
  const response = await axiosClient.post<ResourceResponse<Activity>>('/activities', formData, {
    headers: MULTIPART_HEADERS
  });

  return response.data.data;
}

/** Updates an activity through multipart POST with Laravel method override. */
export async function updateActivity(
  id: number,
  data: ActivityEditForm,
  projectMitraId?: number
): Promise<Activity> {
  const formData = buildActivityFormData(data, projectMitraId);
  formData.append('_method', 'PUT');

  const response = await axiosClient.post<ResourceResponse<Activity>>(
    `/activities/${id}`,
    formData,
    {
      headers: MULTIPART_HEADERS
    }
  );

  return response.data.data;
}

/** Deletes an activity by id. */
export async function deleteActivity(id: number): Promise<void> {
  await axiosClient.delete(`/activities/${id}`);
}

/** Extracts activity form fields from an uploaded document through the AI endpoint. */
export async function extractActivityFromDocument(
  file: File,
  projectId?: string | number
): Promise<ExtractedActivity> {
  const formData = new FormData();
  formData.append('action', 'extract');
  formData.append('document', file);

  if (projectId) {
    formData.append('project_id', String(projectId));
  }

  const response = await axiosClient.post<ResourceResponse<ExtractedActivity>>(
    '/activities',
    formData,
    {
      headers: MULTIPART_HEADERS
    }
  );

  return response.data.data;
}
