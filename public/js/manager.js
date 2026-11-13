/**
 * Manager dashboard — UI interactions & API calls
 */

const els = {
    daily: document.getElementById('metric-daily'),
    weekly: document.getElementById('metric-weekly'),
    vehicles: document.getElementById('metric-vehicles'),
    bays: document.getElementById('metric-bays'),
    priceForms: document.getElementById('price-forms'),
    recentBody: document.getElementById('recent-body'),
    machineryBody: document.getElementById('machinery-body'),
    addEquipmentForm: document.getElementById('add-equipment-form'),
    performanceBody: document.getElementById('performance-body'),
    toast: document.getElementById('toast'),
};

function money(amount) {
    return 'GH₵ ' + Number(amount || 0).toFixed(2);
}

function washerInitials(name) {
    return String(name || '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('') || '?';
}

function washerIdFrom(row) {
    return row?.washer_id || row?.assigned_washer_id || row?.id || '';
}

function washerProfile(name, id, { size = '', asStrong = true } = {}) {
    if (!name) return '—';
    const sizeClass = size === 'sm' ? ' washer-avatar-sm' : '';
    const safeName = String(name).replace(/"/g, '&quot;');
    const initials = washerInitials(name);
    const label = asStrong
        ? `<strong class="washer-label">${name}</strong>`
        : `<span class="washer-label">${name}</span>`;

    if (id) {
        return `
            <div class="washer-name-cell">
                <img class="washer-avatar${sizeClass}" src="/assets/washers/${id}.svg" alt="${safeName}"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <span class="washer-avatar washer-avatar-fallback${sizeClass}" style="display:none">${initials}</span>
                ${label}
            </div>`;
    }

    return `
        <div class="washer-name-cell">
            <span class="washer-avatar washer-avatar-fallback${sizeClass}">${initials}</span>
            ${label}
        </div>`;
}

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

function renderMetrics(metrics) {
    if (!els.daily) return;
    els.daily.textContent = money(metrics.daily_revenue);
    els.weekly.textContent = money(metrics.weekly_revenue);
    els.vehicles.textContent = metrics.vehicles_washed;
    els.bays.textContent = metrics.empty_bays;
}

function renderPrices(services) {
    if (!els.priceForms) return;
    if (!services.length) {
        els.priceForms.innerHTML = '<div class="empty-state">No services found.</div>';
        return;
    }

    els.priceForms.innerHTML = services
        .map(
            (service) => `
            <form class="price-row" data-service-id="${service.id}">
                <div class="service-name">${service.service_name}</div>
                <div class="form-group">
                    <label>Sedan</label>
                    <input class="form-control" type="number" min="0" step="0.01" name="price_sedan" value="${service.price_sedan}" required>
                </div>
                <div class="form-group">
                    <label>SUV</label>
                    <input class="form-control" type="number" min="0" step="0.01" name="price_suv" value="${service.price_suv}" required>
                </div>
                <div class="form-group">
                    <label>Van</label>
                    <input class="form-control" type="number" min="0" step="0.01" name="price_van" value="${service.price_van}" required>
                </div>
                <button class="btn btn-primary" type="submit">Save</button>
            </form>
        `
        )
        .join('');
}

function renderRecent(rows) {
    if (!els.recentBody) return;
    if (!rows.length) {
        els.recentBody.innerHTML = `
            <tr><td colspan="5" class="empty-state">No paid jobs yet.</td></tr>`;
        return;
    }

    els.recentBody.innerHTML = rows
        .map((row) => {
            const method = String(row.payment_method || '').toLowerCase();
            const methodLabel = method === 'cash' ? 'Cash' : method === 'momo' ? 'MoMo' : '—';
            return `
            <tr>
                <td><strong>${row.license_plate}</strong></td>
                <td>${row.service_name}</td>
                <td>${washerProfile(row.washer_name, washerIdFrom(row), { size: 'sm' })}</td>
                <td>${methodLabel}</td>
                <td>${money(row.total_amount)}</td>
            </tr>
        `;
        })
        .join('');
}

function renderMachinery(rows) {
    if (!els.machineryBody) return;
    if (!rows.length) {
        els.machineryBody.innerHTML = `
            <tr><td colspan="3" class="empty-state">No equipment yet. Add one above.</td></tr>`;
        return;
    }

    els.machineryBody.innerHTML = rows
        .map((row) => {
            const active = row.machinery_status === 'active';
            return `
                <tr>
                    <td><strong>${row.machine_name}</strong></td>
                    <td>
                        <span class="badge ${active ? 'badge-emerald' : 'badge-rose'} badge-dot">
                            ${active ? 'Active' : 'Under Repair'}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="btn ${active ? 'btn-danger' : 'btn-success'}" type="button" data-toggle-machine="${row.id}">
                                ${active ? 'Set Repair' : 'Set Active'}
                            </button>
                            <button class="btn btn-secondary" type="button" data-remove-machine="${row.id}" data-machine-name="${row.machine_name}">
                                Remove
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function washerAvatarSrc(row) {
    const id = washerIdFrom(row);
    if (row.photo) return row.photo;
    if (id) return `/assets/washers/${id}.svg`;
    return '';
}

function renderPerformance(rows) {
    if (!els.performanceBody) return;
    els.performanceBody.innerHTML = rows
        .map((row) => {
            const active = row.washer_status === 'active';
            return `
                <tr>
                    <td>${washerProfile(row.washer_name, washerIdFrom(row))}</td>
                    <td>
                        <span class="badge ${active ? 'badge-emerald' : 'badge-amber'} badge-dot">
                            ${active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>${row.vehicles_washed}</td>
                    <td>${money(row.total_revenue)}</td>
                </tr>
            `;
        })
        .join('');
}

function applyDashboard(data) {
    renderMetrics(data.metrics);
    renderPrices(data.services);
    renderRecent(data.recent);
    renderMachinery(data.machinery);
    renderPerformance(data.performance);
}

async function loadDashboard() {
    const result = await api('/api/manager.php');
    applyDashboard(result.data);
}

if (els.priceForms) {
    els.priceForms.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target.closest('form');
        if (!form) return;

        const payload = {
            action: 'update_prices',
            service_id: Number(form.dataset.serviceId),
            price_sedan: Number(form.price_sedan.value),
            price_suv: Number(form.price_suv.value),
            price_van: Number(form.price_van.value),
        };

        try {
            const result = await api('/api/manager.php', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            renderPrices(result.services);
            showToast(result.message);
        } catch (error) {
            showToast(error.message, true);
        }
    });
}

if (els.machineryBody) {
    els.machineryBody.addEventListener('click', async (event) => {
        const toggleBtn = event.target.closest('[data-toggle-machine]');
        const removeBtn = event.target.closest('[data-remove-machine]');

        if (toggleBtn) {
            try {
                const result = await api('/api/manager.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'toggle_machinery',
                        machinery_id: Number(toggleBtn.dataset.toggleMachine),
                    }),
                });
                renderMachinery(result.machinery);
                showToast(result.message);
            } catch (error) {
                showToast(error.message, true);
            }
            return;
        }

        if (removeBtn) {
            const name = removeBtn.dataset.machineName || 'this equipment';
            const confirmed = window.confirm(`Remove ${name} from the registry?`);
            if (!confirmed) return;

            try {
                const result = await api('/api/manager.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'remove_machinery',
                        machinery_id: Number(removeBtn.dataset.removeMachine),
                    }),
                });
                renderMachinery(result.machinery);
                showToast(result.message);
            } catch (error) {
                showToast(error.message, true);
            }
        }
    });
}

if (els.addEquipmentForm) {
    const addBtn = document.getElementById('add-equipment-btn');
    const machineInput = document.getElementById('machine_name');

    async function submitNewEquipment() {
        const name = ((machineInput && machineInput.value) || '').trim();
        if (!name) {
            showToast('Enter an equipment name.', true);
            return;
        }

        try {
            const result = await api('/api/manager.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'add_machinery',
                    machine_name: name,
                }),
            });

            if (result.machinery) {
                renderMachinery(result.machinery);
            }
            if (machineInput) {
                machineInput.value = '';
            }
            showToast(result.message || 'Equipment added.');
        } catch (error) {
            showToast(error.message, true);
        }
    }

    els.addEquipmentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await submitNewEquipment();
    });

    if (addBtn) {
        addBtn.addEventListener('click', async (event) => {
            event.preventDefault();
            await submitNewEquipment();
        });
    }
}

loadDashboard().catch((error) => showToast(error.message, true));
