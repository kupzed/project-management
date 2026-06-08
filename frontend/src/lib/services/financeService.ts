import axiosClient from '$lib/axiosClient';
import type { Project } from '$lib/types';

export type FinanceReportMode = 'month' | 'project';

export type FinanceProjectOption = Pick<Project, 'id' | 'name'>;

export type FinanceAttachmentObject = {
  path?: string | null;
  file_path?: string | null;
  url?: string | null;
  name?: string | null;
  label?: string | null;
  description?: string | null;
  desc?: string | null;
  sizeLabel?: string | null;
  size?: number | string | null;
};

export type FinanceItem = {
  id: number;
  kategori?: string | null;
  activity_date?: string | null;
  name?: string | null;
  short_desc?: string | null;
  value?: number | string | null;
  value_formatted?: string | null;
  project?: FinanceProjectOption | null;
  jenis?: string | null;
  mitra?: { nama: string } | null;
  description?: string | null;
  attachments?: unknown;
};

export type FinanceReportMeta = {
  total_records: number;
  total_value: number;
  period?: string;
  project?: FinanceProjectOption | null;
  filters?: Record<string, unknown>;
};

export type FinanceReportParams = {
  type: FinanceReportMode;
  month?: number;
  year?: number;
  project_id?: number;
  start_date?: string;
  end_date?: string;
};

export type FinanceReportResponse = {
  data: FinanceItem[];
  meta: FinanceReportMeta;
};

export type FinanceSavedPayload = {
  activityId: number;
  value: number | string | null;
  value_formatted?: string;
  item: FinanceItem;
};

type FinanceUpdateResponse = {
  data: FinanceItem;
  meta?: {
    value_formatted?: string;
  };
};

type ProjectListResponse = {
  data?: FinanceProjectOption[];
};

const defaultMeta: FinanceReportMeta = {
  total_records: 0,
  total_value: 0,
  period: '',
  project: null,
  filters: {}
};

/** Fetches finance report rows for month or project mode. */
export async function fetchFinanceReport(
  params: FinanceReportParams
): Promise<FinanceReportResponse> {
  const response = await axiosClient.get<FinanceReportResponse>('/finance', { params });

  return {
    data: response.data.data ?? [],
    meta: response.data.meta ?? defaultMeta
  };
}

/** Fetches projects for the finance project filter. */
export async function fetchFinanceProjects(): Promise<FinanceProjectOption[]> {
  const response = await axiosClient.get<ProjectListResponse | FinanceProjectOption[]>(
    '/projects',
    {
      params: { per_page: 500 }
    }
  );

  const payload = Array.isArray(response.data) ? response.data : (response.data.data ?? []);

  return payload.map((project) => ({
    id: project.id,
    name: project.name
  }));
}

/** Updates a finance value and returns the normalized saved payload. */
export async function updateFinanceValue(
  activityId: number,
  value: number
): Promise<FinanceSavedPayload> {
  const response = await axiosClient.put<FinanceUpdateResponse>(`/finance/${activityId}`, {
    value
  });

  return {
    activityId,
    value: response.data.data.value ?? null,
    value_formatted: response.data.meta?.value_formatted,
    item: response.data.data
  };
}
