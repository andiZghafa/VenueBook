<header class="app-topbar">
    <div class="topbar-context">
        @if (auth()->user()->isAdmin())
            <span class="user-dest-tag admin-tag" title="Hak Akses: Seluruh Kawasan Destinasi">
                <span class="dest-dot"></span> Seluruh Destinasi
            </span>
        @elseif (auth()->user()->destination)
            <span class="user-dest-tag sales-tag" title="Destinasi Penugasan: {{ auth()->user()->destination->name }}">
                <span class="dest-icon">📍</span> {{ auth()->user()->destination->name }}
            </span>
        @else
            <span class="user-dest-tag warning-tag" title="Akun belum memiliki destinasi penugasan">
                ⚠️ Belum ada destinasi
            </span>
        @endif
    </div>

    <div class="topbar-actions">
        @include('partials.notification-bell')
    </div>
</header>
