<div class="notif-wrapper" id="notifWrapper">
    <button type="button"
            id="notifBellBtn"
            class="notif-bell-btn"
            aria-label="Notifikasi event mendatang"
            aria-haspopup="true"
            aria-expanded="false"
            onclick="toggleNotifDropdown(event)"
            title="Event Mendatang">
        <svg class="bell-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
        </svg>
        <span id="notifBadge" class="notif-badge" style="display: none;" aria-live="polite">0</span>
    </button>

    <div id="notifDropdown" class="notif-dropdown" hidden role="region" aria-label="Daftar Notifikasi Event Mendatang">
        <div class="notif-dropdown-header">
            <div class="notif-dropdown-title-row">
                <div class="notif-title-group">
                    <span class="notif-heading">Event Mendatang</span>
                    <span id="notifHeaderCount" class="notif-count-pill" style="display: none;">0</span>
                </div>
                <button type="button"
                        id="notifMarkAllBtn"
                        class="notif-mark-all-link"
                        onclick="markAllNotificationsRead(event)"
                        title="Tandai semua event mendatang sebagai sudah dibaca">
                    Tandai semua dibaca
                </button>
            </div>

            @if (auth()->user()->isAdmin())
                <div class="notif-admin-filter-bar">
                    <label for="notifAdminDestSelect" class="notif-admin-filter-label">Destinasi:</label>
                    <select id="notifAdminDestSelect" class="notif-admin-filter-select" onchange="onNotifAdminDestChange(this.value)">
                        <option value="">Semua Destinasi</option>
                    </select>
                </div>
            @endif
        </div>

        <div id="notifDropdownBody" class="notif-dropdown-body" tabindex="0">
            <div class="notif-state notif-loading">
                <span class="notif-spinner"></span>
                <span>Memuat data event...</span>
            </div>
        </div>

        <div class="notif-dropdown-footer">
            <span>Event dalam {{ config('booking.upcoming_days', 7) }} hari mendatang</span>
        </div>
    </div>
</div>

