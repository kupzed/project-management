import type { Certificate, CertificateForm, ExistingAttachment } from '$lib/types';

export type CertificateModalForm = Omit<CertificateForm, 'attachment_descriptions'> & {
  attachment_descriptions: string[];
  existing_attachments?: ExistingAttachment[];
  removed_existing_ids?: number[];
};

function normalizeExistingAttachments(
  attachments: Certificate['attachments']
): ExistingAttachment[] {
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

export function makeCreateCertificateForm(projectId: number): CertificateModalForm {
  return {
    name: '',
    no_certificate: '',
    project_id: projectId,
    barang_certificate_id: '',
    status: '',
    date_of_issue: '',
    date_of_expired: '',
    attachments: [],
    attachment_names: [],
    attachment_descriptions: []
  };
}

export function makeEditCertificateForm(
  projectId: number,
  certificate: Certificate
): CertificateModalForm {
  return {
    ...makeCreateCertificateForm(projectId),
    name: certificate.name,
    no_certificate: certificate.no_certificate,
    barang_certificate_id: certificate.barang_certificate_id ?? '',
    status: certificate.status,
    date_of_issue: certificate.date_of_issue ?? '',
    date_of_expired: certificate.date_of_expired ?? '',
    existing_attachments: normalizeExistingAttachments(certificate.attachments ?? []),
    removed_existing_ids: []
  };
}
