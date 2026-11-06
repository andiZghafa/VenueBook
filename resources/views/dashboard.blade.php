@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Dashboard Venue Availability</h1>
        </div>
        <div class="actions">
            <a href="{{ route('bookings.export.index', array_merge($filters, ['y' => $month->year, 'm' => $month->month])) }}" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:-3px;" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Excel
            </a>
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
        <div class="kpi"><small>Tingkat Utilisasi</small><strong>{{ $kpis['utilization'] }}%</strong><span class="up">Terpakai / Total Kapasitas</span></div>
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

    <section class="panel util-section" id="utilizationSection">
        <div class="util-head">
            <div>
                <h3>Utilisasi Venue &amp; Analisis Okupansi</h3>
                <p style="font-size: 13px; color: var(--muted); margin: 2px 0 0;">
                    Ringkasan pemanfaatan venue periode {{ $month->translatedFormat('F Y') }}
                </p>
            </div>
        </div>

        <div class="util-grid">
            {{-- Card 1: Overall Breakdown Donut --}}
            <div class="util-card">
                <div class="util-card-header">
                    <h4 class="util-card-title">Komposisi Kapasitas</h4>
                    <span class="util-card-badge" title="Kapasitas total bulan ini">
                        {{ number_format($utilizationData['totalCapacityDays']) }} venue-hari
                    </span>
                </div>
                <div class="util-overview-content">
                    @php
                        $donutR = 44;
                        $donutCirc = 2 * pi() * $donutR; // ~276.46
                        $uPct = max(0, min(100, (float) $utilizationData['utilization']));
                        $mPct = max(0, min(100 - $uPct, (float) $utilizationData['maintenancePercent']));
                        $uLen = round(($uPct / 100) * $donutCirc, 2);
                        $mLen = round(($mPct / 100) * $donutCirc, 2);
                        $uOffset = 0;
                        $mOffset = -$uLen;
                    @endphp
                    <div class="util-donut-wrap">
                        <svg viewBox="0 0 116 116" class="util-donut-svg" role="img" aria-label="Tingkat Utilisasi {{ $utilizationData['utilization'] }}%">
                            {{-- Base circle (Ready / Available capacity) --}}
                            <circle cx="58" cy="58" r="{{ $donutR }}" fill="none" stroke="#e2e8f0" stroke-width="12" />
                            {{-- Maintenance slice --}}
                            @if ($mLen > 0)
                                <circle cx="58" cy="58" r="{{ $donutR }}" fill="none" stroke="var(--yellow)" stroke-width="12" stroke-dasharray="{{ $mLen }} {{ round($donutCirc, 2) }}" stroke-dashoffset="{{ $mOffset }}" transform="rotate(-90 58 58)" />
                            @endif
                            {{-- Terpakai slice --}}
                            @if ($uLen > 0)
                                <circle cx="58" cy="58" r="{{ $donutR }}" fill="none" stroke="var(--primary)" stroke-width="12" stroke-dasharray="{{ $uLen }} {{ round($donutCirc, 2) }}" stroke-dashoffset="{{ $uOffset }}" transform="rotate(-90 58 58)" />
                            @endif
                            <text x="58" y="52" text-anchor="middle" class="util-donut-val">{{ $utilizationData['utilization'] }}%</text>
                            <text x="58" y="69" text-anchor="middle" class="util-donut-sub">Terpakai</text>
                        </svg>
                    </div>
                    <div class="util-legend">
                        <div class="util-legend-row" title="Termasuk hari event serta periode Loading &amp; Unloading">
                            <span class="util-legend-label">
                                <i class="dot" style="background:var(--primary); width:8px; height:8px; border-radius:50%; display:inline-block;"></i>
                                Terpakai
                            </span>
                            <span class="util-legend-val">{{ number_format($utilizationData['terpakaiDays']) }} hr ({{ $utilizationData['utilization'] }}%)</span>
                        </div>
                        <div class="util-legend-row">
                            <span class="util-legend-label">
                                <i class="dot" style="background:#94a3b8; width:8px; height:8px; border-radius:50%; display:inline-block;"></i>
                                Ready
                            </span>
                            <span class="util-legend-val">{{ number_format($utilizationData['readyDays']) }} hr ({{ $utilizationData['readyPercent'] }}%)</span>
                        </div>
                        <div class="util-legend-row">
                            <span class="util-legend-label">
                                <i class="dot" style="background:var(--yellow); width:8px; height:8px; border-radius:50%; display:inline-block;"></i>
                                Maintenance
                            </span>
                            <span class="util-legend-val">{{ number_format($utilizationData['maintenanceDays']) }} hr ({{ $utilizationData['maintenancePercent'] }}%)</span>
                        </div>
                    </div>
                </div>
                <p class="util-card-foot-note">
                    Total {{ $utilizationData['venues']->count() }} venue &times; {{ $month->daysInMonth }} hari = <strong>{{ number_format($utilizationData['totalCapacityDays']) }}</strong> slot venue-hari.
                </p>
            </div>

            {{-- Card 2: Most Used Venue(s) --}}
            <div class="util-card">
                <div class="util-card-header">
                    <h4 class="util-card-title">Paling Sering Digunakan</h4>
                    <span class="util-card-badge" style="color:#065f46; background:#ecfdf5;">
                        Aktivitas Tertinggi
                    </span>
                </div>
                <div class="util-spotlight">
                    @forelse ($utilizationData['mostUsedVenues']->take(3) as $stat)
                        <div class="util-spotlight-item">
                            <div class="util-spotlight-venue">
                                <strong>{{ $stat['venue']->name }}</strong>
                                <span>{{ $stat['venue']->destination->name }} &middot; {{ $stat['events_count'] }} event</span>
                            </div>
                            <div class="util-spotlight-stat">
                                <strong>{{ $stat['utilization_rate'] }}%</strong>
                                <span>{{ $stat['terpakai_days'] }} / {{ $stat['total_capacity'] }} hr</span>
                            </div>
                        </div>
                    @empty
                        <div class="util-empty-box">
                            <div class="util-empty-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <div>
                                <p class="util-empty-title">Belum Ada Event Terjadwal</p>
                                <p class="util-empty-desc">Belum ada booking aktif pada periode {{ $month->translatedFormat('F Y') }}. Semua venue masih berstatus siap dipesan.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
                <p class="util-card-foot-note">
                    Venue dengan rasio hari terpakai tertinggi di periode {{ $month->translatedFormat('F Y') }}.
                </p>
            </div>

            {{-- Card 3: Unused / Available Venues --}}
            <div class="util-card">
                <div class="util-card-header">
                    <h4 class="util-card-title">Ketersediaan &amp; Kebutuhan Sales</h4>
                    <span class="util-card-badge" style="color:#0369a1; background:#f0f9ff;">
                        Peluang Slot
                    </span>
                </div>
                <div class="util-spotlight">
                    @if ($utilizationData['unusedVenues']->count() === $utilizationData['venues']->count())
                        <div class="util-empty-box util-empty-box-callout">
                            <div class="util-empty-icon" style="background:#ecfdf5; color:#059669;" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </div>
                            <div>
                                <p class="util-empty-title" style="color:#065f46;">100% Kapasitas Siap Jual</p>
                                <p class="util-empty-desc">Seluruh {{ $utilizationData['venues']->count() }} venue memiliki slot kosong penuh di bulan {{ $month->translatedFormat('F Y') }}.</p>
                            </div>
                        </div>
                    @else
                        @forelse ($utilizationData['unusedVenues']->take(3) as $stat)
                            <div class="util-spotlight-item">
                                <div class="util-spotlight-venue">
                                    <strong>{{ $stat['venue']->name }}</strong>
                                    <span>{{ $stat['venue']->destination->name }} &middot; Kapasitas {{ number_format($stat['venue']->capacity ?? 0) }} org</span>
                                </div>
                                <div class="util-spotlight-stat">
                                    <span class="util-badge util-badge-idle">0% Terpakai</span>
                                    <span style="display:block; margin-top:2px;">Tersedia {{ $stat['ready_days'] }} hr</span>
                                </div>
                            </div>
                        @empty
                            <div class="util-empty-box util-empty-box-callout">
                                <div class="util-empty-icon" style="background:#ecfdf5; color:#059669;" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <div>
                                    <p class="util-empty-title" style="color:#065f46;">Semua Venue Terjadwal</p>
                                    <p class="util-empty-desc">Setiap venue memiliki minimal 1 jadwal event pada bulan ini.</p>
                                </div>
                            </div>
                        @endforelse
                    @endif
                </div>
                <p class="util-card-foot-note">
                    {{ $utilizationData['unusedVenues']->isNotEmpty() ? 'Slot siap jual untuk diprioritaskan oleh tim sales ke calon client.' : 'Tingkat utilisasi tersebar merata di semua venue.' }}
                </p>
            </div>
        </div>

        {{-- Per-Venue Ranking & Breakdown --}}
        <div style="margin-top: 14px;">
            <div style="margin-bottom: 12px;">
                <h4 style="font-size: 14px; font-weight: 700; color: var(--text); margin: 0 0 2px;">
                    Rincian &amp; Peringkat Utilisasi Seluruh Venue
                </h4>
                <p style="font-size: 12px; color: var(--muted); margin: 0;">
                    Tercatat {{ $utilizationData['venues']->count() }} venue di {{ $filters['destination_id'] ?? null ? 'destinasi terpilih' : 'seluruh destinasi' }}
                </p>
            </div>

            {{-- Desktop Table View (>= 768px) --}}
            <div class="util-table-wrap util-table-desktop">
                <table class="table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">#</th>
                            <th>Venue</th>
                            <th>Destinasi</th>
                            <th style="min-width: 160px;">Komposisi (Terpakai / Ready / Maint)</th>
                            <th style="text-align: right;">Terpakai</th>
                            <th style="text-align: right;">Ready</th>
                            <th style="text-align: right;">Maintenance</th>
                            <th style="text-align: right;">Utilisasi</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($utilizationData['venues'] as $index => $stat)
                            <tr>
                                <td style="text-align: center; color: var(--muted); font-weight: 600;">
                                    {{ $index + 1 }}
                                </td>
                                <td>
                                    <strong>{{ $stat['venue']->name }}</strong>
                                </td>
                                <td>{{ $stat['venue']->destination->name }}</td>
                                <td>
                                    <div class="util-stacked-bar" title="Terpakai: {{ $stat['terpakai_days'] }} hr ({{ $stat['utilization_rate'] }}%) | Ready: {{ $stat['ready_days'] }} hr | Maintenance: {{ $stat['maintenance_days'] }} hr">
                                        <div class="util-bar-slice-terpakai" style="width: {{ $stat['utilization_rate'] }}%;"></div>
                                        <div class="util-bar-slice-maintenance" style="width: {{ $stat['maintenance_rate'] }}%;"></div>
                                        <div class="util-bar-slice-ready" style="width: {{ $stat['ready_rate'] }}%;"></div>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <strong>{{ $stat['terpakai_days'] }}</strong> <span style="font-size:11px; color:var(--muted);">hr</span>
                                </td>
                                <td style="text-align: right;">
                                    {{ $stat['ready_days'] }} <span style="font-size:11px; color:var(--muted);">hr</span>
                                </td>
                                <td style="text-align: right;">
                                    {{ $stat['maintenance_days'] > 0 ? $stat['maintenance_days'] : '-' }} @if($stat['maintenance_days'] > 0)<span style="font-size:11px; color:var(--muted);">hr</span>@endif
                                </td>
                                <td style="text-align: right;">
                                    <strong style="color: {{ $stat['utilization_rate'] >= 40 ? 'var(--primary-text)' : ($stat['utilization_rate'] > 0 ? 'var(--text)' : 'var(--muted)') }};">
                                        {{ $stat['utilization_rate'] }}%
                                    </strong>
                                </td>
                                <td style="text-align: center;">
                                    @if ($stat['is_unused'])
                                        <span class="util-badge util-badge-idle">Siap Dipesan</span>
                                    @elseif ($index === 0 && $stat['terpakai_days'] > 0)
                                        <span class="util-badge util-badge-top">Paling Aktif</span>
                                    @elseif ($stat['maintenance_days'] > $stat['terpakai_days'])
                                        <span class="util-badge util-badge-maintenance">Maintenance</span>
                                    @else
                                        <span class="util-badge util-badge-active">Aktif</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card List View (< 768px) --}}
            <div class="util-mobile-cards">
                @foreach ($utilizationData['venues'] as $index => $stat)
                    <div class="util-mobile-card">
                        <div class="util-mobile-card-top">
                            <div class="util-mobile-card-title">
                                <span class="util-mobile-rank">{{ $index + 1 }}</span>
                                <strong>{{ $stat['venue']->name }}</strong>
                            </div>
                            @if ($stat['is_unused'])
                                <span class="util-badge util-badge-idle">Siap Dipesan</span>
                            @elseif ($index === 0 && $stat['terpakai_days'] > 0)
                                <span class="util-badge util-badge-top">Paling Aktif</span>
                            @elseif ($stat['maintenance_days'] > $stat['terpakai_days'])
                                <span class="util-badge util-badge-maintenance">Maintenance</span>
                            @else
                                <span class="util-badge util-badge-active">Aktif</span>
                            @endif
                        </div>
                        <div class="util-mobile-meta">
                            <span>{{ $stat['venue']->destination->name }}</span>
                            @if ($stat['venue']->capacity)
                                <span>&middot; Kapasitas {{ number_format($stat['venue']->capacity) }} org</span>
                            @endif
                        </div>
                        <div class="util-stacked-bar" title="Terpakai: {{ $stat['terpakai_days'] }} hr ({{ $stat['utilization_rate'] }}%) | Ready: {{ $stat['ready_days'] }} hr | Maintenance: {{ $stat['maintenance_days'] }} hr">
                            <div class="util-bar-slice-terpakai" style="width: {{ $stat['utilization_rate'] }}%;"></div>
                            <div class="util-bar-slice-maintenance" style="width: {{ $stat['maintenance_rate'] }}%;"></div>
                            <div class="util-bar-slice-ready" style="width: {{ $stat['ready_rate'] }}%;"></div>
                        </div>
                        <div class="util-mobile-stats">
                            <div class="util-mobile-stat-cell">
                                <span>Terpakai</span>
                                <strong>{{ $stat['terpakai_days'] }} hr</strong>
                            </div>
                            <div class="util-mobile-stat-cell">
                                <span>Ready</span>
                                <strong>{{ $stat['ready_days'] }} hr</strong>
                            </div>
                            <div class="util-mobile-stat-cell">
                                <span>Maint.</span>
                                <strong>{{ $stat['maintenance_days'] }} hr</strong>
                            </div>
                            <div class="util-mobile-stat-cell">
                                <span>Utilisasi</span>
                                <strong style="color: {{ $stat['utilization_rate'] >= 40 ? 'var(--primary-text)' : ($stat['utilization_rate'] > 0 ? 'var(--text)' : 'var(--muted)') }};">
                                    {{ $stat['utilization_rate'] }}%
                                </strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
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
