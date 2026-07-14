<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Trooms ERP</title>
    <meta name="description" content="Trooms Horticulture ERP — Farm management from field to customer">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#2F6B3B">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           TROOMS ERP — "Fresh Greenhouse" theme
           Icon-rail sidebar · Cool-sage background ·
           Leaf-green primary · clay / wheat / water-teal accents
           ============================================ */

        :root {
            /* ============================================================
               PALETTE — "Fresh Greenhouse"
               A crisp botanical light theme: cool-sage background (fresh,
               low eye-strain on data-heavy screens), white cards, a deep
               forest sidebar, leaf-green primary, and earthy clay / wheat /
               water-teal accents. Every text colour meets WCAG AA (most AAA).
               ============================================================ */

            /* ---- Sidebar rail (deep forest — grounding anchor) ---- */
            --rail-bg: #17271C;
            --rail-bg-hover: #223528;
            --rail-icon: #93A89A;
            --rail-icon-hover: #DAE7DE;
            --rail-icon-active: #8FCB84;   /* fresh sprout green */
            --rail-width: 240px;
            --rail-tooltip-bg: #2C3E33;

            /* ---- Main area (soft neutral sage — light & easy on the eyes) ---- */
            --bg-primary: #F3F5EF;         /* app background — cleaner, less saturated */
            --bg-secondary: #E8EDE2;       /* insets, hovers, table stripes */
            --bg-card: #FCFDFB;            /* cards — soft off-white, avoids sunlight glare "halo" */
            --bg-card-hover: #F7F9F4;
            --bg-input: #FFFFFF;
            --border: #DCE2D3;
            --border-strong: #C4CEB7;
            --border-focus: #2F6B3B;

            /* ---- Text (all AA+ on white and on the sage background) ---- */
            --text-primary: #17231B;   /* ~14:1 on white */
            --text-secondary: #43554A; /* ~7:1  on white */
            --text-muted: #5C6A61;     /* ~5:1  on white */

            /* ---- Brand / accents ---- */
            --olive: #2F6B3B;              /* leaf-green primary; white text ~5.2:1 */
            --olive-soft: #3E844B;         /* hover */
            --olive-bg: rgba(47, 107, 59, 0.10);
            --terracotta: #B0562F;         /* clay accent; text on white ~4.9:1 */
            --terracotta-bg: rgba(176, 86, 47, 0.10);
            --gold: #97741A;               /* harvest wheat; text ~5.2:1 */
            --gold-bg: rgba(151, 116, 26, 0.12);

            /* ---- Status (accent = border/badge; -text = AA-safe text on tints) ---- */
            --success: #3C8048;
            --success-bg: rgba(60, 128, 72, 0.12);
            --success-text: #1F5127;   /* ~8:1 on white — field-readable */
            --warning: #8A6210;
            --warning-bg: rgba(138, 98, 16, 0.12);
            --warning-text: #5C4008;   /* ~7.5:1 on white — replaces low-contrast gold */
            --danger: #B23A2C;
            --danger-bg: rgba(178, 58, 44, 0.12);
            --danger-text: #7C2418;    /* ~7.8:1 on white — field-readable */
            --info: #2F6E82;               /* water-teal — fits irrigation context */
            --info-bg: rgba(47, 110, 130, 0.12);
            --info-text: #1E4A58;      /* ~7.6:1 on white — field-readable */

            /* ---- Spacing / Radius ---- */
            --topbar-height: 56px;
            --radius: 10px;
            --radius-sm: 6px;
            --radius-lg: 14px;
            --shadow-sm: 0 1px 3px rgba(23, 39, 28, 0.06);
            --shadow: 0 2px 8px rgba(23, 39, 28, 0.08);
            --shadow-lg: 0 8px 24px rgba(23, 39, 28, 0.12);
            --transition: 0.18s cubic-bezier(0.4, 0, 0.2, 1);

            /* ---- Fonts ---- */
            --font-display: 'Fraunces', Georgia, serif;
            --font-body: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', ui-monospace, monospace;
            --font-sans: var(--font-body);

            /* ---- Aliases ----
               Several views were written against an alternate variable naming
               scheme that was never defined, so those colours rendered broken
               (e.g. links using var(--accent-hover) had no colour). Map them
               onto the hardened palette so every reference resolves to an
               AA-contrast colour. */
            --accent: var(--olive);
            --accent-hover: var(--olive);
            --accent-strong: var(--gold);
            --bg: var(--bg-primary);
            --card: var(--bg-card);
            --input: var(--bg-input);
            --muted: var(--text-muted);
            --text: var(--text-primary);
            --color-white: #FFFFFF;
            --color-black: #1F2A22;
            --glow: var(--shadow);
            --stroke-color: var(--border);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            /* Slightly larger base type improves legibility for low-vision users. */
            font-size: 16px;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        /* Lift the smallest uppercase micro-labels to a legible minimum. */
        .stat-label, .detail-label, .kpi-chip, .table-wrap th { font-size: 12px !important; }

        ::selection { background: rgba(47, 107, 59, 0.20); color: var(--text-primary); }

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
            align-items: stretch;
            z-index: 100;
            padding: 0 12px;
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
            justify-content: flex-start;
            gap: 10px;
            padding: 18px 8px 12px;
            flex-shrink: 0;
        }

        .sidebar-brand .brand-name {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.2px;
            color: #EAF3EC;
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
            width: auto;
            height: 1px;
            background: rgba(255,255,255,0.08);
            margin: 6px 8px;
        }

        .sidebar-nav {
            list-style: none;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 2px;
        }

        .sidebar-nav li {
            width: 100%;
            display: flex;
        }

        .sidebar-nav a {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            width: 100%;
            height: 40px;
            padding: 0 12px;
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

        /* ---- Inline nav label ---- */
        .sidebar-nav a .nav-label {
            font-size: 13.5px;
            font-weight: 500;
            font-family: var(--font-body);
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: 0.15px;
            -webkit-font-smoothing: antialiased;
        }

        /* ---- Hover & active states ---- */
        .sidebar-nav a:hover {
            background: var(--rail-bg-hover);
            color: var(--rail-icon-hover);
        }

        .sidebar-nav a.active {
            background: rgba(143, 203, 132, 0.16);
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

        /* ---- Collapsible nav groups (accordion) ---- */
        .nav-group { width: 100%; }
        .nav-group + .nav-group { margin-top: 2px; }
        .nav-group > summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            height: 40px;
            padding: 0 12px;
            border-radius: 10px;
            color: var(--rail-icon);
            user-select: none;
            transition: background var(--transition), color var(--transition);
        }
        .nav-group > summary::-webkit-details-marker { display: none; }
        .nav-group > summary::marker { content: ''; }
        .nav-group > summary .icon {
            display: flex; align-items: center; justify-content: center;
            width: 20px; height: 20px; flex-shrink: 0;
        }
        .nav-group > summary .nav-group-title { flex: 1; min-width: 0; }
        .nav-group > summary:hover { background: var(--rail-bg-hover); color: var(--rail-icon-hover); }
        .nav-group[open] > summary { color: var(--rail-icon-hover); }
        .nav-group > summary .nav-chevron {
            margin-left: auto; display: flex; align-items: center;
            opacity: 0.7; transition: transform var(--transition);
        }
        .nav-group[open] > summary .nav-chevron { transform: rotate(90deg); }
        .nav-group-items {
            margin: 2px 0 4px 22px;
            padding-left: 8px;
            border-left: 1px solid rgba(255,255,255,0.08);
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
            background: rgba(243, 245, 239, 0.85);
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
            border: 1px solid rgba(47, 107, 59, 0.18);
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
            border: 1px solid rgba(47, 107, 59, 0.18);
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

        /* ---- Notifications bell ---- */
        .notif-wrap { position: relative; }
        .notif-btn { position: relative; }
        .notif-btn.active { background: var(--olive-bg); color: var(--olive); border-color: rgba(47, 107, 59, 0.25); }
        .notif-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 8px;
            background: var(--danger);
            color: #fff;
            font-size: 9.5px;
            font-weight: 700;
            line-height: 16px;
            text-align: center;
            box-shadow: 0 0 0 2px var(--bg-primary);
            font-family: var(--font-mono);
        }
        /* ---- Notification dropdown panel ---- */
        .notif-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 340px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            z-index: 9999;
            overflow: hidden;
        }
        .notif-wrap.open .notif-dropdown { display: flex; flex-direction: column; }
        .notif-dropdown-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .notif-dropdown-count {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1px 7px;
        }
        .notif-dropdown-body {
            max-height: 340px;
            overflow-y: auto;
        }
        .notif-item {
            padding: 11px 16px;
            border-bottom: 1px solid var(--border);
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-item-meta {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 3px;
        }
        .notif-item-type {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .notif-item--danger  .notif-item-type { color: var(--danger-text, #b91c1c); }
        .notif-item--warning .notif-item-type { color: var(--warning-text, #92400e); }
        .notif-item--info    .notif-item-type { color: var(--info-text, #1d4ed8); }
        .notif-item-module {
            font-size: 10px;
            color: var(--text-muted);
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 1px 6px;
        }
        .notif-item-msg {
            font-size: 12.5px;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.45;
        }
        .notif-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 28px 16px;
            color: var(--text-muted);
            font-size: 13px;
        }
        .notif-dropdown-footer {
            display: block;
            text-align: center;
            padding: 10px 16px;
            font-size: 12px;
            color: var(--olive);
            text-decoration: none;
            border-top: 1px solid var(--border);
            background: var(--bg-primary);
        }
        .notif-dropdown-footer:hover { text-decoration: underline; }

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
            border: 1px solid rgba(214, 222, 204, 0.7);
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
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .stat-card {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 1px 2px rgba(23, 39, 28, 0.04), 0 4px 12px rgba(23, 39, 28, 0.05);
            transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(23, 39, 28, 0.06), 0 10px 24px rgba(23, 39, 28, 0.08);
            border-color: var(--border-strong);
        }

        /* Icon-tile variant (soft tinted square + label/value beside it). */
        .stat-card.has-icon { display: flex; align-items: center; gap: 12px; }
        .stat-icon {
            flex-shrink: 0;
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: var(--olive-bg); color: var(--olive);
        }
        .stat-icon svg { width: 18px; height: 18px; }
        .stat-icon.success { background: var(--success-bg); color: var(--success); }
        .stat-icon.warning { background: var(--warning-bg); color: var(--warning); }
        .stat-icon.danger  { background: var(--danger-bg);  color: var(--danger); }
        .stat-icon.info    { background: var(--info-bg);    color: var(--info); }
        .stat-icon.harvest { background: var(--terracotta-bg); color: var(--terracotta); }
        .stat-icon.accent  { background: var(--olive-bg);   color: var(--olive); }
        .stat-icon.muted   { background: var(--bg-secondary); color: var(--text-muted); }
        .stat-body { min-width: 0; flex: 1; overflow: hidden; }

        .stat-label {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-value {
            font-family: var(--font-display);
            font-size: clamp(16px, 1.5vw, 22px);
            font-weight: 700;
            letter-spacing: -0.3px;
            color: var(--text-primary);
            line-height: 1.15;
            font-feature-settings: 'tnum' 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            border-bottom: 1px solid rgba(214, 222, 204, 0.6);
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

        .badge-planned { background: var(--info-bg); color: var(--info-text); }
        .badge-active { background: var(--success-bg); color: var(--success-text); }
        .badge-completed { background: rgba(92, 106, 97, 0.12); color: var(--text-secondary); }
        .badge-cancelled { background: var(--danger-bg); color: var(--danger-text); }
        .badge-operational { background: var(--success-bg); color: var(--success-text); }
        .badge-maintenance { background: var(--warning-bg); color: var(--warning-text); }
        .badge-down { background: var(--danger-bg); color: var(--danger-text); }
        .badge-pump { background: var(--info-bg); color: var(--info-text); }
        .badge-vehicle { background: rgba(90, 78, 128, 0.12); color: #574a86; }
        .badge-equipment { background: var(--terracotta-bg); color: var(--terracotta); }
        .badge-sown { background: var(--info-bg); color: var(--info-text); }
        .badge-growing { background: var(--warning-bg); color: var(--warning-text); }
        .badge-ready { background: var(--success-bg); color: var(--success-text); }
        .badge-transplanted { background: rgba(92, 106, 97, 0.12); color: var(--text-secondary); }
        .badge-neutral { background: rgba(92, 106, 97, 0.12); color: var(--text-secondary); }

        /* ============================================
           ALERTS
           ============================================ */
        .alert {
            padding: 13px 16px;
            border-radius: var(--radius-sm);
            font-size: 14.5px;
            line-height: 1.5;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            /* Body text stays dark & high-contrast; severity is shown by the
               tinted background and the coloured left border, not by text colour. */
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-left: 4px solid var(--text-muted);
            animation: slideIn 0.3s ease;
        }
        /* Coloured label/heading inside an alert keeps the severity hue. */
        .alert strong { letter-spacing: 0.4px; }

        .alert-success { background: var(--success-bg); border-left-color: var(--success); }
        .alert-success strong { color: var(--success-text); }
        .alert-error { background: var(--danger-bg); border-left-color: var(--danger); }
        .alert-error strong { color: var(--danger-text); }
        .alert-warning { background: var(--warning-bg); border-left-color: var(--warning); }
        .alert-warning strong { color: var(--warning-text); }
        .alert-info { background: var(--info-bg); border-left-color: var(--info); }
        .alert-info strong { color: var(--info-text); }

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

        /* Back / forward navigation arrows embedded where breadcrumbs used to be */
        .crumb-nav { display: flex; align-items: center; gap: 6px; margin-bottom: 16px; }
        .crumb-nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--olive-bg);
            color: var(--olive);
            cursor: pointer;
            transition: background var(--transition), color var(--transition), border-color var(--transition);
        }
        .crumb-nav-btn:hover { background: var(--olive); color: #fff; border-color: var(--olive); }
        .crumb-nav-btn .icon { display: inline-flex; }
        .crumb-nav-btn.back .icon { transform: rotate(180deg); }

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

        /* ============================================
           CONFIRM MODAL
           ============================================ */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(26, 31, 28, 0.55);
            backdrop-filter: blur(2px);
            animation: fadeIn 0.15s ease;
        }
        .confirm-overlay.open { display: flex; }
        .confirm-dialog {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
            padding: 24px;
            transform: translateY(4px);
            animation: confirmPop 0.18s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes confirmPop { from { opacity: 0; transform: translateY(12px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .confirm-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
            background: var(--danger-bg);
            color: var(--danger);
        }
        .confirm-title {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .confirm-message {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 22px;
            line-height: 1.55;
        }
        .confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* ============================================
           TABS (block hub, crop-cycle timeline toggle, …)
           ============================================ */
        .hub-tabs { display: flex; flex-wrap: wrap; gap: 4px; border-bottom: 2px solid var(--border); margin-bottom: 0; }
        .hub-tab {
            appearance: none; background: none; border: none; cursor: pointer;
            font-family: inherit; font-size: 13.5px; font-weight: 600; color: var(--text-secondary);
            padding: 10px 14px; border-bottom: 3px solid transparent; margin-bottom: -2px;
            display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
        }
        .hub-tab:hover { color: var(--text-primary); }
        .hub-tab.active { color: var(--olive); border-bottom-color: var(--olive); }
        .hub-tab .count {
            font-size: 11px; font-weight: 700; background: var(--bg-secondary); color: var(--text-secondary);
            border-radius: 20px; padding: 1px 8px; min-width: 20px; text-align: center;
        }
        .hub-tab.active .count { background: var(--olive-bg); color: var(--olive); }
        .hub-panel { display: none; padding-top: 4px; }
        .hub-panel.active { display: block; animation: fadeIn 0.2s ease; }

        /* ============================================
           TOPBAR SEARCH
           ============================================ */
        .topbar-search { position: relative; display: flex; align-items: center; }
        .topbar-search-icon {
            position: absolute; left: 10px; display: flex; align-items: center;
            color: var(--text-muted); pointer-events: none;
        }
        .topbar-search input {
            width: 200px; max-width: 34vw;
            padding: 7px 10px 7px 32px;
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-sm); font-family: inherit; font-size: 13px;
            color: var(--text-primary); transition: all var(--transition);
        }
        .topbar-search input:focus {
            outline: none; border-color: var(--border-focus); width: 260px;
            box-shadow: 0 0 0 3px var(--olive-bg);
        }
        @media (max-width: 640px) { .topbar-search { display: none; } }
    </style>
</head>
<body>
    {{-- ---- Sidebar Rail ---- --}}
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><x-icon name="crops" size="20" /></div>
            <span class="brand-name">Trooms</span>
        </div>

        <div class="sidebar-divider"></div>

        @php $u = auth()->user(); $ma = \App\Support\ModuleAccess::class; @endphp

        {{-- Quick links (single, primary destinations) --}}
        <div class="sidebar-section">
            <ul class="sidebar-nav">
                <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="icon"><x-icon name="dashboard" /></span><span class="nav-label">Dashboard</span></a></li>
                @if($ma::allows($u, 'analytics'))
                <li><a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active' : '' }}"><span class="icon"><x-icon name="modules" /></span><span class="nav-label">Executive Dashboard</span></a></li>
                @endif
            </ul>
        </div>

        <div class="sidebar-divider"></div>

        {{-- Master Data (readable by all roles) --}}
        <details class="nav-group" {{ request()->routeIs('farms.*','blocks.*','crops.*','crop-programs.*','assets.*','crop-cycles.*','checkouts.*') ? 'open' : '' }}>
            <summary>
                <span class="icon"><x-icon name="blocks" /></span>
                <span class="nav-group-title nav-label">Master Data</span>
                <span class="nav-chevron"><x-icon name="chevron" size="14" /></span>
            </summary>
            <ul class="sidebar-nav nav-group-items">
                <li><a href="{{ route('farms.index') }}" class="{{ request()->routeIs('farms.*') ? 'active' : '' }}"><span class="icon"><x-icon name="farm" /></span><span class="nav-label">Farms</span></a></li>
                <li><a href="{{ route('blocks.index') }}" class="{{ request()->routeIs('blocks.*') ? 'active' : '' }}"><span class="icon"><x-icon name="blocks" /></span><span class="nav-label">Blocks</span></a></li>
                <li><a href="{{ route('crops.index') }}" class="{{ request()->routeIs('crops.*') ? 'active' : '' }}"><span class="icon"><x-icon name="crops" /></span><span class="nav-label">Crops</span></a></li>
                @if($ma::allows($u,'crop_cycles'))<li><a href="{{ route('crop-programs.index') }}" class="{{ request()->routeIs('crop-programs.*') ? 'active' : '' }}"><span class="icon"><x-icon name="planning" /></span><span class="nav-label">Crop Programs</span></a></li>@endif
                <li><a href="{{ route('crop-cycles.index') }}" class="{{ request()->routeIs('crop-cycles.*') && ! request()->routeIs('crop-cycles.planner') ? 'active' : '' }}"><span class="icon"><x-icon name="cycles" /></span><span class="nav-label">Crop Cycles</span></a></li>
                <li><a href="{{ route('crop-cycles.planner') }}" class="{{ request()->routeIs('crop-cycles.planner') ? 'active' : '' }}"><span class="icon"><x-icon name="planning" /></span><span class="nav-label">Planting Planner</span></a></li>
                <li><a href="{{ route('assets.index') }}" class="{{ request()->routeIs('assets.*') ? 'active' : '' }}"><span class="icon"><x-icon name="assets" /></span><span class="nav-label">Assets</span></a></li>
                @if($ma::allows($u,'checkouts'))<li><a href="{{ route('checkouts.index') }}" class="{{ request()->routeIs('checkouts.*') ? 'active' : '' }}"><span class="icon"><x-icon name="inventory" /></span><span class="nav-label">Checkouts</span></a></li>@endif
            </ul>
        </details>

        @php $fieldOps = $ma::allows($u,'nursery') || $ma::allows($u,'daily_ops') || $ma::allows($u,'irrigation') || $ma::allows($u,'fertigation') || $ma::allows($u,'pest') || $ma::allows($u,'labour') || $ma::allows($u,'projects'); @endphp
        @if($fieldOps)
        {{-- Field Operations --}}
        <details class="nav-group" {{ request()->routeIs('nursery-batches.*','daily-activities.*','irrigation-logs.*','fertigation-logs.*','spray-logs.*','labour-attendances.*','projects.*','workers.*') ? 'open' : '' }}>
            <summary>
                <span class="icon"><x-icon name="operations" /></span>
                <span class="nav-group-title nav-label">Field Operations</span>
                <span class="nav-chevron"><x-icon name="chevron" size="14" /></span>
            </summary>
            <ul class="sidebar-nav nav-group-items">
                @if($ma::allows($u,'nursery'))<li><a href="{{ route('nursery-batches.index') }}" class="{{ request()->routeIs('nursery-batches.*') ? 'active' : '' }}"><span class="icon"><x-icon name="nursery" /></span><span class="nav-label">Nursery</span></a></li>@endif
                @if($ma::allows($u,'daily_ops'))<li><a href="{{ route('daily-activities.index') }}" class="{{ request()->routeIs('daily-activities.*') ? 'active' : '' }}"><span class="icon"><x-icon name="operations" /></span><span class="nav-label">Daily Operations</span></a></li>@endif
                @if($ma::allows($u,'irrigation'))<li><a href="{{ route('irrigation-logs.index') }}" class="{{ request()->routeIs('irrigation-logs.*') ? 'active' : '' }}"><span class="icon"><x-icon name="irrigation" /></span><span class="nav-label">Irrigation</span></a></li>@endif
                @if($ma::allows($u,'fertigation'))<li><a href="{{ route('fertigation-logs.index') }}" class="{{ request()->routeIs('fertigation-logs.*') ? 'active' : '' }}"><span class="icon"><x-icon name="fertigation" /></span><span class="nav-label">Fertigation</span></a></li>@endif
                @if($ma::allows($u,'pest'))<li><a href="{{ route('spray-logs.index') }}" class="{{ request()->routeIs('spray-logs.*') ? 'active' : '' }}"><span class="icon"><x-icon name="pest" /></span><span class="nav-label">Pest &amp; Disease</span></a></li>@endif
                @if($ma::allows($u,'labour'))<li><a href="{{ route('labour-attendances.index') }}" class="{{ request()->routeIs('labour-attendances.*') ? 'active' : '' }}"><span class="icon"><x-icon name="labour" /></span><span class="nav-label">Labour</span></a></li>@endif
                @if($ma::allows($u,'projects'))<li><a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') || request()->routeIs('workers.*') ? 'active' : '' }}"><span class="icon"><x-icon name="planning" /></span><span class="nav-label">Projects</span></a></li>@endif
            </ul>
        </details>
        @endif

        @php $postHarvest = $ma::allows($u,'inventory') || $ma::allows($u,'harvest') || $ma::allows($u,'packhouse') || $ma::allows($u,'quality'); @endphp
        @if($postHarvest)
        {{-- Post-Harvest --}}
        <details class="nav-group" {{ request()->routeIs('inventory-items.*','procurement-requests.*','harvest-batches.*','packhouse-lots.*','trace.lookup','quality-checks.*') ? 'open' : '' }}>
            <summary>
                <span class="icon"><x-icon name="inventory" /></span>
                <span class="nav-group-title nav-label">Post-Harvest</span>
                <span class="nav-chevron"><x-icon name="chevron" size="14" /></span>
            </summary>
            <ul class="sidebar-nav nav-group-items">
                @if($ma::allows($u,'inventory'))<li><a href="{{ route('inventory-items.index') }}" class="{{ request()->routeIs('inventory-items.*') ? 'active' : '' }}"><span class="icon"><x-icon name="inventory" /></span><span class="nav-label">Inventory</span></a></li>@endif
                @if($ma::allows($u,'inventory'))<li><a href="{{ route('procurement-requests.index') }}" class="{{ request()->routeIs('procurement-requests.*') ? 'active' : '' }}"><span class="icon"><x-icon name="inventory" /></span><span class="nav-label">Procurement</span></a></li>@endif
                @if($ma::allows($u,'harvest'))<li><a href="{{ route('harvest-batches.index') }}" class="{{ request()->routeIs('harvest-batches.*') ? 'active' : '' }}"><span class="icon"><x-icon name="harvest" /></span><span class="nav-label">Harvest</span></a></li>@endif
                @if($ma::allows($u,'packhouse'))<li><a href="{{ route('packhouse-lots.index') }}" class="{{ request()->routeIs('packhouse-lots.*') || request()->routeIs('trace.lookup') ? 'active' : '' }}"><span class="icon"><x-icon name="packhouse" /></span><span class="nav-label">Packhouse</span></a></li>@endif
                @if($ma::allows($u,'quality'))<li><a href="{{ route('quality-checks.index') }}" class="{{ request()->routeIs('quality-checks.*') ? 'active' : '' }}"><span class="icon"><x-icon name="quality" /></span><span class="nav-label">Quality</span></a></li>@endif
            </ul>
        </details>
        @endif

        @php $commercial = $ma::allows($u,'sales') || $ma::allows($u,'logistics') || $ma::allows($u,'finance'); @endphp
        @if($commercial)
        {{-- Commercial --}}
        <details class="nav-group" {{ request()->routeIs('customers.*','sales-orders.*','outgrowers.*','dispatches.*','finance.*') ? 'open' : '' }}>
            <summary>
                <span class="icon"><x-icon name="sales" /></span>
                <span class="nav-group-title nav-label">Commercial</span>
                <span class="nav-chevron"><x-icon name="chevron" size="14" /></span>
            </summary>
            <ul class="sidebar-nav nav-group-items">
                @if($ma::allows($u,'sales'))<li><a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') || request()->routeIs('sales-orders.*') ? 'active' : '' }}"><span class="icon"><x-icon name="sales" /></span><span class="nav-label">Sales</span></a></li>@endif
                @if($ma::allows($u,'sales'))<li><a href="{{ route('outgrowers.index') }}" class="{{ request()->routeIs('outgrowers.*') ? 'active' : '' }}"><span class="icon"><x-icon name="sales" /></span><span class="nav-label">Outgrowers</span></a></li>@endif
                @if($ma::allows($u,'logistics'))<li><a href="{{ route('dispatches.index') }}" class="{{ request()->routeIs('dispatches.*') ? 'active' : '' }}"><span class="icon"><x-icon name="logistics" /></span><span class="nav-label">Logistics</span></a></li>@endif
                @if($ma::allows($u,'finance'))<li><a href="{{ route('finance.index') }}" class="{{ request()->routeIs('finance.*') ? 'active' : '' }}"><span class="icon"><x-icon name="finance" /></span><span class="nav-label">Finance</span></a></li>@endif
            </ul>
        </details>
        @endif

        @if($ma::allows($u,'stables'))
        {{-- Stables --}}
        <details class="nav-group" {{ request()->routeIs('rides.*','horses.*','guides.*') ? 'open' : '' }}>
            <summary>
                <span class="icon"><x-icon name="horse" /></span>
                <span class="nav-group-title nav-label">Stables</span>
                <span class="nav-chevron"><x-icon name="chevron" size="14" /></span>
            </summary>
            <ul class="sidebar-nav nav-group-items">
                <li><a href="{{ route('rides.index') }}" class="{{ request()->routeIs('rides.*') ? 'active' : '' }}"><span class="icon"><x-icon name="sales" /></span><span class="nav-label">Horse Rides</span></a></li>
                <li><a href="{{ route('horses.index') }}" class="{{ request()->routeIs('horses.*') ? 'active' : '' }}"><span class="icon"><x-icon name="horse" /></span><span class="nav-label">Horses</span></a></li>
                <li><a href="{{ route('guides.index') }}" class="{{ request()->routeIs('guides.*') ? 'active' : '' }}"><span class="icon"><x-icon name="labour" /></span><span class="nav-label">Guides</span></a></li>
            </ul>
        </details>
        @endif

        @if($ma::allows($u,'admin'))
        {{-- Administration --}}
        <details class="nav-group" {{ request()->routeIs('users.*','activity-logs.*') ? 'open' : '' }}>
            <summary>
                <span class="icon"><x-icon name="settings" /></span>
                <span class="nav-group-title nav-label">Administration</span>
                <span class="nav-chevron"><x-icon name="chevron" size="14" /></span>
            </summary>
            <ul class="sidebar-nav nav-group-items">
                <li><a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}"><span class="icon"><x-icon name="settings" /></span><span class="nav-label">Users &amp; Roles</span></a></li>
                <li><a href="{{ route('activity-logs.index') }}" class="{{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"><span class="icon"><x-icon name="modules" /></span><span class="nav-label">Audit Log</span></a></li>
            </ul>
        </details>
        @endif

        {{-- Settings & Log Out live in the top-right user menu, not the sidebar. --}}
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
                    $topbarAlerts = app(\App\Services\AlertService::class)->collect();
                    $alertCount = count($topbarAlerts);
                @endphp

                {{-- Global quick-search --}}
                <form method="GET" action="{{ route('search') }}" class="topbar-search" role="search">
                    <span class="topbar-search-icon"><x-icon name="search" size="15" /></span>
                    <input type="text" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Search…" aria-label="Search" autocomplete="off">
                </form>

                {{-- Notifications bell dropdown --}}
                <div class="notif-wrap" id="notif-wrap">
                    <button type="button" class="icon-btn notif-btn {{ request()->routeIs('notifications.*') ? 'active' : '' }}" aria-label="Notifications" title="Notifications" onclick="document.getElementById('notif-wrap').classList.toggle('open')" id="notif-bell-btn">
                        <x-icon name="notifications" size="18" />
                        @if($alertCount > 0)
                            <span class="notif-badge">{{ $alertCount > 9 ? '9+' : $alertCount }}</span>
                        @endif
                    </button>
                    <div class="notif-dropdown" role="dialog" aria-label="Notifications" onclick="event.stopPropagation()">
                        <div class="notif-dropdown-header">
                            <span>Notifications</span>
                            @if($alertCount > 0)
                                <span class="notif-dropdown-count">{{ $alertCount }} alert{{ $alertCount !== 1 ? 's' : '' }}</span>
                            @endif
                        </div>
                        <div class="notif-dropdown-body">
                            @forelse($topbarAlerts as $al)
                                <div class="notif-item notif-item--{{ $al['severity'] }}">
                                    <div class="notif-item-meta">
                                        <span class="notif-item-type">{{ str_replace('_', ' ', $al['type']) }}</span>
                                        <span class="notif-item-module">{{ $al['module'] }}</span>
                                    </div>
                                    <p class="notif-item-msg">{{ $al['message'] }}</p>
                                </div>
                            @empty
                                <div class="notif-empty">
                                    <svg viewBox="0 0 24 24" width="28" height="28" stroke="currentColor" fill="none"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                                    <span>All clear — no alerts</span>
                                </div>
                            @endforelse
                        </div>
                        <a href="{{ route('notifications.index') }}" class="notif-dropdown-footer">View all notifications →</a>
                    </div>
                </div>
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
                            <a href="{{ route('notifications.index') }}" class="user-dropdown-item">
                                <span class="udd-icon"><svg viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></span>
                                Notifications
                                @if($alertCount > 0)<span class="badge badge-down" style="margin-left:auto; font-size:10px;">{{ $alertCount }}</span>@endif
                            </a>
                            <a href="{{ route('settings.index') }}" class="user-dropdown-item">
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

    {{-- ---- Global Confirm Modal (replaces native window.confirm) ---- --}}
    <div class="confirm-overlay" id="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
        <div class="confirm-dialog">
            <div class="confirm-icon">!</div>
            <div class="confirm-title" id="confirm-modal-title">Please confirm</div>
            <div class="confirm-message" id="confirm-modal-message"></div>
            <div class="confirm-actions">
                <button type="button" class="btn btn-ghost" id="confirm-modal-cancel">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-modal-ok">Confirm</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Notification dropdown — close on outside click or Escape
        var notifWrap = document.getElementById('notif-wrap');
        if (notifWrap) {
            document.addEventListener('click', function(e) {
                if (!notifWrap.contains(e.target)) {
                    notifWrap.classList.remove('open');
                }
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') notifWrap.classList.remove('open');
            });
        }


        // In-app confirm modal — intercepts any form with a data-confirm attribute
        // and replaces the native browser confirm() dialog with a styled modal.
        var confirmModal   = document.getElementById('confirm-modal');
        var confirmMessage = document.getElementById('confirm-modal-message');
        var confirmOk      = document.getElementById('confirm-modal-ok');
        var confirmCancel  = document.getElementById('confirm-modal-cancel');
        var pendingForm    = null;

        function closeConfirm() {
            confirmModal.classList.remove('open');
            pendingForm = null;
        }

        if (confirmModal) {
            document.addEventListener('submit', function(e) {
                var form = e.target;
                if (!form.matches('[data-confirm]') || form.dataset.confirmed === 'true') {
                    return; // no confirmation needed, or already confirmed
                }
                e.preventDefault();
                pendingForm = form;
                confirmMessage.textContent = form.getAttribute('data-confirm');
                confirmOk.textContent = form.getAttribute('data-confirm-ok') || 'Confirm';
                confirmModal.classList.add('open');
                confirmOk.focus();
            });

            confirmOk.addEventListener('click', function() {
                if (!pendingForm) return;
                var form = pendingForm;
                form.dataset.confirmed = 'true'; // let the next submit pass through
                closeConfirm();
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });

            confirmCancel.addEventListener('click', closeConfirm);
            confirmModal.addEventListener('click', function(e) {
                if (e.target === confirmModal) closeConfirm();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && confirmModal.classList.contains('open')) closeConfirm();
            });
        }
    });
    </script>
</body>
</html>
