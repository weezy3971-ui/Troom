<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Trooms ERP</title>
    <meta name="description" content="Trooms Horticulture ERP — Farm management from field to customer">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           TROOMS ERP — Earthy Botanical Redesign
           Icon-rail sidebar · Warm cream palette ·
           Olive & terracotta accents
           ============================================ */

        :root {
            /* ---- Sidebar rail (dark) ---- */
            --rail-bg: #1a1f1c;
            --rail-bg-hover: #262c28;
            --rail-icon: #8a9590;
            --rail-icon-hover: #d4ddd8;
            --rail-icon-active: #c8a96e;
            --rail-width: 64px;
            --rail-tooltip-bg: #3a3a3a;

            /* ---- Main area (warm earthy) ---- */
            --bg-primary: #F5F1EB;
            --bg-secondary: #EDE8E0;
            --bg-card: #FFFFFF;
            --bg-card-hover: #FAF8F5;
            --bg-input: #FFFFFF;
            --border: #DDD6CA;
            --border-strong: #C5BBA9;
            --border-focus: #7A8B4A;

            /* ---- Text ---- */
            --text-primary: #2C3A2E;
            --text-secondary: #5A6B5E;
            --text-muted: #8E9A8F;

            /* ---- Accent palette ---- */
            --olive: #6B7F3A;
            --olive-soft: #8A9B5A;
            --olive-bg: rgba(107, 127, 58, 0.10);
            --terracotta: #C0734A;
            --terracotta-bg: rgba(192, 115, 74, 0.10);
            --gold: #B8963E;
            --gold-bg: rgba(184, 150, 62, 0.10);

            /* ---- Status ---- */
            --success: #5E8E42;
            --success-bg: rgba(94, 142, 66, 0.10);
            --warning: #C09A3E;
            --warning-bg: rgba(192, 154, 62, 0.10);
            --danger: #B85A4A;
            --danger-bg: rgba(184, 90, 74, 0.10);
            --info: #4A7A8E;
            --info-bg: rgba(74, 122, 142, 0.10);

            /* ---- Spacing / Radius ---- */
            --topbar-height: 56px;
            --radius: 10px;
            --radius-sm: 6px;
            --radius-lg: 14px;
            --shadow-sm: 0 1px 3px rgba(44, 58, 46, 0.06);
            --shadow: 0 2px 8px rgba(44, 58, 46, 0.08);
            --shadow-lg: 0 8px 24px rgba(44, 58, 46, 0.10);
            --transition: 0.18s cubic-bezier(0.4, 0, 0.2, 1);

            /* ---- Fonts ---- */
            --font-display: 'Fraunces', Georgia, serif;
            --font-body: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', ui-monospace, monospace;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        ::selection { background: rgba(107, 127, 58, 0.2); color: var(--text-primary); }

        /* ============================================
           SIDEBAR RAIL — 64px icon strip
           ============================================ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--rail-width);
            height: 100vh;
            background: var(--rail-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 100;
            padding: 0;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }

        /* ---- Brand mark ---- */
        .sidebar-brand {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px 0 12px;
            flex-shrink: 0;
        }

        .sidebar-brand .logo {
            width: 36px;
            height: 36px;
            background: var(--rail-icon-active);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--rail-bg);
            flex-shrink: 0;
            transition: transform var(--transition);
        }
        .sidebar-brand .logo:hover { transform: scale(1.08); }

        /* ---- Navigation sections ---- */
        .sidebar-section {
            width: 100%;
            padding: 6px 0;
        }

        .sidebar-section-title {
            display: none; /* hidden in icon rail mode */
        }

        .sidebar-divider {
            width: 28px;
            height: 1px;
            background: rgba(255,255,255,0.08);
            margin: 4px auto;
        }

        .sidebar-nav {
            list-style: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        .sidebar-nav li {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .sidebar-nav a {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            color: var(--rail-icon);
            text-decoration: none;
            transition: all var(--transition);
        }

        .sidebar-nav a .icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* ---- Tooltip label (hover-reveal) ---- */
        .sidebar-nav a .nav-label {
            position: fixed;
            top: 0;
            left: 0;
            background: var(--rail-bg);
            color: #e8e4df;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 500;
            font-family: var(--font-body);
            line-height: 1;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transform: translateX(-4px);
            transition: opacity 120ms ease, transform 120ms ease;
            z-index: 9999;
            box-shadow: 0 4px 14px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.05);
            letter-spacing: 0.15px;
            -webkit-font-smoothing: antialiased;
        }

        .sidebar-nav a .nav-label::before {
            content: '';
            position: absolute;
            left: -3px;
            top: 50%;
            transform: translateY(-50%) rotate(45deg);
            width: 6px;
            height: 6px;
            background: var(--rail-bg);
        }

        .sidebar-nav a:hover .nav-label,
        .sidebar-nav a:focus .nav-label {
            opacity: 1;
            transform: translateX(0);
        }

        /* ---- Hover & active states ---- */
        .sidebar-nav a:hover {
            background: var(--rail-bg-hover);
            color: var(--rail-icon-hover);
        }

        .sidebar-nav a.active {
            background: rgba(200, 169, 110, 0.12);
            color: var(--rail-icon-active);
        }

        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            border-radius: 0 3px 3px 0;
            background: var(--rail-icon-active);
        }

        /* ---- Sidebar footer (user) ---- */
        .sidebar-footer {
            margin-top: auto;
            padding: 12px 0 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .sidebar-footer .sidebar-nav a { width: 40px; height: 40px; }

        /* ============================================
           TOPBAR — warm, minimal
           ============================================ */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--rail-width);
            right: 0;
            height: var(--topbar-height);
            background: rgba(245, 241, 235, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            z-index: 99;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-title {
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.3px;
        }

        .topbar-actions { display: flex; align-items: center; gap: 12px; }

        .role-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            background: var(--olive-bg);
            color: var(--olive);
            border: 1px solid rgba(107, 127, 58, 0.18);
        }

        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            font-size: 13px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            transition: background var(--transition);
        }

        .user-menu:hover { background: var(--bg-card-hover); }

        .user-menu .user-chevron {
            display: inline-flex;
            transition: transform var(--transition);
            color: var(--text-muted);
        }

        .user-menu .user-chevron svg {
            width: 12px;
            height: 12px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .user-menu.open .user-chevron { transform: rotate(180deg); }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--olive);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
            color: #fff;
            flex-shrink: 0;
        }

        /* ---- User Dropdown ---- */
        .user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 280px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity 160ms ease, transform 160ms ease, visibility 0ms 160ms;
            z-index: 200;
            overflow: hidden;
        }

        .user-menu.open .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            transition: opacity 160ms ease, transform 160ms ease, visibility 0ms 0ms;
        }

        .user-dropdown-header {
            padding: 16px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
        }

        .user-dropdown-header .udd-name {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.2px;
        }

        .user-dropdown-header .udd-email {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .user-dropdown-header .udd-role {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--olive-bg);
            color: var(--olive);
            border: 1px solid rgba(107, 127, 58, 0.18);
        }

        .user-dropdown-section {
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }

        .user-dropdown-section:last-child { border-bottom: none; }

        .user-dropdown-section .udd-section-title {
            padding: 4px 16px 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            font-size: 13px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all var(--transition);
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            font-family: inherit;
        }

        .user-dropdown-item:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .user-dropdown-item .udd-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            opacity: 0.6;
        }

        .user-dropdown-item .udd-icon svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .user-dropdown-item:hover .udd-icon { opacity: 1; }

        .user-dropdown-item.danger { color: var(--danger); }
        .user-dropdown-item.danger:hover { background: var(--danger-bg); }

        .icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            cursor: pointer;
            transition: all var(--transition);
        }
        .icon-btn:hover { background: var(--bg-card); color: var(--text-primary); border-color: var(--border-strong); }

        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            margin-left: var(--rail-width);
            margin-top: var(--topbar-height);
            padding: 24px 28px;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* ============================================
           KPI BAR — compact horizontal chips
           ============================================ */
        .kpi-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }

        .kpi-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            transition: border-color var(--transition), box-shadow var(--transition), transform var(--transition);
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        .kpi-chip:hover {
            border-color: var(--border-strong);
            box-shadow: var(--shadow);
            transform: translateY(-1px);
        }

        .kpi-chip-label {
            font-size: 11.5px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .kpi-chip-value {
            font-family: var(--font-mono);
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            font-feature-settings: 'tnum' 1;
        }

        .kpi-chip-value.olive { color: var(--olive); }
        .kpi-chip-value.success { color: var(--success); }
        .kpi-chip-value.warning { color: var(--warning); }
        .kpi-chip-value.terracotta { color: var(--terracotta); }

        .kpi-chip-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .kpi-chip-dot.olive { background: var(--olive); }
        .kpi-chip-dot.success { background: var(--success); }
        .kpi-chip-dot.warning { background: var(--warning); }
        .kpi-chip-dot.terracotta { background: var(--terracotta); }

        /* ============================================
           CARDS
           ============================================ */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow-sm);
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        .card:hover { border-color: var(--border-strong); box-shadow: var(--shadow); }

        .card-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(221, 214, 202, 0.6);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .card-title {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.2px;
        }

        /* ============================================
           LEGACY STATS GRID (kept for non-dashboard pages)
           ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: border-color var(--transition);
        }
        .stat-card:hover { border-color: var(--border-strong); }

        .stat-label {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-family: var(--font-mono);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -1.5px;
            color: var(--text-primary);
            font-feature-settings: 'tnum' 1;
        }

        .stat-value.accent { color: var(--olive); }
        .stat-value.success { color: var(--success); }
        .stat-value.warning { color: var(--warning); }
        .stat-value.harvest { color: var(--terracotta); }

        /* ============================================
           TABLES
           ============================================ */
        .table-wrap {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            background: var(--bg-secondary);
            padding: 10px 16px;
            text-align: left;
            font-size: 10.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody td {
            padding: 12px 16px;
            font-size: 13px;
            border-bottom: 1px solid rgba(221, 214, 202, 0.5);
            color: var(--text-secondary);
        }

        tbody tr { transition: background var(--transition); }
        tbody tr:hover { background: var(--bg-card-hover); }
        tbody tr:last-child td { border-bottom: none; }

        td .num, .mono { font-family: var(--font-mono); font-feature-settings: 'tnum' 1; }

        /* ============================================
           BUTTONS
           ============================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all var(--transition);
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary { background: var(--olive); color: #fff; }
        .btn-primary:hover { background: var(--olive-soft); }

        .btn-secondary { background: var(--bg-card); color: var(--text-primary); border-color: var(--border-strong); }
        .btn-secondary:hover { background: var(--bg-card-hover); border-color: var(--olive); }

        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { opacity: 0.9; }

        .btn-warning { background: var(--warning); color: #2a2008; }
        .btn-warning:hover { opacity: 0.9; }

        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { opacity: 0.9; }

        .btn-sm { padding: 5px 10px; font-size: 12px; }

        .btn-ghost { background: transparent; color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-ghost:hover { background: var(--bg-card); color: var(--text-primary); border-color: var(--border-strong); }

        /* ============================================
           FORMS
           ============================================ */
        .form-group { margin-bottom: 20px; }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 13px;
            font-family: inherit;
            transition: all var(--transition);
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px var(--olive-bg);
        }

        .form-textarea { min-height: 100px; resize: vertical; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }

        .form-error { font-size: 12px; color: var(--danger); margin-top: 4px; }

        /* ============================================
           BADGES
           ============================================ */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge-planned { background: var(--info-bg); color: var(--info); }
        .badge-active { background: var(--success-bg); color: var(--success); }
        .badge-completed { background: rgba(142, 154, 143, 0.12); color: var(--text-muted); }
        .badge-cancelled { background: var(--danger-bg); color: var(--danger); }
        .badge-operational { background: var(--success-bg); color: var(--success); }
        .badge-maintenance { background: var(--warning-bg); color: var(--warning); }
        .badge-down { background: var(--danger-bg); color: var(--danger); }
        .badge-pump { background: var(--info-bg); color: var(--info); }
        .badge-vehicle { background: rgba(139, 118, 180, 0.12); color: #7a6aad; }
        .badge-equipment { background: var(--terracotta-bg); color: var(--terracotta); }
        .badge-sown { background: var(--info-bg); color: var(--info); }
        .badge-growing { background: var(--warning-bg); color: var(--warning); }
        .badge-ready { background: var(--success-bg); color: var(--success); }
        .badge-transplanted { background: rgba(142, 154, 143, 0.12); color: var(--text-muted); }
        .badge-neutral { background: rgba(142, 154, 143, 0.12); color: var(--text-secondary); }

        /* ============================================
           ALERTS
           ============================================ */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid rgba(94, 142, 66, 0.18); }
        .alert-error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(184, 90, 74, 0.18); }
        .alert-warning { background: var(--warning-bg); color: var(--warning); border: 1px solid rgba(192, 154, 62, 0.18); }
        .alert-info { background: var(--info-bg); color: var(--info); border: 1px solid rgba(74, 122, 142, 0.18); }

        /* ============================================
           PAGE HEADER
           ============================================ */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-title {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--text-primary);
        }

        .page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 2px; }

        /* ============================================
           SEARCH / FILTER BAR
           ============================================ */
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .search-bar .search-input-wrap {
            position: relative;
            flex: 1;
            min-width: 220px;
            max-width: 380px;
        }

        .search-bar .search-input-wrap .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .search-bar .search-input-wrap .search-icon svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .search-bar .search-input {
            width: 100%;
            padding: 8px 12px 8px 36px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 13px;
            font-family: inherit;
            transition: all var(--transition);
        }

        .search-bar .search-input:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px var(--olive-bg);
        }

        .search-bar .search-input::placeholder {
            color: var(--text-muted);
            font-size: 12.5px;
        }

        .search-bar .filter-select {
            padding: 8px 28px 8px 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            font-size: 12.5px;
            font-family: inherit;
            cursor: pointer;
            transition: all var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' fill='none' stroke='%238E9A8F' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .search-bar .filter-select:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px var(--olive-bg);
        }

        .search-bar .search-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-left: auto;
            white-space: nowrap;
        }

        .search-bar .search-meta strong {
            color: var(--text-secondary);
            font-weight: 600;
        }

        .search-bar .btn-clear {
            padding: 6px 10px;
            font-size: 12px;
            color: var(--text-muted);
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-family: inherit;
            transition: all var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .search-bar .btn-clear:hover {
            color: var(--text-primary);
            border-color: var(--border-strong);
            background: var(--bg-card);
        }

        /* ============================================
           BREADCRUMBS
           ============================================ */
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .breadcrumbs a { color: var(--text-secondary); text-decoration: none; transition: color var(--transition); }
        .breadcrumbs a:hover { color: var(--olive); }

        /* ============================================
           EMPTY STATE
           ============================================ */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state .icon { font-size: 46px; margin-bottom: 16px; opacity: 0.6; }
        .empty-state h3 { font-family: var(--font-display); font-size: 16px; color: var(--text-secondary); margin-bottom: 8px; }
        .empty-state p { font-size: 13px; margin-bottom: 20px; }

        /* ============================================
           DETAIL / SHOW PAGE
           ============================================ */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .detail-item {
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }

        .detail-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .detail-value { font-size: 15px; font-weight: 600; color: var(--text-primary); }

        /* ============================================
           ACTIONS
           ============================================ */
        .actions { display: flex; gap: 8px; align-items: center; }
        .actions form { display: inline; }

        /* ============================================
           ANIMATIONS
           ============================================ */
        @keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .fade-in { animation: fadeIn 0.35s ease; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .topbar { left: 0; padding: 0 16px; }
            .main-content { margin-left: 0; padding: 16px; }
            .kpi-bar { gap: 6px; }
            .kpi-chip { padding: 6px 10px; }
        }
    </style>
</head>
<body>
    {{-- ---- Sidebar Rail ---- --}}
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><x-icon name="crops" size="20" /></div>
        </div>

        <div class="sidebar-divider"></div>

        {{-- Overview --}}
        <div class="sidebar-section">
            <ul class="sidebar-nav">
                <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="icon"><x-icon name="dashboard" /></span><span class="nav-label">Dashboard</span></a></li>
                <li><a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active' : '' }}"><span class="icon"><x-icon name="modules" /></span><span class="nav-label">Executive Dashboard</span></a></li>
            </ul>
        </div>

        <div class="sidebar-divider"></div>

        {{-- Master Data --}}
        <div class="sidebar-section">
            <ul class="sidebar-nav">
                <li><a href="{{ route('farms.index') }}" class="{{ request()->routeIs('farms.*') ? 'active' : '' }}"><span class="icon"><x-icon name="farm" /></span><span class="nav-label">Farms</span></a></li>
                <li><a href="{{ route('blocks.index') }}" class="{{ request()->routeIs('blocks.*') ? 'active' : '' }}"><span class="icon"><x-icon name="blocks" /></span><span class="nav-label">Blocks</span></a></li>
                <li><a href="{{ route('crops.index') }}" class="{{ request()->routeIs('crops.*') ? 'active' : '' }}"><span class="icon"><x-icon name="crops" /></span><span class="nav-label">Crops</span></a></li>
                <li><a href="{{ route('assets.index') }}" class="{{ request()->routeIs('assets.*') ? 'active' : '' }}"><span class="icon"><x-icon name="assets" /></span><span class="nav-label">Assets</span></a></li>
            </ul>
        </div>

        <div class="sidebar-divider"></div>

        {{-- Planning --}}
        <div class="sidebar-section">
            <ul class="sidebar-nav">
                <li><a href="{{ route('crop-cycles.index') }}" class="{{ request()->routeIs('crop-cycles.*') ? 'active' : '' }}"><span class="icon"><x-icon name="cycles" /></span><span class="nav-label">Crop Cycles</span></a></li>
            </ul>
        </div>

        <div class="sidebar-divider"></div>

        {{-- Field Operations --}}
        <div class="sidebar-section">
            <ul class="sidebar-nav">
                <li><a href="{{ route('nursery-batches.index') }}" class="{{ request()->routeIs('nursery-batches.*') ? 'active' : '' }}"><span class="icon"><x-icon name="nursery" /></span><span class="nav-label">Nursery</span></a></li>
                <li><a href="{{ route('daily-activities.index') }}" class="{{ request()->routeIs('daily-activities.*') ? 'active' : '' }}"><span class="icon"><x-icon name="operations" /></span><span class="nav-label">Daily Operations</span></a></li>
                <li><a href="{{ route('irrigation-logs.index') }}" class="{{ request()->routeIs('irrigation-logs.*') ? 'active' : '' }}"><span class="icon"><x-icon name="irrigation" /></span><span class="nav-label">Irrigation</span></a></li>
                <li><a href="{{ route('fertigation-logs.index') }}" class="{{ request()->routeIs('fertigation-logs.*') ? 'active' : '' }}"><span class="icon"><x-icon name="fertigation" /></span><span class="nav-label">Fertigation</span></a></li>
                <li><a href="{{ route('spray-logs.index') }}" class="{{ request()->routeIs('spray-logs.*') ? 'active' : '' }}"><span class="icon"><x-icon name="pest" /></span><span class="nav-label">Pest &amp; Disease</span></a></li>
                <li><a href="{{ route('labour-attendances.index') }}" class="{{ request()->routeIs('labour-attendances.*') ? 'active' : '' }}"><span class="icon"><x-icon name="labour" /></span><span class="nav-label">Labour</span></a></li>
            </ul>
        </div>

        <div class="sidebar-divider"></div>

        {{-- Post-Harvest --}}
        <div class="sidebar-section">
            <ul class="sidebar-nav">
                <li><a href="{{ route('inventory-items.index') }}" class="{{ request()->routeIs('inventory-items.*') ? 'active' : '' }}"><span class="icon"><x-icon name="inventory" /></span><span class="nav-label">Inventory</span></a></li>
                <li><a href="{{ route('harvest-batches.index') }}" class="{{ request()->routeIs('harvest-batches.*') ? 'active' : '' }}"><span class="icon"><x-icon name="harvest" /></span><span class="nav-label">Harvest</span></a></li>
                <li><a href="{{ route('packhouse-lots.index') }}" class="{{ request()->routeIs('packhouse-lots.*') ? 'active' : '' }}"><span class="icon"><x-icon name="packhouse" /></span><span class="nav-label">Packhouse</span></a></li>
                <li><a href="{{ route('quality-checks.index') }}" class="{{ request()->routeIs('quality-checks.*') ? 'active' : '' }}"><span class="icon"><x-icon name="quality" /></span><span class="nav-label">Quality</span></a></li>
            </ul>
        </div>

        <div class="sidebar-divider"></div>

        {{-- Commercial --}}
        <div class="sidebar-section">
            <ul class="sidebar-nav">
                <li><a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') || request()->routeIs('sales-orders.*') ? 'active' : '' }}"><span class="icon"><x-icon name="sales" /></span><span class="nav-label">Sales</span></a></li>
                <li><a href="{{ route('dispatches.index') }}" class="{{ request()->routeIs('dispatches.*') ? 'active' : '' }}"><span class="icon"><x-icon name="logistics" /></span><span class="nav-label">Logistics</span></a></li>
                <li><a href="{{ route('finance.index') }}" class="{{ request()->routeIs('finance.*') ? 'active' : '' }}"><span class="icon"><x-icon name="finance" /></span><span class="nav-label">Finance</span></a></li>
            </ul>
        </div>

        {{-- Footer icons --}}
        <div class="sidebar-footer">
            <div class="sidebar-divider"></div>
            <ul class="sidebar-nav">
                <li><a href="#"><span class="icon"><x-icon name="notifications" /></span><span class="nav-label">Notifications</span></a></li>
                <li><a href="#"><span class="icon"><x-icon name="settings" /></span><span class="nav-label">Settings</span></a></li>
                @auth
                <li>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
                        <span class="icon"><x-icon name="logout" /></span>
                        <span class="nav-label">Log Out</span>
                    </a>
                    <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
                </li>
                @endauth
            </ul>
        </div>
    </aside>

    {{-- ---- Topbar ---- --}}
    <header class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">@yield('title', 'Dashboard')</div>
        </div>
        <div class="topbar-actions">
            @auth
                @php
                    $user = auth()->user();
                    $roleLabel = $user->role === 'owner' ? 'Horticulture Manager' : ucwords(str_replace('_', ' ', $user->role));
                @endphp
                <div class="user-menu" id="user-menu-toggle" onclick="this.classList.toggle('open')">
                    <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    <span>{{ $user->name }}</span>
                    <span class="user-chevron">
                        <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>

                    {{-- Dropdown --}}
                    <div class="user-dropdown" onclick="event.stopPropagation()">
                        {{-- Identity card --}}
                        <div class="user-dropdown-header">
                            <div class="udd-name">{{ $user->name }}</div>
                            <div class="udd-email">{{ $user->email }}</div>
                            <span class="udd-role">{{ $roleLabel }}</span>
                        </div>

                        {{-- Module access --}}
                        <div class="user-dropdown-section">
                            <div class="udd-section-title">Module Access</div>
                            <a href="{{ route('dashboard') }}" class="user-dropdown-item">
                                <span class="udd-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
                                Dashboard
                            </a>
                            <a href="{{ route('farms.index') }}" class="user-dropdown-item">
                                <span class="udd-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                                Master Data
                            </a>
                            <a href="{{ route('crop-cycles.index') }}" class="user-dropdown-item">
                                <span class="udd-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                                Crop Planning
                            </a>
                            <a href="{{ route('daily-activities.index') }}" class="user-dropdown-item">
                                <span class="udd-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                                Field Operations
                            </a>
                        </div>

                        {{-- Account actions --}}
                        <div class="user-dropdown-section">
                            <a href="#" class="user-dropdown-item">
                                <span class="udd-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                                Settings
                            </a>
                            <button class="user-dropdown-item danger" onclick="event.preventDefault(); document.getElementById('topbar-logout-form').submit();">
                                <span class="udd-icon"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                                Log Out
                            </button>
                            <form id="topbar-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </header>

    {{-- ---- Main Content ---- --}}
    <main class="main-content fade-in">
        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">✕ {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @yield('content')
    </main>
    {{-- ---- Sidebar Tooltip Positioning ---- --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar tooltip positioning
        const links = document.querySelectorAll('.sidebar-nav a');
        links.forEach(function(link) {
            link.addEventListener('mouseenter', function() {
                const label = this.querySelector('.nav-label');
                if (!label) return;
                const rect = this.getBoundingClientRect();
                label.style.top = (rect.top + rect.height / 2 - label.offsetHeight / 2) + 'px';
                label.style.left = (rect.right + 10) + 'px';
            });
        });

        // User dropdown — close on outside click or Escape
        var userMenu = document.getElementById('user-menu-toggle');
        if (userMenu) {
            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target)) {
                    userMenu.classList.remove('open');
                }
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    userMenu.classList.remove('open');
                }
            });
        }
    });
    </script>
</body>
</html>
