@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Dashboard Venue Availability</h1>
        </div>
        <div class="actions">
            <a href="{{ route('bookings.create') }}" class="btn primary">+ Tambah Event</a>
            <a href="{{ route('bookings.index') }}" class="btn">Lihat Semua Event</a>
        </div>
    </div>

    <section class="panel filters-bar">
        <form method="GET" action="{{ route('dashboard') }}" class="filters-inline">
            <input type="hidden" name="y" value="{{ $month->year }}">
            <input type="hidden" name="m" value="{{ $month->month }}">
            @if (auth()->user()->isAdmin())
                <div class="field">
                    <label for="dashFilterDest">Destinasi</label>
                    <select id="dashFilterDest" name="destination_id" onchange="this.form.submit()">
                        <option value="">Semua Destinasi</option>
                        @foreach ($destinations as $d)
                            <option value="{{ $d->id }}" @selected(($filters['destination_id'] ?? null) == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="field">
                <label for="dashFilterVenue">Venue</label>
                <select id="dashFilterVenue" name="venue_id" onchange="this.form.submit()">
                    <option value="">Semua Venue</option>
                    @foreach ($venues as $v)
                        <option value="{{ $v->id }}" @selected(($filters['venue_id'] ?? null) == $v->id)>{{ $v->destination->name }} · {{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="dashFilterType">Jenis Event</label>
                <select id="dashFilterType" name="event_type_id" onchange="this.form.submit()">
                    <option value="">Semua Jenis Event</option>
                    @foreach ($eventTypes as $t)
                        <option value="{{ $t->id }}" @selected(($filters['event_type_id'] ?? null) == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="dashFilterStatus">Status Event</label>
                <select id="dashFilterStatus" name="status" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach (['hold' => 'Hold', 'tentative' => 'Tentative', 'fix' => 'Fix', 'cancelled' => 'Cancelled', 'maintenance' => 'Maintenance'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="dashFilterPic">PIC Sales</label>
                <select id="dashFilterPic" name="pic_user_id" onchange="this.form.submit()">
                    <option value="">Semua PIC</option>
                    @foreach ($salesUsers as $u)
                        <option value="{{ $u->id }}" @selected(($filters['pic_user_id'] ?? null) == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="dashFilterSearch">Cari</label>
                <input type="search" id="dashFilterSearch" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama event, kode, client..." enterkeyhint="search" onchange="this.form.submit()">
            </div>
            <div class="field field-actions" style="flex: 0 0 auto; margin-left: auto;">
                <a href="{{ route('dashboard', ['y' => $month->year, 'm' => $month->month]) }}" class="btn btn-reset" title="Reset Filter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </section>

    <section class="kpis">
        <div class="kpi"><small>Total Venue</small><strong>{{ $kpis['venueCount'] }}</strong><span class="up">{{ $filters['destination_id'] ?? null ? 'di destinasi terpilih' : 'di 3 destinasi' }}</span></div>
        <div class="kpi"><small>Event Bulan Ini</small><strong>{{ $kpis['eventCount'] }}</strong><span class="up">{{ $month->translatedFormat('F Y') }}</span></div>
        <div class="kpi"><small>Hold Request</small><strong>{{ $kpis['holdCount'] }}</strong><span class="up">Perlu follow-up sales</span></div>
        <div class="kpi"><small>Event Fix</small><strong>{{ $kpis['fixCount'] }}</strong><span class="up">Confirmed booking</span></div>
        <div class="kpi"><small>Tingkat Utilisasi</small><strong>{{ $kpis['utilization'] }}%</strong><span class="up">Estimasi venue-hari terpakai</span></div>
        <div class="kpi"><small>Expected Earning</small><strong>Rp {{ number_format($kpis['expectedEarning'], 0, ',', '.') }}</strong><span class="up">Fix: Rp {{ number_format($kpis['expectedEarningFix'], 0, ',', '.') }} &middot; Pipeline: Rp {{ number_format($kpis['expectedEarningPipeline'], 0, ',', '.') }}</span></div>
    </section>

    <section class="panel table-card" style="margin-bottom: 18px;">
        <h3>Event Bulan Ini</h3>
        <table class="table">
            <thead>
                <tr><th>Tanggal</th><th>Destinasi</th><th>Venue</th><th>Nama Event</th><th>Jenis</th><th>Status</th><th>PIC</th></tr>
            </thead>
            <tbody>
                @forelse ($recentBookings as $booking)
                    <tr tabindex="0" role="button" aria-label="Lihat ringkasan {{ $booking->title }}" onclick="showDetail({{ $booking->id }})" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();showDetail({{ $booking->id }});}" style="cursor:pointer">
                        <td>{{ $booking->start_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $booking->venue->destination->name }}</td>
                        <td>{{ $booking->venue->name }}</td>
                        <td>{{ $booking->title }}</td>
                        <td>{{ $booking->eventType->name }}</td>
                        <td><span class="pill {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                        <td>{{ $booking->pic?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color:var(--muted)">Tidak ada event pada bulan ini. Gunakan tombol "+ Tambah Event" untuk menjadwalkan event baru.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="content" id="calendarContent">
        <section class="panel calendar-wrap">
            <div class="cal-head">
                <div class="cal-nav">
                    <a href="{{ route('dashboard', array_merge($filters, ['y' => $month->copy()->subMonth()->year, 'm' => $month->copy()->subMonth()->month])) }}" class="btn" aria-label="Bulan sebelumnya" title="Bulan sebelumnya">‹</a>
                    <a href="{{ route('dashboard', array_merge($filters, ['y' => $month->copy()->addMonth()->year, 'm' => $month->copy()->addMonth()->month])) }}" class="btn" aria-label="Bulan berikutnya" title="Bulan berikutnya">›</a>
                    <form method="GET" action="{{ route('dashboard') }}" class="cal-jump">
                        @foreach ($filters as $key => $value)
                            @if ($value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <select name="m" class="cal-jump-select" aria-label="Pilih bulan" onchange="this.form.submit()">
                            @foreach ($monthOptions as $num => $name)
                                <option value="{{ $num }}" @selected($month->month === $num)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <select name="y" class="cal-jump-select" aria-label="Pilih tahun" onchange="this.form.submit()">
                            @foreach ($yearOptions as $yr)
                                <option value="{{ $yr }}" @selected($month->year === $yr)>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            <div class="weekdays">
                <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
            </div>
            <div class="calendar">
                @foreach ($calendar as $cell)
                    <div class="day {{ $cell['inMonth'] ? '' : 'muted' }} {{ $cell['hasWarning'] ? 'has-warning' : '' }}" @if (!empty($cell['isUpcoming'])) title="{{ $cell['upcomingTooltip'] }}" @endif>
                        <div class="date {{ $cell['isToday'] ? 'today' : '' }}">
                            {{ $cell['date']->day }}
                            @if (!empty($cell['isUpcoming']))
                                <span class="day-warn-badge" title="{{ $cell['upcomingTooltip'] }}">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                </span>
                            @endif
                        </div>
                        <div class="events-list">
                            @foreach ($cell['events'] as $event)
                                <button type="button" class="event {{ $event->status }}" title="{{ $event->venue->destination->name }} · {{ $event->title }}" onclick="showDetail({{ $event->id }})">
                                    {{ $event->title }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="legend">
                <span><i class="dot" style="background:var(--yellow)"></i>Hold</span>
                <span><i class="dot" style="background:var(--purple)"></i>Tentative</span>
                <span><i class="dot" style="background:var(--green)"></i>Fix</span>
                <span><i class="dot" style="background:var(--red)"></i>Cancelled</span>
                <span><i class="dot" style="background:var(--gray)"></i>Maintenance/Blackout</span>
                @if (!empty($hasAnyUpcomingHighlight))
                    <span class="legend-upcoming"><i class="dot" style="background:#f59e0b; box-shadow: 0 0 0 1px #c2410c;"></i> Tanggal dengan sorot oranye</span>
                @endif
            </div>
        </section>

        <aside class="panel detail" id="detail" hidden aria-live="polite">
            <div class="detail-head">
                <small>Detail Event</small>
                <button type="button" class="detail-close" onclick="closeDetail()" aria-label="Tutup detail" title="Tutup">✕</button>
            </div>
            <div id="detailBody"></div>
        </aside>
    </section>

    <section class="panel util" style="margin-top: 18px; max-width: 360px;">
        <h3>Utilisasi Venue</h3>
        <div class="ring" style="background:conic-gradient(var(--primary) 0 {{ $kpis['utilization'] }}%, #e5e7eb {{ $kpis['utilization'] }}% 100%)">
            <span>{{ $kpis['utilization'] }}%</span>
        </div>
        <p style="font-size:12px;color:var(--muted);text-align:center">
            Estimasi venue-hari terpakai dari total slot venue bulan ini.
        </p>
    </section>

    <script>
        const EVENTS = @json($eventDetails);

        function esc(value) {
            const el = document.createElement('div');
            el.textContent = value ?? '';
            return el.innerHTML;
        }

        function closeDetail() {
            document.getElementById('calendarContent').classList.remove('has-detail');
            document.getElementById('detail').hidden = true;
            document.getElementById('detailBody').innerHTML = '';
        }

        document.addEventListener('keydown', function(e) {
            const detailEl = document.getElementById('detail');
            if (e.key === 'Escape' && detailEl && !detailEl.hidden) {
                closeDetail();
            }
        });

        function showDetail(id) {
            const e = EVENTS[id];
            if (!e) return;

            document.getElementById('calendarContent').classList.add('has-detail');
            document.getElementById('detail').hidden = false;

            let html = '';
            html += `<span class="badge-destination">${esc(e.destination)}</span>`;
            html += `<span class="pill ${e.status}" style="display:inline-block;margin-left:8px">${esc(e.statusLabel)}</span>`;

            if (e.isLoadingOrUnloading) {
                html += `<div class="warn-banner"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:6px;" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>Event ini sedang dalam periode loading atau unloading.</div>`;
            }

            html += `<h2>${esc(e.title)}</h2>`;
            html += `<p class="desc">${esc(e.client ?? '-')}</p>`;
            html += `<hr style="border:none;border-top:1px solid #e2e8f0">`;
            html += `<div class="kv">`;
            html += `<div>Kode</div><div><b>${esc(e.code)}</b></div>`;
            html += `<div>Venue</div><div>${esc(e.venue)}</div>`;
            html += `<div>Tanggal</div><div>${esc(e.dateLabel)}</div>`;
            html += `<div>Loading</div><div>${esc(e.loadAt ?? '-')}</div>`;
            html += `<div>Unloading</div><div>${esc(e.unloadAt ?? '-')}</div>`;
            html += `<div>Jenis Event</div><div>${esc(e.eventType)}</div>`;
            html += `<div>Status</div><div>${esc(e.statusLabel)}</div>`;
            html += `<div>PIC Sales</div><div>${esc(e.pic ?? '-')}</div>`;
            html += `<div>Jumlah Tamu</div><div>${e.guestCount ? esc(e.guestCount) + ' orang' : '-'}</div>`;
            html += `<div>Harga</div><div>${esc(e.price)}</div>`;
            html += `<div>Catatan</div><div>${esc(e.notes ?? '-')}</div>`;
            html += `</div>`;
            html += `<div class="actions-row">`;
            html += `<a href="${e.showUrl}" class="btn">Detail Lengkap</a>`;
            if (e.canManage) {
                html += `<a href="${e.editUrl}" class="btn primary">Edit Event</a>`;
            }
            html += `</div>`;

            document.getElementById('detailBody').innerHTML = html;
        }
    </script>

@endsection
