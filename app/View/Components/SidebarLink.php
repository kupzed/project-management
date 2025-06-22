<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SidebarLink extends Component
{
    public $href; // Properti untuk URL tautan
    public $route; // Properti untuk nama rute Laravel
    public $routePrefix; // Properti untuk pengecekan rute aktif (misal: 'projects.*')
    public $icon; // Properti untuk path SVG icon
    public $collapsed; // Properti untuk kondisi sidebar terlipat

    /**
     * Create a new component instance.
     *
     * @param string|null $href Opsional: URL langsung. Akan diabaikan jika $route diberikan.
     * @param string|null $route Opsional: Nama rute Laravel. Jika ada, akan diprioritaskan untuk href.
     * @param string|null $routePrefix Opsional: Prefix rute untuk pengecekan aktif.
     * @param string $icon Wajib: Path SVG icon.
     * @param bool $collapsed Opsional: Menandai apakah link untuk sidebar yang terlipat.
     * @return void
     */
    public function __construct(
        $icon, // Icon sekarang menjadi parameter wajib pertama
        $route = null,
        $href = '#', // Beri nilai default untuk $href
        $routePrefix = null,
        $collapsed = false
    ) {
        $this->icon = $icon;
        $this->route = $route;
        $this->routePrefix = $routePrefix;
        $this->collapsed = $collapsed;

        // Tentukan href: jika ada route, gunakan route. Jika tidak, gunakan href yang diberikan atau '#'
        if ($this->route) {
            $this->href = route($this->route);
        } else {
            $this->href = $href;
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $isActive = false;

        if ($this->route) {
            $isActive = request()->routeIs($this->route);
        }

        // Jika ada routePrefix, gunakan itu untuk menentukan active state
        // Ini akan menimpa isActive jika routePrefix cocok.
        if ($this->routePrefix) {
            $isActive = request()->routeIs($this->routePrefix);
        }

        // Kelas dasar untuk tautan navigasi
        $baseClasses = 'group flex items-center px-2 py-2 text-sm font-medium rounded-md';
        $activeClasses = 'bg-indigo-50 text-indigo-700';
        $inactiveClasses = 'text-gray-700 hover:bg-gray-100 hover:text-gray-900';

        // Kelas dasar untuk ikon SVG
        $iconBaseClasses = 'h-6 w-6'; // Hapus mr-3 di sini karena akan diatur per kondisi
        $iconActiveClasses = 'text-indigo-600';
        $iconInactiveClasses = 'text-gray-400 group-hover:text-gray-500';


        // Tambahkan atau sesuaikan kelas berdasarkan kondisi
        if (!$this->collapsed) {
            $iconBaseClasses = 'mr-3 ' . $iconBaseClasses; // Tambahkan mr-3 jika tidak terlipat
        } else {
            $baseClasses .= ' justify-center'; // Untuk tampilan terlipat
        }

        $linkClasses = $baseClasses . ' ' . ($isActive ? $activeClasses : $inactiveClasses);
        $iconClasses = $iconBaseClasses . ' ' . ($isActive ? $iconActiveClasses : $iconInactiveClasses);

        return view('components.sidebar-link', compact('linkClasses', 'iconClasses', 'isActive'));
    }
}