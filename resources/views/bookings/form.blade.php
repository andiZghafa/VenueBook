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
        <form id="bookingForm" method="POST" action="{{ $booking->exists ? route('bookings.update', $booking) : route('bookings.store') }}">
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

                <div class="field full">
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:8px;">
                        <label id="clientLabel" aria-live="polite" style="margin-bottom:0;">Client/Organizer</label>
                        <label class="switch" title="Gunakan organizer yang berbeda dengan client">
                            <input type="checkbox" role="switch" aria-controls="organizerCol" aria-expanded="{{ old('is_separate_organizer', $booking->organizer_id && $booking->organizer_id !== $booking->client_id) ? 'true' : 'false' }}" name="is_separate_organizer" id="separateOrganizerToggle" value="1" @checked(old('is_separate_organizer', $booking->organizer_id && $booking->organizer_id !== $booking->client_id))>
                            <div class="switch-track">
                                <div class="switch-knob"></div>
                            </div>
                            <span>Client dan Organizer berbeda</span>
                        </label>
                    </div>
                    <div class="organizer-container {{ old('is_separate_organizer', $booking->organizer_id && $booking->organizer_id !== $booking->client_id) ? 'is-active' : '' }}" id="organizerContainer">
                        <div class="client-col">
                            <input type="text" name="client_name" list="clientList" value="{{ old('client_name', $booking->client?->name) }}" placeholder="Ketik nama client atau pilih dari daftar..." autocomplete="off">
                            <datalist id="clientList">
                                @foreach ($clients as $c)
                                    <option value="{{ $c->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="organizer-col {{ old('is_separate_organizer', $booking->organizer_id && $booking->organizer_id !== $booking->client_id) ? 'is-active' : '' }}" id="organizerCol">
                            <div class="organizer-col-inner">
                                <input type="text" name="organizer_name" id="organizerInput" list="organizerList" value="{{ old('organizer_name', $booking->organizer?->name) }}" placeholder="Ketik nama organizer..." autocomplete="off">
                                <datalist id="organizerList">
                                    @foreach ($clients as $c)
                                        <option value="{{ $c->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>
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
                    <label>Harga/Price (Rp)</label>
                    <input type="text" id="priceInput" value="{{ old('price', $booking->price ? number_format($booking->price, 0, '', '.') : '') }}" placeholder="Contoh: 1.500.000" autocomplete="off" oninput="formatPrice(this)">
                    <input type="hidden" name="price" id="priceHidden" value="{{ old('price', $booking->price) }}">
                    @error('price') <div class="field-error">{{ $message }}</div> @enderror
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

    <div id="confirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center;">
        <div style="background:#fff; padding:24px; border-radius:12px; width:400px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
            <h3 style="margin-top:0;">Konfirmasi Event</h3>
            <p style="font-size:13px; color:var(--muted); margin-bottom:16px;">Mohon periksa kembali detail event berikut:</p>
            <div id="summaryContent" style="margin-bottom:24px; font-size:13px; line-height:1.6; color:#000;"></div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn" onclick="document.getElementById('confirmModal').style.display='none'">Kembali</button>
                <button type="button" class="btn primary" onclick="document.getElementById('bookingForm').submit()">Konfirmasi & Simpan</button>
            </div>
        </div>
    </div>

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

        const separateToggle = document.getElementById('separateOrganizerToggle');
        const organizerContainer = document.getElementById('organizerContainer');
        const organizerCol = document.getElementById('organizerCol');
        const organizerInput = document.getElementById('organizerInput');
        const clientLabel = document.getElementById('clientLabel');
        let savedOrganizerName = '';
        
        function toggleOrganizer(isInit = false) {
            const isChecked = separateToggle.checked;
            separateToggle.setAttribute('aria-expanded', isChecked);
            
            if (isChecked) {
                organizerContainer.classList.add('is-active');
                organizerCol.classList.add('is-active');
                clientLabel.textContent = 'Client & Organizer';
                
                organizerInput.disabled = false;
                if (!isInit && savedOrganizerName) {
                    organizerInput.value = savedOrganizerName;
                }
            } else {
                organizerContainer.classList.remove('is-active');
                organizerCol.classList.remove('is-active');
                clientLabel.textContent = 'Client/Organizer';
                
                if (!isInit) savedOrganizerName = organizerInput.value;
                organizerInput.disabled = true;
                
                setTimeout(() => {
                    if (!separateToggle.checked) {
                        organizerInput.value = '';
                    }
                }, 250);
            }
        }
        
        if (separateToggle) {
            separateToggle.addEventListener('change', () => toggleOrganizer(false));
            savedOrganizerName = organizerInput.value;
            toggleOrganizer(true);
        }

        function formatPrice(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            if (value) {
                document.getElementById('priceHidden').value = value;
                input.value = new Intl.NumberFormat('id-ID').format(value);
            } else {
                document.getElementById('priceHidden').value = '';
                input.value = '';
            }
        }

        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            // Check HTML5 validity so it doesn't show modal if fields are missing
            if (!this.checkValidity()) return;

            if (document.getElementById('confirmModal').style.display === 'none') {
                e.preventDefault();
                
                const title = this.title.value;
                const venue = this.venue_id.options[this.venue_id.selectedIndex]?.text || '-';
                const type = this.event_type_id.options[this.event_type_id.selectedIndex]?.text || '-';
                const startDate = this.start_date.value;
                const endDate = this.end_date.value;
                const client = this.client_name.value || 'Internal/Tidak ada';
                const isSeparate = this.is_separate_organizer && this.is_separate_organizer.checked;
                const organizer = isSeparate ? (this.organizer_name.value || 'Internal/Tidak ada') : client;
                const priceVal = document.getElementById('priceHidden').value;
                const price = priceVal ? 'Rp ' + new Intl.NumberFormat('id-ID').format(priceVal) : '-';
                
                let html = `
                    <div style="display:grid; grid-template-columns: 100px 1fr; gap:8px;">
                        <strong style="color:var(--muted)">Nama:</strong> <span>${title}</span>
                        <strong style="color:var(--muted)">Venue:</strong> <span>${venue}</span>
                        <strong style="color:var(--muted)">Jenis:</strong> <span>${type}</span>
                        <strong style="color:var(--muted)">Client:</strong> <span>${client}</span>
                        ${isSeparate ? `<strong style="color:var(--muted)">Organizer:</strong> <span>${organizer}</span>` : ''}
                        <strong style="color:var(--muted)">Tanggal:</strong> <span>${startDate} s/d ${endDate}</span>
                        <strong style="color:var(--muted)">Harga:</strong> <span>${price}</span>
                    </div>
                `;
                
                document.getElementById('summaryContent').innerHTML = html;
                document.getElementById('confirmModal').style.display = 'flex';
            }
        });
    </script>

@endsection
