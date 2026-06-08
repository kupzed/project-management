import axiosClient from '$lib/axiosClient';
import type { ProjectStatus } from '$lib/types';

export type DashboardTotals = {
  total_projects: number;
  ongoing: number;
  prospect: number;
  complete: number;
  cancel: number;
  cert_projects: number;
  cert_active: number;
  cert_expiring_30: number;
};

export type DashboardSeries = {
  labels: string[];
  counts: number[];
};

export type DashboardProject = {
  id: number;
  name: string;
  status: ProjectStatus;
  is_cert_projects?: boolean;
  description?: string | null;
  start_date: string;
  mitra?: {
    nama?: string | null;
  } | null;
};

export type DashboardData = {
  totals?: Partial<DashboardTotals>;
  latest_projects?: DashboardProject[];
  trend_12_months?: DashboardSeries;
  status_distribution?: DashboardSeries;
  kategori_distribution?: DashboardSeries;
  top_customers?: DashboardSeries;
};

type DashboardResponse = {
  data?: DashboardData;
};

/** Fetches dashboard counters, charts, and latest project rows. */
export async function fetchDashboardData(): Promise<DashboardData> {
  const response = await axiosClient.get<DashboardResponse>('/dashboard');
  return response.data.data ?? {};
}
