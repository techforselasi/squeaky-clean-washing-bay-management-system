/**
 * Attendant desk — UI interactions & API calls
 */

const state = {
    services: [],
    bays: [],
    washers: [],
    jobs: [],
    currentReceipt: null,
    calculatedTotal: 0,
};

const els = {
    serviceSelect: document.getElementById('service_id'),
    baySelect: document.getElementById('bay_id'),
    washerSelect: document.getElementById('washer_id'),
    carType: document.getElementById('car_type'),
    plate: document.getElementById('license_plate'),
    customerPhone: document.getElementById('customer_phone'),
    totalDisplay: document.getElementById('total-display'),
    sendChooser: document.getElementById('send-chooser'),
    sendPhoneInput: document.getElementById('send-phone'),
    bayGrid: document.getElementById('bay-grid'),
    baySummary: document.getElementById('bay-summary'),
    jobsBody: document.getElementById('jobs-body'),
    jobCount: document.getElementById('job-count'),
    intakeForm: document.getElementById('intake-form'),
    modal: document.getElementById('receipt-modal'),
    receiptBody: document.getElementById('receipt-body'),
    toast: document.getElementById('toast'),
    jobSearch: document.getElementById('job-search'),
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

/** Profile chip used wherever a washer name is listed */
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

function fillSelect(select, items, valueKey, labelKey, placeholder, filterFn = null) {
    if (!select) return;
    const current = select.value;
    select.innerHTML = `<option value="">${placeholder}</option>`;
    items
        .filter(filterFn || (() => true))
        .forEach((item) => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[labelKey];
            select.appendChild(option);
        });
    if ([...select.options].some((o) => o.value === current)) {
        select.value = current;
    }
    syncFancySelect(select);
}

function closeAllFancySelects(exceptWrap = null) {
    document.querySelectorAll('.fancy-select.open').forEach((wrap) => {
        if (wrap !== exceptWrap) wrap.classList.remove('open');
    });
}

function syncFancySelect(select) {
    if (!select || !select._fancy) return;

    const { triggerLabel, menu, withAvatars } = select._fancy;
    const selected = select.options[select.selectedIndex];
    const placeholder = select.options[0] ? select.options[0].textContent : 'Select';

    if (!select.value) {
        triggerLabel.textContent = placeholder;
        triggerLabel.classList.add('is-placeholder');
    } else {
        triggerLabel.textContent = selected ? selected.textContent : placeholder;
        triggerLabel.classList.remove('is-placeholder');
    }

    menu.innerHTML = '';
    [...select.options].forEach((option) => {
        if (!option.value) return;

        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'fancy-select-option' + (option.value === select.value ? ' selected' : '');
        item.dataset.value = option.value;

        if (withAvatars) {
            const avatar = document.createElement('img');
            avatar.className = 'fancy-select-avatar';
            avatar.src = `/assets/washers/${option.value}.svg`;
            avatar.alt = '';
            item.appendChild(avatar);
        }

        const name = document.createElement('span');
        name.textContent = option.textContent;
        item.appendChild(name);

        item.addEventListener('click', () => {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            syncFancySelect(select);
            select._fancy.wrap.classList.remove('open');
        });

        menu.appendChild(item);
    });

    if (!menu.children.length) {
        const emptyMsg = select.dataset.emptyMessage || 'No options available';
        menu.innerHTML = `<div class="fancy-select-empty">${emptyMsg}</div>`;
    }
}

