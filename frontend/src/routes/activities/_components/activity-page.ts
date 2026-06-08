import type {
  Activity,
  ActivityForm,
  ExistingAttachment,
  Option,
  ProjectSummary
} from '$lib/types';

export type View = 'table' | 'list';
export type NamedOption = { id: number; nama: string; email: string | null };
export type ActivityProjectOption = ProjectSummary & {
  mitra_id: number | null;
  mitra?: NamedOption | null;
};
export type ActivityModalForm = Omit<
  ActivityForm,
  'attachment_descriptions' | 'short_desc' | 'from' | 'to' | 'value'
> & {
  short_desc: string;
  from: string;
  to: string;
  value: number | string;
  mitra_id: number | string | '' | null;
  attachment_descriptions: string[];
};
export type ActivityModalEditForm = ActivityModalForm & {
  existing_attachments: ExistingAttachment[];
  removed_existing_ids: number[];
};

function optionName(option: Option): string {
  return option.nama ?? option.name ?? option.title ?? option.no_seri ?? String(option.id);
}

export function toNamedOptions(options: Option[]): NamedOption[] {
  return options.map((option) => ({ id: option.id, nama: optionName(option), email: null }));
}

function normalizeExistingAttachments(attachments: Activity['attachments']): ExistingAttachment[] {
  return (attachments ?? []).flatMap((attachment) => {
    if (typeof attachment.id !== 'number') return [];
    return [
      {
        id: attachment.id,
        name: attachment.name,
        description: attachment.description ?? null,
        size: attachment.size ?? null,
        sizeLabel: attachment.sizeLabel ?? null,
        path: attachment.path,
        url: attachment.url,
        original_name: attachment.name
      }
    ];
  });
}

export function makeActivityForm(activity?: Activity): ActivityModalEditForm {
  return {
    name: activity?.name ?? '',
    short_desc: activity?.short_desc ?? '',
    description: activity?.description ?? '',
    project_id: activity?.project_id ?? '',
    kategori: activity?.kategori ?? '',
    value: activity?.value ?? 0,
    activity_date: activity?.activity_date
      ? new Date(activity.activity_date).toISOString().split('T')[0]
      : '',
    jenis: activity?.jenis ?? '',
    mitra_id: activity?.mitra_id ?? null,
    from: activity?.from ?? '',
    to: activity?.to ?? '',
    attachments: [],
    attachment_names: [],
    attachment_descriptions: [],
    existing_attachments: normalizeExistingAttachments(activity?.attachments ?? []),
    removed_existing_ids: []
  };
}

export function withProjectMitra(
  projects: Array<ProjectSummary & { mitra_id: number | null }>,
  vendors: NamedOption[],
  customers: NamedOption[]
): ActivityProjectOption[] {
  const mitraMap = new Map<number, NamedOption>();
  vendors.forEach((vendor) => mitraMap.set(vendor.id, vendor));
  customers.forEach((customer) => mitraMap.set(customer.id, customer));

  return projects.map((project) => ({
    ...project,
    mitra: project.mitra_id ? (mitraMap.get(project.mitra_id) ?? null) : null
  }));
}
