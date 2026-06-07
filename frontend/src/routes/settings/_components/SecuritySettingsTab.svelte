<script lang="ts">
  type PasswordDraft = { current: string; next: string; confirm: string };
  type PasswordVisibility = { current: boolean; next: boolean; confirm: boolean };
  type PasswordRules = {
    len8: boolean;
    hasLower: boolean;
    hasUpper: boolean;
    notSameAsOld: boolean;
    confirmMatch: boolean;
  };

  /** Password update form and validation summary. */
  let {
    password = $bindable(),
    visibility = $bindable(),
    rules,
    saving,
    canUpdate,
    onUpdate
  }: {
    password: PasswordDraft;
    visibility: PasswordVisibility;
    rules: PasswordRules;
    saving: boolean;
    canUpdate: boolean;
    onUpdate: () => void;
  } = $props();

  const fields = [
    {
      key: 'current',
      label: 'Password Lama',
      placeholder: 'Masukkan password lama',
      autocomplete: 'current-password'
    },
    {
      key: 'next',
      label: 'Password Baru',
      placeholder: 'Masukkan password baru',
      autocomplete: 'new-password'
    },
    {
      key: 'confirm',
      label: 'Konfirmasi Password Baru',
      placeholder: 'Masukkan konfirmasi password baru',
      autocomplete: 'new-password'
    }
  ] as const;
</script>

<div id="panel-keamanan" role="tabpanel" aria-labelledby="tab-keamanan">
  <div class="rounded-lg bg-white px-4 py-4 sm:px-6 lg:px-8 dark:bg-black">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <h2 class="text-base/7 font-semibold text-gray-900 dark:text-white">Ubah Password</h2>
        <p class="mt-1 text-sm/6 text-gray-600 dark:text-gray-300">
          Ubah Password dengan memasukkan password lama dan password baru
        </p>
        <div class="mt-6 space-y-5">
          {#each fields as field (field.key)}
            <div>
              <span class="block text-sm font-medium text-gray-900 dark:text-gray-100"
                >{field.label}</span
              >
              <div class="relative mt-2">
                <input
                  type={visibility[field.key] ? 'text' : 'password'}
                  class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 dark:bg-neutral-900 dark:text-gray-100 dark:outline-gray-700"
                  placeholder={field.placeholder}
                  bind:value={password[field.key]}
                  autocomplete={field.autocomplete}
                />
                <button
                  type="button"
                  class="absolute inset-y-0 right-2 my-auto rounded-md p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400"
                  onclick={() =>
                    (visibility = { ...visibility, [field.key]: !visibility[field.key] })}
                  aria-label={visibility[field.key]
                    ? `Sembunyikan ${field.label.toLowerCase()}`
                    : `Tampilkan ${field.label.toLowerCase()}`}
                >
                  {#if visibility[field.key]}
                    <svg
                      class="size-5"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3.98 8.223A10.48 10.48 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.52 10.52 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                      />
                    </svg>
                  {:else}
                    <svg
                      class="size-5"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.5"
                      aria-hidden="true"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M2.036 12.322a1.01 1.01 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                      />
                    </svg>
                  {/if}
                </button>
              </div>
              {#if field.key === 'confirm' && password.confirm && !rules.confirmMatch}
                <p class="mt-2 text-xs text-red-600 dark:text-red-400">Konfirmasi tidak sama.</p>
              {/if}
            </div>
          {/each}
          <div class="pt-2">
            <button
              type="button"
              class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-60"
              onclick={onUpdate}
              disabled={!canUpdate}
            >
              {saving ? 'Memperbarui...' : 'Update Password'}
            </button>
          </div>
        </div>
      </div>
      <aside class="lg:col-span-1">
        <div class="rounded-xl bg-teal-600/90 p-5 text-white dark:bg-teal-700">
          <h3 class="text-lg font-semibold">Persyaratan Password</h3>
          <p class="mt-1 text-sm opacity-90">
            Untuk membuat password yang kuat, ikuti aturan berikut:
          </p>
          <ul class="mt-4 space-y-2 text-sm">
            <li>{rules.len8 ? 'OK' : '-'} Minimal 8 karakter</li>
            <li>{rules.hasLower ? 'OK' : '-'} Minimal satu huruf kecil</li>
            <li>{rules.hasUpper ? 'OK' : '-'} Minimal satu huruf besar</li>
            <li>{rules.notSameAsOld ? 'OK' : '-'} Tidak sama dengan password lama</li>
            <li>{rules.confirmMatch ? 'OK' : '-'} Konfirmasi harus sama</li>
          </ul>
        </div>
      </aside>
    </div>
  </div>
</div>
