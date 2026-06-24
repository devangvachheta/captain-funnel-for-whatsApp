import { createSlice } from '@reduxjs/toolkit';

// ── Persist dark-mode preference in localStorage ──────────────────────────────
const DARK_KEY = 'capfw_dark_mode';

const getSavedDarkMode = () => {
    try { return localStorage.getItem(DARK_KEY) === 'true'; }
    catch { return false; }
};

const applyThemeToDOM = (isDark) => {
    const el = document.documentElement;
    if (isDark) {
        el.setAttribute('data-capfw-theme', 'dark');
    } else {
        el.removeAttribute('data-capfw-theme');
    }
    try { localStorage.setItem(DARK_KEY, String(isDark)); }
    catch { /* ignore */ }
};

// Apply on first load (before React renders)
applyThemeToDOM(getSavedDarkMode());

// ─────────────────────────────────────────────────────────────────────────────
const initialState = {
    // Dashboard stats
    stats: { sent: 0, failed: 0, pending: 0 },

    // WhatsApp settings
    settings: {
        access_token:         '',
        phone_number_id:      '',
        business_account_id:  '',
        enabled_statuses:     [],
        message_type:         'text',
        template_name:        '',
        template_language:    'en_US',
        template_no_variables: false,
    },

    // Message templates (one per order status)
    templates: {},

    // Funnels list
    funnels: [],

    // Logs
    logs:       [],
    logs_total: 0,

    // Triggers from registry
    available_triggers: [],

    // Global UI
    loading: false,

    // ── Dark Mode ────────────────────────────────────────────────────────────
    darkMode: getSavedDarkMode(),
};

const capfwSlice = createSlice({
    name: 'capfw',
    initialState,
    reducers: {
        setStats(state, action)         { state.stats              = action.payload; },
        setSettings(state, action)      { state.settings           = action.payload; },
        updateSetting(state, action)    {
            const { key, value } = action.payload;
            state.settings[key] = value;
        },
        setTemplates(state, action)     { state.templates          = action.payload; },
        updateTemplate(state, action)   {
            const { status, value } = action.payload;
            state.templates[status] = value;
        },
        setFunnels(state, action)       { state.funnels            = action.payload; },
        addFunnel(state, action)        { state.funnels.unshift(action.payload); },
        updateFunnel(state, action)     {
            const idx = state.funnels.findIndex(f => Number(f.id) === Number(action.payload.id));
            if (idx !== -1) state.funnels[idx] = action.payload;
        },
        removeFunnel(state, action)     {
            state.funnels = state.funnels.filter(f => Number(f.id) !== Number(action.payload));
        },
        setLogs(state, action)          { state.logs               = action.payload; },
        setLogsTotal(state, action)     { state.logs_total         = action.payload; },
        setLoading(state, action)       { state.loading            = action.payload; },
        setAvailableTriggers(state, action) { state.available_triggers = action.payload; },

        // ── Dark Mode Toggle ─────────────────────────────────────────────────
        toggleDarkMode(state) {
            state.darkMode = !state.darkMode;
            applyThemeToDOM(state.darkMode);
        },
        setDarkMode(state, action) {
            state.darkMode = action.payload;
            applyThemeToDOM(state.darkMode);
        },
    },
});

export const {
    setStats,
    setSettings,
    updateSetting,
    setTemplates,
    updateTemplate,
    setFunnels,
    addFunnel,
    updateFunnel,
    removeFunnel,
    setLogs,
    setLogsTotal,
    setLoading,
    setAvailableTriggers,
    toggleDarkMode,
    setDarkMode,
} = capfwSlice.actions;

export default capfwSlice.reducer;