<script>
(function() {
    let notifCache = {
        unread_count: 0,
        badge: '0',
        items: [],
        destinations: [],
        is_admin: false
    };
    let currentAdminDestId = '';
    let isFetching = false;
    let pollInterval = null;

    const bellBtn = document.getElementById('notifBellBtn');
    const badgeEl = document.getElementById('notifBadge');
    const dropdownEl = document.getElementById('notifDropdown');
    const bodyEl = document.getElementById('notifDropdownBody');
    const markAllBtn = document.getElementById('notifMarkAllBtn');
    const headerCountEl = document.getElementById('notifHeaderCount');
    const adminDestSelect = document.getElementById('notifAdminDestSelect');
    const wrapperEl = document.getElementById('notifWrapper');

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    window.toggleNotifDropdown = function(e) {
        if (e) e.stopPropagation();
        const isHidden = dropdownEl.hidden;
        if (isHidden) {
            openDropdown();
        } else {
            closeDropdown();
        }
    };

    function openDropdown() {
        dropdownEl.hidden = false;
        bellBtn.setAttribute('aria-expanded', 'true');
        bellBtn.classList.add('active');
        fetchNotifications(false);
    }

    function closeDropdown() {
        dropdownEl.hidden = true;
        bellBtn.setAttribute('aria-expanded', 'false');
        bellBtn.classList.remove('active');
    }

    window.onNotifAdminDestChange = function(destId) {
        currentAdminDestId = destId;
        fetchNotifications(true);
    };

    async function fetchNotifications(forceRender) {
        if (isFetching) return;
        isFetching = true;

        try {
            const url = new URL('{{ route('notifications.index') }}', window.location.origin);
            if (currentAdminDestId) {
                url.searchParams.set('destination_id', currentAdminDestId);
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Network error');

            const data = await response.json();
            notifCache = data;
            updateBadgeUI(data);

            if (adminDestSelect && data.destinations && adminDestSelect.options.length <= 1) {
                data.destinations.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    adminDestSelect.appendChild(opt);
                });
            }

            if (!dropdownEl.hidden || forceRender) {
                renderDropdownItems(data);
            }
        } catch (err) {
            console.error('Failed to fetch notifications:', err);
        } finally {
            isFetching = false;
        }
    }

    function updateBadgeUI(data) {
        const count = data.unread_count || 0;
        if (count > 0) {
            badgeEl.textContent = data.badge;
            badgeEl.style.display = 'inline-flex';
            if (headerCountEl) {
                headerCountEl.textContent = `${count} baru`;
                headerCountEl.style.display = 'inline-block';
            }
            if (markAllBtn) {
                markAllBtn.style.display = 'inline-block';
                markAllBtn.disabled = false;
            }
        } else {
            badgeEl.style.display = 'none';
            if (headerCountEl) {
                headerCountEl.style.display = 'none';
            }
            if (markAllBtn) {
                markAllBtn.style.display = 'none';
            }
        }
    }

    function renderDropdownItems(data) {
        if (!bodyEl) return;

        const items = data.items || [];
        if (items.length === 0) {
            bodyEl.innerHTML = `
                <div class="notif-state notif-empty">
                    <span class="notif-empty-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted);"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <p class="notif-empty-text">Tidak ada event mendatang</p>
                    <span class="notif-empty-sub">Tidak ada jadwal event aktif dalam 7 hari ke depan</span>
                </div>
            `;
            return;
        }

        let html = '<div class="notif-items-list">';
        items.forEach(item => {
            const urgentClass = item.is_urgent ? 'is-urgent' : '';
            const readClass = item.is_read ? 'is-read' : 'is-unread';
            const badgeUrgentClass = item.is_urgent ? 'badge-urgent' : 'badge-standard';

            html += `
                <a href="${item.go_url}" class="notif-item ${urgentClass} ${readClass}" data-id="${item.id}" onclick="handleNotifClick(event, ${item.id}, '${item.go_url}', '${item.read_url}')">
                    <div class="notif-item-top">
                        <span class="notif-rel-badge ${badgeUrgentClass}">${escapeHtml(item.relative_label)}</span>
                        ${!item.is_read ? '<span class="notif-unread-dot" title="Belum dibaca"></span>' : ''}
                    </div>
                    <div class="notif-item-title">${escapeHtml(item.title)}</div>
                    <div class="notif-item-meta">
                        <span class="notif-venue"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:3px;"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 10v11"/><path d="M15 10v11"/></svg>${escapeHtml(item.venue)}</span>
                        ${item.show_destination ? `<span class="notif-dest">(${escapeHtml(item.destination)})</span>` : ''}
                    </div>
                    <div class="notif-item-time">
                        <span class="notif-date"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:3px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>${escapeHtml(item.date_label)}</span>
                        ${item.time_label ? `<span class="notif-hour"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:3px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>${escapeHtml(item.time_label)}</span>` : ''}
                    </div>
                </a>
            `;
        });
        html += '</div>';

        bodyEl.innerHTML = html;
    }

    window.handleNotifClick = function(e, id, goUrl, readUrl) {
        // Optimistically mark as read in local cache and update badge
        const item = notifCache.items.find(i => i.id === id);
        if (item && !item.is_read) {
            item.is_read = true;
            notifCache.unread_count = Math.max(0, notifCache.unread_count - 1);
            notifCache.badge = notifCache.unread_count > 9 ? '9+' : String(notifCache.unread_count);
            updateBadgeUI(notifCache);
        }

        // Fire asynchronous read request before redirecting via goUrl
        if (navigator.sendBeacon) {
            const formData = new FormData();
            formData.append('_token', getCsrfToken());
            navigator.sendBeacon(readUrl, formData);
        }
    };

    window.markAllNotificationsRead = async function(e) {
        if (e) e.preventDefault();
        if (markAllBtn) markAllBtn.disabled = true;

        try {
            const response = await fetch('{{ route('notifications.markAllRead') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    destination_id: currentAdminDestId || null
                })
            });

            if (response.ok) {
                notifCache.items.forEach(i => i.is_read = true);
                notifCache.unread_count = 0;
                notifCache.badge = '0';
                updateBadgeUI(notifCache);
                renderDropdownItems(notifCache);
            }
        } catch (err) {
            console.error('Failed to mark all as read:', err);
        } finally {
            if (markAllBtn) markAllBtn.disabled = false;
        }
    };

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (wrapperEl && !wrapperEl.contains(e.target)) {
            closeDropdown();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && dropdownEl && !dropdownEl.hidden) {
            closeDropdown();
            bellBtn.focus();
        }
    });

    // Auto-refresh: poll every 60 seconds
    pollInterval = setInterval(function() {
        fetchNotifications(false);
    }, 60000);

    // Auto-refresh: refetch when window / tab regains focus
    window.addEventListener('focus', function() {
        fetchNotifications(false);
    });

    // Initial fetch on page load
    fetchNotifications(true);
})();
</script>
