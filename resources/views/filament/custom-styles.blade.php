{{-- AquaFeed ERP Modern SaaS Theme Stylesheet --}}
<link rel="icon" type="image/svg+xml" href="{{ \App\Models\ShopSetting::getSettings()->getLogoUrl() ?? asset('favicon.svg') }}">
<link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600;1,700&display=swap" rel="stylesheet">
<script src="/js/offline-sync-engine.js" defer></script>

<style>
    /* ==========================================================================
       GLOBAL TYPOGRAPHY & SMOOTHING
       ========================================================================== */
    html, body, .fi, .fi * {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
    }

    /* Modern subtle background glow */
    body {
        background-color: #f8fafc !important;
    }
    .dark body {
        background-color: #080d1a !important;
    }

    /* Custom Sleek Scrollbars */
    ::-webkit-scrollbar {
        width: 7px;
        height: 7px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.4);
        border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(148, 163, 184, 0.7);
    }
    .dark ::-webkit-scrollbar-thumb {
        background: rgba(51, 65, 85, 0.6);
    }
    .dark ::-webkit-scrollbar-thumb:hover {
        background: rgba(71, 85, 105, 0.9);
    }

    /* ==========================================================================
       TOPBAR & HEADER POLISH
       ========================================================================== */
    .fi-topbar {
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        background-color: rgba(255, 255, 255, 0.85) !important;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8) !important;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02) !important;
    }
    .dark .fi-topbar {
        background-color: rgba(11, 17, 33, 0.85) !important;
        border-bottom-color: rgba(30, 41, 59, 0.7) !important;
    }

    /* Brand Logo / Title */
    .fi-logo {
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
        background: linear-gradient(135deg, #0d9488, #0284c7);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* ==========================================================================
       SIDEBAR & NAVIGATION POLISH
       ========================================================================== */
    .fi-sidebar {
        background-color: #ffffff !important;
        border-right: 1px solid rgba(226, 232, 240, 0.8) !important;
    }
    .dark .fi-sidebar {
        background-color: #0b1121 !important;
        border-right-color: rgba(30, 41, 59, 0.6) !important;
    }

    /* Navigation Group Labels */
    .fi-sidebar-group-label {
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.08em !important;
        text-transform: uppercase !important;
        color: #64748b !important;
        padding-top: 0.75rem !important;
        padding-bottom: 0.25rem !important;
    }
    .dark .fi-sidebar-group-label {
        color: #94a3b8 !important;
    }

    /* Sidebar Items */
    .fi-sidebar-item {
        margin-bottom: 2px !important;
    }
    .fi-sidebar-item a {
        border-radius: 0.75rem !important;
        padding: 0.55rem 0.85rem !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border: 1px solid transparent !important;
    }
    .fi-sidebar-item a:hover {
        background-color: rgba(241, 245, 249, 0.8) !important;
        transform: translateX(3px) !important;
    }
    .dark .fi-sidebar-item a:hover {
        background-color: rgba(30, 41, 59, 0.6) !important;
    }

    /* Active Sidebar Item - Eye-catchy modern teal pill */
    .fi-sidebar-item.fi-active a {
        background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(13, 148, 136, 0.04)) !important;
        border: 1px solid rgba(20, 184, 166, 0.35) !important;
        color: #0f766e !important;
        font-weight: 700 !important;
        box-shadow: 0 2px 8px -2px rgba(20, 184, 166, 0.25) !important;
    }
    .dark .fi-sidebar-item.fi-active a {
        background: linear-gradient(135deg, rgba(20, 184, 166, 0.22), rgba(13, 148, 136, 0.08)) !important;
        border-color: rgba(20, 184, 166, 0.45) !important;
        color: #2dd4bf !important;
        box-shadow: 0 4px 12px -2px rgba(20, 184, 166, 0.2) !important;
    }
    .fi-sidebar-item.fi-active a svg {
        color: #0d9488 !important;
    }
    .dark .fi-sidebar-item.fi-active a svg {
        color: #2dd4bf !important;
    }

    /* ==========================================================================
       CARDS & SECTIONS (Modern 1.25rem radius + soft glass glow + generous padding)
       ========================================================================== */
    .fi-section,
    .fi-card,
    .fi-ta-ctn {
        border-radius: 1.25rem !important;
        border: 1px solid rgba(226, 232, 240, 0.85) !important;
        background-color: #ffffff !important;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.03), 0 2px 6px -1px rgba(15, 23, 42, 0.02) !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
    }
    .dark .fi-section,
    .dark .fi-card,
    .dark .fi-ta-ctn {
        background-color: #0f172a !important;
        border-color: rgba(30, 41, 59, 0.7) !important;
        box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.35) !important;
    }
    .fi-section:hover,
    .fi-card:hover {
        border-color: rgba(203, 213, 225, 0.9) !important;
    }
    .dark .fi-section:hover,
    .dark .fi-card:hover {
        border-color: rgba(51, 65, 85, 0.8) !important;
    }

    /* Section Content Spacious Padding */
    .fi-section-content {
        padding: 1.6rem 1.75rem !important;
    }

    /* Section Headers */
    .fi-section-header {
        padding-bottom: 0.85rem !important;
        border-bottom: 1px solid rgba(241, 245, 249, 0.9) !important;
        margin-bottom: 1.25rem !important;
    }
    .dark .fi-section-header {
        border-bottom-color: rgba(30, 41, 59, 0.6) !important;
    }

    /* Dashboard Widget Grid Spacing */
    .fi-widgets-grid,
    .fi-page-body > .grid {
        gap: 1.5rem !important;
    }
    @media (min-width: 1024px) {
        .fi-widgets-grid,
        .fi-page-body > .grid {
            gap: 1.75rem !important;
        }
    }

    /* ==========================================================================
       STATS OVERVIEW WIDGET CARDS (Modern Fintech Style)
       ========================================================================== */
    .fi-wi-stats-overview {
        gap: 1.35rem !important;
        margin-bottom: 0.75rem !important;
    }
    .fi-wi-stats-overview-stat {
        border-radius: 1.25rem !important;
        border: 1px solid rgba(226, 232, 240, 0.85) !important;
        background: #ffffff !important;
        padding: 1.4rem 1.6rem !important;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03) !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative;
        overflow: hidden;
    }
    .dark .fi-wi-stats-overview-stat {
        background: #0f172a !important;
        border-color: rgba(30, 41, 59, 0.7) !important;
        box-shadow: 0 6px 20px -2px rgba(0, 0, 0, 0.3) !important;
    }
    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-3px) !important;
        border-color: rgba(20, 184, 166, 0.45) !important;
        box-shadow: 0 14px 30px -4px rgba(20, 184, 166, 0.12) !important;
    }
    .dark .fi-wi-stats-overview-stat:hover {
        border-color: rgba(20, 184, 166, 0.5) !important;
        box-shadow: 0 16px 36px -4px rgba(0, 0, 0, 0.5) !important;
    }

    /* Stat Label & Value */
    .fi-wi-stats-overview-stat-label {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.04em !important;
        text-transform: uppercase !important;
        color: #64748b !important;
        margin-bottom: 0.35rem !important;
        line-height: 1.4 !important;
    }
    .dark .fi-wi-stats-overview-stat-label {
        color: #94a3b8 !important;
    }
    .fi-wi-stats-overview-stat-value {
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
        color: #0f172a !important;
        margin-top: 0.35rem !important;
        margin-bottom: 0.45rem !important;
        line-height: 1.2 !important;
    }
    .dark .fi-wi-stats-overview-stat-value {
        color: #f8fafc !important;
    }
    .fi-wi-stats-overview-stat-description {
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        line-height: 1.4 !important;
    }

    /* ==========================================================================
       FEED CATEGORY TURNOVER CARDS (Generous Padding & Anti-Congestion)
       ========================================================================== */
    .feed-category-section {
        margin-top: 2rem !important;
        padding-top: 1.5rem !important;
    }
    .feed-category-section-header {
        margin-bottom: 1.25rem !important;
    }
    .feed-category-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
        gap: 1.25rem !important;
    }
    .feed-category-card {
        padding: 1.35rem 1.5rem !important;
        border-radius: 1.25rem !important;
        background: rgba(248, 250, 252, 0.8) !important;
        border: 1px solid rgba(226, 232, 240, 0.95) !important;
        box-shadow: 0 4px 14px -2px rgba(15, 23, 42, 0.04) !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        min-height: 120px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .dark .feed-category-card {
        background: rgba(15, 23, 42, 0.65) !important;
        border-color: rgba(51, 65, 85, 0.85) !important;
        box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.35) !important;
    }
    .feed-category-card:hover {
        transform: translateY(-2px) !important;
        border-color: rgba(20, 184, 166, 0.5) !important;
        box-shadow: 0 10px 24px -4px rgba(20, 184, 166, 0.15) !important;
    }
    .dark .feed-category-card:hover {
        border-color: rgba(20, 184, 166, 0.55) !important;
        box-shadow: 0 10px 28px -4px rgba(0, 0, 0, 0.5) !important;
    }

    .feed-category-header {
        margin-bottom: 0.85rem !important;
        padding-bottom: 0.25rem !important;
    }

    .feed-category-name {
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        line-height: 1.6 !important;
        color: #334155 !important;
        display: block !important;
        word-break: break-word !important;
    }
    .dark .feed-category-name {
        color: #e2e8f0 !important;
    }

    .feed-category-body {
        margin-top: auto !important;
        padding-top: 0.5rem !important;
    }

    .feed-category-stat-row {
        display: flex !important;
        align-items: baseline !important;
        justify-content: space-between !important;
        margin-bottom: 0.65rem !important;
    }

    .feed-category-bags-wrap {
        display: flex !important;
        align-items: baseline !important;
        gap: 0.35rem !important;
    }

    .feed-category-bags-val {
        font-size: 1.45rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
        line-height: 1 !important;
        color: #0f172a !important;
    }
    .dark .feed-category-bags-val {
        color: #ffffff !important;
    }

    .feed-category-bags-unit {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        color: #94a3b8 !important;
    }

    .feed-category-badge {
        display: inline-flex !important;
        align-items: center !important;
        padding: 0.25rem 0.65rem !important;
        border-radius: 9999px !important;
        font-size: 0.75rem !important;
        font-weight: 800 !important;
        background: rgba(20, 184, 166, 0.12) !important;
        color: #0d9488 !important;
        border: 1px solid rgba(20, 184, 166, 0.28) !important;
        box-shadow: 0 1px 2px 0 rgba(20, 184, 166, 0.08) !important;
    }
    .dark .feed-category-badge {
        background: rgba(20, 184, 166, 0.2) !important;
        color: #2dd4bf !important;
        border-color: rgba(20, 184, 166, 0.45) !important;
    }

    .feed-category-bar-track {
        width: 100% !important;
        height: 5px !important;
        border-radius: 9999px !important;
        background: rgba(226, 232, 240, 0.85) !important;
        overflow: hidden !important;
    }
    .dark .feed-category-bar-track {
        background: rgba(30, 41, 59, 0.85) !important;
    }

    .feed-category-bar-fill {
        height: 100% !important;
        border-radius: 9999px !important;
        background: linear-gradient(90deg, #14b8a6, #06b6d4) !important;
        transition: width 0.4s ease-in-out !important;
    }

    /* Low Stock Alert Item Cards */
    .low-stock-item-card {
        padding: 1.25rem 1.35rem !important;
        border-radius: 1.25rem !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .low-stock-item-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.08) !important;
    }
    .dark .low-stock-item-card:hover {
        box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.4) !important;
    }

    /* Fractional spacing safeguards */
    .p-2\.5 { padding: 0.625rem !important; }
    .p-3\.5 { padding: 0.875rem !important; }
    .gap-2\.5 { gap: 0.625rem !important; }
    .gap-3\.5 { gap: 0.875rem !important; }
    .py-2\.5 { padding-top: 0.625rem !important; padding-bottom: 0.625rem !important; }
    .py-3\.5 { padding-top: 0.875rem !important; padding-bottom: 0.875rem !important; }
    .px-3\.5 { padding-left: 0.875rem !important; padding-right: 0.875rem !important; }
    .mb-2\.5 { margin-bottom: 0.625rem !important; }

    /* ==========================================================================
       DARK MODE COLOR COMPATIBILITY (Slate & Utility Fallbacks)
       ========================================================================== */
    .dark .dark\:bg-slate-900,
    .dark .dark\:bg-slate-900\/40,
    .dark .dark\:bg-slate-900\/50,
    .dark .dark\:bg-slate-900\/60,
    .dark .dark\:bg-slate-900\/70 {
        background-color: #0f172a !important;
    }
    .dark .dark\:bg-slate-950,
    .dark .dark\:bg-slate-950\/50,
    .dark .dark\:bg-slate-950\/60 {
        background-color: #020617 !important;
    }
    .dark .dark\:bg-slate-800,
    .dark .dark\:bg-slate-800\/40,
    .dark .dark\:bg-slate-800\/50,
    .dark .dark\:bg-slate-800\/60,
    .dark .dark\:bg-slate-800\/80 {
        background-color: #1e293b !important;
    }
    .dark .dark\:border-slate-800,
    .dark .dark\:border-slate-800\/60,
    .dark .dark\:border-slate-800\/80,
    .dark .dark\:border-slate-800\/90 {
        border-color: #1e293b !important;
    }
    .dark .dark\:border-slate-700,
    .dark .dark\:border-slate-700\/60,
    .dark .dark\:border-slate-700\/80 {
        border-color: #334155 !important;
    }
    .dark .dark\:text-white {
        color: #ffffff !important;
    }
    .dark .dark\:text-slate-100 {
        color: #f1f5f9 !important;
    }
    .dark .dark\:text-slate-200 {
        color: #e2e8f0 !important;
    }
    .dark .dark\:text-slate-300 {
        color: #cbd5e1 !important;
    }
    .dark .dark\:text-slate-400 {
        color: #94a3b8 !important;
    }
    .dark .dark\:text-slate-500 {
        color: #64748b !important;
    }
    .dark .dark\:hover\:bg-gray-800\/30:hover {
        background-color: rgba(30, 41, 59, 0.4) !important;
    }
    .dark .dark\:bg-gray-800\/60 {
        background-color: rgba(30, 41, 59, 0.7) !important;
    }
    .dark .dark\:border-gray-700 {
        border-color: #334155 !important;
    }
    .dark .dark\:divide-gray-800 > :not([hidden]) ~ :not([hidden]) {
        border-color: #1e293b !important;
    }

    /* ==========================================================================
       REPORT PAGES MODERN FINTECH STYLING SUITE
       ========================================================================== */
    .rpt-filter-card {
        background: #ffffff !important;
        border: 1px solid rgba(226, 232, 240, 0.9) !important;
        border-radius: 1.25rem !important;
        padding: 1.35rem 1.6rem !important;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03) !important;
        margin-bottom: 1.5rem !important;
    }
    .dark .rpt-filter-card {
        background: #0f172a !important;
        border-color: rgba(51, 65, 85, 0.85) !important;
        box-shadow: 0 4px 18px -2px rgba(0, 0, 0, 0.35) !important;
    }

    .rpt-kpi-grid {
        display: grid !important;
        grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
        gap: 1.15rem !important;
        margin-bottom: 1.5rem !important;
    }
    @media (min-width: 640px) {
        .rpt-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (min-width: 1024px) {
        .rpt-kpi-grid-5 {
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        }
        .rpt-kpi-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    .rpt-kpi-card {
        background: #ffffff !important;
        border: 1px solid rgba(226, 232, 240, 0.9) !important;
        border-radius: 1.25rem !important;
        padding: 1.35rem 1.5rem !important;
        box-shadow: 0 4px 14px -2px rgba(15, 23, 42, 0.03) !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        min-height: 112px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .dark .rpt-kpi-card {
        background: #0f172a !important;
        border-color: rgba(51, 65, 85, 0.85) !important;
        box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.35) !important;
    }
    .rpt-kpi-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 10px 24px -4px rgba(15, 23, 42, 0.08) !important;
    }
    .dark .rpt-kpi-card:hover {
        box-shadow: 0 10px 24px -4px rgba(0, 0, 0, 0.5) !important;
        border-color: rgba(20, 184, 166, 0.45) !important;
    }

    .rpt-kpi-title {
        font-size: 0.725rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.05em !important;
        text-transform: uppercase !important;
        line-height: 1.4 !important;
        color: #64748b !important;
        margin-bottom: 0.4rem !important;
    }
    .dark .rpt-kpi-title {
        color: #94a3b8 !important;
    }

    .rpt-kpi-val {
        font-size: 1.65rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.15 !important;
        margin-bottom: 0.35rem !important;
    }

    .rpt-kpi-sub {
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        color: #94a3b8 !important;
        line-height: 1.4 !important;
    }
    .dark .rpt-kpi-sub {
        color: #64748b !important;
    }

    .rpt-table-card {
        background: #ffffff !important;
        border: 1px solid rgba(226, 232, 240, 0.9) !important;
        border-radius: 1.25rem !important;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03) !important;
        overflow: hidden !important;
    }
    .dark .rpt-table-card {
        background: #0f172a !important;
        border-color: rgba(51, 65, 85, 0.85) !important;
        box-shadow: 0 4px 18px -2px rgba(0, 0, 0, 0.35) !important;
    }

    .rpt-table {
        width: 100% !important;
        border-collapse: collapse !important;
        text-align: left !important;
        font-size: 0.875rem !important;
    }
    .rpt-table thead tr {
        background: rgba(248, 250, 252, 0.9) !important;
        border-bottom: 1px solid rgba(226, 232, 240, 0.9) !important;
    }
    .dark .rpt-table thead tr {
        background: rgba(30, 41, 59, 0.7) !important;
        border-bottom-color: rgba(51, 65, 85, 0.85) !important;
    }
    .rpt-table th {
        padding: 0.95rem 1.15rem !important;
        font-size: 0.725rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.05em !important;
        text-transform: uppercase !important;
        color: #475569 !important;
    }
    .dark .rpt-table th {
        color: #94a3b8 !important;
    }

    .rpt-table tbody tr {
        border-bottom: 1px solid rgba(241, 245, 249, 0.9) !important;
        transition: background-color 0.15s ease !important;
    }
    .dark .rpt-table tbody tr {
        border-bottom-color: rgba(30, 41, 59, 0.6) !important;
    }
    .rpt-table tbody tr:hover {
        background-color: rgba(240, 253, 250, 0.5) !important;
    }
    .dark .rpt-table tbody tr:hover {
        background-color: rgba(19, 78, 74, 0.18) !important;
    }

    .rpt-table td {
        padding: 0.95rem 1.15rem !important;
        color: #1e293b !important;
    }
    .dark .rpt-table td {
        color: #e2e8f0 !important;
    }

    /* ==========================================================================
       TABLES (Clean Fintech Rows & Micro-Typography)
       ========================================================================== */
    .fi-ta-ctn {
        overflow: hidden !important;
    }
    .fi-ta-header-cell {
        background-color: rgba(248, 250, 252, 0.85) !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.06em !important;
        text-transform: uppercase !important;
        color: #64748b !important;
        padding-top: 0.95rem !important;
        padding-bottom: 0.95rem !important;
    }
    .dark .fi-ta-header-cell {
        background-color: rgba(15, 23, 42, 0.8) !important;
        color: #94a3b8 !important;
    }
    .fi-ta-cell {
        padding-top: 0.9rem !important;
        padding-bottom: 0.9rem !important;
    }
    .fi-ta-row {
        transition: background-color 0.15s ease !important;
    }
    .fi-ta-row:hover {
        background-color: rgba(240, 253, 250, 0.5) !important;
    }
    .dark .fi-ta-row:hover {
        background-color: rgba(19, 78, 74, 0.15) !important;
    }

    /* Badges */
    .fi-badge {
        border-radius: 9999px !important;
        font-weight: 600 !important;
        font-size: 0.725rem !important;
        padding: 0.2rem 0.65rem !important;
        letter-spacing: 0.01em !important;
    }

    /* ==========================================================================
       BUTTONS & INPUT CONTROLS
       ========================================================================== */
    .fi-btn {
        border-radius: 0.75rem !important;
        font-weight: 600 !important;
        letter-spacing: -0.01em !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .fi-btn:hover {
        transform: translateY(-1px) !important;
    }
    .fi-btn:active {
        transform: translateY(0px) !important;
    }

    /* Primary Buttons Gradient Glow */
    .fi-btn-color-primary {
        background: linear-gradient(135deg, #14b8a6, #0d9488) !important;
        border: none !important;
        box-shadow: 0 4px 14px -2px rgba(13, 148, 136, 0.35) !important;
    }
    .fi-btn-color-primary:hover {
        box-shadow: 0 6px 18px -2px rgba(13, 148, 136, 0.5) !important;
    }

    .fi-input-wrp,
    .fi-select-wrp {
        border-radius: 0.75rem !important;
        transition: all 0.15s ease !important;
    }
    .fi-input-wrp:focus-within,
    .fi-select-wrp:focus-within {
        border-color: #14b8a6 !important;
        box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.18) !important;
    }

    /* Modal dialogs */
    .fi-modal-window {
        border-radius: 1.5rem !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    }
    .dark .fi-modal-window {
        border-color: rgba(30, 41, 59, 0.7) !important;
    }

    /* Offline Sync Toast Notifications */
    .aquafeed-sync-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 99999;
        background: #0f172a;
        color: #ffffff;
        padding: 12px 18px;
        border-radius: 14px;
        box-shadow: 0 10px 30px -4px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    }
    .aquafeed-sync-toast.show {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }
    .aquafeed-sync-toast.success {
        border-left: 4px solid #10b981;
    }
    .aquafeed-sync-toast.warning {
        border-left: 4px solid #f59e0b;
    }
    .aquafeed-sync-toast.info {
        border-left: 4px solid #06b6d4;
    }

    /* ==========================================================================
       MOBILE RESPONSIVE FULL-WIDTH ENHANCEMENTS (< 768px)
       ========================================================================== */
    @media (max-width: 768px) {
        /* 1. Dashboard Widget Container: single full-width column with compact gap */
        .fi-widgets-grid,
        .fi-page-body > .grid {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }

        /* 2. Stats Overview Cards: 100% Full-Width column stack */
        .fi-wi-stats-overview {
            gap: 0.75rem !important;
            margin-bottom: 0.5rem !important;
        }

        .fi-wi-stats-overview-stats-ctn {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
            width: 100% !important;
        }

        .fi-wi-stats-overview-stat {
            width: 100% !important;
            padding: 1.15rem 1.25rem !important;
            margin: 0 !important;
        }

        .fi-wi-stats-overview-stat-value {
            font-size: 1.5rem !important;
        }

        /* 3. Feed Category Breakdown Cards */
        .feed-category-grid {
            grid-template-columns: 1fr !important;
            gap: 0.75rem !important;
        }

        /* 4. Page body padding adjustment for edge-to-edge breathing room */
        .fi-page-body {
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
        }
    }
</style>
