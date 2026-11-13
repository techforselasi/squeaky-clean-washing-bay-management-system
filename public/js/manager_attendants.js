/**
 * Manager — Attendant account management
 */

const state = {
    attendants: [],
};

const els = {
    body: document.getElementById('attendants-body'),
    count: document.getElementById('attendant-count'),
    search: document.getElementById('attendant-search'),
    addForm: document.getElementById('add-attendant-form'),
    resetCard: document.getElementById('reset-password-card'),
    resetForm: document.getElementById('reset-password-form'),
    resetUserId: document.getElementById('reset_user_id'),
    resetLabel: document.getElementById('reset-target-label'),
    cancelReset: document.getElementById('cancel-reset-btn'),
    toast: document.getElementById('toast'),
};

function showToast(message, isError = false) {
    if (!els.toast) return;
    els.toast.textContent = message;
    els.toast.classList.toggle('error', isError);
    els.toast.classList.add('show');
    setTimeout(() => els.toast.classList.remove('show'), 2800);
}

async function api(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        ...options,
    });

    let data;
    try {
        data = await response.json();
    } catch (error) {
        throw new Error('Server returned an invalid response.');
    }

    if (!response.ok || data.success === false) {
        throw new Error(data.message || 'Request failed');
    }
    return data;
}

function renderAttendants(filter = '') {
    if (!els.body) return;

    const q = filter.trim().toLowerCase();
    const rows = state.attendants.filter((row) => {
        if (!q) return true;
        return String(row.username || '').toLowerCase().includes(q);
    });

    if (els.count) {
        const active = state.attendants.filter((r) => r.user_status === 'active').length;
        els.count.textContent = `${active} active · ${state.attendants.length} total`;
        els.count.className = `badge badge-dot ${active ? 'badge-emerald' : 'badge-amber'}`;
    }

    if (!rows.length) {
        els.body.innerHTML = `
            <tr>
                <td colspan="3" class="empty-state">No attendant accounts yet. Create one above.</td>
            </tr>`;
        return;
    }

    els.body.innerHTML = rows
        .map((row) => {
            const active = String(row.user_status || '') === 'active';
            return `
                <tr>
                    <td><strong>${row.username}</strong></td>
                    <td>
                        <span class="badge ${active ? 'badge-emerald' : 'badge-amber'} badge-dot">
                            ${active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <div class="job-actions">
                            <button class="btn btn-secondary" type="button" data-reset-password="${row.id}" data-username="${row.username}">
                                Reset password
                            </button>
                            <button class="btn ${active ? 'btn-secondary' : 'btn-primary'}" type="button"
                                data-toggle-status="${row.id}"
                                data-next-status="${active ? 'inactive' : 'active'}">
                                ${active ? 'Deactivate' : 'Reactivate'}
                            </button>
                        </div>
                    </td>
                </tr>`;
        })
        .join('');
}

async function loadAttendants() {
    const result = await api('/api/manager.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'list_attendants' }),
    });
    state.attendants = result.attendants || [];
    renderAttendants(els.search ? els.search.value : '');
}

function hideResetForm() {
    if (els.resetCard) els.resetCard.hidden = true;
    if (els.resetForm) els.resetForm.reset();
    if (els.resetUserId) els.resetUserId.value = '';
}

function showResetForm(userId, username) {
    if (!els.resetCard || !els.resetUserId) return;
    els.resetUserId.value = String(userId);
    if (els.resetLabel) {
        els.resetLabel.textContent = `Set a new temporary password for "${username}".`;
    }
    els.resetCard.hidden = false;
    els.resetCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

if (els.addForm) {
    els.addForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const username = document.getElementById('new_username')?.value.trim() || '';
        const password = document.getElementById('new_password')?.value || '';
        const confirm = document.getElementById('new_password_confirm')?.value || '';

        try {
            const result = await api('/api/manager.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'create_attendant',
                    username,
                    password,
                    password_confirm: confirm,
                }),
            });
            state.attendants = result.attendants || [];
            renderAttendants(els.search ? els.search.value : '');
            els.addForm.reset();
            showToast(result.message || 'Attendant created.');
        } catch (error) {
            showToast(error.message, true);
        }
    });
}

if (els.resetForm) {
    els.resetForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const userId = Number(els.resetUserId?.value || 0);
        const password = document.getElementById('reset_password')?.value || '';
        const confirm = document.getElementById('reset_password_confirm')?.value || '';

        try {
            const result = await api('/api/manager.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'reset_attendant_password',
                    user_id: userId,
                    password,
                    password_confirm: confirm,
                }),
            });
            state.attendants = result.attendants || [];
            renderAttendants(els.search ? els.search.value : '');
            hideResetForm();
            showToast(result.message || 'Password updated.');
        } catch (error) {
            showToast(error.message, true);
        }
    });
}

if (els.cancelReset) {
    els.cancelReset.addEventListener('click', hideResetForm);
}

if (els.body) {
    els.body.addEventListener('click', async (event) => {
        const resetBtn = event.target.closest('[data-reset-password]');
        if (resetBtn) {
            showResetForm(Number(resetBtn.dataset.resetPassword), resetBtn.dataset.username || 'attendant');
            return;
        }

        const toggleBtn = event.target.closest('[data-toggle-status]');
        if (!toggleBtn) return;

        const userId = Number(toggleBtn.dataset.toggleStatus);
        const nextStatus = toggleBtn.dataset.nextStatus;
        const actionLabel = nextStatus === 'inactive' ? 'deactivate' : 'reactivate';

        if (nextStatus === 'inactive' && !window.confirm('Deactivate this attendant? They will not be able to log in.')) {
            return;
        }

        try {
            const result = await api('/api/manager.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'set_attendant_status',
                    user_id: userId,
                    user_status: nextStatus,
                }),
            });
            state.attendants = result.attendants || [];
            renderAttendants(els.search ? els.search.value : '');
            showToast(result.message || `Account ${actionLabel}d.`);
        } catch (error) {
            showToast(error.message, true);
        }
    });
}

if (els.search) {
    els.search.addEventListener('input', () => renderAttendants(els.search.value));
}

document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.togglePassword);
        if (!input) return;

        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.classList.toggle('is-showing', !showing);
        btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
});

loadAttendants().catch((error) => showToast(error.message, true));
