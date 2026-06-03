<script lang="ts">
  import { getCertificateStatusBadgeClasses, getStatusBadgeClasses } from '$lib/utils/badges';

  /**
   * Props for status badges backed by shared project/certificate badge utilities.
   */
  let {
    status,
    type = 'project'
  }: {
    status: string;
    type?: 'project' | 'certificate';
  } = $props();

  let classes = $derived(
    type === 'certificate'
      ? getCertificateStatusBadgeClasses(status)
      : getStatusBadgeClasses(status)
  );
</script>

<span class="inline-flex rounded-full px-2 text-xs leading-5 font-semibold {classes}">
  {status}
</span>
