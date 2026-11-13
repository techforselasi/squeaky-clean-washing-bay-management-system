<div class="modal-backdrop" id="receipt-modal" role="dialog" aria-modal="true" aria-labelledby="receipt-title">
    <div class="modal bill-modal" id="bill-modal">
        <button class="bill-close no-print" type="button" id="close-modal-btn" aria-label="Close">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>

        <div id="receipt-body"></div>

        <div class="pay-chooser no-print" id="pay-chooser" hidden>
            <div class="pay-chooser-card">
                <h4>How is the customer paying?</h4>
                <p>Choose Cash or MoMo (Paystack). After payment, a receipt will be ready to print or send.</p>
                <div class="pay-chooser-actions">
                    <button class="btn-bill-choice" type="button" id="pay-cash-btn">Cash</button>
                    <button class="btn-bill-choice btn-bill-choice-primary" type="button" id="pay-momo-btn">MoMo</button>
                </div>
                <button class="pay-chooser-cancel" type="button" id="pay-chooser-cancel">Cancel</button>
            </div>
        </div>

        <div class="pay-chooser no-print" id="send-chooser" hidden>
            <div class="pay-chooser-card">
                <h4>Send receipt to customer</h4>
                <p>SMS sends text. WhatsApp downloads an A5 PDF receipt for you to attach (looks like a real bill).</p>
                <div class="send-fields">
                    <label for="send-phone">Customer phone</label>
                    <input class="form-control" type="tel" id="send-phone" placeholder="e.g. 0241234567" autocomplete="off">
                </div>
                <div class="pay-chooser-actions">
                    <button class="btn-bill-choice btn-send-sms" type="button" id="send-sms-btn">SMS</button>
                    <button class="btn-bill-choice btn-send-whatsapp" type="button" id="send-whatsapp-btn">WhatsApp</button>
                </div>
                <button class="pay-chooser-cancel" type="button" id="send-chooser-cancel">Cancel</button>
            </div>
        </div>
    </div>
</div>