function enhanceSelect(select) {
    if (!select || select.dataset.fancyReady === '1') return;

    const withAvatars = select.dataset.fancyAvatars === 'true';
    const wrap = document.createElement('div');
    wrap.className = 'fancy-select' + (withAvatars ? ' has-avatars' : '');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'fancy-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');

    const triggerLabel = document.createElement('span');
    triggerLabel.className = 'fancy-select-label is-placeholder';
    trigger.appendChild(triggerLabel);

    const chevron = document.createElement('span');
    chevron.className = 'fancy-select-chevron';
    chevron.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>`;
    trigger.appendChild(chevron);

    const menu = document.createElement('div');
    menu.className = 'fancy-select-menu';
    menu.setAttribute('role', 'listbox');

    select.classList.add('fancy-select-native-hidden');
    select.dataset.fancyReady = '1';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    wrap.appendChild(trigger);
    wrap.appendChild(menu);

    select._fancy = { wrap, trigger, triggerLabel, menu, withAvatars };

    trigger.addEventListener('click', () => {
        const willOpen = !wrap.classList.contains('open');
        closeAllFancySelects();
        wrap.classList.toggle('open', willOpen);
    });

    syncFancySelect(select);
}

function initFancySelects() {
    document.querySelectorAll('select.fancy-native').forEach(enhanceSelect);
}

function refreshAllFancySelects() {
    document.querySelectorAll('select.fancy-native').forEach(syncFancySelect);
}

function renderBays() {
    if (els.baySummary && els.bayGrid) {
        // Hard guarantee: only one card per bay id (Bay 1–4)
        const uniqueBays = [];
        const seen = new Set();
        (state.bays || []).forEach((bay) => {
            const id = Number(bay.id);
            if (!id || seen.has(id)) return;
            seen.add(id);
            uniqueBays.push(bay);
        });
        uniqueBays.sort((a, b) => Number(a.id) - Number(b.id));
        state.bays = uniqueBays;

        const emptyCount = uniqueBays.filter((b) => b.bay_status === 'empty').length;
        els.baySummary.textContent = `${emptyCount} empty`;

        els.bayGrid.innerHTML = uniqueBays
            .map((bay) => {
                const occupied = bay.bay_status === 'occupied';
                const statusIcon = occupied
                    ? `<span class="bay-status-icon" aria-hidden="true">
                            <svg viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="2.5"/></svg>
                       </span>`
                    : `<span class="bay-status-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                       </span>`;

                return `
                    <article class="bay-card ${bay.bay_status}" data-bay-id="${bay.id}">
                        <div class="bay-top">
                            <h4>${bay.bay_name}</h4>
                            <span class="bay-status ${occupied ? 'occupied' : 'empty'}">
                                ${statusIcon}
                                ${occupied ? 'In use' : 'Empty'}
                            </span>
                        </div>
                        <div class="detail">
                            ${
                                occupied
                                    ? `<div><strong>${bay.license_plate || '—'}</strong> · ${bay.car_type || ''}</div>
                                       <div class="bay-washer">${washerProfile(bay.washer_name, bay.washer_id, { size: 'sm', asStrong: false })}</div>`
                                    : '<div>Ready for next vehicle</div>'
                            }
                        </div>
                    </article>
                `;
            })
            .join('');
    }

    if (els.baySelect) {
        fillSelect(
            els.baySelect,
            state.bays,
            'id',
            'bay_name',
            'Select bay',
            (b) => b.bay_status === 'empty'
        );
    }
}

function isWashActive(status) {
    return ['pending', 'washing'].includes(String(status || '').toLowerCase());
}

