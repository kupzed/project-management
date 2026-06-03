import axiosClient from '$lib/axiosClient';
import { appendAttachments, appendScalar } from '$lib/utils/form-data';
import type {
  Certificate,
  CertificateEditForm,
  CertificateFilterParams,
  CertificateForm,
  CertificateFormDependencies,
  CertificatePaginatedResponse,
  PaginationMeta
} from '$lib/types';

export type CertificateListResult = {
  data: Certificate[];
  meta: PaginationMeta;
  formDeps: CertificateFormDependencies;
};

type ResourceResponse<T> = {
  data: T;
  message?: string;
  form_dependencies?: CertificateFormDependencies;
};

const MULTIPART_HEADERS = {
  'Content-Type': 'multipart/form-data'
} as const;

/** Returns empty dependencies when the backend omits optional metadata. */
function getDefaultFormDeps(): CertificateFormDependencies {
  return {
    projects: [],
    barang_certificates: [],
    statuses: [],
    barang_options: []
  };
}

/** Fetches the paginated certificate list and dependency payloads. */
export async function fetchCertificates(
  params: CertificateFilterParams
): Promise<CertificateListResult> {
  const response = await axiosClient.get<CertificatePaginatedResponse>('/certificates', { params });

  return {
    data: response.data.data,
    meta: response.data.meta,
    formDeps: response.data.form_dependencies ?? getDefaultFormDeps()
  };
}

/** Builds multipart certificate payloads for create and edit flows. */
export function buildCertificateFormData(data: CertificateForm, projectId: number): FormData;
export function buildCertificateFormData(
  data: CertificateForm | CertificateEditForm,
  projectId?: number
): FormData;
export function buildCertificateFormData(
  data: CertificateForm | CertificateEditForm,
  projectId?: number
): FormData {
  const fd = new FormData();
  const resolvedProjectId = projectId ?? data.project_id;

  appendScalar(fd, 'project_id', resolvedProjectId);
  appendScalar(fd, 'name', data.name);
  appendScalar(fd, 'no_certificate', data.no_certificate);
  appendScalar(fd, 'barang_certificate_id', data.barang_certificate_id);
  appendScalar(fd, 'status', data.status);
  fd.append('date_of_issue', data.date_of_issue ?? '');
  fd.append('date_of_expired', data.date_of_expired ?? '');

  appendAttachments(fd, data);

  return fd;
}

/** Creates a certificate through the Laravel API. */
export async function createCertificate(
  data: CertificateForm,
  projectId?: number
): Promise<Certificate> {
  const formData = buildCertificateFormData(data, projectId);
  const response = await axiosClient.post<ResourceResponse<Certificate>>(
    '/certificates',
    formData,
    {
      headers: MULTIPART_HEADERS
    }
  );

  return response.data.data;
}

/** Updates a certificate through multipart POST with Laravel method override. */
export async function updateCertificate(
  id: number,
  data: CertificateForm | CertificateEditForm,
  projectId?: number
): Promise<Certificate> {
  const formData = buildCertificateFormData(data, projectId);
  formData.append('_method', 'PUT');

  const response = await axiosClient.post<ResourceResponse<Certificate>>(
    `/certificates/${id}`,
    formData,
    {
      headers: MULTIPART_HEADERS
    }
  );

  return response.data.data;
}

/** Deletes a certificate by id. */
export async function deleteCertificate(id: number): Promise<void> {
  await axiosClient.delete(`/certificates/${id}`);
}
