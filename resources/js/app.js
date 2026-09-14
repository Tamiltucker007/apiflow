import './bootstrap';

import DataTable from 'datatables.net';

// A leading row-number column, correct across pages: meta.row is the index
// within the current page's response, so it needs the page's own offset
// (_iDisplayStart) added rather than just being meta.row + 1.
window.dtSerialColumn = function () {
    return {
        data: null,
        orderable: false,
        searchable: false,
        className: 'px-4 py-3 text-gray-400 w-12',
        render: (data, type, row, meta) => meta.row + meta.settings.displayStart + 1,
    };
};

// Shared setup so every list page (Plans, Customers, Subscriptions, Invoices)
// gets the same server-side pagination/search behavior and styling hooks —
// see app.css for the .dt-* rules that make it match the rest of the UI.
// Uses DataTables' standalone API (no jQuery — this package is jQuery-free
// unless explicitly bridged with DataTable.use(), which this app doesn't need).
window.initDataTable = function (selector, ajaxUrl, columns, order = [[0, 'asc']]) {
    return new DataTable(selector, {
        processing: true,
        serverSide: true,
        ajax: ajaxUrl,
        columns,
        order,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        language: {
            search: '',
            searchPlaceholder: 'Search…',
            emptyTable: 'Nothing here yet.',
            zeroRecords: 'No matching records found.',
        },
    });
};

// Confirmation modal for destructive/state-changing actions (delete,
// deactivate, change plan). Row actions in Plans/Customers/Subscriptions/Team
// are built as raw HTML strings server-side (Yajra DataTables columns), so a
// static <script> handler per page can't target them — this listens for the
// bubbled `submit` event at the document level instead, which fires
// regardless of when the form was inserted into the DOM (including DataTables
// ajax re-renders), and works for both server-rendered and Blade forms alike.
// A form opts in with data-confirm="..."; everything else submits untouched.
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;

    const titleEl = document.getElementById('confirm-modal-title');
    const messageEl = document.getElementById('confirm-modal-message');
    const iconEl = document.getElementById('confirm-modal-icon');
    const confirmBtn = document.getElementById('confirm-modal-confirm');

    const variants = {
        danger: { icon: 'bg-red-100 text-red-600', button: 'bg-red-600 hover:bg-red-700' },
        warning: { icon: 'bg-amber-100 text-amber-600', button: 'bg-amber-600 hover:bg-amber-700' },
        default: { icon: 'bg-indigo-100 text-indigo-600', button: 'bg-indigo-600 hover:bg-indigo-700' },
    };

    let pendingForm = null;

    function closeModal() {
        modal.classList.add('hidden');
        pendingForm = null;
    }

    function openModal(form) {
        const variant = variants[form.dataset.confirmVariant] || variants.default;

        let message = form.dataset.confirm || 'Are you sure?';
        if (form.dataset.confirmTemplate) {
            const select = form.querySelector('select');
            const label = select ? select.options[select.selectedIndex].text : '';
            message = form.dataset.confirmTemplate.replace('{value}', label);
        }

        titleEl.textContent = form.dataset.confirmTitle || 'Please confirm';
        messageEl.textContent = message;
        iconEl.className = 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center ' + variant.icon;
        confirmBtn.textContent = form.dataset.confirmAction || 'Confirm';
        confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white rounded-lg transition ' + variant.button;

        pendingForm = form;
        modal.classList.remove('hidden');
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.dataset.confirm && !form.dataset.confirmTemplate) return;

        event.preventDefault();
        openModal(form);
    });

    confirmBtn.addEventListener('click', function () {
        const form = pendingForm;
        closeModal();
        form?.submit();
    });

    modal.querySelectorAll('[data-confirm-cancel]').forEach((el) => el.addEventListener('click', closeModal));

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
});