function renderJobs(filter = '') {
    if (!els.jobsBody) return;
    const q = filter.trim().toLowerCase();
    const jobs = state.jobs.filter((job) => {
        if (!q) return true;
        return (
            (job.license_plate || '').toLowerCase().includes(q) ||
            (job.washer_name || '').toLowerCase().includes(q)
        );
    });

    const unpaid = state.jobs.filter(
        (job) => String(job.payment_status || '').toLowerCase() !== 'paid'
    ).length;
    const washing = state.jobs.filter((job) => isWashActive(job.vehicle_status)).length;

    if (els.jobCount) {
        els.jobCount.textContent = `${washing} washing · ${unpaid} unpaid · ${state.jobs.length} total`;
        els.jobCount.className = `badge badge-dot ${washing || unpaid ? 'badge-amber' : 'badge-emerald'}`;
    }

    if (!jobs.length) {
        els.jobsBody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">No jobs yet today.</td>
            </tr>`;
        return;
    }

    els.jobsBody.innerHTML = jobs
        .map((job) => {
            const paid = String(job.payment_status || '').toLowerCase() === 'paid';
            const washingJob = isWashActive(job.vehicle_status);
            return `
                <tr>
                    <td><strong>${job.license_plate}</strong></td>
                    <td>${job.car_type}</td>
                    <td>${job.service_name || '—'}</td>
                    <td>${job.bay_name || '—'}</td>
                    <td>${washerProfile(job.washer_name, washerIdFrom(job), { size: 'sm' })}</td>
                    <td>${money(job.total_amount)}</td>
                    <td>
                        <span class="badge ${paid ? 'badge-emerald' : 'badge-amber'} badge-dot">
                            ${paid ? 'Paid' : 'Pending'}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${washingJob ? 'badge-blue' : 'badge-emerald'} badge-dot">
                            ${washingJob ? 'Washing' : 'Done'}
                        </span>
                    </td>
                    <td class="no-print">
                        <div class="job-actions">
                            <button class="btn btn-secondary" type="button" data-open-receipt="${job.transaction_id}">
                                ${paid ? 'Receipt' : 'Invoice'}
                            </button>
                            ${
                                washingJob
                                    ? `<button class="btn btn-primary" type="button" data-complete-wash="${job.transaction_id}">
                                           Complete wash
                                       </button>`
                                    : ''
                            }
                        </div>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function formatShortDate(value) {
    if (!value) return '—';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return String(value);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
}

function hidePayChooser() {
    const chooser = document.getElementById('pay-chooser');
    if (chooser) chooser.hidden = true;
}

function showPayChooser() {
    hideSendChooser();
    const chooser = document.getElementById('pay-chooser');
    if (chooser) chooser.hidden = false;
}

function hideSendChooser() {
    if (els.sendChooser) els.sendChooser.hidden = true;
}

function showSendChooser() {
    if (!state.currentReceipt) return;
    hidePayChooser();
    if (els.sendPhoneInput) {
        els.sendPhoneInput.value = state.currentReceipt.customer_phone || '';
    }
    if (els.sendChooser) els.sendChooser.hidden = false;
}

async function downloadReceiptPdf(transactionId) {
    const id = Number(transactionId || state.currentReceipt?.id || 0);
    if (!id) {
        showToast('Open a paid receipt first.', true);
        return null;
    }

    try {
        const response = await fetch(
            `/api/attendant.php?action=download_receipt_pdf&transaction_id=${id}`,
            { credentials: 'same-origin' }
        );
        if (!response.ok) {
            throw new Error('Could not download the PDF receipt.');
        }

        const blob = await response.blob();
        const filename = `receipt-${id}.pdf`;
        const file = new File([blob], filename, { type: 'application/pdf' });
        const objectUrl = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = objectUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(objectUrl), 2000);
        return file;
    } catch (error) {
        showToast(error.message || 'PDF download failed.', true);
        return null;
    }
}

async function sendReceiptToCustomer(channel) {
    if (!state.currentReceipt) return;

    const phone = els.sendPhoneInput ? els.sendPhoneInput.value.trim() : '';

    try {
        const result = await api('/api/attendant.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'send_receipt',
                transaction_id: state.currentReceipt.id,
                channel,
                customer_phone: phone,
            }),
        });

        if (result.receipt) {
            state.currentReceipt = result.receipt;
        }

        if (channel === 'whatsapp') {
            const pdfFile = await downloadReceiptPdf(state.currentReceipt.id);

            // On phones that support it, share the PDF straight into WhatsApp.
            if (pdfFile && navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
                try {
                    await navigator.share({
                        files: [pdfFile],
                        title: result.pdf_name || 'Receipt',
                        text: 'Squeaky Clean A5 receipt PDF',
                    });
                    hideSendChooser();
                    showToast('Share the PDF in WhatsApp.');
                    return;
                } catch (shareError) {
                    // User cancelled or share failed — fall through to download + chat link.
                }
            }

            if (result.whatsapp_url) {
                window.open(result.whatsapp_url, '_blank');
            }
            hideSendChooser();
            showToast(result.message || 'PDF downloaded — attach it in WhatsApp.');
            return;
        }

        hideSendChooser();
        showToast(result.message || 'Receipt sent.');
    } catch (error) {
        showToast(error.message, true);
    }
}

