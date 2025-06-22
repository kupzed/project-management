{{-- resources/views/components/sidebar-link.blade.php --}}
<a href="{{ $href }}" class="{{ $linkClasses }}">
    <svg class="{{ $iconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
    </svg>
    @if (!$collapsed)
        {{ $slot }} {{-- Ini akan merender teks seperti 'Dashboard' atau 'Project' --}}
    @endif
</a>