import type { ExistingAttachment } from '$lib/types';

export type AttachmentPayload = {
	attachments?: File[];
	attachment_names?: Array<string | null | undefined>;
	attachment_descriptions?: Array<string | null | undefined>;
	existing_attachments?: ExistingAttachment[];
	removed_existing_ids?: number[];
};

/** Appends non-empty scalar values to FormData using Laravel-friendly strings. */
export function appendScalar(fd: FormData, key: string, val: unknown): void {
	if (val === null || val === undefined || val === '') {
		return;
	}

	fd.append(key, String(val));
}

/** Appends new, existing, and removed attachment payloads to FormData. */
export function appendAttachments(fd: FormData, data: AttachmentPayload): void {
	data.attachments?.forEach((file, index) => {
		fd.append(`attachments[${index}]`, file);
	});

	data.attachment_names?.forEach((name, index) => {
		if (name !== null && name !== undefined) {
			fd.append(`attachment_names[${index}]`, name);
		}
	});

	data.attachment_descriptions?.forEach((description, index) => {
		if (description !== null && description !== undefined) {
			fd.append(`attachment_descriptions[${index}]`, description);
		}
	});

	data.existing_attachments?.forEach((attachment, index) => {
		fd.append(`existing_attachment_ids[${index}]`, String(attachment.id));
		fd.append(`existing_attachment_names[${index}]`, attachment.name ?? '');
		fd.append(`existing_attachment_descriptions[${index}]`, attachment.description ?? '');
	});

	data.removed_existing_ids?.forEach((id) => {
		fd.append('removed_existing_ids[]', String(id));
	});
}
