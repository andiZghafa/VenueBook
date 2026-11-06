@extends('layouts.app')

@section('title', 'Export Excel')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Export Excel</h1>
            <p id="exportSubtitle" aria-live="polite">
                @if (! auth()->user()->isAdmin())
                    <strong id="statTotalEvents">{{ number_format($totalMatching, 0, ',', '.') }} event</strong> di destinasi <b>{{ auth()->user()->destination?->name }}</b> siap diunduh ke format <code>.xlsx</code>.
                @else
                    <strong id="statTotalEvents">{{ number_format($totalMatching, 0, ',', '.') }} event</strong> siap diunduh sebagai <span id="filenamePreview">
                        @if ($startDate && $endDate)
                            daftar-event-{{ $startDate }}-sd-{{ $endDate }}.xlsx
                        @elseif ($startDate)
                            daftar-event-mulai-{{ $startDate }}.xlsx
                        @elseif ($endDate)
                            daftar-event-sampai-{{ $endDate }}.xlsx
                        @else
                            daftar-event-{{ now()->format('Y-m-d') }}.xlsx
                        @endif
                    </span>
                @endif
            </p>
        </div>
        <div class="actions">
            <a href="{{ route('bookings.export.index') }}" id="exportResetBtn" class="btn btn-reset">Reset</a>
            <button type="submit" form="exportConfigForm" id="downloadExcelMainBtn" class="btn primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Download Excel (.xlsx)</span>
            </button>
        </div>
    </div>

    <section class="panel export-sheet" id="exportSheetCard">
        <form id="exportConfigForm" method="GET" action="{{ route('bookings.export') }}">
            {{-- 1. Flat Filter & Date Range Bar --}}
            <div class="export-ControlsBar">
                <div class="export-filters-row">
                    <div class="field">
                        <label for="exportStartDate">Dari Tanggal</label>
                        <input
                            type="text"
                            id="exportStartDate"
                            name="start_date"
                            data-flatpickr="date"
                            value="{{ $startDate }}"
                            placeholder="Semua tanggal"
                            autocomplete="off"
                        >
                    </div>
                    <div class="field">
                        <label for="exportEndDate">Sampai Tanggal</label>
                        <input
                            type="text"
                            id="exportEndDate"
                            name="end_date"
                            data-flatpickr="date"
                            value="{{ $endDate }}"
                            placeholder="Semua tanggal"
                            autocomplete="off"
                        >
                    </div>

                    @if (auth()->user()->isAdmin())
                        <div class="field">
                            <label for="exportDestination">Destinasi</label>
                            <select id="exportDestination" name="destination_id">
                                <option value="">Semua Destinasi</option>
                                @foreach ($destinations as $d)
                                    <option value="{{ $d->id }}" @selected(($filters['destination_id'] ?? null) == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="field">
                        <label for="exportVenue">Venue</label>
                        <select id="exportVenue" name="venue_id">
                            <option value="">Semua Venue</option>
                            @foreach ($venues as $v)
                                <option value="{{ $v->id }}" data-destination-id="{{ $v->destination_id }}" @selected(($filters['venue_id'] ?? null) == $v->id)>
                                    {{ $v->destination->name }} &middot; {{ $v->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="exportStatus">Status</label>
                        <select id="exportStatus" name="status">
                            <option value="">Semua Status</option>
                            @foreach (['hold' => 'Hold', 'tentative' => 'Tentative', 'fix' => 'Fix', 'cancelled' => 'Cancelled', 'maintenance' => 'Maintenance'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="exportPic">PIC Sales</label>
                        <select id="exportPic" name="pic_user_id">
                            <option value="">Semua PIC</option>
                            @foreach ($salesUsers as $u)
                                <option value="{{ $u->id }}" @selected(($filters['pic_user_id'] ?? null) == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="export-period-Shortcuts" role="group" aria-label="Pintasan rentang tanggal">
                    <span class="export-shortcut-label">Rentang Tanggal Event:</span>
                    <button type="button" class="export-text-link {{ !$startDate && !$endDate ? 'is-active' : '' }}" data-date-preset="all">Semua Jadwal</button>
                    <span class="export-sep" aria-hidden="true">/</span>
                    <button type="button" class="export-text-link" data-date-preset="this_month">Bulan Ini</button>
                    <span class="export-sep" aria-hidden="true">/</span>
                    <button type="button" class="export-text-link" data-date-preset="next_month">Bulan Depan</button>
                    <span class="export-sep" aria-hidden="true">/</span>
                    <button type="button" class="export-text-link" data-date-preset="last_month">Bulan Lalu</button>
                    <span class="export-sep" aria-hidden="true">/</span>
                    <button type="button" class="export-text-link" data-date-preset="this_year">Tahun Ini</button>
                </div>
            </div>

            {{-- 2. Semantic 4-Column Ledger + Overdrive Controls --}}
            @php
                $isAllSelected = count($selectedColumns) === count($availableColumns);
                $isNoneSelected = count($selectedColumns) === 0;
                $sortedSelected = $selectedColumns;
                $sortedDefaults = $defaultFormColumns;
                sort($sortedSelected);
                sort($sortedDefaults);
                $isDefaultSelected = $sortedSelected === $sortedDefaults;

                $columnGroups = [
                    'identity' => [
                        'title' => 'Identitas & Lokasi',
                        'keys' => ['code', 'title', 'destination', 'venue', 'event_type'],
                    ],
                    'schedule' => [
                        'title' => 'Jadwal & Operasional',
                        'keys' => ['start_date', 'end_date', 'load_at', 'unload_at'],
                    ],
                    'stakeholders' => [
                        'title' => 'Pihak Terkait',
                        'keys' => ['client', 'organizer', 'pic'],
                    ],
                    'metrics' => [
                        'title' => 'Kapasitas, Nilai & Catatan',
                        'keys' => ['guest_count', 'price', 'status', 'notes'],
                    ],
                ];
            @endphp
            <div class="export-cols-band" role="group" aria-labelledby="exportColsHeading">
                <div class="export-cols-header">
                    <div class="export-cols-title-wrap">
                        <span id="exportColsHeading" class="export-cols-legend">Kolom yang Diekspor</span>
                        <span class="export-cols-count" id="colCounterBadge" aria-live="polite">
                            ({{ count($selectedColumns) }} dari {{ count($availableColumns) }} dipilih)
                        </span>
                        <span class="export-drag-hint" id="exportDragHint" aria-hidden="true">
                            Tips: Klik judul grup atau tahan &amp; geser untuk memilih cepat
                        </span>
                    </div>
                    <div class="export-cols-actions">
                        <button type="button" class="export-text-link {{ $isAllSelected ? 'is-active' : '' }}" id="selectAllColsBtn" aria-pressed="{{ $isAllSelected ? 'true' : 'false' }}">Pilih Semua</button>
                        <span class="export-sep" aria-hidden="true">/</span>
                        <button type="button" class="export-text-link {{ $isDefaultSelected ? 'is-active' : '' }}" id="selectDefaultColsBtn" aria-pressed="{{ $isDefaultSelected ? 'true' : 'false' }}">Standar Form (14)</button>
                        <span class="export-sep" aria-hidden="true">/</span>
                        <button type="button" class="export-text-link {{ $isNoneSelected ? 'is-active' : '' }}" id="clearAllColsBtn" aria-pressed="{{ $isNoneSelected ? 'true' : 'false' }}">Kosongkan</button>
                    </div>
                </div>

                <div class="export-cols-meter" aria-hidden="true">
                    <div class="export-cols-meter-fill" id="exportColsMeterFill" style="transform: scaleX({{ count($availableColumns) > 0 ? round(count($selectedColumns) / count($availableColumns), 4) : 0 }});"></div>
                </div>

                <div id="noColumnAlert" class="export-alert-banner" hidden role="alert">
                    Pilih minimal 1 kolom agar file Excel dapat diunduh.
                </div>

                <div class="export-cols-ledger" id="exportColsLedger">
                    @foreach ($columnGroups as $groupKey => $groupMeta)
                        @php
                            $groupKeys = array_values(array_filter($groupMeta['keys'], fn ($k) => isset($availableColumns[$k])));
                            $groupCheckedCount = count(array_intersect($groupKeys, $selectedColumns));
                            $groupTotalCount = count($groupKeys);
                            $groupState = $groupCheckedCount === $groupTotalCount ? 'all' : ($groupCheckedCount > 0 ? 'some' : 'none');
                        @endphp
                        <div class="export-col-group" data-group-container="{{ $groupKey }}">
                            <button
                                type="button"
                                class="export-group-head"
                                data-col-group="{{ $groupKey }}"
                                data-group-state="{{ $groupState }}"
                                title="Klik untuk memilih atau mengosongkan grup {{ $groupMeta['title'] }}"
                            >
                                <span class="export-group-indicator" aria-hidden="true">
                                    <svg class="export-group-icon-all" width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="2.25 6.25 4.75 8.75 9.75 3.25"/></svg>
                                    <svg class="export-group-icon-some" width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="2.5" y1="6" x2="9.5" y2="6"/></svg>
                                </span>
                                <span class="export-group-title">{{ $groupMeta['title'] }}</span>
                                <span class="export-group-fraction" data-group-counter="{{ $groupKey }}">{{ $groupCheckedCount }}/{{ $groupTotalCount }}</span>
                            </button>

                            <div class="export-group-items">
                                @foreach ($groupKeys as $colKey)
                                    @php
                                        $colMeta = $availableColumns[$colKey];
                                        $isChecked = in_array($colKey, $selectedColumns, true);
                                    @endphp
                                    <label
                                        class="export-col-option {{ $isChecked ? 'is-checked' : '' }}"
                                        for="col_{{ $colKey }}"
                                        data-col-option="{{ $colKey }}"
                                        data-col-parent-group="{{ $groupKey }}"
                                    >
                                        <span class="export-check-control">
                                            <input
                                                type="checkbox"
                                                class="export-col-checkbox"
                                                id="col_{{ $colKey }}"
                                                name="columns[]"
                                                value="{{ $colKey }}"
                                                data-group="{{ $groupKey }}"
                                                @checked($isChecked)
                                            >
                                            <svg class="export-check-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polyline points="2.25 6.25 4.75 8.75 9.75 3.25"/>
                                            </svg>
                                        </span>
                                        <span class="export-col-text">{{ $colMeta['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>

        {{-- 3. Integrated Live Table Preview --}}
        <div class="export-table-section" aria-live="polite" id="exportTableRegion">
            <div class="export-table-meta">
                <span id="sheetSubMeta">
                    @if ($totalMatching > 10)
                        Pratinjau 10 baris pertama dari {{ number_format($totalMatching, 0, ',', '.') }} event
                    @elseif ($totalMatching > 0)
                        Pratinjau {{ number_format($totalMatching, 0, ',', '.') }} event
                    @else
                        Tidak ada event yang cocok dengan filter saat ini
                    @endif
                </span>
                <span id="exportLoadingStatus" class="export-loading-text" hidden>Memperbarui pratinjau data...</span>
            </div>

            <div id="exportErrorAlert" class="export-alert-banner" hidden role="alert">
                Gagal memuat pembaruan pratinjau. Anda tetap dapat mengunduh file Excel dengan tombol di kanan atas.
            </div>

            <div class="export-table-scroll">
                <table class="table" id="exportPreviewTable">
                    <thead>
                        <tr>
                            @foreach ($availableColumns as $colKey => $colMeta)
                                <th
                                    data-col-cell="{{ $colKey }}"
                                    style="view-transition-name: export-th-{{ $colKey }};"
                                    @if(!in_array($colKey, $selectedColumns, true)) hidden @endif
                                >
                                    {{ $colMeta['label'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="exportPreviewTbody">
                        @forelse ($previewRows as $row)
                            <tr>
                                @foreach ($availableColumns as $colKey => $colMeta)
                                    @php
                                        $val = $row['cells'][$colKey] ?? '';
                                    @endphp
                                    <td
                                        data-col-cell="{{ $colKey }}"
                                        @if(!in_array($colKey, $selectedColumns, true)) hidden @endif
                                    >
                                        @if ($colKey === 'price')
                                            {{ $val !== null && $val !== '' ? 'Rp '.number_format((float) $val, 0, ',', '.') : '-' }}
                                        @elseif ($colKey === 'guest_count')
                                            {{ $val !== null && $val !== '' ? number_format((int) $val, 0, ',', '.') : '-' }}
                                        @elseif ($colKey === 'status')
                                            <span class="pill {{ $row['status_key'] }}">{{ $val }}</span>
                                        @else
                                            {{ $val !== '' && $val !== null ? $val : '-' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($availableColumns) }}" class="export-empty-cell">
                                    Belum ada jadwal event pada rentang tanggal atau filter ini. Pilih <b>Semua Jadwal</b> atau klik <b>Reset</b> di kanan atas untuk menampilkan seluruh data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('exportConfigForm');
            const startInput = document.getElementById('exportStartDate');
            const endInput = document.getElementById('exportEndDate');
            const destSelect = document.getElementById('exportDestination');
            const venueSelect = document.getElementById('exportVenue');
            const statusSelect = document.getElementById('exportStatus');
            const picSelect = document.getElementById('exportPic');

            const colCheckboxes = Array.from(document.querySelectorAll('input[name="columns[]"]'));
            const colCounterBadge = document.getElementById('colCounterBadge');
            const noColumnAlert = document.getElementById('noColumnAlert');
            const errorAlert = document.getElementById('exportErrorAlert');
            const loadingStatus = document.getElementById('exportLoadingStatus');
            const downloadMainBtn = document.getElementById('downloadExcelMainBtn');
            const sheetCard = document.getElementById('exportSheetCard');

            const TOTAL_COLS = colCheckboxes.length;
            const DEFAULT_FORM_COLS = @json($defaultFormColumns);

            let abortController = null;

            function formatYmd(date) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            function setPickerDate(inputEl, ymdString) {
                if (inputEl && inputEl._flatpickr) {
                    inputEl._flatpickr.setDate(ymdString || '', false);
                } else if (inputEl) {
                    inputEl.value = ymdString || '';
                }
            }

            function updateVenueOptions() {
                if (!destSelect || !venueSelect) return;
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

            const meterFill = document.getElementById('exportColsMeterFill');
            const dragHint = document.getElementById('exportDragHint');
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

            function runWithViewTransition(mutateDomFn, useTransition = true) {
                if (
                    useTransition &&
                    typeof document.startViewTransition === 'function' &&
                    !prefersReducedMotion.matches
                ) {
                    try {
                        document.startViewTransition(() => {
                            mutateDomFn();
                        });
                        return;
                    } catch (_) {
                        // Fallback when concurrent transition is skipped
                    }
                }
                mutateDomFn();
            }

            function syncTableColumnsVisibility(activeKeys, useTransition = false) {
                runWithViewTransition(() => {
                    document.querySelectorAll('[data-col-cell]').forEach((cell) => {
                        const key = cell.getAttribute('data-col-cell');
                        cell.hidden = !activeKeys.includes(key);
                    });
                }, useTransition);
            }

            function updateColumnsUI(useTransition = false) {
                const activeKeys = [];
                const groupTotals = {};
                const groupChecked = {};

                colCheckboxes.forEach((cb) => {
                    const optionLabel = cb.closest('.export-col-option');
                    const gKey = cb.getAttribute('data-group') || '';
                    if (gKey) {
                        groupTotals[gKey] = (groupTotals[gKey] || 0) + 1;
                        if (cb.checked) {
                            groupChecked[gKey] = (groupChecked[gKey] || 0) + 1;
                        }
                    }

                    if (cb.checked) {
                        activeKeys.push(cb.value);
                        if (optionLabel) optionLabel.classList.add('is-checked');
                    } else {
                        if (optionLabel) optionLabel.classList.remove('is-checked');
                    }
                });

                Object.keys(groupTotals).forEach((gKey) => {
                    const total = groupTotals[gKey] || 0;
                    const checked = groupChecked[gKey] || 0;
                    const state = checked === total ? 'all' : (checked > 0 ? 'some' : 'none');
                    const headBtn = document.querySelector(`[data-col-group="${gKey}"]`);
                    const counterEl = document.querySelector(`[data-group-counter="${gKey}"]`);
                    if (headBtn) {
                        headBtn.setAttribute('data-group-state', state);
                    }
                    if (counterEl) {
                        counterEl.textContent = `${checked}/${total}`;
                    }
                });

                syncTableColumnsVisibility(activeKeys, useTransition);

                const count = activeKeys.length;
                if (colCounterBadge) {
                    colCounterBadge.textContent = `(${count} dari ${TOTAL_COLS} dipilih)`;
                }

                if (meterFill && TOTAL_COLS > 0) {
                    const ratio = Math.max(0, Math.min(1, count / TOTAL_COLS));
                    meterFill.style.transform = `scaleX(${ratio.toFixed(4)})`;
                }

                const isAll = count === TOTAL_COLS;
                const isNone = count === 0;
                const isDefault = count === DEFAULT_FORM_COLS.length && DEFAULT_FORM_COLS.every((k) => activeKeys.includes(k));

                const allBtn = document.getElementById('selectAllColsBtn');
                const defBtn = document.getElementById('selectDefaultColsBtn');
                const clrBtn = document.getElementById('clearAllColsBtn');

                if (allBtn) {
                    allBtn.classList.toggle('is-active', isAll);
                    allBtn.setAttribute('aria-pressed', isAll ? 'true' : 'false');
                }
                if (defBtn) {
                    defBtn.classList.toggle('is-active', isDefault);
                    defBtn.setAttribute('aria-pressed', isDefault ? 'true' : 'false');
                }
                if (clrBtn) {
                    clrBtn.classList.toggle('is-active', isNone);
                    clrBtn.setAttribute('aria-pressed', isNone ? 'true' : 'false');
                }

                if (noColumnAlert) noColumnAlert.hidden = !isNone;
                if (downloadMainBtn) downloadMainBtn.disabled = isNone;
            }

            function updateFilenameState() {
                const startVal = startInput.value.trim();
                const endVal = endInput.value.trim();

                const filenameEl = document.getElementById('filenamePreview');
                if (filenameEl) {
                    if (startVal && endVal) {
                        filenameEl.textContent = `daftar-event-${startVal}-sd-${endVal}.xlsx`;
                    } else if (startVal) {
                        filenameEl.textContent = `daftar-event-mulai-${startVal}.xlsx`;
                    } else if (endVal) {
                        filenameEl.textContent = `daftar-event-sampai-${endVal}.xlsx`;
                    } else {
                        filenameEl.textContent = `daftar-event-${formatYmd(new Date())}.xlsx`;
                    }
                }
            }

            function buildPreviewUrl() {
                const url = new URL('{{ route('bookings.export.index') }}', window.location.origin);
                if (startInput.value.trim()) url.searchParams.set('start_date', startInput.value.trim());
                if (endInput.value.trim()) url.searchParams.set('end_date', endInput.value.trim());
                if (destSelect && destSelect.value) url.searchParams.set('destination_id', destSelect.value);
                if (venueSelect && venueSelect.value) url.searchParams.set('venue_id', venueSelect.value);
                if (statusSelect && statusSelect.value) url.searchParams.set('status', statusSelect.value);
                if (picSelect && picSelect.value) url.searchParams.set('pic_user_id', picSelect.value);
                return url.toString();
            }

            async function refreshPreview() {
                updateFilenameState();
                const url = buildPreviewUrl();

                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();
                if (sheetCard) sheetCard.classList.add('is-loading');
                if (loadingStatus) loadingStatus.hidden = false;
                if (errorAlert) errorAlert.hidden = true;

                try {
                    const res = await fetch(url, {
                        signal: abortController.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    });
                    if (!res.ok) throw new Error('Status ' + res.status);

                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    const newTbody = doc.getElementById('exportPreviewTbody');
                    const currentTbody = document.getElementById('exportPreviewTbody');
                    if (newTbody && currentTbody) {
                        currentTbody.innerHTML = newTbody.innerHTML;
                    }

                    ['statTotalEvents', 'sheetSubMeta'].forEach((id) => {
                        const nextEl = doc.getElementById(id);
                        const currEl = document.getElementById(id);
                        if (nextEl && currEl) {
                            currEl.innerHTML = nextEl.innerHTML;
                        }
                    });

                    updateColumnsUI();
                    window.history.replaceState(null, '', url);
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Gagal memuat pratinjau export:', err);
                        if (errorAlert) errorAlert.hidden = false;
                    }
                } finally {
                    if (sheetCard) sheetCard.classList.remove('is-loading');
                    if (loadingStatus) loadingStatus.hidden = true;
                }
            }

            // Date range shortcuts
            document.querySelectorAll('[data-date-preset]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const preset = this.getAttribute('data-date-preset');
                    const now = new Date();
                    let start = '';
                    let end = '';

                    if (preset === 'this_month') {
                        start = formatYmd(new Date(now.getFullYear(), now.getMonth(), 1));
                        end = formatYmd(new Date(now.getFullYear(), now.getMonth() + 1, 0));
                    } else if (preset === 'next_month') {
                        start = formatYmd(new Date(now.getFullYear(), now.getMonth() + 1, 1));
                        end = formatYmd(new Date(now.getFullYear(), now.getMonth() + 2, 0));
                    } else if (preset === 'last_month') {
                        start = formatYmd(new Date(now.getFullYear(), now.getMonth() - 1, 1));
                        end = formatYmd(new Date(now.getFullYear(), now.getMonth(), 0));
                    } else if (preset === 'this_year') {
                        start = formatYmd(new Date(now.getFullYear(), 0, 1));
                        end = formatYmd(new Date(now.getFullYear(), 11, 31));
                    }

                    setPickerDate(startInput, start);
                    setPickerDate(endInput, end);

                    document.querySelectorAll('[data-date-preset]').forEach((b) => b.classList.remove('is-active'));
                    this.classList.add('is-active');

                    refreshPreview();
                });
            });

            [startInput, endInput].forEach((input) => {
                if (!input) return;
                input.addEventListener('change', function () {
                    document.querySelectorAll('[data-date-preset]').forEach((b) => {
                        b.classList.toggle('is-active', !startInput.value && !endInput.value && b.getAttribute('data-date-preset') === 'all');
                    });
                    refreshPreview();
                });
            });

            document.getElementById('selectAllColsBtn')?.addEventListener('click', function () {
                colCheckboxes.forEach((cb) => { cb.checked = true; });
                updateColumnsUI(true);
            });

            document.getElementById('selectDefaultColsBtn')?.addEventListener('click', function () {
                colCheckboxes.forEach((cb) => { cb.checked = DEFAULT_FORM_COLS.includes(cb.value); });
                updateColumnsUI(true);
            });

            document.getElementById('clearAllColsBtn')?.addEventListener('click', function () {
                colCheckboxes.forEach((cb) => { cb.checked = false; });
                updateColumnsUI(true);
            });

            // Group Header Toggle (toggle entire semantic column group at once)
            document.querySelectorAll('[data-col-group]').forEach((groupBtn) => {
                groupBtn.addEventListener('click', function () {
                    const gKey = this.getAttribute('data-col-group');
                    const groupCbs = colCheckboxes.filter((cb) => cb.getAttribute('data-group') === gKey);
                    const allChecked = groupCbs.length > 0 && groupCbs.every((cb) => cb.checked);
                    const targetState = !allChecked;

                    groupCbs.forEach((cb) => {
                        cb.checked = targetState;
                    });
                    updateColumnsUI(true);
                });

                // Spotlight all columns in this group on hover
                groupBtn.addEventListener('pointerenter', function (e) {
                    if (e.pointerType === 'touch') return;
                    const gKey = this.getAttribute('data-col-group');
                    const groupKeys = colCheckboxes
                        .filter((cb) => cb.getAttribute('data-group') === gKey)
                        .map((cb) => cb.value);
                    document.querySelectorAll('[data-col-cell]').forEach((cell) => {
                        cell.toggleAttribute('data-col-spotlight', groupKeys.includes(cell.getAttribute('data-col-cell')));
                    });
                });

                groupBtn.addEventListener('pointerleave', function () {
                    document.querySelectorAll('[data-col-spotlight]').forEach((cell) => {
                        cell.removeAttribute('data-col-spotlight');
                    });
                });
            });

            // Click-and-Drag Paint Selection + Single-Column Spotlight
            let isDragPainting = false;
            let dragTargetChecked = true;
            let didDragAcrossMultiple = false;
            let dragStartOption = null;

            const optionLabels = Array.from(document.querySelectorAll('.export-col-option'));

            optionLabels.forEach((labelEl) => {
                const cb = labelEl.querySelector('.export-col-checkbox');
                const colKey = labelEl.getAttribute('data-col-option');

                labelEl.addEventListener('pointerdown', function (e) {
                    if (e.button !== 0 || e.pointerType === 'touch') return;
                    isDragPainting = true;
                    didDragAcrossMultiple = false;
                    dragStartOption = labelEl;
                    dragTargetChecked = cb ? !cb.checked : true;
                });

                labelEl.addEventListener('pointerenter', function (e) {
                    if (e.pointerType === 'touch') return;

                    if (isDragPainting && cb) {
                        if (dragStartOption && dragStartOption !== labelEl && !didDragAcrossMultiple) {
                            didDragAcrossMultiple = true;
                            document.body.setAttribute('data-drag-painting', 'true');
                            if (dragHint) dragHint.setAttribute('data-active', 'true');
                            const startCb = dragStartOption.querySelector('.export-col-checkbox');
                            if (startCb && startCb.checked !== dragTargetChecked) {
                                startCb.checked = dragTargetChecked;
                            }
                        }
                        if (didDragAcrossMultiple && cb.checked !== dragTargetChecked) {
                            cb.checked = dragTargetChecked;
                            updateColumnsUI(false);
                        }
                    }

                    if (colKey) {
                        document.querySelectorAll('[data-col-cell]').forEach((cell) => {
                            cell.toggleAttribute('data-col-spotlight', cell.getAttribute('data-col-cell') === colKey);
                        });
                    }
                });

                labelEl.addEventListener('pointerleave', function () {
                    if (!isDragPainting) {
                        document.querySelectorAll('[data-col-spotlight]').forEach((cell) => {
                            cell.removeAttribute('data-col-spotlight');
                        });
                    }
                });

                labelEl.addEventListener('click', function (e) {
                    if (didDragAcrossMultiple) {
                        e.preventDefault();
                        didDragAcrossMultiple = false;
                    }
                });
            });

            window.addEventListener('pointerup', function () {
                if (!isDragPainting) return;
                const paintedMultiple = didDragAcrossMultiple;
                isDragPainting = false;
                dragStartOption = null;
                document.body.removeAttribute('data-drag-painting');
                if (dragHint) dragHint.removeAttribute('data-active');
                document.querySelectorAll('[data-col-spotlight]').forEach((cell) => {
                    cell.removeAttribute('data-col-spotlight');
                });
                if (paintedMultiple) {
                    updateColumnsUI(true);
                    setTimeout(() => {
                        didDragAcrossMultiple = false;
                    }, 0);
                }
            });

            colCheckboxes.forEach((cb) => {
                cb.addEventListener('change', function () {
                    if (!didDragAcrossMultiple) {
                        updateColumnsUI(true);
                    }
                });
            });

            if (destSelect) {
                destSelect.addEventListener('change', function () {
                    updateVenueOptions();
                    refreshPreview();
                });
            }

            [venueSelect, statusSelect, picSelect].forEach((sel) => {
                if (!sel) return;
                sel.addEventListener('change', refreshPreview);
            });

            form.addEventListener('submit', function (e) {
                const anyChecked = colCheckboxes.some((cb) => cb.checked);
                if (!anyChecked) {
                    e.preventDefault();
                    if (noColumnAlert) noColumnAlert.hidden = false;
                }
            });

            updateVenueOptions();
            updateColumnsUI(false);
            updateFilenameState();
        });
    </script>
@endsection
