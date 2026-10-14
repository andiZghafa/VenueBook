@php
    $client = $client ?? null;
    $prefix = $prefix ?? '';
@endphp

<div class="form-grid">
    <div class="field full">
        <label for="{{ $prefix }}name">Nama <span style="color:var(--red)">*</span></label>
        <input type="text" id="{{ $prefix }}name" name="name" value="{{ old('name', $client?->name) }}" required placeholder="Nama client atau instansi">
        <div id="{{ $prefix }}name_error" class="field-error" style="display:none">Nama wajib diisi</div>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="{{ $prefix }}email">Email</label>
        <input type="email" id="{{ $prefix }}email" name="email" value="{{ old('email', $client?->email) }}" placeholder="email@contoh.com">
        @error('email') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="{{ $prefix }}phone">Telepon</label>
        <input type="text" id="{{ $prefix }}phone" name="phone" value="{{ old('phone', $client?->phone) }}" placeholder="08xxxxxxxxxx">
        @error('phone') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="field full">
        <label for="{{ $prefix }}address">Alamat</label>
        <textarea id="{{ $prefix }}address" name="address" rows="3" placeholder="Masukkan alamat client">{{ old('address', $client?->address) }}</textarea>
        @error('address') <div class="field-error">{{ $message }}</div> @enderror
    </div>
</div>