function openReceipt(receipt) {
    if (!els.modal || !els.receiptBody) {
        showToast('Invoice window is missing on this page.', true);
        return;
    }

    if (!receipt || !receipt.id) {
        showToast('Invoice details were not returned. Open a job and click Invoice.', true);
        return;
    }

    state.currentReceipt = receipt;
    hidePayChooser();
    hideSendChooser();

    const isPaid = String(receipt.payment_status || '').toLowerCase() === 'paid';
    const stillWashing = isWashActive(receipt.vehicle_status);
    const issuedRaw = receipt.created_at || receipt.transaction_created_at || receipt.checked_in_at;
    const shortDate = formatShortDate(issuedRaw);
    const paidLabel = receipt.payment_method && receipt.payment_method !== 'none'
        ? String(receipt.payment_method).toUpperCase()
        : 'PAID';
    const amount = money(receipt.total_amount);
    const service = receipt.service_name || 'Wash Service';
    const carType = receipt.car_type || 'Vehicle';
    const bay = receipt.bay_name || '—';
    const washer = receipt.washer_name || '—';
    const plate = receipt.license_plate || '—';
    const docTitle = isPaid ? 'Receipt' : 'Invoice';

    const actionButtons = [];
    if (!isPaid) {
        actionButtons.push('<button class="btn-pay-now" type="button" id="pay-now-btn">Pay Now</button>');
    } else {
        actionButtons.push('<button class="btn-print-receipt" type="button" id="print-receipt-btn">Print Receipt</button>');
        actionButtons.push('<button class="btn-send-receipt" type="button" id="send-receipt-btn">Send Receipt</button>');
    }
    if (stillWashing) {
        actionButtons.push(
            `<button class="btn-complete-wash" type="button" id="complete-wash-btn" data-transaction-id="${receipt.id}">Complete wash</button>`
        );
    }

    els.receiptBody.innerHTML = `
        <article class="bill-card">
            <div class="bill-hero">
                <div class="bill-parties">
                    <div class="bill-party">
                        <span class="bill-party-label">From:</span>
                        <span class="bill-party-name">Squeaky Clean Washing Bay</span>
                        <span class="bill-party-sub">Accra, Ghana<br>t: +233 00 000 0000</span>
                    </div>
                    <div class="bill-party">
                        <span class="bill-party-label">Vehicle:</span>
                        <span class="bill-party-name">${plate}</span>
                        <span class="bill-party-sub">${carType}<br>${bay}</span>
                    </div>
                </div>
                <div class="bill-hero-right">
                    <h3 id="receipt-title">${docTitle}</h3>
                    <p class="bill-meta">#${receipt.id} | ${shortDate}</p>
                    <div class="bill-total-big">${amount}</div>
                    ${isPaid ? `<span class="bill-paid-stamp">${paidLabel}</span>` : ''}
                </div>
            </div>

            <div class="bill-body">
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>${service}</strong><br>
                                <span class="bill-line-meta">${washer}</span>
                            </td>
                            <td>${amount}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="bill-balance">
                    <span>${isPaid ? 'Amount paid' : 'Balance due'}</span>
                    <span>${amount}</span>
                </div>

                <div class="bill-actions no-print ${actionButtons.length > 1 ? 'bill-actions-split' : ''}">
                    ${actionButtons.join('')}
                </div>
            </div>
        </article>
    `;

    const payNowBtn = document.getElementById('pay-now-btn');
    const printBtn = document.getElementById('print-receipt-btn');
    const sendBtn = document.getElementById('send-receipt-btn');
    const completeBtn = document.getElementById('complete-wash-btn');
    if (payNowBtn) payNowBtn.addEventListener('click', showPayChooser);
    if (printBtn) printBtn.addEventListener('click', printReceipt);
    if (sendBtn) sendBtn.addEventListener('click', showSendChooser);
    if (completeBtn) {
        completeBtn.addEventListener('click', () => completeWash(Number(completeBtn.dataset.transactionId)));
    }

    els.modal.classList.add('open');
    document.body.classList.add('modal-open');
}

