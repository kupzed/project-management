import type { ExistingAttachment, Project } from '$lib/types';

export type ActivityModalForm = {
  name: string;
  short_desc: string;
  description: string;
  project_id: string | number | '';
  kategori: string;
  activity_date: string;
  jenis: string;
  mitra_id?: number | string | '' | null;
  from?: string;
  to?: string;
  value: number | string;
  attachments: File[];
  attachment_names: string[];
  attachment_descriptions: string[];
  existing_attachments?: Array<
    Omit<ExistingAttachment, 'path' | 'url'> & { path?: string; url?: string | null }
  >;
  removed_existing_ids?: number[];
};

export type ActivityModalEditForm = ActivityModalForm & {
  existing_attachments: ExistingAttachment[];
  removed_existing_ids: number[];
};

export type ProjectOption = Pick<Project, 'id' | 'name' | 'mitra_id' | 'mitra'>;
export type VendorOption = { id: number; nama: string };

export function makeDefaultActivityForm(): ActivityModalForm {
  return {
    name: '',
    short_desc: '',
    description: '',
    project_id: '',
    kategori: '',
    value: 0,
    activity_date: '',
    jenis: '',
    mitra_id: '',
    from: '',
    to: '',
    attachments: [],
    attachment_names: [],
    attachment_descriptions: []
  };
}
