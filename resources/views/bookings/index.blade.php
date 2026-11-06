@extends('layouts.app')

@section('title', 'Daftar Event')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Daftar Event</h1>
        </div>
        <div class="actions">
            <a href="{{ route('bookings.export.index', request()->query()) }}" id="exportExcelBtn" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:-3px;" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Excel
            </a>
            <a href="{{ route('bookings.create') }}" class="btn primary">+ Tambah Event</a>
        </div>
    </div>

    <section class="panel filters" style="margin-bottom:14px">
        <form id="filterForm" method="GET" action="{{ route('bookings.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;padding:16px">
            <div class="field" style="min-width:200px;margin:0">
                <label for="filterSearch">Cari</label>
                <input type="search" id="filterSearch" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama event, kode, client, venue..." enterkeyhint="search">
            </div>
            <div class="field" style="min-width:180px;margin:0">
                <label for="filterDestination">Destinasi</label>
                <select id="filterDestination" name="destination_id">
                    <option value="">Semua Destinasi</option>
                    @foreach ($destinations as $d)
                        <option value="{{ $d->id }}" @selected(($filters['destination_id'] ?? null) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:180px;margin:0">
                <label for="filterVenue">Venue</label>
                <select id="filterVenue" name="venue_id">
                    <option value="">Semua Venue</option>
                    @foreach ($venues as $v)
                        <option value="{{ $v->id }}" data-destination-id="{{ $v->destination_id }}" @selected(($filters['venue_id'] ?? null) == $v->id)>{{ $v->destination->name }} · {{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:150px;margin:0">
                <label for="filterStatus">Status</label>
                <select id="filterStatus" name="status">
                    <option value="">Semua Status</option>
                    @foreach (['hold' => 'Hold', 'tentative' => 'Tentative', 'fix' => 'Fix', 'cancelled' => 'Cancelled', 'maintenance' => 'Maintenance'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="min-width:180px;margin:0">
                <label for="filterPic">PIC Sales</label>
                <select id="filterPic" name="pic_user_id">
                    <option value="">Semua PIC</option>
                    @foreach ($salesUsers as $u)
                        <option value="{{ $u->id }}" @selected(($filters['pic_user_id'] ?? null) == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('bookings.index') }}" id="filterReset" class="btn btn-reset">Reset</a>
        </form>
    </section>

    <section class="panel table-card" id="eventsTableCard" aria-live="polite">
        <table class="table">
            <thead>
                <tr><th>Kode</th><th>Tanggal</th><th>Destinasi</th><th>Venue</th><th>Nama Event</th><th>Harga</th><th>Status</th><th>PIC</th></tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr tabindex="0" role="link" aria-label="Buka detail {{ $booking->title }}" onclick="window.location='{{ route('bookings.show', $booking) }}'" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location='{{ route('bookings.show', $booking) }}';}" style="cursor:pointer">
                        <td>{{ $booking->code }}</td>
                        <td>{{ $booking->start_date->translatedFormat('d M Y') }}@if(!$booking->start_date->eq($booking->end_date)) &ndash; {{ $booking->end_date->translatedFormat('d M Y') }}@endif</td>
                        <td>{{ $booking->venue->destination->name }}</td>
                        <td>{{ $booking->venue->name }}</td>
                        <td>{{ $booking->title }}</td>
                        <td>{{ $booking->price !== null ? 'Rp '.number_format($booking->price, 0, ',', '.') : '-' }}</td>
                        <td><span class="pill {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                        <td>{{ $booking->pic?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="color:var(--muted)">Belum ada event yang cocok dengan filter ini. Klik "Reset" untuk menampilkan seluruh jadwal atau "+ Tambah Event" untuk membuat baru.</td></tr>
                @endforelse
            </tbody>
        </table>

        @include('partials.pagination', ['paginator' => $bookings])
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('filterForm');
            const searchInput = document.getElementById('filterSearch');
            const destSelect = document.getElementById('filterDestination');
            const venueSelect = document.getElementById('filterVenue');
            const statusSelect = document.getElementById('filterStatus');
            const picSelect = document.getElementById('filterPic');
            const resetBtn = document.getElementById('filterReset');
            const exportExcelBtn = document.getElementById('exportExcelBtn');
            const tableCard = document.getElementById('eventsTableCard');

            let abortController = null;
            let debounceTimer = null;

            function updateVenueOptions() {
                const selectedDest = destSelect.value;
                let selectedVenueValid = false;

                Array.from(venueSelect.options).forEach((opt, idx) => {
                    if (idx === 0) return;
                    const optDestId = opt.getAttribute('data-destination-id');
                    const show = !selectedDest || optDestId === selectedDest;
                    opt.hidden = !show;
                    opt.style.display = show ? '' : 'none';
                    if (show && opt.value === venueSelect.value) {
                        selectedVenueValid = true;
                    }
                });

                if (venueSelect.value && !selectedVenueValid) {
                    venueSelect.value = '';
                }
            }

            updateVenueOptions();

            function buildUrl(page = null) {
                const url = new URL('{{ route('bookings.index') }}', window.location.origin);
                const q = searchInput.value.trim();
                if (q) url.searchParams.set('q', q);
                if (destSelect.value) url.searchParams.set('destination_id', destSelect.value);
                if (venueSelect.value) url.searchParams.set('venue_id', venueSelect.value);
                if (statusSelect.value) url.searchParams.set('status', statusSelect.value);
                if (picSelect.value) url.searchParams.set('pic_user_id', picSelect.value);
                if (page && page > 1) url.searchParams.set('page', page);
                return url.toString();
            }

            function updateExportUrl() {
                if (!exportExcelBtn) return;
                const exportUrl = new URL('{{ route('bookings.export.index') }}', window.location.origin);
                const q = searchInput.value.trim();
                if (q) exportUrl.searchParams.set('q', q);
                if (destSelect.value) exportUrl.searchParams.set('destination_id', destSelect.value);
                if (venueSelect.value) exportUrl.searchParams.set('venue_id', venueSelect.value);
                if (statusSelect.value) exportUrl.searchParams.set('status', statusSelect.value);
                if (picSelect.value) exportUrl.searchParams.set('pic_user_id', picSelect.value);
                exportExcelBtn.href = exportUrl.toString();
            }

            async function loadTable(url, pushState = true) {
                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                tableCard.classList.add('is-loading');

                try {
                    const res = await fetch(url, {
                        signal: abortController.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    });

                    if (!res.ok) throw new Error('Response error: ' + res.status);

                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newCard = doc.getElementById('eventsTableCard');

                    if (newCard) {
                        tableCard.innerHTML = newCard.innerHTML;
                    }

                    if (pushState) {
                        window.history.pushState(null, '', url);
                    }
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Filter request failed:', err);
                        if (pushState) window.location.href = url;
                    }
                } finally {
                    tableCard.classList.remove('is-loading');
                }
            }

            function applyFilters() {
                const url = buildUrl();
                updateExportUrl();
                loadTable(url, true);
            }

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    applyFilters();
                }, 350);
            });

            filterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                applyFilters();
            });

            destSelect.addEventListener('change', function () {
                updateVenueOptions();
                applyFilters();
            });

            [venueSelect, statusSelect, picSelect].forEach(function (select) {
                select.addEventListener('change', function () {
                    applyFilters();
                });
            });

            resetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                searchInput.value = '';
                destSelect.value = '';
                updateVenueOptions();
                venueSelect.value = '';
                statusSelect.value = '';
                picSelect.value = '';
                applyFilters();
            });

            tableCard.addEventListener('click', function (e) {
                const link = e.target.closest('.pagination a');
                if (link && link.href) {
                    e.preventDefault();
                    loadTable(link.href, true);
                }
            });

            window.addEventListener('popstate', function () {
                const currentUrl = new URL(window.location.href);
                searchInput.value = currentUrl.searchParams.get('q') || '';
                destSelect.value = currentUrl.searchParams.get('destination_id') || '';
                updateVenueOptions();
                venueSelect.value = currentUrl.searchParams.get('venue_id') || '';
                statusSelect.value = currentUrl.searchParams.get('status') || '';
                picSelect.value = currentUrl.searchParams.get('pic_user_id') || '';
                updateExportUrl();
                loadTable(window.location.href, false);
            });
        });
    </script>

@endsection
