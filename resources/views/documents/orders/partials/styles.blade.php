<style>
    :root {
        --gk-green: #0b6b16;
        --gk-orange: #f97316;
        --gk-border: #d9e2d9;
        --gk-muted: #647067;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #f3f6f2;
        color: #1f2a24;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 13px;
        line-height: 1.45;
    }

    .document-shell {
        width: 210mm;
        min-height: 297mm;
        margin: 20px auto;
        padding: 16mm;
        background: #fff;
        border: 1px solid var(--gk-border);
        box-shadow: 0 12px 32px rgba(0, 0, 0, .08);
    }

    .document-actions {
        width: 210mm;
        margin: 20px auto 0;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .btn-print {
        border: 1px solid var(--gk-green);
        background: var(--gk-green);
        color: #fff;
        border-radius: 6px;
        padding: 8px 14px;
        cursor: pointer;
        font-weight: 700;
    }

    .doc-header,
    .doc-grid,
    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
    }

    .doc-title {
        color: var(--gk-green);
        font-size: 26px;
        margin: 0 0 4px;
    }

    .doc-subtitle {
        color: var(--gk-orange);
        font-size: 18px;
        font-weight: 700;
        margin: 0;
    }

    .muted {
        color: var(--gk-muted);
    }

    .text-end {
        text-align: right;
    }

    .section {
        margin-top: 18px;
    }

    .box {
        border: 1px solid var(--gk-border);
        border-radius: 8px;
        padding: 12px;
        flex: 1;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        border: 1px solid var(--gk-border);
        padding: 8px;
        vertical-align: top;
    }

    th {
        background: #eef7ee;
        color: var(--gk-green);
        text-align: left;
    }

    .totals {
        width: 310px;
        margin-left: auto;
    }

    .summary-row {
        border-bottom: 1px solid var(--gk-border);
        padding: 7px 0;
    }

    .grand-total {
        color: var(--gk-green);
        font-size: 18px;
        font-weight: 800;
    }

    .badge {
        display: inline-block;
        border: 1px solid var(--gk-border);
        border-radius: 999px;
        padding: 3px 8px;
        background: #f7fbf7;
    }

    @page {
        size: A4;
        margin: 10mm;
    }

    @media print {
        body {
            background: #fff;
        }

        .document-actions {
            display: none;
        }

        .document-shell {
            width: auto;
            min-height: auto;
            margin: 0;
            padding: 0;
            border: 0;
            box-shadow: none;
        }

        a {
            color: inherit;
            text-decoration: none;
        }
    }
</style>
