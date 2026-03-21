<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'ticketSP')</title>
        <style>
            :root {
                --bg: #f5efe6;
                --panel: #fffaf4;
                --panel-strong: #fff;
                --line: #d7c8b4;
                --text: #2b241c;
                --muted: #6f6457;
                --brand: #9b3d23;
                --brand-strong: #7c2d12;
                --accent: #d7a54b;
                --ok: #246a4d;
                --warn: #8b5e00;
                --danger: #9b1c1c;
                --shadow: 0 20px 45px rgba(43, 36, 28, 0.08);
                --radius: 22px;
                --font: "IBM Plex Sans", "Segoe UI", sans-serif;
            }

            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: var(--font);
                color: var(--text);
                background:
                    radial-gradient(circle at top left, rgba(215, 165, 75, 0.22), transparent 28%),
                    radial-gradient(circle at top right, rgba(155, 61, 35, 0.14), transparent 22%),
                    linear-gradient(180deg, #f9f2e8 0%, var(--bg) 100%);
                min-height: 100vh;
            }
            a { color: inherit; text-decoration: none; }
            .shell {
                max-width: 1180px;
                margin: 0 auto;
                padding: 32px 20px 48px;
            }
            .topbar {
                display: flex;
                gap: 16px;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 28px;
            }
            .brand {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .brand-mark {
                width: 46px;
                height: 46px;
                border-radius: 16px;
                background: linear-gradient(135deg, var(--brand), var(--accent));
                color: #fff;
                display: grid;
                place-items: center;
                font-weight: 700;
            }
            .brand h1 {
                margin: 0;
                font-size: 1.35rem;
            }
            .brand p {
                margin: 3px 0 0;
                color: var(--muted);
                font-size: 0.92rem;
            }
            .nav {
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
            }
            .card {
                background: rgba(255, 250, 244, 0.92);
                border: 1px solid rgba(215, 200, 180, 0.9);
                border-radius: var(--radius);
                box-shadow: var(--shadow);
                backdrop-filter: blur(8px);
            }
            .button,
            button,
            input,
            select,
            textarea {
                font: inherit;
            }
            .button,
            button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 0;
                cursor: pointer;
                border-radius: 999px;
                padding: 11px 18px;
                max-width: 100%;
                white-space: nowrap;
                transition: transform 140ms ease, opacity 140ms ease, background 140ms ease;
            }
            .button:hover,
            button:hover {
                transform: translateY(-1px);
            }
            .button-primary {
                background: var(--brand);
                color: #fff;
            }
            .button-secondary {
                background: rgba(155, 61, 35, 0.1);
                color: var(--brand-strong);
            }
            .button-muted {
                background: rgba(111, 100, 87, 0.12);
                color: var(--text);
            }
            .button-danger {
                background: rgba(155, 28, 28, 0.12);
                color: var(--danger);
            }
            .stack {
                display: grid;
                gap: 20px;
            }
            .grid {
                display: grid;
                gap: 20px;
            }
            .grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .grid-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .panel {
                padding: 24px;
            }
            .section-title {
                margin: 0 0 6px;
                font-size: 1.35rem;
            }
            .section-copy {
                margin: 0;
                color: var(--muted);
            }
            .hero {
                padding: 28px;
                display: grid;
                gap: 10px;
                background:
                    linear-gradient(135deg, rgba(155, 61, 35, 0.94), rgba(215, 165, 75, 0.88)),
                    #9b3d23;
                color: #fff;
            }
            .hero p {
                margin: 0;
                max-width: 720px;
                color: rgba(255, 244, 230, 0.92);
            }
            .metrics {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
            }
            .metric {
                padding: 16px;
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.18);
            }
            .metric strong {
                display: block;
                font-size: 1.4rem;
                margin-top: 8px;
            }
            label {
                display: grid;
                gap: 8px;
                color: var(--muted);
                font-size: 0.95rem;
            }
            input,
            select,
            textarea {
                width: 100%;
                border: 1px solid var(--line);
                background: var(--panel-strong);
                border-radius: 16px;
                padding: 12px 14px;
                color: var(--text);
            }
            input:disabled,
            select:disabled,
            textarea:disabled,
            .input-disabled {
                background: #ebe6de;
                color: #827668;
                cursor: not-allowed;
            }
            mark {
                background: rgba(215, 165, 75, 0.35);
                color: inherit;
                padding: 0 2px;
                border-radius: 4px;
            }
            textarea {
                min-height: 140px;
                resize: vertical;
            }
            .toolbar {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
                align-items: end;
            }
            .toolbar > * {
                flex: 1 1 180px;
            }
            .toolbar .toolbar-actions {
                flex: 0 0 auto;
                display: flex;
                gap: 10px;
            }
            .toolbar .toolbar-wide {
                flex: 1 1 240px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th,
            td {
                padding: 14px 10px;
                border-bottom: 1px solid rgba(215, 200, 180, 0.8);
                text-align: left;
                vertical-align: top;
            }
            th {
                color: var(--muted);
                font-weight: 600;
                font-size: 0.9rem;
            }
            .badge {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 6px 10px;
                font-size: 0.84rem;
                font-weight: 600;
                background: rgba(111, 100, 87, 0.12);
                color: var(--text);
            }
            .badge-open { background: rgba(215, 165, 75, 0.18); color: var(--warn); }
            .badge-progress { background: rgba(36, 106, 77, 0.16); color: var(--ok); }
            .badge-resolved { background: rgba(59, 130, 246, 0.14); color: #1d4ed8; }
            .badge-closed { background: rgba(111, 100, 87, 0.16); color: #4b5563; }
            .list {
                display: grid;
                gap: 14px;
                align-content: start;
            }
            .comment,
            .activity {
                padding: 16px 18px;
                border-radius: 18px;
                background: var(--panel-strong);
                border: 1px solid rgba(215, 200, 180, 0.88);
            }
            .meta {
                color: var(--muted);
                font-size: 0.9rem;
            }
            .flash {
                padding: 14px 16px;
                border-radius: 16px;
                margin-bottom: 18px;
            }
            .flash-success {
                background: rgba(36, 106, 77, 0.12);
                color: var(--ok);
            }
            .flash-error {
                background: rgba(155, 28, 28, 0.12);
                color: var(--danger);
            }
            .empty {
                padding: 28px;
                text-align: center;
                color: var(--muted);
            }
            .inline-form {
                display: inline-flex;
            }
            .ticket-actions {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
                white-space: nowrap;
            }
            .checkbox-cell {
                width: 44px;
            }
            .checkbox-input {
                width: 18px;
                height: 18px;
                padding: 0;
                accent-color: var(--brand);
            }
            .people-list {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
            .person-chip {
                display: inline-flex;
                align-items: center;
                padding: 6px 10px;
                border-radius: 999px;
                background: rgba(155, 61, 35, 0.08);
                border: 1px solid rgba(155, 61, 35, 0.12);
                color: var(--brand-strong);
                font-size: 0.88rem;
            }
            .person-chip-muted {
                background: rgba(111, 100, 87, 0.08);
                border-color: rgba(111, 100, 87, 0.12);
                color: var(--muted);
            }
            .inline-note {
                margin-top: 6px;
                color: var(--muted);
                font-size: 0.88rem;
            }
            .copy-share {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                align-items: center;
            }
            .login-shell {
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 24px;
            }
            .login-card {
                max-width: 440px;
                width: 100%;
                padding: 28px;
            }
            .helper {
                padding: 14px 16px;
                border-radius: 16px;
                background: rgba(215, 165, 75, 0.14);
                color: #734400;
            }
            .search-result {
                width: 100%;
                justify-content: flex-start;
                text-align: left;
                border-radius: 16px;
                padding: 14px 16px;
                background: var(--panel-strong);
                border: 1px solid rgba(215, 200, 180, 0.88);
                display: grid;
                gap: 4px;
            }
            .search-result span {
                color: var(--muted);
                font-size: 0.9rem;
            }
            .admin-footnote {
                display: flex;
                gap: 4px;
                flex-wrap: wrap;
                justify-content: center;
                color: var(--muted);
                font-size: 0.88rem;
            }
            .admin-footnote-form {
                display: inline-flex;
            }
            .text-link-button {
                padding: 0;
                border: 0;
                border-radius: 0;
                background: transparent;
                color: var(--muted);
                text-decoration: underline;
                text-underline-offset: 2px;
                white-space: normal;
            }
            .text-link-button:hover {
                transform: none;
                color: var(--brand-strong);
            }
            .pagination {
                display: flex;
                justify-content: center;
                margin-top: 20px;
            }
            .pagination nav {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
                width: 100%;
                flex-wrap: wrap;
            }
            .pagination-summary {
                margin: 0;
                color: var(--muted);
                font-size: 0.92rem;
                text-align: center;
            }
            .pagination-links {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: center;
            }
            .pagination span,
            .pagination a {
                padding: 10px 13px;
                border-radius: 999px;
                border: 1px solid var(--line);
                background: var(--panel-strong);
            }
            .pagination span[aria-current="page"] {
                background: rgba(155, 61, 35, 0.12);
                border-color: rgba(155, 61, 35, 0.3);
                color: var(--brand-strong);
                font-weight: 700;
            }
            .pagination span[aria-disabled="true"] {
                color: var(--muted);
                opacity: 0.72;
            }
            .export-panel {
                margin-top: 24px;
                padding-top: 24px;
                border-top: 1px solid rgba(215, 200, 180, 0.8);
                display: grid;
                gap: 14px;
            }
            .export-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .simple-row-form {
                display: grid;
                grid-template-columns: minmax(220px, 1fr) auto;
                gap: 10px;
                align-items: center;
                padding: 14px 10px;
            }
            @media (max-width: 900px) {
                .grid-2,
                .grid-3,
                .metrics {
                    grid-template-columns: 1fr;
                }
                .topbar {
                    align-items: flex-start;
                    flex-direction: column;
                }
                .toolbar .toolbar-actions,
                .toolbar .toolbar-wide {
                    flex: 1 1 100%;
                }
                .toolbar {
                    display: grid;
                }
                .nav {
                    width: 100%;
                    align-items: stretch;
                }
                .nav > a,
                .nav > button,
                .nav > .inline-form {
                    width: 100%;
                }
                .nav > .inline-form > button {
                    width: 100%;
                }
                .simple-row-form {
                    grid-template-columns: 1fr;
                }
                table,
                thead,
                tbody,
                tr,
                th,
                td {
                    display: block;
                }
                thead {
                    display: none;
                }
                td {
                    padding-left: 0;
                    padding-right: 0;
                }
            }
        </style>
    </head>
    <body>
        @yield('body')
    </body>
</html>
