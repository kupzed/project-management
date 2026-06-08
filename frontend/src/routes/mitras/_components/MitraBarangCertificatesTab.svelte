<script lang="ts">
  import { onMount } from 'svelte';
  import Drawer from '$lib/components/Drawer.svelte';
  import Pagination from '$lib/components/Pagination.svelte';
  import EmptyState from '$lib/components/common/EmptyState.svelte';
  import LoadingState from '$lib/components/common/LoadingState.svelte';
  import { confirm } from '$lib/components/common/ConfirmDialog.svelte';
  import BarangCertificatesDetail from '$lib/components/detail/BarangCertificatesDetail.svelte';
  import BarangCertificateFormModal from '$lib/components/form/BarangCertificateFormModal.svelte';
  import RowActionButtons from '$lib/components/ui/RowActionButtons.svelte';
  import SearchInput from '$lib/components/ui/SearchInput.svelte';
  import ViewToggle from '$lib/components/ui/ViewToggle.svelte';
  import { DEFAULT_PER_PAGE, PER_PAGE_OPTIONS } from '$lib/constants';
  import {
    createBarangCertificate,
    deleteBarangCertificate,
    fetchBarangCertificates,
    updateBarangCertificate
  } from '$lib/services/barangCertificateService';
  import { userPermissions } from '$lib/stores/permissions';
  import { extractApiErrors } from '$lib/utils/errors';
  import { showError, showSuccess } from '$lib/utils/toast';
  import type { BarangCertificate, BarangCertificateForm, Mitra, SortOrder } from '$lib/types';

  type View = 'table' | 'list';
  type BarangCertificateModalForm = Omit<BarangCertificateForm, 'mitra_id'> & {
    mitra_id: number | '' | null;
  };

  /**
   * Barang certificate tab scoped to a single mitra.
   */
  let { mitra }: { mitra: Mitra } = $props();

  function makeForm(item?: BarangCertificate): BarangCertificateModalForm {
    return {
      name: item?.name ?? '',
      no_seri: item?.no_seri ?? '',
      mitra_id: mitra.id
    };
  }

  let items = $state<BarangCertificate[]>([]);
  let loading = $state(true);
  let error = $state('');
  let search = $state('');
  let currentPage = $state(1);
  let lastPage = $state(1);
  let totalItems = $state(0);
  let perPage = $state(DEFAULT_PER_PAGE);
  let sortBy = $state<'created'>('created');
  let sortDir = $state<SortOrder>('desc');
  let activeView = $state<View>('table');
  let showCreateModal = $state(false);
  let showEditModal = $state(false);
  let showDetailDrawer = $state(false);
  let editingItem = $state<BarangCertificate | null>(null);
  let selectedItem = $state<BarangCertificate | null>(null);
  let form = $state<BarangCertificateModalForm>(makeForm());

  let canCreate = $derived(($userPermissions ?? []).includes('bc-create'));
  let canUpdate = $derived(($userPermissions ?? []).includes('bc-update'));
  let canDelete = $derived(($userPermissions ?? []).includes('bc-delete'));

  async function fetchList(): Promise<void> {
    loading = true;
    error = '';
    try {
      const result = await fetchBarangCertificates({
        search: search || undefined,
        mitra_id: mitra.id,
        page: currentPage,
        per_page: perPage,
        sort_by: sortBy,
        sort_dir: sortDir
      });
      items = result.data;
      currentPage = result.meta.current_page;
      lastPage = result.meta.last_page;
      totalItems = result.meta.total;
    } catch (err) {
      error = extractApiErrors(err);
    } finally {
      loading = false;
    }
  }

  function applyFilter(): void {
    currentPage = 1;
    void fetchList();
  }

  function goToPage(page: number): void {
    if (page > 0 && page <= lastPage) {
      currentPage = page;
      void fetchList();
    }
  }

  function changePerPage(nextPerPage: number): void {
    perPage = nextPerPage;
    currentPage = 1;
    void fetchList();
  }

  function openCreateModal(): void {
    if (!canCreate) return;
    form = makeForm();
    showCreateModal = true;
  }

  function openEditModal(item: BarangCertificate): void {
    if (!canUpdate) return;
    editingItem = item;
    form = makeForm(item);
    showEditModal = true;
  }

  function openDetailDrawer(item: BarangCertificate): void {
    selectedItem = item;
    showDetailDrawer = true;
  }

  async function handleSubmitCreate(): Promise<void> {
    if (!canCreate) return;
    try {
      form.mitra_id = mitra.id;
      await createBarangCertificate(form as BarangCertificateForm);
      showCreateModal = false;
      showSuccess('Data berhasil ditambahkan');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleSubmitUpdate(): Promise<void> {
    if (!editingItem?.id || !canUpdate) return;
    try {
      form.mitra_id = mitra.id;
      await updateBarangCertificate(editingItem.id, form as BarangCertificateForm);
      showEditModal = false;
      showSuccess('Data berhasil diperbarui');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  async function handleDelete(id: number): Promise<void> {
    if (!canDelete) return;
    const accepted = await confirm({
      title: 'Hapus barang certificate?',
      text: 'Data barang certificate akan dihapus permanen.',
      confirmText: 'Hapus',
      isDangerous: true
    });
    if (!accepted) return;

    try {
      await deleteBarangCertificate(id);
      showSuccess('Data berhasil dihapus');
      await fetchList();
    } catch (err) {
      showError(extractApiErrors(err));
    }
  }

  onMount(() => {
    void fetchList();
  });
</script>

<div class="mb-8">
  <div
    class="mb-4 flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4"
  >
    <div class="flex w-full space-x-2 sm:w-auto">
      <select
        bind:value={sortDir}
        onchange={applyFilter}
        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900
               sm:w-auto dark:border-gray-700 dark:bg-neutral-900 dark:text-gray-100"
        aria-label="Sortir create"
      >
        <option value="desc">Sortir: Create Terbaru</option>
        <option value="asc">Sortir: Create Terlama</option>
      </select>
    </div>

    <div class="w-full flex-grow sm:w-auto">
      <SearchInput
        bind:value={search}
        placeholder="Cari barang certificate..."
        onSearch={applyFilter}
      />
    </div>

    {#if canCreate}
      <button
        type="button"
        onclick={openCreateModal}
        class="w-full rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm
               hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none
               sm:w-auto dark:focus:ring-offset-gray-800"
      >
        Tambah Barang
      </button>
    {/if}
  </div>

  <div class="mb-4 flex items-center justify-between">
    <ViewToggle bind:activeView />
  </div>

  {#snippet pagination()}
    <Pagination
      {currentPage}
      {lastPage}
      {totalItems}
      itemsPerPage={perPage}
      perPageOptions={[...PER_PAGE_OPTIONS]}
      onPageChange={goToPage}
      onPerPageChange={changePerPage}
    />
  {/snippet}

  {#if loading}
    <LoadingState label="Memuat barang certificate..." />
  {:else if error}
    <p class="text-red-500">{error}</p>
  {:else if items.length === 0}
    <EmptyState title="Belum ada data." />
  {:else if activeView === 'list'}
    <div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-black">
      <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        {#each items as item (item.id)}
          <li>
            <button
              type="button"
              class="block w-full px-4 py-4 text-left hover:bg-gray-50 sm:px-6 dark:hover:bg-neutral-950"
              onclick={() => openDetailDrawer(item)}
            >
              <p class="truncate text-sm font-medium text-indigo-600 dark:text-indigo-400">
                {item.name}
              </p>
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-300">
                No. Seri: {item.no_seri} | Mitra: {mitra.nama}
              </p>
            </button>
            <div class="flex justify-end px-4 py-2 sm:px-6">
              <RowActionButtons
                label={item.name}
                canEdit={canUpdate}
                {canDelete}
                onDetail={() => openDetailDrawer(item)}
                onEdit={() => openEditModal(item)}
                onDelete={() => handleDelete(item.id)}
              />
            </div>
          </li>
        {/each}
      </ul>
      {@render pagination()}
    </div>
  {:else}
    <div class="bg-white shadow-md sm:rounded-lg dark:bg-black">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-neutral-900">
            <tr>
              <th
                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
                >Nama Barang</th
              >
              <th
                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
                >No. Seri</th
              >
              <th
                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
                >Mitra</th
              >
              <th
                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100"
                >Aksi</th
              >
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-black">
            {#each items as item (item.id)}
              <tr>
                <td
                  class="px-3 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100"
                >
                  <button
                    type="button"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                    onclick={() => openDetailDrawer(item)}
                  >
                    {item.name}
                  </button>
                </td>
                <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
                  {item.no_seri}
                </td>
                <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
                  {mitra.nama}
                </td>
                <td class="relative px-3 py-4 text-sm whitespace-nowrap">
                  <RowActionButtons
                    label={item.name}
                    canEdit={canUpdate}
                    {canDelete}
                    onDetail={() => openDetailDrawer(item)}
                    onEdit={() => openEditModal(item)}
                    onDelete={() => handleDelete(item.id)}
                  />
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
      {@render pagination()}
    </div>
  {/if}
</div>

<BarangCertificateFormModal
  bind:show={showCreateModal}
  title="Tambah Barang Certificate"
  submitLabel="Simpan"
  idPrefix="bc_create"
  bind:form
  showMitra={false}
  onSubmit={handleSubmitCreate}
/>

{#if editingItem}
  <BarangCertificateFormModal
    bind:show={showEditModal}
    title="Edit Barang Certificate"
    submitLabel="Update"
    idPrefix="bc_edit"
    bind:form
    showMitra={false}
    onSubmit={handleSubmitUpdate}
  />
{/if}

<Drawer
  bind:show={showDetailDrawer}
  title="Detail Barang Certificate"
  onClose={() => (showDetailDrawer = false)}
>
  <BarangCertificatesDetail barangCertificates={selectedItem} />
</Drawer>