function printReceipt() {
    if (!els.modal || !els.modal.classList.contains('open')) {
        showToast('Open a receipt first, then print.', true);
        return;
    }
    hidePayChooser();
    hideSendChooser();
    window.print();
}

function closeModal() {
    if (!els.modal) return;
    hidePayChooser();
    hideSendChooser();
    els.modal.classList.remove('open');
    document.body.classList.remove('modal-open');
}

/** Ghana DVLA region / series codes (standard plates) */
const GHANA_REGION_CODES = new Set([
    'GR', 'GB', 'GC', 'GE', 'GG', 'GH', 'GL', 'GM', 'GN', 'GS', 'GT', 'GW', 'GX', 'GY',
    'AS', 'AE', 'AW', 'AC', 'AK', 'AP',
    'BA', 'BE', 'BO', 'CR', 'ER', 'NR', 'NE', 'OT', 'SV', 'UE', 'UW', 'VR', 'WR', 'WN',
]);

function normalizeGhanaPlate(raw) {
    let value = String(raw || '').trim().toUpperCase().replace(/\s+/g, ' ');
    value = value.replace(/^([A-Z]{2})(\d)/, '$1 $2');
    return value;
}

function isStandardGhanaPlate(plate) {
    const match = plate.match(/^([A-Z]{2}) (\d{1,4})-([0-9]{2}|[A-Z]{2})$/);
    if (!match) return false;

    const region = match[1];
    const serial = Number(match[2]);
    const suffix = match[3];

    if (!GHANA_REGION_CODES.has(region)) return false;
    if (serial < 1 || serial > 9999) return false;

    if (/^\d{2}$/.test(suffix)) {
        const year = Number(suffix);
        const maxYear = (new Date().getFullYear() + 1) % 100;
        if (year < 9 || year > maxYear) return false;
    }

    return true;
}

/** Custom / vanity: letters + numbers, plate-like length (blocks random letter-only junk). */
function isCustomPlate(plate) {
    if (plate.length < 3 || plate.length > 12) return false;
    if (!/^[A-Z0-9 \-]{3,12}$/.test(plate)) return false;
    if (!/[A-Z]/.test(plate) || !/[0-9]/.test(plate)) return false;

    const compact = plate.replace(/[\s\-]/g, '');
    if (compact && /^(.)\1+$/.test(compact)) return false;

    return true;
}

function isValidGhanaPlate(raw) {
    const plate = normalizeGhanaPlate(raw);
    if (!plate) return false;
    return isStandardGhanaPlate(plate) || isCustomPlate(plate);
}

function setPlateHint(isError) {
    const hint = document.getElementById('plate-hint');
    if (!hint) return;
    hint.classList.toggle('is-error', !!isError);
    hint.textContent = isError
        ? 'Use a Ghana plate (e.g. GR 2345-26) or a custom plate with letters and numbers'
        : 'Use a Ghana plate (e.g. GR 2345-26) or a custom plate with letters and numbers';
}

function intakeMissingFields() {
    const missing = [];
    if (!els.plate || !els.plate.value.trim()) missing.push('license plate');
    if (!els.carType || !els.carType.value) missing.push('car type');
    if (!els.serviceSelect || !els.serviceSelect.value) missing.push('service');
    if (!els.baySelect || !els.baySelect.value) missing.push('bay');
    if (!els.washerSelect || !els.washerSelect.value) missing.push('washer');
    return missing;
}

function clearIntakeForm() {
    if (!els.intakeForm) return;
    els.intakeForm.reset();
    clearIntakeDraft();
    refreshAllFancySelects();
    if (els.totalDisplay) {
        els.totalDisplay.textContent = 'GH₵ 0.00';
    }
    state.calculatedTotal = 0;
    setPlateHint(false);
    if (els.plate) els.plate.focus();
}

const INTAKE_DRAFT_KEY = 'squeaky_intake_draft';

