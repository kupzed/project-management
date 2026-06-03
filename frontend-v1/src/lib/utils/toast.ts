import { browser } from '$app/environment';
import Swal, { type SweetAlertIcon } from 'sweetalert2';

type ToastOptions = {
  message: string;
  icon: SweetAlertIcon;
  timer?: number;
};

function isDarkMode(): boolean {
  return browser && document.documentElement.classList.contains('dark');
}

/** Shows a SweetAlert toast with shared dark mode styling. */
function showToast({ message, icon, timer = 3000 }: ToastOptions): void {
  if (!browser) {
    return;
  }

  const darkMode = isDarkMode();
  void Swal.fire({
    text: message,
    icon,
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer,
    timerProgressBar: true,
    background: darkMode ? '#171717' : '#ffffff',
    color: darkMode ? '#f5f5f5' : '#111827'
  });
}

/** Shows a success toast. */
export function showSuccess(message: string): void {
  showToast({ message, icon: 'success' });
}

/** Shows an error toast. */
export function showError(message: string): void {
  showToast({ message, icon: 'error', timer: 4000 });
}

/** Shows an informational toast. */
export function showInfo(message: string): void {
  showToast({ message, icon: 'info' });
}
