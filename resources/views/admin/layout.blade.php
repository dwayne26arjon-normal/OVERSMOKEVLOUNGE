<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#100b20">
    <title>@yield('title', 'Admin') | Oversmoke Vlounge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: dark; font-family: 'DM Sans', sans-serif; color: #f4f1ff; background: #0c0a12; font-synthesis: none; }
        * { box-sizing: border-box; }
        body { min-width: 320px; min-height: 100vh; margin: 0; background: radial-gradient(ellipse at 70% 0, rgba(66, 42, 116, .11), transparent 44%), #0c0a12; }
        a { color: inherit; }
        .admin-shell { min-height: 100vh; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; clip-path: inset(50%); border: 0; }
        .sidebar { position: fixed; z-index: 5; inset: 0 auto 0 0; display: flex; width: 218px; flex-direction: column; border-right: 1px solid #211d2b; background: rgba(13, 11, 19, .97); padding: 18px 12px 14px; }
        .brand { display: flex; height: 32px; align-items: center; gap: 9px; padding: 0 8px; text-decoration: none; }
        .brand img { width: 28px; height: 28px; object-fit: contain; }
        .brand span { font-family: 'Outfit', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: .04em; }
        .nav-label { margin: 28px 8px 9px; color: #686273; font-size: 9px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; }
        .nav-links { display: grid; gap: 4px; }
        .nav-link { position: relative; display: flex; min-height: 34px; align-items: center; gap: 10px; padding: 0 10px; border: 1px solid transparent; border-radius: 6px; color: #9c97a8; font-size: 11px; text-decoration: none; transition: background .18s, color .18s, border-color .18s; }
        .nav-link:hover { background: #171320; color: #f4f1ff; }
        .nav-link.active { border-color: rgba(130, 88, 255, .16); background: linear-gradient(90deg, rgba(100, 54, 236, .28), rgba(90, 50, 180, .1)); color: #cdbdff; }
        .nav-icon { display: block; width: 16px; height: 16px; flex: 0 0 16px; object-fit: contain; opacity: .9; }
        .nav-icon-svg { fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .nav-link.active .nav-icon { opacity: 1; }
        .nav-link.disabled { color: #625d6b; cursor: not-allowed; }
        .nav-link.disabled .nav-icon { opacity: .45; }
        .admin-chat-nav-count, .admin-nav-count { position: absolute; top: -5px; right: -5px; display: grid; min-width: 17px; height: 17px; place-items: center; padding: 0 4px; border: 1px solid #0c0a12; border-radius: 999px; background: #e74d70; color: #fff; font-size: 9px; font-weight: 700; }
        .admin-chat-nav-count[hidden], .admin-nav-count[hidden] { display: none; }
        .sidebar-bottom { margin-top: auto; }
        .add-shortcut { display: flex; min-height: 32px; align-items: center; justify-content: center; margin-bottom: 12px; border: 1px solid #32264d; border-radius: 6px; color: #c8b7ff; font-size: 10px; text-decoration: none; transition: background .18s; }
        .add-shortcut:hover { background: #1c1530; }
        .main-area { min-height: 100vh; margin-left: 218px; }
        .topbar { position: sticky; z-index: 4; top: 0; display: flex; min-height: 54px; align-items: center; justify-content: space-between; gap: 20px; border-bottom: 1px solid #211d2b; background: rgba(12, 10, 18, .92); padding: 0 26px; backdrop-filter: blur(16px); }
        .topbar-search { position: relative; width: min(280px, 42vw); }
        .topbar-search input { width: 100%; height: 30px; padding: 0 34px 0 11px; border: 1px solid #272331; border-radius: 6px; outline: 0; background: #14111b; color: #eee; font: inherit; font-size: 10px; }
        .topbar-search input:focus { border-color: #7045eb; box-shadow: 0 0 0 2px rgba(112, 69, 235, .15); }
        .search-icon { position: absolute; top: 7px; right: 10px; color: #8d8798; font-size: 12px; pointer-events: none; }
        .admin-notifications { position: relative; margin-left: auto; }
        .notification-button { position: relative; display: grid; width: 32px; height: 32px; place-items: center; border: 1px solid #302a3b; border-radius: 6px; background: #14111b; color: #c8c2d2; cursor: pointer; }
        .notification-button:hover, .notification-button:focus-visible, .notification-button[aria-expanded="true"] { border-color: #7045eb; color: #fff; outline: 0; }
        .notification-button svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .notification-count { position: absolute; top: -6px; right: -6px; display: grid; min-width: 16px; height: 16px; place-items: center; padding: 0 4px; border: 1px solid #0c0a12; border-radius: 999px; background: #e74d70; color: #fff; font-size: 9px; font-weight: 700; }
        .notification-count[hidden] { display: none; }
        .notification-menu { position: absolute; top: calc(100% + 10px); right: 0; z-index: 9; width: min(330px, calc(100vw - 30px)); overflow: hidden; border: 1px solid #322c3e; border-radius: 8px; background: #17141f; box-shadow: 0 14px 36px rgba(0,0,0,.48); }
        .notification-menu[hidden] { display: none; }
        .notification-heading { display: flex; align-items: center; justify-content: space-between; padding: 11px 12px; border-bottom: 1px solid #302a3b; color: #e8e4ef; font-size: 11px; font-weight: 600; }
        .notification-clear { border: 0; background: none; color: #ab91ff; font: inherit; font-size: 9px; cursor: pointer; }
        .notification-list { max-height: 310px; overflow-y: auto; }
        .notification-item { display: block; padding: 11px 12px; border-bottom: 1px solid rgba(48,42,59,.65); color: #c9c3d1; font-size: 10px; text-decoration: none; }
        .notification-item.is-unread { background: rgba(116,79,205,.1); }
        .notification-item:last-child { border-bottom: 0; }
        .notification-item:hover, .notification-item:focus-visible { outline: 0; background: rgba(116,79,205,.14); }
        .notification-item strong { display: block; margin-bottom: 4px; color: #f1ecfa; font-size: 10px; }
        .notification-empty { padding: 22px 12px; color: #888291; font-size: 10px; text-align: center; }
        .notification-status { padding: 8px 12px; color: #aaa3b5; font-size: 10px; }
        .notification-status:empty { display: none; }
        .order-toast { position: fixed; top: 66px; right: 24px; z-index: 20; width: min(340px, calc(100vw - 32px)); padding: 13px 15px; border: 1px solid rgba(132,99,235,.55); border-radius: 8px; background: #1b1726; box-shadow: 0 14px 36px rgba(0,0,0,.48), 0 0 24px rgba(112,69,235,.2); color: #eee9f6; font-size: 11px; animation: notification-in .25s ease-out both; }
        .order-toast[hidden] { display: none; }
        .order-toast strong { display: block; margin-bottom: 4px; font-size: 12px; }
        .order-toast a { color: #c7b5ff; text-decoration: none; }
        @keyframes notification-in { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        .admin-profile { position: relative; display: flex; align-items: center; }
        .admin-profile-button { display: flex; align-items: center; gap: 9px; padding: 4px 0 4px 8px; border: 0; border-radius: 6px; background: transparent; color: inherit; font: inherit; text-align: right; cursor: pointer; }
        .admin-profile-button:hover .avatar, .admin-profile-button:focus-visible .avatar, .admin-profile-button[aria-expanded="true"] .avatar { border-color: #7658bc; background: #2a2140; }
        .admin-profile-button:focus-visible { outline: 2px solid #a98cff; outline-offset: 3px; }
        .admin-profile-copy { text-align: right; }
        .admin-profile-copy strong { display: block; font-size: 10px; font-weight: 600; }
        .admin-profile-copy span { display: block; margin-top: 3px; color: #837d8e; font-size: 9px; }
        .avatar { display: grid; width: 27px; height: 27px; place-items: center; border: 1px solid #423460; border-radius: 50%; background: #211a34; color: #cbbaff; font-size: 10px; font-weight: 700; }
        .admin-account-menu { position: absolute; top: calc(100% + 9px); right: 0; z-index: 8; min-width: 170px; padding: 7px; border: 1px solid #322c3e; border-radius: 7px; background: #17141f; box-shadow: 0 12px 30px rgba(0, 0, 0, .4); }
        .admin-account-menu[hidden] { display: none; }
        .admin-account-menu form { margin: 0; }
        .admin-account-menu button { width: 100%; padding: 9px 10px; border: 0; border-radius: 5px; background: transparent; color: #c8c2d2; font: inherit; font-size: 11px; text-align: left; cursor: pointer; }
        .admin-account-menu button:hover, .admin-account-menu button:focus-visible { outline: 0; background: rgba(116, 79, 205, .16); color: #fff; }
        .admin-logout-dialog { width: min(420px, calc(100% - 32px)); padding: 24px; border: 1px solid rgba(124,92,240,.45); border-radius: 14px; color: #f4f1ff; background: #15121c; box-shadow: 0 24px 80px rgba(0,0,0,.58); }
        .admin-logout-dialog::backdrop { background: rgba(5,4,8,.75); backdrop-filter: blur(4px); }
        .admin-logout-dialog-icon { display: grid; width: 38px; height: 38px; margin-bottom: 12px; place-items: center; border: 1px solid rgba(124,92,240,.45); border-radius: 50%; color: #c4b5fd; background: rgba(124,92,240,.14); font-size: 19px; font-weight: 700; }
        .admin-logout-eyebrow { margin: 0 0 5px; color: #b6a5ff; font-size: 9px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .admin-logout-dialog h2 { margin: 0; color: #f4f1ff; font: 600 16px 'Outfit', sans-serif; }
        .admin-logout-dialog > p:not(.admin-logout-eyebrow) { margin: 8px 0 0; color: #aaa4b2; font-size: 11px; line-height: 1.55; }
        .admin-logout-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 21px; }
        .admin-logout-actions button { min-height: 34px; padding: 0 12px; border: 1px solid #393347; border-radius: 6px; color: #d0cad8; background: #1b1823; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .admin-logout-actions button:focus-visible { outline: 2px solid #a98cff; outline-offset: 2px; }
        .admin-logout-actions .admin-logout-proceed { border-color: #7655df; color: #fff; background: #6232e8; }
        .admin-logout-actions .admin-logout-proceed:hover { background: #7655f2; }
        .page-content { max-width: 1500px; margin: 0 auto; padding: 25px 27px 38px; }
        .page-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 20px; }
        .page-heading h1 { margin: 0; font-family: 'Outfit', sans-serif; font-size: 19px; font-weight: 600; letter-spacing: -.025em; }
        .page-heading p { margin: 5px 0 0; color: #8e8898; font-size: 10px; }
        .primary-button, .secondary-button { display: inline-flex; min-height: 32px; align-items: center; justify-content: center; gap: 7px; padding: 0 12px; border: 1px solid #5235a0; border-radius: 6px; background: linear-gradient(100deg, #5127d2, #7140e8); color: #fff; font: inherit; font-size: 10px; font-weight: 600; text-decoration: none; cursor: pointer; transition: filter .18s, transform .18s; }
        .primary-button:hover, .secondary-button:hover { filter: brightness(1.15); }
        .primary-button:active, .secondary-button:active { transform: translateY(1px); }
        .secondary-button { border-color: #302a3b; background: #17141f; color: #c1bbc9; }
        .panel { border: 1px solid #26212f; border-radius: 8px; background: linear-gradient(145deg, rgba(24, 21, 33, .92), rgba(17, 15, 23, .95)); }
        .panel-title { margin: 0; color: #e8e4ef; font-size: 11px; font-weight: 600; }
        .muted { color: #888291; }
        .flash { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; width: min(440px, calc(100vw - 40px)); align-items: center; justify-content: space-between; gap: 14px; margin: 0; padding: 12px 14px; border: 1px solid rgba(112, 220, 170, .36); border-radius: 9px; background: rgba(17, 33, 27, .97); color: #9de2bd; box-shadow: 0 16px 42px rgba(0,0,0,.4); font-size: 11px; animation: flash-enter .22s ease-out; }
        .flash[hidden] { display: none; }
        .flash-dismiss { display: grid; width: 26px; height: 26px; flex: 0 0 26px; place-items: center; border: 0; border-radius: 5px; background: transparent; color: inherit; font: inherit; font-size: 18px; line-height: 1; cursor: pointer; }
        .flash-dismiss:hover, .flash-dismiss:focus-visible { background: rgba(255,255,255,.1); color: #fff; }
        .flash-dismiss:focus-visible { outline: 2px solid #a78bfa; outline-offset: 2px; }
        @keyframes flash-enter { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 600px) { .flash { top: 12px; right: 12px; width: calc(100vw - 24px); } }
        @media (prefers-reduced-motion: reduce) { .flash { animation: none; } }
        .field-error { margin: 6px 0 0; color: #ff8e9b; font-size: 10px; }
        .table-wrap { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; white-space: nowrap; }
        .data-table th { padding: 10px 12px; border-bottom: 1px solid #2b2634; color: #807a89; font-size: 9px; font-weight: 600; }
        .data-table td { padding: 10px 12px; border-bottom: 1px solid rgba(43, 38, 52, .66); color: #c8c3d0; font-size: 10px; }
        .data-table tbody tr:last-child td { border-bottom: 0; }
        .data-table tbody tr:hover { background: rgba(116, 79, 205, .045); }
        .product-cell { display: flex; align-items: center; gap: 9px; }
        .product-thumb { display: grid; width: 31px; height: 31px; flex: 0 0 31px; place-items: center; overflow: hidden; border: 1px solid #322c3e; border-radius: 5px; background: #15121c; color: #797187; font-size: 9px; }
        .product-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .product-name { color: #eeeaf5; font-weight: 600; }
        .product-meta { display: block; margin-top: 3px; color: #797382; font-size: 9px; }
        .badge { display: inline-flex; min-height: 19px; align-items: center; padding: 0 7px; border: 1px solid #312943; border-radius: 4px; background: #1c1725; color: #c4b1ff; font-size: 9px; }
        .badge.good { border-color: rgba(48, 180, 122, .22); background: rgba(28, 125, 81, .1); color: #60d9a1; }
        .badge.warning { border-color: rgba(212, 160, 45, .22); background: rgba(160, 117, 18, .1); color: #e8c35f; }
        .badge.danger { border-color: rgba(225, 77, 97, .24); background: rgba(151, 34, 52, .12); color: #ff7888; }
        .table-action { border: 0; background: none; color: #a98cff; font: inherit; font-size: 10px; text-decoration: none; cursor: pointer; }
        .table-action:hover { color: #fff; }
        .empty-state { padding: 38px 16px; color: #888291; font-size: 11px; text-align: center; }
        .pagination { display: flex; justify-content: space-between; align-items: center; gap: 12px; border-top: 1px solid #26212f; padding: 11px 12px; color: #888291; font-size: 9px; }
        .pagination:not(.pagination-window) nav { display: flex; flex: 0 0 auto; justify-content: flex-end; }
        .pagination:not(.pagination-window) nav > div:first-child, .pagination:not(.pagination-window) nav > div:nth-child(2) > div:first-child { display: none; }
        .pagination:not(.pagination-window) nav > div:nth-child(2), .pagination:not(.pagination-window) nav > div:nth-child(2) > div:last-child { display: flex; align-items: center; justify-content: flex-end; }
        .pagination:not(.pagination-window) nav > div:nth-child(2) > div:last-child > span { display: flex; align-items: center; gap: 4px; }
        .pagination:not(.pagination-window) a, .pagination:not(.pagination-window) span[aria-current] span, .pagination:not(.pagination-window) span[aria-disabled] span { display: inline-grid; min-width: 24px; height: 24px; place-items: center; border: 1px solid #302a3b; border-radius: 5px; color: #c3bdce; text-decoration: none; }
        .pagination:not(.pagination-window) span[aria-current] span { border-color: #6543bb; background: #281e3d; color: #e1d7ff; }
        .pagination:not(.pagination-window) span[aria-disabled] span { color: #615b6a; }
        .pagination-window { min-width: 0; }
        .pagination-window > span { flex: 0 0 auto; white-space: nowrap; }
        .admin-pagination { display: flex; flex: 0 1 auto; min-width: 0; align-items: center; justify-content: flex-end; gap: 5px; }
        .admin-pagination-window { width: 24px; flex: 0 0 24px; overflow: hidden; }
        .admin-pagination-track { display: flex; width: max-content; align-items: center; gap: 4px; padding: 1px 0; }
        .admin-pagination-window.is-sliding-forward .admin-pagination-track { animation: admin-pagination-slide-forward .24s ease-out both; }
        .admin-pagination-window.is-sliding-backward .admin-pagination-track { animation: admin-pagination-slide-backward .24s ease-out both; }
        .admin-pagination-number, .admin-pagination-control { display: inline-grid; width: 24px; min-width: 24px; height: 24px; flex: 0 0 24px; place-items: center; border: 1px solid #302a3b; border-radius: 5px; color: #c3bdce; text-decoration: none; }
        .admin-pagination-number { scroll-snap-align: center; }
        .admin-pagination-number.is-current { border-color: #6543bb; background: #281e3d; color: #e1d7ff; }
        .admin-pagination-control svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 2; }
        .admin-pagination-control.is-disabled { color: #615b6a; }
        .admin-pagination a:hover, .admin-pagination a:focus-visible { border-color: #6543bb; background: #281e3d; color: #fff; outline: 0; }
        @keyframes admin-pagination-slide-forward {
            from { opacity: .6; transform: translateX(12px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes admin-pagination-slide-backward {
            from { opacity: .6; transform: translateX(-12px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @media (max-width: 760px) {
            .sidebar { width: 62px; align-items: center; padding: 14px 7px; }
            .brand { padding: 0; }
            .brand span, .nav-label, .nav-link .nav-text, .add-shortcut { display: none; }
            .nav-links { width: 100%; margin-top: 24px; }
            .nav-link { justify-content: center; padding: 0; }
            .nav-icon { width: 18px; height: 18px; flex-basis: 18px; }
            .main-area { margin-left: 62px; }
            .topbar { padding: 0 15px; }
            .page-content { padding: 20px 15px 28px; }
        }
        @media (max-width: 480px) {
            .topbar { min-height: 50px; gap: 8px; }
            .topbar-search { width: min(190px, 52vw); }
            .admin-profile-copy { display: none; }
            .notification-menu { position: fixed; top: 58px; right: 12px; width: min(330px, calc(100vw - 86px)); max-height: 45vh; display: flex; flex-direction: column; }
            .notification-list { min-height: 0; max-height: none; flex: 1 1 auto; overflow-y: auto; }
            .page-heading { align-items: center; flex-wrap: wrap; }
            .page-heading h1 { font-size: 17px; }
            .page-heading p { max-width: 210px; line-height: 1.5; }
            .page-heading > .primary-button, .dashboard-heading-actions > .primary-button { flex-shrink: 0; white-space: nowrap; }
        }
        @media (max-width: 600px) {
            .pagination-window { flex-wrap: wrap; justify-content: center; }
            .pagination-window > span { flex-basis: 100%; text-align: center; }
            .pagination-window .admin-pagination { width: 100%; justify-content: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('images/oversmokev-logo.png') }}" alt="">
                <span>OVERSMOKE VLOUNGE</span>
            </a>
            <p class="nav-label">Workspace</p>
            <nav class="nav-links" aria-label="Admin navigation">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><img class="nav-icon" src="{{ asset('images/admin-icons/dashboard.png') }}" alt=""><span class="nav-text">Dashboard</span></a>
                <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}"><img class="nav-icon" src="{{ asset('images/admin-icons/products.png') }}" alt=""><span class="nav-text">Products</span></a>
                <a class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}"><img class="nav-icon" src="{{ asset('images/admin-icons/inventory.png') }}" alt=""><span class="nav-text">Inventory</span></a>
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}" aria-label="Orders"><img class="nav-icon" src="{{ asset('images/admin-icons/orders.png') }}" alt=""><span class="nav-text">Orders</span><span class="admin-nav-count" data-admin-orders-unread-count aria-live="polite" hidden></span></a>
                <a class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}" href="{{ route('admin.refunds.index') }}" aria-label="Refund requests"><svg class="nav-icon nav-icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7v5h-5"/><path d="M4.9 9a7.5 7.5 0 0 1 12.4-2.8L20 9"/><path d="M4 17v-5h5"/><path d="M19.1 15a7.5 7.5 0 0 1-12.4 2.8L4 15"/></svg><span class="nav-text">Refund requests</span><span class="admin-nav-count" data-admin-refunds-unread-count aria-live="polite" hidden></span></a>
                <a class="nav-link {{ request()->routeIs('admin.support-chat.*') ? 'active' : '' }}" href="{{ route('admin.support-chat.index') }}" data-admin-chat-unread-url="{{ route('admin.support-chat.unread-count') }}" aria-label="Support chat"><svg class="nav-icon nav-icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H5l1.5-3A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8.5 11h7M8.5 14h4"/></svg><span class="nav-text">Support chat</span><span class="admin-chat-nav-count" data-admin-chat-unread-count aria-live="polite" hidden></span></a>
                @if (auth('admin')->user()->is_admin)
                    <a class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}"><svg class="nav-icon nav-icon-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21c0-4 2.8-6.5 7-6.5s7 2.5 7 6.5"/></svg><span class="nav-text">Staff</span></a>
                @endif
            </nav>
            <div class="sidebar-bottom">
                <a class="add-shortcut" href="{{ route('admin.products.create') }}">+ &nbsp; Add product</a>
            </div>
        </aside>

        <div class="main-area">
            <header class="topbar">
                @if (! request()->routeIs('admin.support-chat.*'))
                <form class="topbar-search" method="GET" action="{{ request()->routeIs('admin.refunds.*') ? route('admin.refunds.index') : (request()->routeIs('admin.orders.*') ? route('admin.orders.index') : route('admin.products.index')) }}" role="search">
                    <label class="sr-only" for="admin-search">Search {{ request()->routeIs('admin.refunds.*') ? 'refund requests' : (request()->routeIs('admin.orders.*') ? 'orders' : 'products') }}</label>
                    <input id="admin-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ request()->routeIs('admin.refunds.*') ? 'refund requests...' : (request()->routeIs('admin.orders.*') ? 'orders...' : 'products...') }}">
                    @if (request()->routeIs('admin.refunds.*'))
                        @if (request()->filled('date'))
                            <input type="hidden" name="date" value="{{ request('date') }}">
                        @endif
                        @if (request()->filled('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                    @endif
                    <span class="search-icon" aria-hidden="true">⌕</span>
                </form>
                @endif
                <div class="admin-notifications"
                     id="adminNotifications"
                     data-endpoint="{{ route('admin.orders.notifications') }}"
                     data-read-url-template="{{ route('admin.orders.notifications.read', ['notification' => '__NOTIFICATION_ID__']) }}"
                     data-read-all-url="{{ route('admin.orders.notifications.read-all') }}"
                     data-csrf-token="{{ csrf_token() }}">
                    <button class="notification-button" id="notificationButton" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notificationMenu">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                        <span class="notification-count" id="notificationCount" hidden></span>
                    </button>
                    <div class="notification-menu" id="notificationMenu" hidden>
                        <div class="notification-heading">
                            <span>Notifications</span>
                            <button class="notification-clear" id="markAllNotificationsRead" type="button">Mark all read</button>
                        </div>
                        <p class="notification-status" id="notificationStatus" role="status" aria-live="polite"></p>
                        <div class="notification-list" id="notificationList" aria-live="polite"></div>
                    </div>
                </div>
                <div class="admin-profile">
                    <button class="admin-profile-button" id="adminProfileButton" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="adminAccountMenu">
                        <span class="admin-profile-copy">
                            <strong>{{ auth('admin')->user()->name }}</strong>
                            <span>{{ auth('admin')->user()->is_admin ? 'Store owner' : 'Store staff' }}</span>
                        </span>
                        <span class="avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth('admin')->user()->name, 0, 1)) }}</span>
                    </button>
                    <div class="admin-account-menu" id="adminAccountMenu" hidden>
                        <form method="POST" action="{{ route('admin.logout') }}" data-admin-logout>
                            @csrf
                            <button type="submit">Sign out</button>
                        </form>
                    </div>
                </div>
                <div class="order-toast" id="orderToast" role="status" aria-live="polite" hidden></div>
            </header>

            <main class="page-content">
                @if (session('status'))
                    <div class="flash" data-admin-flash role="status" aria-live="polite">
                        <span>{{ session('status') }}</span>
                        <button class="flash-dismiss" type="button" data-dismiss-flash aria-label="Dismiss notification">×</button>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <dialog class="admin-logout-dialog" id="adminLogoutConfirmDialog" aria-labelledby="adminLogoutConfirmTitle" aria-describedby="adminLogoutConfirmMessage">
        <div class="admin-logout-dialog-icon" aria-hidden="true">?</div>
        <p class="admin-logout-eyebrow">Please confirm</p>
        <h2 id="adminLogoutConfirmTitle">Sign out of the admin portal?</h2>
        <p id="adminLogoutConfirmMessage">You will need to sign in again to manage the store.</p>
        <div class="admin-logout-actions">
            <button class="admin-logout-cancel" id="cancelAdminLogout" type="button" autofocus>Stay signed in</button>
            <button class="admin-logout-proceed" id="confirmAdminLogout" type="button">Sign out</button>
        </div>
    </dialog>
    <script>
        (() => {
            const flash = document.querySelector('[data-admin-flash]');
            if (flash) {
                const dismissFlash = () => {
                    flash.hidden = true;
                };
                const flashTimeout = window.setTimeout(dismissFlash, 5000);
                flash.querySelector('[data-dismiss-flash]').addEventListener('click', () => {
                    window.clearTimeout(flashTimeout);
                    dismissFlash();
                });
            }

            const logoutForm = document.querySelector('[data-admin-logout]');
            const logoutDialog = document.getElementById('adminLogoutConfirmDialog');
            if (logoutForm && logoutDialog) {
                let confirmedLogout = false;

                logoutForm.addEventListener('submit', event => {
                    if (confirmedLogout) {
                        confirmedLogout = false;
                        return;
                    }

                    event.preventDefault();
                    logoutDialog.showModal();
                });

                document.getElementById('cancelAdminLogout').addEventListener('click', () => logoutDialog.close());
                document.getElementById('confirmAdminLogout').addEventListener('click', () => {
                    confirmedLogout = true;
                    logoutDialog.close();
                    logoutForm.requestSubmit();
                });
                logoutDialog.addEventListener('click', event => {
                    if (event.target === logoutDialog) logoutDialog.close();
                });
            }

            const button = document.getElementById('adminProfileButton');
            const menu = document.getElementById('adminAccountMenu');
            if (!button || !menu) return;

            const setOpen = open => {
                menu.hidden = !open;
                button.setAttribute('aria-expanded', String(open));
            };

            button.addEventListener('click', () => setOpen(menu.hidden));
            document.addEventListener('click', event => {
                if (!event.target.closest('.admin-profile')) setOpen(false);
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !menu.hidden) {
                    setOpen(false);
                    button.focus();
                }
            });
        })();
    </script>
    <script src="{{ asset('js/admin-pagination.js') }}?v={{ filemtime(public_path('js/admin-pagination.js')) }}"></script>
    <script src="{{ asset('js/admin-notifications.js') }}?v={{ filemtime(public_path('js/admin-notifications.js')) }}"></script>
    <script src="{{ asset('js/admin-support-chat-notifications.js') }}?v={{ filemtime(public_path('js/admin-support-chat-notifications.js')) }}"></script>
    @stack('scripts')
</body>
</html>
