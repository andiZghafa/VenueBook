@extends('layouts.app')

@section('title', 'Kursi &mdash; '.$booking->code)

@section('content')

    <div class="topbar">
        <div class="title">
            <p style="margin:0 0 4px;font-size:12px;color:var(--muted)"><a href="{{ route('bookings.show', $booking) }}">{{ $booking->code }}</a> / Kursi</p>
            <h1>{{ $booking->title }}</h1>
            <p style="margin:4px 0 0;font-size:12px;color:var(--muted)">{{ $booking->venue->name }} &middot; {{ $booking->start_date->translatedFormat('d M Y') }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('bookings.show', $booking) }}" class="btn">&lsaquo; Kembali ke Event</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert success" style="margin-bottom:16px">{{ session('success') }}</div>
    @endif

    <style>
        :root {
            --vip: #dc2626;      /* red — VIP */
            --vip-tint: #fef2f2;
            --class1: #2563eb;   /* blue — Class 1 */
            --class1-tint: #eaf1ff;
            --class2: #f97316;   /* orange — Class 2 */
            --class2-tint: #fef1e6;
            --special: #16a34a;  /* green — Special/Khusus */
            --special-tint: #eafaf0;
            --seat-blocked: #ef4444;
            --seat-blocked-dark: #dc2626;
            --seat-booked: #9ca3af;
        }

        .seat-legend { display:flex; flex-wrap:wrap; gap:18px; align-items:center; font-size:12.5px; color:var(--muted); }
        .seat-legend .swatch { display:inline-block; width:15px; height:15px; border-radius:45% 45% 15% 15%; margin-right:7px; vertical-align:-3px; }
        .legend-bar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:12px 24px; align-items:center; padding-bottom:16px; margin-bottom:20px; border-bottom:1px solid #eef1f5; }
        .theater-zone-legend .swatch { border-width:2px; border-style:solid; }

        .seat-map { display:flex; flex-direction:column; gap:8px; margin-top:8px; overflow-x:auto; }
        .seat-row { display:grid; grid-auto-flow:column; grid-auto-columns:36px; align-items:center; gap:10px; }
        .seat-row-label { grid-column:1; width:22px; font-size:12px; font-weight:600; color:var(--muted); text-align:center; }
        .seat-btn {
            grid-row:1; width:34px; height:34px; border-radius:45% 45% 15% 15%; font-size:11.5px; font-weight:600;
            border:1.5px solid #d7dce3; background:#fff; color:#4b5563; cursor:pointer; padding:0;
            transition:background-color .12s ease, border-color .12s ease, transform .08s ease, box-shadow .12s ease;
        }
        .seat-btn:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 3px 8px rgba(17,24,39,.12); border-color:var(--primary); }
        .seat-btn[data-status="booked"] { background:var(--seat-booked); border-color:var(--seat-booked); color:#fff; cursor:not-allowed; }
        .seat-btn[data-status="blocked"] { background:var(--seat-blocked); border-color:var(--seat-blocked-dark); color:#fff; }
        .seat-btn[data-status="selected"] { background:var(--primary); border-color:var(--primary); color:#fff; box-shadow:0 3px 8px rgba(160,191,59,.35); }
        .seat-btn[data-status="to-release"] { background:#fff; border-color:var(--seat-blocked); color:var(--seat-blocked); box-shadow:inset 0 0 0 1.5px var(--seat-blocked); }
        .seat-btn[data-status="to-block"] { background:var(--seat-blocked); border-color:var(--seat-blocked-dark); color:#fff; opacity:.6; }
        .seat-btn.vip { border-width:2.5px; }
        .seat-btn:disabled { cursor:default; }

        .theater-map-scroll { overflow-x:auto; overflow-y:hidden; margin-top:8px; -webkit-overflow-scrolling:touch; scroll-behavior: smooth; }
        .theater-map { position:relative; width:100%; min-width:760px; aspect-ratio:1090/615; transition: width 0.3s ease, min-width 0.3s ease; }
        .theater-map.zoomed { width: 170%; min-width: 1300px; }
        .theater-map svg { position:absolute; inset:0; width:100%; height:100%; overflow:visible; }
        .theater-seat {
            position:absolute; aspect-ratio:1/1; transform:translate(-50%,-50%);
            border-radius:48% 48% 18% 18% / 62% 62% 20% 20%; padding:0; font-size:0;
            border:1.5px solid transparent; cursor:pointer;
            transition:background-color .12s ease, border-color .12s ease, transform .1s ease, box-shadow .12s ease, filter .12s ease;
        }
        .theater-seat[data-class="vip"]     { width:1.9%; min-width:16px; background:var(--vip-tint); border-color:var(--vip); }
        .theater-seat[data-class="special"] { width:1.9%; min-width:16px; background:var(--special-tint); border-color:var(--special); }
        .theater-seat[data-class="1"] { width:1.5%; min-width:13px; background:var(--class1-tint); border-color:var(--class1); }
        .theater-seat[data-class="2"] { width:1.4%; min-width:12px; background:var(--class2-tint); border-color:var(--class2); }
        .theater-seat:hover:not(:disabled) { transform:translate(-50%,-50%) scale(1.35); z-index:5; box-shadow:0 3px 6px rgba(17,24,39,.2); filter:brightness(1.05); }
        .theater-seat[data-status="booked"] { background:var(--seat-booked); border-color:var(--seat-booked); cursor:not-allowed; }
        .theater-seat[data-status="blocked"] { background:var(--seat-blocked); border-color:var(--seat-blocked-dark); }
        .theater-seat[data-status="selected"] { background:var(--primary); border-color:var(--primary); box-shadow:0 2px 6px rgba(160,191,59,.45); }
        .theater-seat[data-status="to-release"] { background:#fff; border-style:dashed; border-color:var(--seat-blocked); box-shadow:inset 0 0 0 1.5px var(--seat-blocked); }
        .theater-seat[data-status="to-block"] { background:var(--seat-blocked); border-style:dashed; border-color:var(--seat-blocked-dark); opacity:.6; }
        .theater-seat:disabled { cursor:default; }
        .theater-seat:disabled:hover { transform:translate(-50%,-50%); box-shadow:none; }

        .theater-row-label {
            position:absolute; transform:translate(-50%,-50%); font-size:11px; font-weight:800;
            pointer-events:none; white-space:nowrap; opacity:.9; z-index:8;
            text-shadow: 0 0 3px #fff, 0 0 3px #fff, 0 0 3px #fff, 0 0 3px #fff;
        }
        .theater-row-label[data-class="1"] { color:var(--class1); }
        .theater-row-label[data-class="2"] { color:var(--class2); }
        .theater-row-label[data-class="special"] { color:var(--special); }

        .seat-summary { display:flex; flex-wrap:wrap; gap:10px; margin:20px 0 4px; }
        .seat-summary .chip { display:flex; align-items:center; gap:7px; padding:7px 14px; border-radius:999px; background:#f7f8fa; font-size:12.5px; font-weight:600; color:#374151; }
        .seat-summary .chip i { width:9px; height:9px; border-radius:50%; display:inline-block; }
        .seat-summary .chip strong { font-weight:800; }

        .chosen-seats { margin-top:14px; }
        .chosen-seats-label { font-size:12.5px; font-weight:600; color:#374151; margin-bottom:8px; }
        .chosen-seats-chips { display:flex; flex-wrap:wrap; gap:8px; }
        .chosen-seat-chip {
            display:inline-flex; align-items:center; gap:6px; padding:5px 8px 5px 12px; border-radius:999px;
            background:var(--primary-tint); color:var(--primary-hover); font-size:12.5px; font-weight:700;
            border:1px solid var(--primary);
        }
        .chosen-seat-chip button {
            border:none; background:rgba(0,0,0,.08); color:inherit; width:16px; height:16px; border-radius:50%;
            font-size:11px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0;
        }
        .chosen-seat-chip button:hover { background:rgba(0,0,0,.18); }
        .chosen-seats-empty { font-size:12.5px; color:var(--muted); }

        .seat-action-bar {
            position:sticky; bottom:0; left:0; right:0; z-index:10; margin-top:22px;
            background:#fff; border-top:1px solid #eef1f5; padding:16px 4px 4px;
            display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;
        }
        .mode-switch { display:inline-flex; align-items:center; gap:10px; font-size:13px; color:#374151; cursor:pointer; user-select:none; }
        .mode-switch .track {
            width:38px; height:22px; border-radius:999px; background:#d1d5db; position:relative; flex-shrink:0;
            transition:background-color .15s ease;
        }
        .mode-switch .track::after {
            content:''; position:absolute; top:2px; left:2px; width:18px; height:18px; border-radius:50%;
            background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.25); transition:transform .15s ease;
        }
        .mode-switch input { display:none; }
        .mode-switch input:checked + .track { background:var(--seat-blocked-dark); }
        .mode-switch input:checked + .track::after { transform:translateX(16px); }
        #pendingCount { font-size:12.5px; color:var(--muted); }
        #pendingCount.has-pending { color:var(--primary-hover); font-weight:600; }

        .bulk-select-bar {
            display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px; padding:14px 16px;
            margin-bottom:16px; background:#f7f8fa; border-radius:10px;
        }
        .bulk-select-bar .field { display:flex; flex-direction:column; gap:4px; }
        .bulk-select-bar label { font-size:11.5px; font-weight:600; color:#374151; }
        .bulk-select-bar input, .bulk-select-bar select {
            border:1px solid #d7dce3; border-radius:6px; padding:7px 10px; font-size:13px; min-width:100px;
        }
        .bulk-select-note { font-size:12px; color:var(--muted); flex-basis:100%; }
        .bulk-select-note.error { color:var(--seat-blocked-dark); font-weight:600; }

        .view-toggles { display: flex; justify-content: center; gap: 8px; margin-bottom: 16px; }
        .view-toggles .btn-toggle { background: #fff; border: 1px solid #d7dce3; color: #374151; font-weight: 600; padding: 6px 16px; border-radius: 6px; cursor: pointer; transition: all .15s ease; font-size: 13px; }
        .view-toggles .btn-toggle:hover { background: #f3f4f6; }
        .view-toggles .btn-toggle.active { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 2px 6px rgba(160,191,59,.3); pointer-events: none; }

        .scroll-hint { display:none; font-size:12px; color:var(--muted); margin-top:8px; }
        @media (max-width: 900px) {
            .scroll-hint { display:block; }
            .legend-bar { gap:10px 18px; }
            .seat-legend { gap:12px; font-size:12px; }
            .seat-action-bar { justify-content:center; text-align:center; }
        }
    </style>

    <section class="panel" style="padding:24px">
        <div class="legend-bar">
            <div class="seat-legend">
                <span><i class="swatch" style="background:#fff;border:1.5px solid #d7dce3"></i>Tersedia</span>
                <span><i class="swatch" style="background:var(--primary)"></i>Dipilih</span>
                <span><i class="swatch" style="background:var(--seat-booked)"></i>Booked</span>
                <span><i class="swatch" style="background:var(--seat-blocked)"></i>Blocked</span>
                @if (! $isTheaterFan && $seatsByRow->flatten()->contains(fn ($s) => $s->category))
                    <span><i class="swatch" style="border:2px solid #374151;background:#fff"></i>Kategori khusus (border tebal)</span>
                @endif
            </div>

            @if ($isTheaterFan)
                <div class="seat-legend theater-zone-legend">
                    <span><i class="swatch" style="border-color:var(--class2);background:var(--class2-tint)"></i>Class 2 &middot; 92 kursi/sisi</span>
                    <span><i class="swatch" style="border-color:var(--class1);background:var(--class1-tint)"></i>Class 1 &middot; 67 kursi/sisi</span>
                    <span><i class="swatch" style="border-color:var(--special);background:var(--special-tint)"></i>Special &middot; 60 kursi</span>
                </div>
            @endif
        </div>

        @if ($isTheaterFan)
            @if ($canManage)
                <div class="bulk-select-bar">
                    <div class="field">
                        <label for="bulkCount">Booking cepat — jumlah kursi</label>
                        <input type="number" id="bulkCount" min="1" max="{{ $totalSeats }}" placeholder="mis. 15">
                    </div>
                    <div class="field">
                        <label for="bulkClass">Kelas</label>
                        <select id="bulkClass">
                            <option value="">Manapun</option>
                            <option value="special">Special</option>
                            <option value="1">Class 1</option>
                            <option value="2">Class 2</option>
                        </select>
                    </div>
                    <button type="button" class="btn primary" id="bulkSelectBtn">Cari &amp; Pilih Kursi</button>
                    <span class="bulk-select-note" id="bulkSelectNote">Untuk rombongan/pembelian banyak (&gt;10 kursi) — cari kursi berdekatan otomatis, lalu tinjau di "Kursi yang dipilih" sebelum menyimpan.</span>
                </div>
            @endif
            
            <div class="view-toggles">
                <button type="button" class="btn-toggle active" id="btnViewAll">Lihat Semua</button>
                <button type="button" class="btn-toggle" id="btnViewLeft">Fokus Sisi Kiri</button>
                <button type="button" class="btn-toggle" id="btnViewRight">Fokus Sisi Kanan</button>
            </div>

            <p class="scroll-hint" style="text-align:center">â€¹ Geser untuk melihat semua bagian kursi â€º</p>
            <div class="theater-map-scroll" id="theaterScroll">
                <div class="theater-map" id="theaterMap">
                    <svg viewBox="0 0 1090 615" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                        <path d="M345,20 L745,20 L665,110 L425,110 Z" fill="#1f2937" />
                        <text x="545" y="65" text-anchor="middle" fill="#fff" font-size="20" font-weight="700">STAGE</text>
                        <text x="545" y="540" text-anchor="middle" fill="#9ca3af" font-size="13" font-weight="700">Special</text>
                        <text x="176" y="587" text-anchor="middle" fill="#9ca3af" font-size="13" font-weight="700">Class 1</text>
                        <text x="914" y="587" text-anchor="middle" fill="#9ca3af" font-size="13" font-weight="700">Class 1</text>
                        <text x="195" y="46" text-anchor="middle" fill="#9ca3af" font-size="12" font-weight="700">Class 2</text>
                        <text x="895" y="46" text-anchor="middle" fill="#9ca3af" font-size="12" font-weight="700">Class 2</text>
                    </svg>

                    @php
                        $classNames = ['N1' => 'Class 1', 'S1' => 'Class 1', 'N2' => 'Class 2', 'S2' => 'Class 2', 'special' => 'Special'];
                        $classKeys = ['N1' => '1', 'S1' => '1', 'N2' => '2', 'S2' => '2', 'special' => 'special'];
                    @endphp

                    @foreach ($rowLabelPositions as $rowLabelPos)
                        <span class="theater-row-label" data-class="{{ $rowLabelPos['classKey'] }}" style="left:{{ $rowLabelPos['xPct'] }}%; top:{{ $rowLabelPos['yPct'] }}%">{{ $rowLabelPos['letter'] }}</span>
                    @endforeach

                    @foreach ($seatsByRow as $rowLabel => $rowSeats)
                        @foreach ($rowSeats as $seat)
                            @php
                                $status = $statusBySeatId[$seat->id] ?? 'available';
                                $pos = $seatPositions[$seat->id];
                                $rowLetter = substr($rowLabel, strpos($rowLabel, '-') + 1);
                                $seatLabel = "{$classNames[$seat->category]} · {$rowLetter}{$seat->seat_number}";
                            @endphp
                            <button
                                type="button"
                                class="theater-seat"
                                style="left:{{ $pos['xPct'] }}%; top:{{ $pos['yPct'] }}%; rotate:{{ $pos['rotation'] ?? 0 }}deg;"
                                data-seat-id="{{ $seat->id }}"
                                data-class="{{ $classKeys[$seat->category] }}"
                                data-row="{{ $rowLabel }}"
                                data-seat-number="{{ $seat->seat_number }}"
                                data-label="{{ $seatLabel }}"
                                data-original-status="{{ $status }}"
                                data-status="{{ $status }}"
                                title="Kursi {{ $seatLabel }}"
                                @unless ($canManage) disabled @endunless
                            ></button>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="seat-summary">
                <span class="chip"><i style="background:var(--seat-booked)"></i>Booked: <strong id="countBooked">{{ $bookedCount }}</strong></span>
                <span class="chip"><i style="background:var(--seat-blocked)"></i>Blocked: <strong id="countBlocked">{{ $blockedCount }}</strong></span>
                <span class="chip"><i style="background:var(--primary)"></i>Dipilih: <strong id="countSelected">0</strong></span>
                <span class="chip"><i style="background:#e5e7eb"></i>Tersedia: <strong id="countAvailable">{{ $totalSeats - $bookedCount - $blockedCount }}</strong></span>
                <span class="chip" style="background:transparent;color:var(--muted);font-weight:500">dari {{ $totalSeats }} kursi total</span>
            </div>

            @if ($canManage)
                <div class="chosen-seats">
                    <div class="chosen-seats-label">Kursi yang dipilih</div>
                    <div class="chosen-seats-chips" id="chosenSeatsChips">
                        <span class="chosen-seats-empty" id="chosenSeatsEmpty">Belum ada kursi dipilih — klik kursi yang tersedia pada denah di atas.</span>
                    </div>
                </div>
            @endif

        @elseif ($isOutdoor)
            <style>
                .outdoor-map-container { display: flex; flex-direction: column; gap: 16px; position: relative; }
                .zone-group { cursor: pointer; transition: opacity 0.2s, filter 0.2s; }
                .zone-group:hover { opacity: 1 !important; filter: brightness(1.15); }
                .zone-group:hover .zone-top { filter: brightness(1.1); }
                .zone-popup { position: absolute; background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); border: 1px solid #eef1f5; z-index: 50; display: none; width: 280px; transform: translate(-50%, -10px); }
                .zone-popup-title { font-weight: 700; color: #111827; margin-bottom: 8px; font-size: 15px; }
                .zone-popup-btn { background: #059669; color: #fff; border: none; padding: 10px; width: 100%; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 12px; }
                .zone-popup-btn:hover { background: #047857; }
                .zone-seat-picker { display: none; background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #eef1f5; }
                .zone-grid { display: none; flex-direction: column; gap: 12px; overflow-x: auto; padding-bottom: 16px; align-items: center; }
                .zone-grid.active { display: flex; }
                .btn-back-map { background: #eafaf0; color: #059669; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; margin-bottom: 16px; }
                .btn-back-map:hover { background: #d1f4e0; }
                
                /* Indoor-style seats for outdoor zones */
                .indoor-style-container { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 16px; margin-top: 16px; }
                .indoor-legend { background: #fff; border-radius: 999px; padding: 12px 24px; display: flex; justify-content: center; gap: 24px; margin: 0 auto 20px; width: fit-content; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
                .indoor-stage { background: #4f46e5; color: #fff; text-align: center; font-weight: 700; padding: 10px; border-radius: 8px 8px 0 0; margin-bottom: 16px; letter-spacing: 1px; }
                .indoor-arrows { display: flex; justify-content: space-around; margin: 12px 0; color: #cbd5e1; font-weight: 800; font-size: 16px; }
                .indoor-grid-seat { width: 34px; height: 34px; padding: 0; min-width: 34px; border-radius: 45% 45% 15% 15%; font-size: 11.5px; font-weight: 600; display: flex; align-items: center; justify-content: center; border: 1.5px solid #d7dce3; background: #fff; color: #4b5563; cursor: pointer; transition: background-color .12s ease, border-color .12s ease, transform .08s ease, box-shadow .12s ease; }
                .indoor-grid-seat:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 3px 8px rgba(17,24,39,.12); border-color: var(--primary); }
                .indoor-grid-seat[data-status="selected"] { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 3px 8px rgba(160,191,59,.35); }
                .indoor-grid-seat[data-status="booked"] { background: var(--seat-booked); border-color: var(--seat-booked); color: #fff; cursor: not-allowed; }
                .indoor-grid-seat[data-status="blocked"] { background: var(--seat-blocked); border-color: var(--seat-blocked-dark); color: #fff; }
                .indoor-grid-seat[data-status="to-release"] { background: #fff; border-color: var(--seat-blocked); color: var(--seat-blocked); box-shadow: inset 0 0 0 1.5px var(--seat-blocked); }
                .indoor-grid-seat[data-status="to-block"] { background: var(--seat-blocked); border-color: var(--seat-blocked-dark); color: #fff; opacity: .6; }
            </style>
            
            <div class="outdoor-map-container" id="outdoorMapContainer">
                <p class="scroll-hint" id="outdoorScrollHint" style="text-align:left; margin:0; font-weight: 500; color: #6b7280;">Click on a zone to see ticket's availability</p>
                
                <div class="outdoor-map-scroll" id="outdoorScroll" style="position: relative; overflow-x: auto; margin-top:0;">
                    <div class="outdoor-map" id="outdoorMap" style="min-width: 900px; aspect-ratio: 2200/1000; position: relative;">
                        <!-- Using generated 3D Isometric SVG -->
                        <svg viewBox="0 0 1000 672" preserveAspectRatio="xMidYMid meet" aria-hidden="true" style="width:100%; height:100%; font-family:'Segoe UI',system-ui,sans-serif; display:block;">
                            <style>
                                .zone-group { cursor: pointer; transition: opacity 0.15s ease, transform 0.15s ease; transform-box: fill-box; transform-origin: center; }
                                .zone-group:hover { opacity: 0.82; transform: translateY(-2px); }
                                .zl { fill: rgba(255,255,255,0.95); font-weight: 700; text-anchor: middle; dominant-baseline: middle; pointer-events: none; letter-spacing: 0.8px; }
                            </style>

                            <!-- Background -->
                            <rect width="1000" height="672" fill="#f8fafc" rx="10"/>
                            <rect x="1" y="1" width="998" height="670" fill="none" stroke="#e2e8f0" stroke-width="1" rx="10"/>

                            <!-- STAGE — not a zone, not clickable -->
                            <g style="cursor:default; pointer-events:none;">
                                <rect x="272" y="20" width="456" height="232" fill="#94a3b8" rx="10"/>
                                <text class="zl" x="500" y="132" font-size="28" fill="white">STAGE</text>
                                <text class="zl" x="500" y="164" font-size="11" fill="rgba(255,255,255,0.5)" letter-spacing="3">OUTDOOR RAMAYANA</text>
                            </g>

                            <!-- VIP -->
                            <g class="zone-group" data-zone="VIP" data-label="VIP">
                                <rect x="352" y="264" width="296" height="116" fill="#ef4444" rx="8"/>
                                <text class="zl" x="500" y="322" font-size="20">VIP</text>
                            </g>

                            <!-- Khusus N -->
                            <g class="zone-group" data-zone="Khusus N" data-label="Khusus">
                                <rect x="352" y="390" width="142" height="100" fill="#22c55e" rx="8"/>
                                <text class="zl" x="423" y="440" font-size="13">KHUSUS</text>
                            </g>

                            <!-- Khusus S -->
                            <g class="zone-group" data-zone="Khusus S" data-label="Khusus">
                                <rect x="506" y="390" width="142" height="100" fill="#22c55e" rx="8"/>
                                <text class="zl" x="577" y="440" font-size="13">KHUSUS</text>
                            </g>

                            <!-- Klas I — left -->
                            <g class="zone-group" data-zone="Klas N1" data-label="Class I (North)">
                                <rect x="220" y="264" width="127" height="226" fill="#3b82f6" rx="8"/>
                                <text class="zl" x="284" y="377" font-size="14">KLAS I</text>
                            </g>

                            <!-- Klas I — right -->
                            <g class="zone-group" data-zone="Klas S1" data-label="Class I (South)">
                                <rect x="653" y="264" width="127" height="226" fill="#3b82f6" rx="8"/>
                                <text class="zl" x="716" y="377" font-size="14">KLAS I</text>
                            </g>

                            <!-- Klas II — left L-shape -->
                            <g class="zone-group" data-zone="Klas N2 C" data-label="Class II (North)">
                                <polygon points="78,20 268,20 268,200 212,200 212,532 78,532" fill="#f59e0b" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                                <text class="zl" x="173" y="108" font-size="14">KLAS II</text>
                                <text class="zl" x="145" y="368" font-size="14">KLAS II</text>
                            </g>

                            <!-- Klas II — right L-shape -->
                            <g class="zone-group" data-zone="Klas S2 C" data-label="Class II (South)">
                                <polygon points="732,20 922,20 922,532 788,532 788,200 732,200" fill="#f59e0b" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                                <text class="zl" x="827" y="108" font-size="14">KLAS II</text>
                                <text class="zl" x="855" y="368" font-size="14">KLAS II</text>
                            </g>

                            <!-- Free Seating — left -->
                            <g class="zone-group" data-zone="Free Seating N" data-label="Free Seating">
                                <polygon points="12,106 72,106 72,550 204,550 204,632 12,632" fill="#8b5cf6" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                                <text class="zl" x="42" y="375" font-size="12">FREE</text>
                            </g>

                            <!-- Free Seating — right -->
                            <g class="zone-group" data-zone="Free Seating S" data-label="Free Seating">
                                <polygon points="928,106 988,106 988,632 796,632 796,550 928,550" fill="#8b5cf6" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                                <text class="zl" x="958" y="375" font-size="12">FREE</text>
                            </g>
                        </svg>
                            
                        
                        <!-- Zone Popover -->
                        <div class="zone-popup" id="zonePopup">
                            <div class="zone-popup-title" id="zonePopupTitle">Zone Name</div>
                            <div style="font-size:13px; color:#6b7280; margin-bottom:4px" id="zonePopupSub">Outdoor Ramayana</div>
                            <button type="button" class="zone-popup-btn" id="btnChooseZone">Choose This Zone</button>
                        </div>
                    </div>
                </div>

                <!-- Seat Picker View -->
                <div class="zone-seat-picker" id="zoneSeatPicker">
                    <button type="button" class="btn-back-map" id="btnBackToMap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px"><path d="m15 18-6-6 6-6"/></svg> Back
                    </button>
                    
                    <h3 style="margin: 0 0 16px 0; font-size: 16px; color: #374151;">
                        Choose seat(s) for <strong id="pickerZoneLabel"></strong>
                    </h3>

                    @php
                        $outdoorZones = [];
                        foreach ($seatsByRow as $rowLabel => $rowSeats) {
                            $zone = explode('-', $rowLabel, 2)[0];
                            $mappedZone = $zone;
                            if (in_array($zone, ['Klas N2 A', 'Klas N2 B'])) $mappedZone = 'Klas N1';
                            if (in_array($zone, ['Klas S2 A', 'Klas S2 B'])) $mappedZone = 'Klas S1';
                            
                            $outdoorZones[$mappedZone][$rowLabel] = $rowSeats;
                        }
                    @endphp

                    <div class="indoor-style-container">
                        <div class="indoor-legend">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 20px; height: 20px; border-radius: 6px; border: 1.5px solid #d7dce3; background: #fff;"></div>
                                <span style="font-size: 13px; color: #475569;">Available</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 20px; height: 20px; border-radius: 6px; background: var(--primary);"></div>
                                <span style="font-size: 13px; color: #475569;">Chosen</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 20px; height: 20px; border-radius: 6px; background: var(--seat-booked);"></div>
                                <span style="font-size: 13px; color: #475569;">Unavailable</span>
                            </div>
                        </div>

                        <div class="indoor-stage">STAGE</div>

                        <div class="seat-map" style="margin-top:0">
                            @foreach ($outdoorZones as $zoneKey => $zoneRows)
                                <div class="zone-grid" id="grid-{{ Str::slug($zoneKey) }}">
                                    <div class="indoor-arrows">&uarr; &nbsp; &nbsp; &nbsp; &uarr; &nbsp; &nbsp; &nbsp; &uarr; &nbsp; &nbsp; &nbsp; &uarr; &nbsp; &nbsp; &nbsp; &uarr;</div>
                                    @php $rowsCollection = is_array($zoneRows) ? collect($zoneRows) : $zoneRows; @endphp
                                    @foreach ($rowsCollection->reverse() as $rowLabel => $rowSeats)
                                        <div class="seat-row" style="grid-template-columns:repeat({{ count($rowSeats) }}, 42px); justify-content: center;">
                                            @php $seatsCollection = is_array($rowSeats) ? collect($rowSeats) : (is_object($rowSeats) && method_exists($rowSeats, 'all') ? collect($rowSeats->all()) : collect($rowSeats)); @endphp
                                            @foreach ($seatsCollection->reverse()->values() as $index => $seat)
                                                @php 
                                                    $status = $statusBySeatId[$seat->id] ?? 'available';
                                                    $letter = explode('-', $rowLabel);
                                                    $letter = end($letter); 
                                                @endphp
                                                <button
                                                    type="button"
                                                    class="seat-btn indoor-grid-seat"
                                                    style="grid-column:{{ $index + 1 }}"
                                                    data-seat-id="{{ $seat->id }}"
                                                    data-original-status="{{ $status }}"
                                                    data-status="{{ $status }}"
                                                    title="Kursi {{ $rowLabel }}-{{ $seat->seat_number }}"
                                                    @unless ($canManage) disabled @endunless
                                                >{{ $letter }}{{ $seat->seat_number }}</button>
                                            @endforeach
                                        </div>
                                    @endforeach
                                    <div class="indoor-arrows">&uarr; &nbsp; &nbsp; &nbsp; &uarr; &nbsp; &nbsp; &nbsp; &uarr; &nbsp; &nbsp; &nbsp; &uarr; &nbsp; &nbsp; &nbsp; &uarr;</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const paths = document.querySelectorAll('.zone-group');
                    const popup = document.getElementById('zonePopup');
                    const popupTitle = document.getElementById('zonePopupTitle');
                    const popupSub = document.getElementById('zonePopupSub');
                    const btnChoose = document.getElementById('btnChooseZone');
                    const mapScroll = document.getElementById('outdoorScroll');
                    const scrollHint = document.getElementById('outdoorScrollHint');
                    const picker = document.getElementById('zoneSeatPicker');
                    const btnBack = document.getElementById('btnBackToMap');
                    const pickerLabel = document.getElementById('pickerZoneLabel');
                    let currentZoneKey = null;

                    paths.forEach(path => {
                        path.addEventListener('click', (e) => {
                            e.stopPropagation();
                            
                            // Map coordinates to DOM coordinates
                            const rect = path.getBoundingClientRect();
                            const mapRect = document.getElementById('outdoorMap').getBoundingClientRect();
                            
                            // Estimate position in the center of the bounding box
                            const left = rect.left - mapRect.left + (rect.width / 2);
                            const top = rect.top - mapRect.top;

                            popup.style.left = `${left}px`;
                            popup.style.top = `${top}px`;
                            popup.style.display = 'block';
                            
                            const label = path.dataset.label;
                            popupTitle.textContent = label;
                            popupSub.textContent = `Outdoor Ramayana - Tiket ${label}`;
                            currentZoneKey = path.dataset.zone;
                        });
                    });

                    document.addEventListener('click', (e) => {
                        if (!e.target.closest('.zone-popup') && !e.target.closest('.zone-group')) {
                            popup.style.display = 'none';
                        }
                    });

                    btnChoose.addEventListener('click', () => {
                        mapScroll.style.display = 'none';
                        scrollHint.style.display = 'none';
                        picker.style.display = 'block';
                        popup.style.display = 'none';
                        pickerLabel.textContent = popupTitle.textContent;
                        
                        document.querySelectorAll('.zone-grid').forEach(el => el.classList.remove('active'));
                        const slug = currentZoneKey.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
                        const targetGrid = document.getElementById(`grid-${slug}`);
                        if (targetGrid) targetGrid.classList.add('active');
                    });

                    btnBack.addEventListener('click', () => {
                        picker.style.display = 'none';
                        mapScroll.style.display = 'block';
                        scrollHint.style.display = 'block';
                    });
                });
            </script>

            <div class="seat-summary">
                <span class="chip"><i style="background:var(--seat-booked)"></i>Booked: <strong id="countBooked">{{ $bookedCount }}</strong></span>
                <span class="chip"><i style="background:var(--seat-blocked)"></i>Blocked: <strong id="countBlocked">{{ $blockedCount }}</strong></span>
                <span class="chip"><i style="background:var(--primary)"></i>Dipilih: <strong id="countSelected">0</strong></span>
                <span class="chip"><i style="background:#e5e7eb"></i>Tersedia: <strong id="countAvailable">{{ $totalSeats - $bookedCount - $blockedCount }}</strong></span>
                <span class="chip" style="background:transparent;color:var(--muted);font-weight:500">dari {{ $totalSeats }} kursi total</span>
            </div>

            @if ($canManage)
                <div class="chosen-seats">
                    <div class="chosen-seats-label">Kursi yang dipilih</div>
                    <div class="chosen-seats-chips" id="chosenSeatsChips">
                        <span class="chosen-seats-empty" id="chosenSeatsEmpty">Belum ada kursi dipilih — klik kursi yang tersedia pada denah di atas.</span>
                    </div>
                </div>
            @endif

        @else
            <div class="seat-map">
                @foreach ($seatsByRow as $rowLabel => $rowSeats)
                    <div class="seat-row" style="grid-template-columns:22px repeat({{ $maxSeatNumber }}, 36px)">
                        <div class="seat-row-label">{{ $rowLabel }}</div>
                        @foreach ($rowSeats as $seat)
                            @php $status = $statusBySeatId[$seat->id] ?? 'available'; @endphp
                            <button
                                type="button"
                                class="seat-btn {{ $seat->category ? 'vip' : '' }}"
                                style="grid-column:{{ $seat->seat_number + 1 }}"
                                data-seat-id="{{ $seat->id }}"
                                data-original-status="{{ $status }}"
                                data-status="{{ $status }}"
                                title="{{ $seat->label() }}{{ $seat->category ? ' · '.$seat->category : '' }}"
                                @unless ($canManage) disabled @endunless
                            >{{ $seat->seat_number }}</button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        @if ($canManage)
            <form method="POST" action="{{ route('bookings.seats.update', $booking) }}" id="seatForm">
                @csrf
                <div id="seatInputs"></div>

                <div class="seat-action-bar">
                    <label class="mode-switch">
                        <input type="checkbox" id="blockMode">
                        <span class="track"></span>
                        Mode blokir kursi (mis. kursi rusak / maintenance)
                    </label>

                    <div style="display:flex;align-items:center;gap:12px">
                        <span id="pendingCount"></span>
                        <button type="submit" class="btn primary">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        @endif
    </section>

    <script>
        const canManage = {{ $canManage ? 'true' : 'false' }};
        const pending = {}; // seatId -> 'book' | 'block' | 'release'
        const seatButtons = {}; // seatId -> button element

        function updateCounters() {
            const pendingEl = document.getElementById('pendingCount');
            const count = Object.keys(pending).length;
            if (pendingEl) {
                pendingEl.textContent = count ? `${count} kursi belum disimpan` : '';
                pendingEl.classList.toggle('has-pending', count > 0);
            }

            const selectedIds = Object.entries(pending).filter(([, a]) => a === 'book').map(([id]) => id);

            const selectedEl = document.getElementById('countSelected');
            if (selectedEl) selectedEl.textContent = selectedIds.length;

            const chipsBox = document.getElementById('chosenSeatsChips');
            if (chipsBox) {
                chipsBox.innerHTML = '';

                if (selectedIds.length === 0) {
                    const empty = document.createElement('span');
                    empty.className = 'chosen-seats-empty';
                    empty.id = 'chosenSeatsEmpty';
                    empty.textContent = 'Belum ada kursi dipilih — klik kursi yang tersedia pada denah di atas.';
                    chipsBox.appendChild(empty);
                } else {
                    selectedIds
                        .map(id => ({ id, label: seatButtons[id]?.dataset.label || id }))
                        .sort((a, b) => a.label.localeCompare(b.label, 'id', { numeric: true }))
                        .forEach(({ id, label }) => {
                            const chip = document.createElement('span');
                            chip.className = 'chosen-seat-chip';
                            chip.innerHTML = `${label} <button type="button" aria-label="Batal pilih ${label}">&times;</button>`;
                            chip.querySelector('button').addEventListener('click', () => toggleSeat(id));
                            chipsBox.appendChild(chip);
                        });
                }
            }
        }

        function render(btn) {
            const original = btn.dataset.originalStatus;
            const action = pending[btn.dataset.seatId];

            if (!action) {
                btn.dataset.status = original;
                return;
            }

            if (action === 'book') btn.dataset.status = 'selected';
            else if (action === 'block') btn.dataset.status = 'to-block';
            else if (action === 'release') btn.dataset.status = 'to-release';
        }

        function toggleSeat(seatId) {
            const btn = seatButtons[seatId];
            if (!btn) return;

            const original = btn.dataset.originalStatus;
            const blockMode = document.getElementById('blockMode').checked;

            if (blockMode) {
                if (original === 'available') {
                    pending[seatId] = pending[seatId] === 'block' ? undefined : 'block';
                } else if (original === 'blocked') {
                    pending[seatId] = pending[seatId] === 'release' ? undefined : 'release';
                } else {
                    return; // booked seats: release them from booking mode first
                }
            } else {
                if (original === 'available') {
                    pending[seatId] = pending[seatId] === 'book' ? undefined : 'book';
                } else if (original === 'booked') {
                    pending[seatId] = pending[seatId] === 'release' ? undefined : 'release';
                } else {
                    return; // blocked seats: switch to block mode to unblock
                }
            }

            if (pending[seatId] === undefined) delete pending[seatId];
            render(btn);
            updateCounters();
        }

        function bulkSelect() {
            const noteEl = document.getElementById('bulkSelectNote');
            const countInput = document.getElementById('bulkCount');
            const count = parseInt(countInput.value, 10);
            noteEl.classList.remove('error');

            if (!count || count < 1) {
                noteEl.textContent = 'Masukkan jumlah kursi terlebih dahulu.';
                noteEl.classList.add('error');
                return;
            }

            const classFilter = document.getElementById('bulkClass').value;

            // Only seats that are genuinely available and not already staged by a
            // previous click/search are candidates.
            const rows = {};
            Object.values(seatButtons).forEach(btn => {
                if (btn.dataset.originalStatus !== 'available') return;
                if (pending[btn.dataset.seatId]) return;
                if (classFilter && btn.dataset.class !== classFilter) return;
                const row = btn.dataset.row;
                if (!row) return;
                (rows[row] = rows[row] || []).push(btn);
            });

            Object.values(rows).forEach(list => list.sort((a, b) => Number(a.dataset.seatNumber) - Number(b.dataset.seatNumber)));

            // Prefer seats sitting next to each other in the same row — find the
            // tightest-fitting consecutive run of at least `count` seats.
            let best = null;
            Object.values(rows).forEach(list => {
                let runStart = 0;
                for (let i = 1; i <= list.length; i++) {
                    const broke = i === list.length || Number(list[i].dataset.seatNumber) !== Number(list[i - 1].dataset.seatNumber) + 1;
                    if (broke) {
                        const runLength = i - runStart;
                        if (runLength >= count && (!best || runLength < best.length)) {
                            best = { seats: list.slice(runStart, runStart + count), length: runLength };
                        }
                        runStart = i;
                    }
                }
            });

            let chosen;
            let message;

            if (best) {
                chosen = best.seats;
                message = `${count} kursi berdekatan dipilih: ${chosen[0].dataset.label} s/d ${chosen[chosen.length - 1].dataset.label}.`;
            } else {
                const all = Object.values(rows).flat();
                if (all.length < count) {
                    noteEl.textContent = `Hanya ${all.length} kursi tersedia untuk filter ini — kurangi jumlah atau ubah kelas.`;
                    noteEl.classList.add('error');
                    return;
                }
                chosen = all.slice(0, count);
                message = `${count} kursi tersedia dipilih — tidak seluruhnya berdekatan dalam satu baris. Tinjau di "Kursi yang dipilih" di bawah.`;
            }

            chosen.forEach(btn => {
                pending[btn.dataset.seatId] = 'book';
                render(btn);
            });
            updateCounters();
            noteEl.textContent = message;
        }

            if (canManage) {
                document.querySelectorAll('.seat-btn, .theater-seat').forEach(btn => {
                    seatButtons[btn.dataset.seatId] = btn;
                    btn.addEventListener('click', () => toggleSeat(btn.dataset.seatId));
                });

                document.getElementById('bulkSelectBtn')?.addEventListener('click', bulkSelect);

                document.getElementById('seatForm').addEventListener('submit', () => {
                    const inputs = document.getElementById('seatInputs');
                    inputs.innerHTML = '';

                    Object.entries(pending).forEach(([seatId, action]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `${action}[]`;
                        input.value = seatId;
                        inputs.appendChild(input);
                    });
                });
            }

            // View Toggles for Maps
            const btnAll = document.getElementById('btnViewAll');
            const btnLeft = document.getElementById('btnViewLeft');
            const btnRight = document.getElementById('btnViewRight');
            const mapEl = document.getElementById('outdoorMap') || document.getElementById('theaterMap');
            const scrollEl = document.getElementById('outdoorScroll') || document.getElementById('theaterScroll');

            if (btnAll && btnLeft && btnRight && mapEl && scrollEl) {
                const setActive = (btn) => {
                    [btnAll, btnLeft, btnRight].forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                };

                btnAll.addEventListener('click', () => {
                    setActive(btnAll);
                    mapEl.classList.remove('zoomed');
                });

                btnLeft.addEventListener('click', () => {
                    setActive(btnLeft);
                    mapEl.classList.add('zoomed');
                    setTimeout(() => { scrollEl.scrollLeft = 0; }, 50);
                });

                btnRight.addEventListener('click', () => {
                    setActive(btnRight);
                    mapEl.classList.add('zoomed');
                    setTimeout(() => { scrollEl.scrollLeft = scrollEl.scrollWidth; }, 50);
                });
            }
        </script>

@endsection
