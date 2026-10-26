<header class="app-topbar">
    <div class="topbar-left">
        <button type="button" class="mobile-nav-toggle" onclick="toggleMobileNav()" aria-label="Buka menu navigasi" title="Menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="4" y1="6" x2="20" y2="6"/>
                <line x1="4" y1="12" x2="20" y2="12"/>
                <line x1="4" y1="18" x2="20" y2="18"/>
            </svg>
        </button>
        <div class="topbar-context">
            @if (auth()->user()->isAdmin())
                <span class="user-dest-tag admin-tag" title="Hak Akses: Seluruh Kawasan Destinasi">
                    <span class="dest-dot"></span> Seluruh Destinasi
                </span>
            @elseif (auth()->user()->destination)
                <span class="user-dest-tag sales-tag" title="Destinasi Penugasan: {{ auth()->user()->destination->name }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-2px; margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ auth()->user()->destination->name }}
                </span>
            @else
                <span class="user-dest-tag warning-tag" title="Akun belum memiliki destinasi penugasan">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-2px; margin-right:4px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>Belum ada destinasi
                </span>
            @endif
        </div>
    </div>

    <div class="topbar-actions">
        @include('partials.notification-bell')
    </div>
</header>
