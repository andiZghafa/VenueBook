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

    <section class="kpis">
        <div class="kpi"><small>Total Venue</small><strong>{{ $kpis['venueCount'] }}</strong><span class="up">{{ $filters['destination_id'] ?? null ? 'di destinasi terpilih' : 'di 3 destinasi' }}</span></div>
        <div class="kpi"><small>Event Bulan Ini</small><strong>{{ $kpis['eventCount'] }}</strong><span class="up">{{ $month->translatedFormat('F Y') }}</span></div>
        <div class="kpi"><small>Hold Request</small><strong>{{ $kpis['holdCount'] }}</strong><span class="up">Perlu follow-up sales</span></div>
        <div class="kpi"><small>Event Fix</small><strong>{{ $kpis['fixCount'] }}</strong><span class="up">Confirmed booking</span></div>
        <div class="kpi"><small>Tingkat Utilisasi</small><strong>{{ $kpis['utilization'] }}%</strong><span class="up">Estimasi venue-hari terpakai</span></div>
        <div class="kpi"><small>Expected Earning</small><strong>Rp {{ number_format($kpis['expectedEarning'], 0, ',', '.') }}</strong><span class="up">Fix: Rp {{ number_format($kpis['expectedEarningFix'], 0, ',', '.') }} &middot; Pipeline: Rp {{ number_format($kpis['expectedEarningPipeline'], 0, ',', '.') }}</span></div>
    </section>

    <section class="panel filters-bar">
        <form method="GET" action="{{ route('dashboard') }}" class="filters-inline">
            <input type="hidden" name="y" value="{{ $month->year }}">
            <input type="hidden" name="m" value="{{ $month->month }}">
            <div class="field">
                <label>Destinasi</label>
                <select name="destination_id" onchange="this.form.submit()">
                    <option value="">Semua Destinasi</option>
                    @foreach ($destinations as $d)
                        <option value="{{ $d->id }}" @selected(($filters['destination_id'] ?? null) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Venue</label>
                <select name="venue_id" onchange="this.form.submit()">
                    <option value="">Semua Venue</option>
                    @foreach ($venues as $v)
                        <option value="{{ $v->id }}" @selected(($filters['venue_id'] ?? null) == $v->id)>{{ $v->destination->name }} — {{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Jenis Event</label>
                <select name="event_type_id" onchange="this.form.submit()">
                    <option value="">Semua Jenis Event</option>
                    @foreach ($eventTypes as $t)
                        <option value="{{ $t->id }}" @selected(($filters['event_type_id'] ?? null) == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Status Event</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach (['hold' => 'Hold', 'tentative' => 'Tentative', 'fix' => 'Fix', 'cancelled' => 'Cancelled', 'maintenance' => 'Maintenance'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>PIC Sales</label>
                <select name="pic_user_id" onchange="this.form.submit()">
                    <option value="">Semua PIC</option>
                    @foreach ($salesUsers as $u)
                        <option value="{{ $u->id }}" @selected(($filters['pic_user_id'] ?? null) == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama event, kode, client..." onchange="this.form.submit()">
            </div>
            <div class="field field-actions" style="flex: 0 0 auto; margin-left: auto;">
                <a href="{{ route('dashboard', ['y' => $month->year, 'm' => $month->month]) }}" class="btn" title="Reset Filter" style="padding: 9px 12px; display: flex; align-items: center; border: 1.5px solid #94a3b8; color: #475569; border-radius: 8px; background: #f8fafc; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#64748b'; this.style.color='#1e293b';" onmouseout="this.style.borderColor='#94a3b8'; this.style.color='#475569';">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    <span style="font-size: 13px; font-weight: 700;">Reset</span>
                </a>
            </div>
        </form>
    </section>

    <section class="content" id="calendarContent">
        <section class="panel calendar-wrap">
            <div class="cal-head">
                <div class="cal-nav">
                    <a href="{{ route('dashboard', array_merge($filters, ['y' => $month->copy()->subMonth()->year, 'm' => $month->copy()->subMonth()->month])) }}" class="btn">‹</a>
                    <a href="{{ route('dashboard', array_merge($filters, ['y' => $month->copy()->addMonth()->year, 'm' => $month->copy()->addMonth()->month])) }}" class="btn">›</a>
                    <form method="GET" action="{{ route('dashboard') }}" class="cal-jump">
                        @foreach ($filters as $key => $value)
                            @if ($value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <select name="m" class="cal-jump-select" onchange="this.form.submit()">
                            @foreach ($monthOptions as $num => $name)
                                <option value="{{ $num }}" @selected($month->month === $num)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <select name="y" class="cal-jump-select" onchange="this.form.submit()">
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
                    <div class="day {{ $cell['inMonth'] ? '' : 'muted' }} {{ $cell['hasWarning'] ? 'has-warning' : '' }}">
                        <div class="date {{ $cell['isToday'] ? 'today' : '' }}">
                            {{ $cell['date']->day }}
                            @if ($cell['hasWarning'])
                                <span class="day-warn-badge" title="Ada event yang sedang loading/unloading">⚠️</span>
                            @endif
                        </div>
                        <div class="events-list">
                            @foreach ($cell['events'] as $event)
                                <div class="event {{ $event->status }}" title="{{ $event->venue->destination->name }} — {{ $event->title }}" onclick="showDetail({{ $event->id }})">
                                    {{ $event->title }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="legend">
                <span><i class="dot" style="background:#f59e0b"></i>Hold</span>
                <span><i class="dot" style="background:#8b5cf6"></i>Tentative</span>
                <span><i class="dot" style="background:var(--green)"></i>Fix</span>
                <span><i class="dot" style="background:#ef4444"></i>Cancelled</span>
                <span><i class="dot" style="background:#64748b"></i>Maintenance/Blackout</span>
                <span>⚠️ Tanggal dengan sorot oranye = ada event sedang loading/unloading</span>
            </div>
        </section>

        <aside class="panel detail" id="detail" hidden>
            <div class="detail-head">
                <small>Detail Event</small>
                <button type="button" class="detail-close" onclick="closeDetail()" aria-label="Tutup detail" title="Tutup">✕</button>
            </div>
            <div id="detailBody"></div>
        </aside>
    </section>

    <section class="bottom">
        <div class="panel table-card">
            <h3>Event Bulan Ini</h3>
            <table class="table">
                <thead>
                    <tr><th>Tanggal</th><th>Destinasi</th><th>Venue</th><th>Nama Event</th><th>Jenis</th><th>Status</th><th>PIC</th></tr>
                </thead>
                <tbody>
                    @forelse ($recentBookings as $booking)
                        <tr onclick="showDetail({{ $booking->id }})" style="cursor:pointer">
                            <td>{{ $booking->start_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $booking->venue->destination->name }}</td>
                            <td>{{ $booking->venue->name }}</td>
                            <td>{{ $booking->title }}</td>
                            <td>{{ $booking->eventType->name }}</td>
                            <td><span class="pill {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                            <td>{{ $booking->pic?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="color:var(--muted)">Tidak ada event pada bulan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel util">
            <h3>Utilisasi Venue</h3>
            <div class="ring" style="background:conic-gradient(var(--primary) 0 {{ $kpis['utilization'] }}%, #e5e7eb {{ $kpis['utilization'] }}% 100%)">
                <span>{{ $kpis['utilization'] }}%</span>
            </div>
            <p style="font-size:12px;color:var(--muted);text-align:center">
                Estimasi venue-hari terpakai dari total slot venue bulan ini.
            </p>
        </div>
    </section>

    <script>
        const EVENTS = @json($eventDetails);

        function closeDetail() {
            document.getElementById('calendarContent').classList.remove('has-detail');
            document.getElementById('detail').hidden = true;
            document.getElementById('detailBody').innerHTML = '';
        }

        function showDetail(id) {
            const e = EVENTS[id];
            if (!e) return;

            document.getElementById('calendarContent').classList.add('has-detail');
            document.getElementById('detail').hidden = false;

            let html = '';
            html += `<span class="badge-destination">${e.destination}</span>`;
            html += `<span class="pill ${e.status}" style="display:inline-block;margin-left:8px">${e.statusLabel}</span>`;

            if (e.isLoadingOrUnloading) {
                html += `<div class="warn-banner">⚠️ Hey, event ini masih dalam proses loading atau unloading.</div>`;
            }

            html += `<h2>${e.title}</h2>`;
            html += `<p class="desc">${e.client ?? '—'}</p>`;
            html += `<hr style="border:none;border-top:1px solid #edf0f4">`;
            html += `<div class="kv">`;
            html += `<div>Kode</div><div><b>${e.code}</b></div>`;
            html += `<div>Venue</div><div>${e.venue}</div>`;
            html += `<div>Tanggal</div><div>${e.dateLabel}</div>`;
            html += `<div>Loading</div><div>${e.loadAt ?? '—'}</div>`;
            html += `<div>Unloading</div><div>${e.unloadAt ?? '—'}</div>`;
            html += `<div>Jenis Event</div><div>${e.eventType}</div>`;
            html += `<div>Status</div><div>${e.statusLabel}</div>`;
            html += `<div>PIC Sales</div><div>${e.pic ?? '—'}</div>`;
            html += `<div>Jumlah Tamu</div><div>${e.guestCount ? e.guestCount + ' orang' : '—'}</div>`;
            html += `<div>Harga</div><div>${e.price}</div>`;
            html += `<div>Catatan</div><div>${e.notes ?? '—'}</div>`;
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
