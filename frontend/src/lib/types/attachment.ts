/**
 * Existing attachment payload returned by Activity and Certificate accessors.
 * Legacy records can omit `id`; new relational attachment rows include it.
 */
export type Attachment = {
  id?: number;
  name: string;
  description?: string | null;
  size?: number | null;
  sizeLabel?: string | null;
  path: string;
  url: string | null;
};

export type ExistingAttachment = Attachment & {
  id: number;
  original_name?: string | null;
};

export type AttachmentUpload = {
  file: File;
  name: string;
  description?: string | null;
};

export type AttachmentEditPayload = {
  existing_attachment_ids: number[];
  existing_attachment_names: string[];
  existing_attachment_descriptions: Array<string | null>;
  removed_existing_ids: number[];
};