function readIntakeDraft() {
    try {
        const raw = sessionStorage.getItem(INTAKE_DRAFT_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch (error) {
        return null;
    }
}

function saveIntakeDraft() {
    if (!els.intakeForm) return;

    const draft = {
        license_plate: els.plate ? els.plate.value : '',
        car_type: els.carType ? els.carType.value : '',
        service_id: els.serviceSelect ? els.serviceSelect.value : '',
        bay_id: els.baySelect ? els.baySelect.value : '',
        washer_id: els.washerSelect ? els.washerSelect.value : '',
        customer_phone: els.customerPhone ? els.customerPhone.value : '',
    };

    const hasAny = Object.values(draft).some((value) => String(value || '').trim() !== '');
    if (!hasAny) {
        sessionStorage.removeItem(INTAKE_DRAFT_KEY);
        return;
    }

    sessionStorage.setItem(INTAKE_DRAFT_KEY, JSON.stringify(draft));
}

function clearIntakeDraft() {
    sessionStorage.removeItem(INTAKE_DRAFT_KEY);
}

function setSelectValue(select, value) {
    if (!select || value === undefined || value === null || value === '') return false;
    const next = String(value);
    if (![...select.options].some((option) => option.value === next)) return false;
    select.value = next;
    return true;
}

function restoreIntakeDraft() {
    if (!els.intakeForm) return;

    const draft = readIntakeDraft();
    if (!draft) return;

    if (els.plate && draft.license_plate) {
        els.plate.value = draft.license_plate;
    }
    if (els.customerPhone && draft.customer_phone) {
        els.customerPhone.value = draft.customer_phone;
    }

    setSelectValue(els.carType, draft.car_type);
    setSelectValue(els.serviceSelect, draft.service_id);
    setSelectValue(els.baySelect, draft.bay_id);
    setSelectValue(els.washerSelect, draft.washer_id);

    refreshAllFancySelects();

    if (els.carType?.value && els.serviceSelect?.value) {
        calculateTotal();
    }
}

function refreshWasherOptions(washers) {
    state.washers = washers || [];
    const busy = state.washers.length === 0;
    const placeholder = busy
        ? 'All washers are busy at the moment'
        : 'Select washer';

    if (els.washerSelect) {
        els.washerSelect.dataset.emptyMessage = 'All washers are busy at the moment';
    }

    fillSelect(els.washerSelect, state.washers, 'id', 'washer_name', placeholder);
    refreshAllFancySelects();
}

function applyDashboard(data) {
    state.services = data.services || [];
    state.bays = data.bays || [];
    state.jobs = data.jobs || [];

    fillSelect(els.serviceSelect, state.services, 'id', 'service_name', 'Select service');
    refreshWasherOptions(data.washers || []);
    renderBays();
    renderJobs(els.jobSearch ? els.jobSearch.value : '');
    restoreIntakeDraft();
}

async function loadDashboard() {
    const result = await api('/api/attendant_data.php');
    applyDashboard(result.data);
}

async function calculateTotal() {
    if (!els.serviceSelect || !els.carType) return;
    const serviceId = Number(els.serviceSelect.value);
    const carType = els.carType.value;

    if (!serviceId || !carType) {
        showToast('Select car type and service first.', true);
        return;
    }

    try {
        const result = await api('/api/attendant.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'calculate',
                service_id: serviceId,
                car_type: carType,
            }),
        });
        state.calculatedTotal = result.total;
        if (els.totalDisplay) {
            els.totalDisplay.textContent = result.formatted;
        }
    } catch (error) {
        showToast(error.message, true);
    }
}

