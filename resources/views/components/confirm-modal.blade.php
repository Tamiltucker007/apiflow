{{--
    Single shared modal, driven entirely by data-* attributes on whichever
    form triggers it (see resources/js/app.js). Needed because row actions in
    Plans/Customers/Subscriptions/Team are built as raw HTML strings on the
    server (Yajra DataTables columns) — a native confirm() was the only
    option there before; this keeps one on-brand dialog for all of them
    instead of duplicating modal markup per page.
--}}
<div id="confirm-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/50" data-confirm-cancel></div>

    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-sm w-full p-6">
            <div class="flex items-start gap-3">
                <div id="confirm-modal-icon" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 7v4M10 13.5h.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.75"/>
                    </svg>
                </div>
                <div class="flex-1 pt-1">
                    <h3 id="confirm-modal-title" class="text-base font-semibold text-gray-900"></h3>
                    <p id="confirm-modal-message" class="text-sm text-gray-500 mt-1"></p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-confirm-cancel
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" id="confirm-modal-confirm"
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg transition">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
