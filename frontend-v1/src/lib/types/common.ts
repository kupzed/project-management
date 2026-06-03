// Shared types yang dipakai lintas domain
export type ActionResult<T = null> = {
  success: boolean;
  message?: string;
  data?: T;
  error?: string;
};

export type PaginationMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type PaginatedResponse<T> = {
  data: T[];
  meta: PaginationMeta;
  form_dependencies?: Record<string, unknown>;
};

export type SortOrder = 'asc' | 'desc';

export type DateRangeFilter = {
  from?: string;
  to?: string;
};

export type Option = {
  id: number;
  name?: string;
  nama?: string;
  title?: string;
  no_seri?: string;
};