async function checkIn(event) {
    event.preventDefault();
    if (!els.intakeForm) return;

    const missing = intakeMissingFields();
    if (missing.length) {
        showToast('Please fill in: ' + missing.join(', ') + '.', true);
        return;
    }

    const plate = normalizeGhanaPlate(els.plate.value);
    els.plate.value = plate;
    if (!isValidGhanaPlate(plate)) {
        setPlateHint(true);
        showToast('Enter a Ghana plate (e.g. GR 2345-26) or a custom plate with letters and numbers.', true);
        els.plate.focus();
        return;
    }
    setPlateHint(false);

    const payload = {
        action: 'check_in',
        license_plate: plate,
        car_type: els.carType.value,
        service_id: Number(els.serviceSelect.value),
        bay_id: Number(els.baySelect.value),
        washer_id: Number(els.washerSelect.value),
        customer_phone: els.customerPhone ? els.customerPhone.value.trim() : '',
    };

    try {
        const result = await api('/api/attendant.php', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        state.bays = result.bays || [];
        state.jobs = result.jobs || [];
        if (result.washers) refreshWasherOptions(result.washers);
        renderBays();
        renderJobs(els.jobSearch ? els.jobSearch.value : '');

        els.intakeForm.reset();
        clearIntakeDraft();
        refreshAllFancySelects();
        if (els.totalDisplay) {
            els.totalDisplay.textContent = 'GH₵ 0.00';
        }
        state.calculatedTotal = 0;

        showToast(result.message || 'Vehicle checked in.');
        openReceipt(result.receipt);
    } catch (error) {
        showToast(error.message, true);
    }
}

async function markPaid(method) {
    if (!state.currentReceipt) return;

    if (String(state.currentReceipt.payment_status || '').toLowerCase() === 'paid') {
        showToast('This job is already paid.');
        return;
    }

    if (method === 'momo') {
        await payWithPaystackMomo();
        return;
    }

    try {
        const result = await api('/api/attendant.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'mark_paid',
                transaction_id: state.currentReceipt.id,
                payment_method: 'cash',
            }),
        });

        state.bays = result.bays || [];
        state.jobs = result.jobs || [];
        if (result.washers) refreshWasherOptions(result.washers);
        state.currentReceipt = result.receipt;
        renderBays();
        renderJobs(els.jobSearch ? els.jobSearch.value : '');
        openReceipt(result.receipt);
        showToast(result.message || 'Cash payment recorded.');
    } catch (error) {
        showToast(error.message, true);
    }
}

async function payWithPaystackMomo() {
    if (!state.currentReceipt) return;

    if (typeof PaystackPop === 'undefined') {
        showToast('Paystack script failed to load. Check your internet connection.', true);
        return;
    }

    hidePayChooser();
    showToast('Opening Paystack MoMo…');

    try {
        const init = await api('/api/attendant.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'paystack_initialize',
                transaction_id: state.currentReceipt.id,
            }),
        });

        const handler = PaystackPop.setup({
            key: init.public_key,
            email: init.email,
            amount: init.amount,
            currency: init.currency || 'GHS',
            ref: init.reference,
            label: 'Squeaky Clean MoMo',
            callback: function (response) {
                verifyPaystackPayment(response.reference);
            },
            onClose: function () {
                showToast('Paystack window closed. Payment not completed.', true);
            },
        });

        handler.openIframe();
    } catch (error) {
        showToast(error.message, true);
    }
}

async function verifyPaystackPayment(reference) {
    try {
        const result = await api('/api/attendant.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'paystack_verify',
                reference,
            }),
        });

        state.bays = result.bays || [];
        state.jobs = result.jobs || [];
        if (result.washers) refreshWasherOptions(result.washers);
        state.currentReceipt = result.receipt;
        renderBays();
        renderJobs(els.jobSearch ? els.jobSearch.value : '');
        openReceipt(result.receipt);
        showToast(result.message || 'MoMo payment confirmed.');
    } catch (error) {
        showToast(error.message, true);
    }
}

async function completeWash(transactionId) {
    const id = Number(transactionId || state.currentReceipt?.id || 0);
    if (!id) {
        showToast('No job selected to complete.', true);
        return;
    }

    try {
        const result = await api('/api/attendant.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'complete_wash',
                transaction_id: id,
            }),
        });

        state.bays = result.bays || [];
        state.jobs = result.jobs || [];
        if (result.washers) refreshWasherOptions(result.washers);
        state.currentReceipt = result.receipt;
        renderBays();
        renderJobs(els.jobSearch ? els.jobSearch.value : '');
        if (els.modal && els.modal.classList.contains('open') && result.receipt) {
            openReceipt(result.receipt);
        }
        showToast(result.message || 'Wash completed.');
    } catch (error) {
        showToast(error.message, true);
    }
}

if (els.intakeForm) {
    els.intakeForm.addEventListener('submit', checkIn);
}

