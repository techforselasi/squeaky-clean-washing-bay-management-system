/**
 * Manager — Revenue Reports (generate / download CSV / print)
 */

const state = {
    fromDate: '',
    toDate: '',
    summary: null,
    rows: [],
};

const els = {
    form: document.getElementById('report-form'),
    fromDate: document.getElementById('from_date'),
    toDate: document.getElementById('to_date'),
    output: document.getElementById('report-output'),
    periodLabel: document.getElementById('report-period-label'),
    total: document.getElementById('report-total'),
    cash: document.getElementById('report-cash'),
    momo: document.getElementById('report-momo'),
    count: document.getElementById('report-count'),
    body: document.getElementById('report-body'),
    footerTotal: document.getElementById('report-footer-total'),
    downloadBtn: document.getElementById('download-csv-btn'),
    printBtn: document.getElementById('print-report-btn'),
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

function todayIso() {
    const d = new Date();
    return d.toISOString().slice(0, 10);
}

function startOfWeekIso() {
    const d = new Date();
    const day = d.getDay(); // 0 Sun
    const diff = day === 0 ? 6 : day - 1; // Monday start
    d.setDate(d.getDate() - diff);
    return d.toISOString().slice(0, 10);
}

function startOfMonthIso() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
}

function formatDisplayDate(iso) {
    if (!iso) return '—';
    const date = new Date(iso + 'T12:00:00');
    if (Number.isNaN(date.getTime())) return iso;
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatPaidAt(value) {
    if (!value) return '—';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function methodLabel(method) {
    const m = String(method || '').toLowerCase();
    if (m === 'cash') return 'Cash';
    if (m === 'momo') return 'MoMo';
    return method || '—';
}

function renderReport() {
    const summary = state.summary || {
        total_revenue: 0,
        cash_total: 0,
        momo_total: 0,
        payment_count: 0,
    };

    if (els.periodLabel) {
        els.periodLabel.textContent = `${formatDisplayDate(state.fromDate)} — ${formatDisplayDate(state.toDate)}`;
    }
    if (els.total) els.total.textContent = money(summary.total_revenue);
    if (els.cash) els.cash.textContent = money(summary.cash_total);
    if (els.momo) els.momo.textContent = money(summary.momo_total);
    if (els.count) els.count.textContent = String(summary.payment_count);
    if (els.footerTotal) els.footerTotal.textContent = 'Total: ' + money(summary.total_revenue);

    if (!els.body) return;

    if (!state.rows.length) {
        els.body.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">No paid transactions in this period.</td>
            </tr>`;
        return;
    }

    els.body.innerHTML = state.rows
        .map(
            (row, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${formatPaidAt(row.paid_at)}</td>
                <td><strong>${row.license_plate || '—'}</strong></td>
                <td>${row.car_type || '—'}</td>
                <td>${row.service_name || '—'}</td>
                <td>${washerProfile(row.washer_name, row.washer_id, { size: 'sm' })}</td>
                <td>${row.bay_name || '—'}</td>
                <td>${methodLabel(row.payment_method)}</td>
                <td>${money(row.total_amount)}</td>
            </tr>`
        )
        .join('');
}

async function generateReport(event) {
    if (event) event.preventDefault();
    if (!els.fromDate || !els.toDate) return;

    const fromDate = els.fromDate.value;
    const toDate = els.toDate.value;

    try {
        const result = await api('/api/manager.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'revenue_report',
                from_date: fromDate,
                to_date: toDate,
            }),
        });

        state.fromDate = result.from_date;
        state.toDate = result.to_date;
        state.summary = result.summary;
        state.rows = result.rows || [];

        renderReport();
        if (els.output) els.output.hidden = false;
        showToast(
            state.rows.length
                ? `Report ready · ${state.rows.length} payment(s).`
                : 'Report ready · no payments in this period.'
        );
    } catch (error) {
        showToast(error.message, true);
    }
}

function downloadCsv() {
    if (!state.summary) {
        showToast('Generate a report first.', true);
        return;
    }

    const lines = [
        ['Squeaky Clean Washing Bay — Revenue Report'],
        [`Period`, `${state.fromDate} to ${state.toDate}`],
        [`Total revenue`, Number(state.summary.total_revenue || 0).toFixed(2)],
        [`Cash total`, Number(state.summary.cash_total || 0).toFixed(2)],
        [`MoMo total`, Number(state.summary.momo_total || 0).toFixed(2)],
        [`Payment count`, String(state.summary.payment_count || 0)],
        [],
        ['#', 'Paid at', 'Plate', 'Vehicle', 'Service', 'Washer', 'Bay', 'Method', 'Amount (GHS)'],
    ];

    state.rows.forEach((row, index) => {
        lines.push([
            String(index + 1),
            row.paid_at || '',
            row.license_plate || '',
            row.car_type || '',
            row.service_name || '',
            row.washer_name || '',
            row.bay_name || '',
            methodLabel(row.payment_method),
            Number(row.total_amount || 0).toFixed(2),
        ]);
    });

    const csv = lines
        .map((cols) =>
            cols
                .map((cell) => {
                    const text = String(cell ?? '');
                    if (/[",\n]/.test(text)) return `"${text.replace(/"/g, '""')}"`;
                    return text;
                })
                .join(',')
        )
        .join('\r\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `squeaky-clean-revenue-${state.fromDate}-to-${state.toDate}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
    showToast('CSV downloaded.');
}

function printReport() {
    if (!state.summary) {
        showToast('Generate a report first.', true);
        return;
    }
    document.body.classList.add('printing-report');
    window.print();
    setTimeout(() => document.body.classList.remove('printing-report'), 500);
}

function applyPreset(preset) {
    const today = todayIso();
    if (!els.fromDate || !els.toDate) return;

    if (preset === 'today') {
        els.fromDate.value = today;
        els.toDate.value = today;
    } else if (preset === 'week') {
        els.fromDate.value = startOfWeekIso();
        els.toDate.value = today;
    } else if (preset === 'month') {
        els.fromDate.value = startOfMonthIso();
        els.toDate.value = today;
    }
}

if (els.fromDate && els.toDate) {
    const today = todayIso();
    els.fromDate.value = today;
    els.toDate.value = today;
}

if (els.form) {
    els.form.addEventListener('submit', generateReport);
}

document.querySelectorAll('[data-preset]').forEach((btn) => {
    btn.addEventListener('click', () => {
        applyPreset(btn.dataset.preset);
        generateReport();
    });
});

if (els.downloadBtn) els.downloadBtn.addEventListener('click', downloadCsv);
if (els.printBtn) els.printBtn.addEventListener('click', printReport);

// Auto-load today's report on open
generateReport().catch((error) => showToast(error.message, true));
