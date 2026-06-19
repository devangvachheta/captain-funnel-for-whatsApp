import { createSlice } from '@reduxjs/toolkit';

const initialState = {
    // Dashboard stats
    stats: {
        sent:    0,
        failed:  0,
        pending: 0,
    },

    // WhatsApp settings
    settings: {
        access_token:         '',
        phone_number_id:      '',
        business_account_id:  '',
        enabled_statuses:     [],
    },

    // Message templates (one per order status)
    templates: {},

    // Funnels list
    funnels: [],

    // Logs list
    logs: [],
    logs_total: 0,

    // Triggers from registry (set once on funnels/templates page load)
    available_triggers: [],

    // Global UI
    loading: false,
};

const capfwSlice = createSlice({
    name: 'capfw',
    initialState,
    reducers: {
        setStats(state, action)         { state.stats           = action.payload; },
        setSettings(state, action)      { state.settings        = action.payload; },
        updateSetting(state, action)    {
            const { key, value } = action.payload;
            state.settings[key] = value;
        },
        setTemplates(state, action)     { state.templates       = action.payload; },
        updateTemplate(state, action)   {
            const { status, value } = action.payload;
            state.templates[status] = value;
        },
        setFunnels(state, action)       { state.funnels         = action.payload; },
        addFunnel(state, action)        { state.funnels.unshift(action.payload); },
        updateFunnel(state, action)     {
            const idx = state.funnels.findIndex(f => Number(f.id) === Number(action.payload.id));
            if (idx !== -1) state.funnels[idx] = action.payload;
        },
        removeFunnel(state, action)     {
            state.funnels = state.funnels.filter(f => Number(f.id) !== Number(action.payload));
        },
        setLogs(state, action)          { state.logs            = action.payload; },
        setLogsTotal(state, action)     { state.logs_total      = action.payload; },
        setLoading(state, action)           { state.loading              = action.payload; },
        setAvailableTriggers(state, action) { state.available_triggers   = action.payload; },
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
} = capfwSlice.actions;

export default capfwSlice.reducer;