const clearIntakeBtn = document.getElementById('clear-intake-btn');
if (clearIntakeBtn) {
    clearIntakeBtn.addEventListener('click', () => {
        clearIntakeForm();
        showToast('Intake form cleared.');
    });
}

const closeModalBtn = document.getElementById('close-modal-btn');
const payCashBtn = document.getElementById('pay-cash-btn');
const payMomoBtn = document.getElementById('pay-momo-btn');
const payChooserCancel = document.getElementById('pay-chooser-cancel');

if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
if (payCashBtn) payCashBtn.addEventListener('click', () => markPaid('cash'));
if (payMomoBtn) payMomoBtn.addEventListener('click', () => markPaid('momo'));
if (payChooserCancel) payChooserCancel.addEventListener('click', hidePayChooser);

const sendSmsBtn = document.getElementById('send-sms-btn');
const sendWhatsappBtn = document.getElementById('send-whatsapp-btn');
const sendChooserCancel = document.getElementById('send-chooser-cancel');
if (sendSmsBtn) sendSmsBtn.addEventListener('click', () => sendReceiptToCustomer('sms'));
if (sendWhatsappBtn) sendWhatsappBtn.addEventListener('click', () => sendReceiptToCustomer('whatsapp'));
if (sendChooserCancel) sendChooserCancel.addEventListener('click', hideSendChooser);

if (els.jobSearch) {
    els.jobSearch.addEventListener('input', () => renderJobs(els.jobSearch.value));
}

if (els.jobsBody) {
    els.jobsBody.addEventListener('click', async (event) => {
        const completeBtn = event.target.closest('[data-complete-wash]');
        if (completeBtn) {
            await completeWash(Number(completeBtn.dataset.completeWash));
            return;
        }

        const btn = event.target.closest('[data-open-receipt]');
        if (!btn) return;
        const txnId = Number(btn.dataset.openReceipt);
        const job = state.jobs.find((j) => Number(j.transaction_id) === txnId);
        if (!job) return;

        openReceipt({
            id: job.transaction_id,
            license_plate: job.license_plate,
            car_type: job.car_type,
            service_name: job.service_name,
            bay_name: job.bay_name,
            washer_name: job.washer_name,
            washer_id: job.washer_id || job.assigned_washer_id,
            payment_status: job.payment_status,
            payment_method: job.payment_method,
            total_amount: job.total_amount,
            created_at: job.transaction_created_at || job.checked_in_at,
            paid_at: job.paid_at,
            checked_in_at: job.checked_in_at,
            vehicle_status: job.vehicle_status,
            vehicle_id: job.id,
            customer_phone: job.customer_phone || '',
        });
    });
}

if (els.intakeForm) {
    if (els.plate) {
        els.plate.addEventListener('input', () => {
            saveIntakeDraft();
            if (!els.plate.value.trim()) {
                setPlateHint(false);
                return;
            }
            const normalized = normalizeGhanaPlate(els.plate.value);
            setPlateHint(!isValidGhanaPlate(normalized));
        });

        els.plate.addEventListener('blur', () => {
            if (!els.plate.value.trim()) return;
            els.plate.value = normalizeGhanaPlate(els.plate.value);
            setPlateHint(!isValidGhanaPlate(els.plate.value));
            saveIntakeDraft();
        });
    }

    if (els.customerPhone) {
        els.customerPhone.addEventListener('input', saveIntakeDraft);
    }

    [els.carType, els.serviceSelect, els.baySelect, els.washerSelect].forEach((select) => {
        if (!select) return;
        select.addEventListener('change', () => {
            saveIntakeDraft();
            if (
                (select === els.carType || select === els.serviceSelect) &&
                els.carType?.value &&
                els.serviceSelect?.value
            ) {
                calculateTotal();
            }
        });
    });
}

window.addEventListener('pagehide', saveIntakeDraft);

if (els.modal) {
    els.modal.addEventListener('click', (event) => {
        if (event.target === els.modal) closeModal();
    });
}

document.addEventListener('click', (event) => {
    if (!event.target.closest('.fancy-select')) {
        closeAllFancySelects();
    }
});

initFancySelects();
loadDashboard().catch((error) => showToast(error.message, true));
