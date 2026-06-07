import axiosClient from '$lib/axiosClient';
import type {
  Mitra,
  MitraFilterParams,
  MitraForm,
  MitraFormDependencies,
  PaginationMeta,
  PaginatedResponse
} from '$lib/types';

export type MitraListResult = {
  data: Mitra[];
  meta: PaginationMeta;
  formDeps: MitraFormDependencies;
};

type ResourceResponse<T> = {
  data: T;
  message?: string;
  form_dependencies?: MitraFormDependencies;
};

type MitraPaginatedResponse = Omit<PaginatedResponse<Mitra>, 'form_dependencies'> & {
  form_dependencies?: MitraFormDependencies;
};

const DEFAULT_FORM_DEPS: MitraFormDependencies = {
  kategori_options: [
    { value: 'pribadi', label: 'Pribadi' },
    { value: 'perusahaan', label: 'Perusahaan' },
    { value: 'customer', label: 'Customer' },
    { value: 'vendor', label: 'Vendor' }
  ]
};

/** Fetches the paginated mitra list with filter dependencies. */
export async function fetchMitras(params: MitraFilterParams): Promise<MitraListResult> {
  const response = await axiosClient.get<MitraPaginatedResponse>('/mitras', { params });

  return {
    data: response.data.data,
    meta: response.data.meta,
    formDeps: response.data.form_dependencies ?? DEFAULT_FORM_DEPS
  };
}

/** Fetches a single mitra and its form dependencies. */
export async function fetchMitra(id: string | number): Promise<{
  mitra: Mitra;
  formDeps: MitraFormDependencies;
}> {
  const response = await axiosClient.get<ResourceResponse<Mitra>>(`/mitras/${id}`);

  return {
    mitra: response.data.data,
    formDeps: response.data.form_dependencies ?? DEFAULT_FORM_DEPS
  };
}

/** Creates a mitra through the Laravel API. */
export async function createMitra(form: MitraForm): Promise<Mitra> {
  const response = await axiosClient.post<ResourceResponse<Mitra>>('/mitras', form);
  return response.data.data;
}

/** Updates a mitra through the Laravel API. */
export async function updateMitra(id: number, form: MitraForm): Promise<Mitra> {
  const response = await axiosClient.put<ResourceResponse<Mitra>>(`/mitras/${id}`, form);
  return response.data.data;
}

/** Deletes a mitra by id. */
export async function deleteMitra(id: number): Promise<void> {
  await axiosClient.delete(`/mitras/${id}`);
}
