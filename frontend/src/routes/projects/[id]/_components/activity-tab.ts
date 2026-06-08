import type { Activity, ActivityForm, ExistingAttachment, Option, Project } from '$lib/types';

export type NamedOption = { id: number; nama: string };
type ProjectWithMitraFlag = Project & {
  mitra?: (Project['mitra'] & { is_customer?: boolean }) | null;
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
  return options.map((option) => ({ id: option.id, nama: optionName(option) }));
}

function projectUsesCustomer(project: Project): boolean {
  const projectWithFlag = project as ProjectWithMitraFlag;
  return Boolean(projectWithFlag.mitra?.is_customer ?? project.mitra_id);
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

export function makeCreateActivityForm(project: Project): ActivityModalForm {
  const usesCustomer = projectUsesCustomer(project);
  return {
    name: '',
    short_desc: '',
    description: '',
    project_id: project.id,
    kategori: '',
    value: 0,
    activity_date: '',
    jenis: usesCustomer ? 'Customer' : '',
    mitra_id: usesCustomer ? project.mitra_id : '',
    from: '',
    to: '',
    attachments: [],
    attachment_names: [],
    attachment_descriptions: []
  };
}

export function makeEditActivityForm(project: Project, activity?: Activity): ActivityModalEditForm {
  const createForm = makeCreateActivityForm(project);
  return {
    ...createForm,
    name: activity?.name ?? '',
    short_desc: activity?.short_desc ?? '',
    description: activity?.description ?? '',
    kategori: activity?.kategori ?? '',
    value: activity?.value ?? 0,
    activity_date: activity?.activity_date ?? '',
    jenis: activity?.jenis ?? createForm.jenis,
    mitra_id: activity?.mitra_id ?? '',
    from: activity?.from ?? '',
    to: activity?.to ?? '',
    existing_attachments: normalizeExistingAttachments(activity?.attachments ?? []),
    removed_existing_ids: []
  };
}
