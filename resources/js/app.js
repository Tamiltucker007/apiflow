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
