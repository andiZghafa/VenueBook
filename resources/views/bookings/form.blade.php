@extends('layouts.app')

@section('title', $booking->exists ? 'Edit Event' : ($seatedOnly ? 'Tambah Jadwal Pertunjukan' : 'Tambah Event'))

@section('content')

    <div class="topbar">
        <div class="title">
            <p style="margin:0 0 4px;font-size:12px;color:var(--muted)">
                @if ($seatedOnly && ! $booking->exists)
                    <a href="{{ route('seats.index') }}">Booking Kursi</a> / Tambah Jadwal
                @endif
            </p>
            <h1>{{ $booking->exists ? 'Edit Event' : ($seatedOnly ? 'Tambah Jadwal Pertunjukan' : 'Tambah Event') }}</h1>
            <p>
                @if ($booking->exists)
                    {{ $booking->code }}
                @elseif ($seatedOnly)
                    Jadwal baru untuk venue berkursi — kelola ketersediaan kursinya setelah disimpan.
                @elseif (! auth()->user()->isAdmin())
                    Anda hanya dapat membuat event untuk destinasi <b>{{ auth()->user()->destination?->name }}</b>.
                @else
                    Isi detail event baru di bawah ini.
                @endif
            </p>
        </div>
    </div>

    <section class="panel form-card">
        <form method="POST" action="{{ $booking->exists ? route('bookings.update', $booking) : route('bookings.store') }}">
            @csrf
            @if ($booking->exists)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="field full">
                    <label>{{ $seatedOnly ? 'Nama Pertunjukan' : 'Nama Event' }}</label>
                    <input type="text" name="title" value="{{ old('title', $booking->title) }}" required placeholder="{{ $seatedOnly ? 'Contoh: Ramayana Ballet' : 'Contoh: Corporate Gathering' }}">
                    @error('title') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label>Venue</label>
                    <select name="venue_id" id="venueSelect" required onchange="onVenueChange()">
                        <option value="">— Pilih Venue —</option>
                        @foreach ($venues->groupBy(fn ($v) => $v->destination->name) as $destinationName => $venueGroup)
                            <optgroup label="{{ $destinationName }}">
                                @foreach ($venueGroup as $v)
                                    <option value="{{ $v->id }}" data-destination-id="{{ $v->destination_id }}" @selected(old('venue_id', $booking->venue_id) == $v->id)>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div id="venueHint" style="font-size:12px;color:var(--muted);margin-top:6px"></div>
                    @error('venue_id') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label>Jenis Event</label>
                    <select name="event_type_id" required>
                        <option value="">— Pilih Jenis —</option>
                        @foreach ($eventTypes as $t)
                            <option value="{{ $t->id }}" @selected(old('event_type_id', $booking->event_type_id) == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                    @error('event_type_id') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label>Tanggal Mulai</label>
                    <input type="text" id="startDate" data-flatpickr="date" name="start_date" value="{{ old('start_date', optional($booking->start_date)->format('Y-m-d')) }}" placeholder="Pilih tanggal" required autocomplete="off">
                    @error('start_date') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label>Tanggal Selesai</label>
                    <input type="text" id="endDate" data-flatpickr="date" name="end_date" value="{{ old('end_date', optional($booking->end_date)->format('Y-m-d')) }}" placeholder="Pilih tanggal" required autocomplete="off">
                    @error('end_date') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field full loading-unloading-box">
                    <div class="loading-unloading-title">Loading &amp; Unloading <span>(opsional — lacak jam kru setup/bongkar di luar jam event)</span></div>
                    <div class="loading-unloading-grid">
                        <div class="field">
                            <label>Loading (kru mulai setup)</label>
                            <input type="text" data-flatpickr="datetime" name="load_at" value="{{ old('load_at', optional($booking->load_at)->format('Y-m-d H:i')) }}" placeholder="Pilih tanggal & jam" autocomplete="off">
                            <div style="font-size:11px;color:var(--muted);margin-top:4px">Boleh sebelum tanggal mulai. Kosongkan jika tidak perlu dilacak.</div>
                            @error('load_at') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field">
                            <label>Unloading (venue kosong lagi)</label>
                            <input type="text" data-flatpickr="datetime" name="unload_at" value="{{ old('unload_at', optional($booking->unload_at)->format('Y-m-d H:i')) }}" placeholder="Pilih tanggal & jam" autocomplete="off">
                            <div style="font-size:11px;color:var(--muted);margin-top:4px">Boleh setelah tanggal selesai.</div>
                            @error('unload_at') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label>Klien / Organizer</label>
                    <select name="client_id">
                        <option value="">— Tidak ada / Internal —</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}" @selected(old('client_id', $booking->client_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <div style="margin-top:6px"><a href="{{ route('clients.create') }}" target="_blank" style="font-size:12px;color:var(--primary-hover);font-weight:700">+ Tambah klien baru (tab baru)</a></div>
                </div>

                <div class="field">
                    <label>PIC Sales</label>
                    <select name="pic_user_id" id="picSelect">
                        <option value="">— Belum ditentukan —</option>
                        @foreach ($salesUsers as $u)
                            <option value="{{ $u->id }}" data-destination-id="{{ $u->destination_id }}" @selected(old('pic_user_id', $booking->pic_user_id) == $u->id)>{{ $u->name }}{{ $u->isAdmin() ? ' (Admin)' : '' }}</option>
                        @endforeach
                    </select>
                    <div id="picHint" style="font-size:11px;color:var(--muted);margin-top:4px">Pilih venue dulu untuk menyaring PIC sesuai destinasi.</div>
                </div>

                <div class="field">
                    <label>Jumlah Tamu (estimasi)</label>
                    <input type="number" min="1" name="guest_count" value="{{ old('guest_count', $booking->guest_count) }}">
                </div>

                <div class="field">
                    <label>Status</label>
                    <select name="status" required>
                        @foreach (['hold' => 'Hold', 'tentative' => 'Tentative', 'fix' => 'Fix', 'cancelled' => 'Cancelled', 'maintenance' => 'Maintenance'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $booking->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field full">
                    <label>Catatan</label>
                    <textarea name="notes" rows="4" placeholder="Kebutuhan teknis, setup, dll.">{{ old('notes', $booking->notes) }}</textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn primary">{{ $booking->exists ? 'Simpan Perubahan' : 'Simpan Event' }}</button>
                <a href="{{ $booking->exists ? route('bookings.show', $booking) : ($seatedOnly ? route('seats.index') : route('bookings.index')) }}" class="btn">Batal</a>
            </div>
        </form>
    </section>

    <script>
        const VENUE_INFO = @json($venueInfo);

        function onVenueChange() {
            const venueSelect = document.getElementById('venueSelect');
            const selected = venueSelect.options[venueSelect.selectedIndex];
            const destId = selected ? selected.getAttribute('data-destination-id') : null;

            const hint = document.getElementById('venueHint');
            const info = VENUE_INFO[venueSelect.value];
            hint.textContent = info
                ? `${info.type}${info.capacity ? ' · kapasitas ' + info.capacity.toLocaleString('id-ID') + ' orang' : ''}${info.sizeSqm ? ' · ' + info.sizeSqm.toLocaleString('id-ID') + ' m²' : ''}${info.description ? ' · ' + info.description : ''}`
                : '';

            const picSelect = document.getElementById('picSelect');
            const picHint = document.getElementById('picHint');
            let stillValid = false;

            Array.from(picSelect.options).forEach((opt) => {
                if (!opt.value) {
                    return;
                }
                const optDestId = opt.getAttribute('data-destination-id');
                const isAdminOption = !optDestId;
                const show = !destId || isAdminOption || optDestId === destId;
                opt.hidden = !show;
                if (show && opt.value === picSelect.value) {
                    stillValid = true;
                }
            });

            if (picSelect.value && !stillValid) {
                picSelect.value = '';
            }

            picHint.textContent = destId
                ? 'Hanya menampilkan sales destinasi ini (dan admin).'
                : 'Pilih venue dulu untuk menyaring PIC sesuai destinasi.';
        }

        onVenueChange();
    </script>

@endsection
