import type { SortOrder } from './common';

export type MitraCategory = 'pribadi' | 'perusahaan' | 'customer' | 'vendor';

export type MitraKategoriOption = {
  value: MitraCategory;
  label: string;
};

export type Mitra = {
  id: number;
  nama: string;
  is_pribadi: boolean;
  is_perusahaan: boolean;
  is_customer: boolean;
  is_vendor: boolean;
  alamat: string;
  website: string | null;
  email: string | null;
  kontak_1: string | null;
  kontak_1_nama: string | null;
  kontak_1_jabatan: string | null;
  kontak_2_nama: string | null;
  kontak_2: string | null;
  kontak_2_jabatan: string | null;
  created_at: string;
  updated_at: string;
};

export type Customer = Mitra & {
  is_customer: true;
};

export type Vendor = Mitra & {
  is_vendor: true;
};

export type MitraSummary = Pick<Mitra, 'id' | 'nama'>;

export type MitraContactSummary = Pick<Mitra, 'id' | 'nama' | 'email'> & {
  phone?: string | null;
};

export type MitraForm = {
  nama: string;
  is_pribadi?: boolean;
  is_perusahaan?: boolean;
  is_customer?: boolean;
  is_vendor?: boolean;
  alamat: string;
  website?: string | null;
  email?: string | null;
  kontak_1?: string | null;
  kontak_1_nama?: string | null;
  kontak_1_jabatan?: string | null;
  kontak_2_nama?: string | null;
  kontak_2?: string | null;
  kontak_2_jabatan?: string | null;
};

export type MitraFilterParams = {
  search?: string;
  kategori?: MitraCategory | '';
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
  sort_by?: 'created';
  sort_dir?: SortOrder;
};

export type MitraFormDependencies = {
  kategori_options: MitraKategoriOption[];
};
