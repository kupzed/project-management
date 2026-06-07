import axiosClient from '$lib/axiosClient';
import type {
  BarangCertificate,
  BarangCertificateFilterParams,
  BarangCertificateForm,
  BarangCertificateFormDependencies,
  PaginatedResponse,
  PaginationMeta
} from '$lib/types';

export type BarangCertificateListResult = {
  data: BarangCertificate[];
  meta: PaginationMeta;
  formDeps: BarangCertificateFormDependencies;
};

type ResourceResponse<T> = {
  data: T;
  message?: string;
  form_dependencies?: BarangCertificateFormDependencies;
};

type BarangCertificatePaginatedResponse = Omit<
  PaginatedResponse<BarangCertificate>,
  'form_dependencies'
> & {
  form_dependencies?: BarangCertificateFormDependencies;
};

const DEFAULT_FORM_DEPS: BarangCertificateFormDependencies = {
  mitras: []
};

/** Fetches paginated barang certificates with form dependencies. */
export async function fetchBarangCertificates(
  params: BarangCertificateFilterParams
): Promise<BarangCertificateListResult> {
  const response = await axiosClient.get<BarangCertificatePaginatedResponse>(
    '/barang-certificates',
    { params }
  );

  return {
    data: response.data.data,
    meta: response.data.meta,
    formDeps: response.data.form_dependencies ?? DEFAULT_FORM_DEPS
  };
}

/** Fetches a single barang certificate. */
export async function fetchBarangCertificate(id: string | number): Promise<{
  barangCertificate: BarangCertificate;
  formDeps: BarangCertificateFormDependencies;
}> {
  const response = await axiosClient.get<ResourceResponse<BarangCertificate>>(
    `/barang-certificates/${id}`
  );

  return {
    barangCertificate: response.data.data,
    formDeps: response.data.form_dependencies ?? DEFAULT_FORM_DEPS
  };
}

/** Creates a barang certificate. */
export async function createBarangCertificate(
  form: BarangCertificateForm
): Promise<BarangCertificate> {
  const response = await axiosClient.post<ResourceResponse<BarangCertificate>>(
    '/barang-certificates',
    form
  );
  return response.data.data;
}

/** Updates a barang certificate. */
export async function updateBarangCertificate(
  id: number,
  form: BarangCertificateForm
): Promise<BarangCertificate> {
  const response = await axiosClient.put<ResourceResponse<BarangCertificate>>(
    `/barang-certificates/${id}`,
    form
  );
  return response.data.data;
}

/** Deletes a barang certificate. */
export async function deleteBarangCertificate(id: number): Promise<void> {
  await axiosClient.delete(`/barang-certificates/${id}`);
}
