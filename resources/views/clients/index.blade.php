@extends('layouts.app')

@section('title', 'Client/Organizer')

@section('content')

    <div class="topbar">
        <div class="title">
            <h1>Client/Organizer</h1>
        </div>
        <div class="actions">
            <a href="{{ route('clients.create') }}" class="btn primary">+ Tambah Client</a>
        </div>
    </div>

    <section class="panel table-card" id="clientsTableCard">
        <table class="table">
            <thead>
                <tr>
                    <th style="min-width: 180px;">Nama</th>
                    <th style="min-width: 180px;">Kontak</th>
                    <th style="min-width: 220px;">Alamat</th>
                    <th style="min-width: 110px; text-align: right;">Jumlah Event</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr class="client-row"
                        data-id="{{ $client->id }}"
                        data-name="{{ $client->name }}"
                        data-email="{{ $client->email ?? '' }}"
                        data-phone="{{ $client->phone ?? '' }}"
                        data-address="{{ $client->address ?? '' }}"
                        data-bookings-count="{{ $client->bookings_count ?? 0 }}"
                        title="Klik untuk melihat & mengedit detail client">
                        <td><b>{{ $client->name }}</b></td>
                        <td>
                            {{ $client->email ?: '—' }}
                            @if($client->phone)
                                <div style="color:var(--muted);font-size:11px">{{ $client->phone }}</div>
                            @endif
                        </td>
                        <td class="client-address" title="{{ $client->address }}">{{ $client->address ?: '—' }}</td>
                        <td style="text-align: right;">{{ $client->bookings_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="color:var(--muted)">Belum ada client terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table>

        @include('partials.pagination', ['paginator' => $clients])
    </section>

    <!-- Detail / Edit Client Modal -->
    <div id="clientModal" class="modal-backdrop" style="display:none" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-card">
            <div class="modal-head">
                <h3 id="modalTitle" class="modal-title">Detail Client</h3>
                <button type="button" class="modal-close" id="modalCloseBtn" aria-label="Tutup modal" title="Tutup">✕</button>
            </div>

            <form id="clientEditForm">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" id="editClientId" value="">

                <div class="modal-body">
                    <div class="client-events-summary" id="modalEventsWrap">
                        <span class="events-label">Jumlah Event Terdaftar</span>
                        <span class="events-count" id="modalClientEvents">0 Event</span>
                    </div>

                    @include('clients.form-fields', ['prefix' => 'modal_'])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn" id="modalCancelBtn">Batal</button>
                    <button type="submit" class="btn primary" id="modalSaveBtn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('clientModal');
            const form = document.getElementById('clientEditForm');
            const closeBtn = document.getElementById('modalCloseBtn');
            const cancelBtn = document.getElementById('modalCancelBtn');
            const saveBtn = document.getElementById('modalSaveBtn');
            const tableCard = document.getElementById('clientsTableCard');

            const editIdInput = document.getElementById('editClientId');
            const nameInput = document.getElementById('modal_name');
            const emailInput = document.getElementById('modal_email');
            const phoneInput = document.getElementById('modal_phone');
            const addressInput = document.getElementById('modal_address');
            const nameError = document.getElementById('modal_name_error');
            const clientEventsBadge = document.getElementById('modalClientEvents');

            let isSaving = false;

            function openModal(data) {
                editIdInput.value = data.id || '';
                nameInput.value = data.name || '';
                emailInput.value = data.email || '';
                phoneInput.value = data.phone || '';
                addressInput.value = data.address || '';
                clientEventsBadge.textContent = (data.bookingsCount || 0) + ' Event';

                hideNameError();

                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                setTimeout(() => nameInput.focus(), 60);
            }

            function closeModal() {
                if (isSaving) return;
                modal.style.display = 'none';
                document.body.style.overflow = '';
                hideNameError();
            }

            function showNameError(msg = 'Nama wajib diisi') {
                nameError.textContent = msg;
                nameError.style.display = 'block';
                nameInput.style.borderColor = 'var(--red)';
            }

            function hideNameError() {
                nameError.style.display = 'none';
                nameInput.style.borderColor = '';
            }

            nameInput.addEventListener('input', function () {
                if (nameInput.value.trim() !== '') {
                    hideNameError();
                }
            });

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.style.display !== 'none') {
                    closeModal();
                }
            });

            tableCard.addEventListener('click', function (e) {
                const row = e.target.closest('.client-row');
                if (!row) return;

                openModal({
                    id: row.dataset.id,
                    name: row.dataset.name,
                    email: row.dataset.email,
                    phone: row.dataset.phone,
                    address: row.dataset.address,
                    bookingsCount: row.dataset.bookingsCount
                });
            });

            function showToast(message, type = 'success') {
                let container = document.getElementById('globalToastContainer');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'globalToastContainer';
                    container.className = 'toast-container';
                    document.body.appendChild(container);
                }

                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.textContent = message;
                container.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.add('show');
                });

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 3500);
            }

            async function refreshTable() {
                tableCard.classList.add('is-loading');

                try {
                    const res = await fetch(window.location.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    });

                    if (res.ok) {
                        const html = await res.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newTableCard = doc.getElementById('clientsTableCard');
                        if (newTableCard) {
                            tableCard.innerHTML = newTableCard.innerHTML;
                        }
                    }
                } catch (err) {
                    console.error('Failed to refresh table:', err);
                } finally {
                    tableCard.classList.remove('is-loading');
                }
            }

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const nameVal = nameInput.value.trim();
                if (!nameVal) {
                    showNameError('Nama wajib diisi');
                    nameInput.focus();
                    showToast('Gagal menyimpan perubahan', 'error');
                    return;
                }

                const clientId = editIdInput.value;
                if (!clientId) return;

                isSaving = true;
                saveBtn.disabled = true;
                const originalText = saveBtn.textContent;
                saveBtn.textContent = 'Menyimpan...';

                const payload = {
                    name: nameVal,
                    email: emailInput.value.trim() || null,
                    phone: phoneInput.value.trim() || null,
                    address: addressInput.value.trim() || null
                };

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('input[name="_token"]')?.value;

                try {
                    const res = await fetch(`/clients/${clientId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json().catch(() => null);

                    if (res.ok && data?.success) {
                        closeModal();
                        showToast('Client berhasil diperbarui', 'success');
                        await refreshTable();
                    } else {
                        if (data?.errors?.name) {
                            showNameError(data.errors.name[0]);
                        }
                        showToast('Gagal menyimpan perubahan', 'error');
                    }
                } catch (err) {
                    console.error('Save client error:', err);
                    showToast('Gagal menyimpan perubahan', 'error');
                } finally {
                    isSaving = false;
                    saveBtn.disabled = false;
                    saveBtn.textContent = originalText;
                }
            });
        });
    </script>

@endsection
