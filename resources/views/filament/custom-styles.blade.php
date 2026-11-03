{{-- AquaFeed ERP Modern SaaS Theme Stylesheet --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600;1,700&display=swap" rel="stylesheet">

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
       CARDS & SECTIONS (Modern 1.25rem radius + soft glass glow)
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

    /* Section Headers */
    .fi-section-header {
        padding-bottom: 0.75rem !important;
        border-bottom: 1px solid rgba(241, 245, 249, 0.9) !important;
        margin-bottom: 1rem !important;
    }
    .dark .fi-section-header {
        border-bottom-color: rgba(30, 41, 59, 0.6) !important;
    }

    /* ==========================================================================
       STATS OVERVIEW WIDGET CARDS (Modern Fintech Style)
       ========================================================================== */
    .fi-wi-stats-overview {
        gap: 1.15rem !important;
    }
    .fi-wi-stats-overview-stat {
        border-radius: 1.25rem !important;
        border: 1px solid rgba(226, 232, 240, 0.85) !important;
        background: #ffffff !important;
        padding: 1.25rem !important;
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
        margin-bottom: 0.35rem !important;
    }
    .dark .fi-wi-stats-overview-stat-value {
        color: #f8fafc !important;
    }
    .fi-wi-stats-overview-stat-description {
        font-size: 0.8rem !important;
        font-weight: 500 !important;
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
        padding-top: 0.85rem !important;
        padding-bottom: 0.85rem !important;
    }
    .dark .fi-ta-header-cell {
        background-color: rgba(15, 23, 42, 0.8) !important;
        color: #94a3b8 !important;
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
</style>
