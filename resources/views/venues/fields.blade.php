<div class="form-grid">
    <div class="field full">
        <label>Destinasi</label>
        <select name="destination_id" required>
            <option value="">— Pilih Destinasi —</option>
            @foreach ($destinations as $d)
                <option value="{{ $d->id }}" @selected(old('destination_id', $venue->destination_id) == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
        @error('destination_id') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field full">
        <label>Nama Venue</label>
        <input type="text" name="name" value="{{ old('name', $venue->name) }}" required placeholder="Contoh: Manohara Field">
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label>Tipe</label>
        <select name="type" required>
            <option value="outdoor" @selected(old('type', $venue->type ?? 'outdoor') === 'outdoor')>Outdoor</option>
            <option value="indoor" @selected(old('type', $venue->type ?? '') === 'indoor')>Indoor</option>
        </select>
    </div>
    <div class="field">
        <label>Kapasitas (orang)</label>
        <input type="number" min="1" name="capacity" value="{{ old('capacity', $venue->capacity) }}">
    </div>
    <div class="field">
        <label>Luas (m²)</label>
        <input type="number" min="1" name="size_sqm" value="{{ old('size_sqm', $venue->size_sqm) }}" placeholder="Belum ada data">
    </div>
    <div class="field full">
        <label>Deskripsi</label>
        <textarea name="description" rows="4" placeholder="Catatan singkat tentang venue ini">{{ old('description', $venue->description) }}</textarea>
    </div>
    <div class="field">
        <label>Status</label>
        <select name="is_active">
            <option value="1" @selected(old('is_active', $venue->is_active ?? true) == 1)>Aktif</option>
            <option value="0" @selected(old('is_active', $venue->is_active ?? true) == 0)>Nonaktif</option>
        </select>
    </div>
    <div class="field full">
        <label><input type="checkbox" name="is_seated" value="1" @checked(old('is_seated', $venue->is_seated ?? false))> Booking per kursi (venue punya denah kursi tetap, mis. teater)</label>
        <p style="font-size:12px;color:var(--muted);margin:4px 0 0">Jika diaktifkan, setiap event di venue ini bisa mengelola ketersediaan per kursi. Denah kursi (baris &amp; nomor) perlu disiapkan lewat data seeding/import.</p>
    </div>
</div>
