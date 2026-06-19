import React, { useEffect, useState, useCallback } from 'react';
import { __ }  from '@wordpress/i18n';
import Spinner from '../../components/Spinner/Spinner.jsx';
import './templates.scss';

const ajaxData = () => ({
    ajax_url: typeof capfw_data !== 'undefined' ? capfw_data.ajax_url : '',
    nonce:    typeof capfw_data !== 'undefined' ? capfw_data.nonce    : '',
});

const CATEGORY_ORDER = [
    'E-commerce', 'Form Submissions', 'User Registration',
    'Booking Systems', 'Membership', 'LMS', 'Custom Automation',
];

const Templates = () => {
    // All triggers from enabled integrations
    const [triggers,     setTriggers]     = useState([]);
    // templates[integration_slug][trigger_key] = { body, status }
    const [templates,    setTemplates]    = useState({});
    const [loading,      setLoading]      = useState(true);
    const [fetchError,   setFetchError]   = useState('');
    // Active tab: { integration, trigger_key }
    const [activeInt,    setActiveInt]    = useState('');
    const [activeTrigger,setActiveTrigger]= useState('');
    const [saveStatus,   setSaveStatus]   = useState('');
    const [copiedVar,    setCopiedVar]    = useState('');
    const [serverHasData,setServerHasData]= useState(false);

    // ── Fetch triggers + templates ────────────────────────────────────────────
    const fetchAll = useCallback(async () => {
        setFetchError('');
        const { ajax_url, nonce } = ajaxData();

        const makeReq = (type, extra = {}) => {
            const fd = new FormData();
            fd.append('action', 'capfw_react_ajax');
            fd.append('nonce',  nonce);
            fd.append('type',   type);
            Object.entries(extra).forEach(([k, v]) => fd.append(k, v));
            return fetch(ajax_url, { method: 'POST', body: fd }).then(r => r.json());
        };

        try {
            const triggersRes = await makeReq('get_available_triggers');
            if (!triggersRes.success || !triggersRes.data?.length) {
                setFetchError(__('No active integrations found. Please enable integrations first.', 'captain-funnel-for-whatsapp'));
                setLoading(false);
                return;
            }

            const allTriggers = triggersRes.data;
            setTriggers(allTriggers);

            // Set first trigger as active
            const firstTrigger = allTriggers[0];
            setActiveInt(firstTrigger.integration);
            setActiveTrigger(firstTrigger.key);

            // Fetch templates for every unique integration slug
            const slugs = [...new Set(allTriggers.map(t => t.integration))];
            const results = await Promise.all(
                slugs.map(slug => makeReq('get_integration_templates', { integration_slug: slug }))
            );

            const allTemplates = {};
            slugs.forEach((slug, i) => {
                allTemplates[slug] = results[i].data || {};
            });

            setTemplates(allTemplates);

            // Check if any templates are saved
            const hasAny = Object.values(allTemplates).some(
                intTpls => Object.values(intTpls).some(t => t.body && t.body.trim())
            );
            setServerHasData(hasAny);

        } catch {
            setFetchError(__('Network error loading templates.', 'captain-funnel-for-whatsapp'));
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { fetchAll(); }, [fetchAll]);

    // ── Save single template ──────────────────────────────────────────────────
    const handleSave = async () => {
        if (!activeInt || !activeTrigger) return;
        setSaveStatus('saving');

        const body = templates[activeInt]?.[activeTrigger]?.body || '';
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action',           'capfw_react_ajax');
        fd.append('nonce',            nonce);
        fd.append('type',             'save_integration_template');
        fd.append('integration_slug', activeInt);
        fd.append('trigger_key',      activeTrigger);
        fd.append('template_body',    body);
        fd.append('status',           'active');

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();
            if (result.success) {
                setServerHasData(true);
                setSaveStatus('saved');
                // Update local status
                setTemplates(prev => ({
                    ...prev,
                    [activeInt]: {
                        ...prev[activeInt],
                        [activeTrigger]: { body, status: 'active' },
                    },
                }));
            } else {
                setSaveStatus('error');
            }
        } catch {
            setSaveStatus('error');
        }
        setTimeout(() => setSaveStatus(''), 3000);
    };

    // ── Template body change ──────────────────────────────────────────────────
    const handleBodyChange = (value) => {
        setTemplates(prev => ({
            ...prev,
            [activeInt]: {
                ...prev[activeInt],
                [activeTrigger]: { ...(prev[activeInt]?.[activeTrigger] || {}), body: value },
            },
        }));
    };

    // ── Insert variable at cursor ─────────────────────────────────────────────
    const insertVar = (varName) => {
        const taId = `capfw-tmpl-${activeInt}-${activeTrigger}`;
        const ta   = document.getElementById(taId);
        if (!ta) return;
        ta.focus();
        const start   = ta.selectionStart;
        const end     = ta.selectionEnd;
        const current = templates[activeInt]?.[activeTrigger]?.body || '';
        const updated = current.substring(0, start) + varName + current.substring(end);
        handleBodyChange(updated);
        setTimeout(() => {
            ta.focus();
            ta.setSelectionRange(start + varName.length, start + varName.length);
        }, 0);
        setCopiedVar(varName);
        setTimeout(() => setCopiedVar(''), 1400);
    };

    // ── Group triggers by category ────────────────────────────────────────────
    const grouped = {};
    CATEGORY_ORDER.forEach(cat => { grouped[cat] = []; });
    triggers.forEach(t => {
        if (!grouped[t.category]) grouped[t.category] = [];
        grouped[t.category].push(t);
    });

    // Active trigger object
    const activeTriggerObj = triggers.find(t => t.key === activeTrigger && t.integration === activeInt);
    const currentBody      = templates[activeInt]?.[activeTrigger]?.body || '';
    const isSaved          = !!(templates[activeInt]?.[activeTrigger]?.body);

    if (loading) {
        return (
            <div className="capfw-templates-page">
                <div className="capfw-page-header">
                    <h2 className="capfw-page-title">{__('Message Templates', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                <div style={{display:'flex',justifyContent:'center',padding:'48px'}}>
                    <Spinner size={32} />
                </div>
            </div>
        );
    }

    if (fetchError) {
        return (
            <div className="capfw-templates-page">
                <div className="capfw-page-header">
                    <h2 className="capfw-page-title">{__('Message Templates', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                <div className="capfw-card" style={{textAlign:'center',padding:'32px'}}>
                    <p className="capfw-error-msg" style={{marginBottom:'16px'}}>⚠ {fetchError}</p>
                    <a href="#/integrations" className="capfw-btn-primary" style={{display:'inline-flex'}}>
                        {__('Go to Integrations', 'captain-funnel-for-whatsapp')}
                    </a>
                </div>
            </div>
        );
    }

    return (
        <div className="capfw-templates-page">
            {/* Header */}
            <div className="capfw-page-header">
                <h2 className="capfw-page-title">{__('Message Templates', 'captain-funnel-for-whatsapp')}</h2>
                <p className="capfw-page-subtitle">
                    {__('Set the WhatsApp message for each trigger. Only enabled integrations are shown.', 'captain-funnel-for-whatsapp')}
                </p>
            </div>

            {!serverHasData && (
                <div className="capfw-notice-banner">
                    ℹ {__('No templates saved yet. Select a trigger, write your message, and click Save.', 'captain-funnel-for-whatsapp')}
                </div>
            )}

            <div className="capfw-templates-layout">
                {/* Left sidebar — trigger list */}
                <aside className="capfw-tmpl-sidebar">
                    {CATEGORY_ORDER.map(category => {
                        const items = grouped[category] || [];
                        if (!items.length) return null;
                        return (
                            <div key={category} className="capfw-tmpl-group">
                                <div className="capfw-tmpl-group-title">{category}</div>
                                {items.map(trigger => {
                                    const isActive  = activeInt === trigger.integration && activeTrigger === trigger.key;
                                    const hasTmpl   = !!(templates[trigger.integration]?.[trigger.key]?.body);
                                    return (
                                        <button
                                            key={trigger.key}
                                            type="button"
                                            className={`capfw-tmpl-trigger-btn${isActive ? ' capfw-tmpl-trigger-btn--active' : ''}`}
                                            onClick={() => { setActiveInt(trigger.integration); setActiveTrigger(trigger.key); }}
                                        >
                                            <span className="capfw-tmpl-trigger-name">{trigger.label}</span>
                                            <span className="capfw-tmpl-trigger-int">{trigger.int_label}</span>
                                            {hasTmpl && <span className="capfw-tmpl-saved-dot" title="Template saved" />}
                                        </button>
                                    );
                                })}
                            </div>
                        );
                    })}
                </aside>

                {/* Right editor panel */}
                <div className="capfw-tmpl-editor-panel">
                    {activeTriggerObj ? (
                        <>
                            {/* Trigger info */}
                            <div className="capfw-tmpl-editor-header">
                                <div>
                                    <h3 className="capfw-tmpl-editor-title">{activeTriggerObj.label}</h3>
                                    <p className="capfw-tmpl-editor-desc">{activeTriggerObj.description}</p>
                                </div>
                                <span className="capfw-trigger-pill">{activeTriggerObj.int_label}</span>
                            </div>

                            {/* Variable chips */}
                            {activeTriggerObj.variables?.length > 0 && (
                                <div className="capfw-vars-section">
                                    <div className="capfw-vars-label">
                                        {__('Click to insert variable:', 'captain-funnel-for-whatsapp')}
                                    </div>
                                    <div className="capfw-vars-row">
                                        {activeTriggerObj.variables.map(v => (
                                            <button
                                                key={v}
                                                type="button"
                                                className={`capfw-var-chip${copiedVar === v ? ' capfw-var-chip--active' : ''}`}
                                                onClick={() => insertVar(v)}
                                            >
                                                {copiedVar === v ? '✓ ' : ''}{v}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Textarea */}
                            <textarea
                                id={`capfw-tmpl-${activeInt}-${activeTrigger}`}
                                className="capfw-textarea capfw-tmpl-textarea"
                                rows={12}
                                value={currentBody}
                                onChange={e => handleBodyChange(e.target.value)}
                                placeholder={__('Write your WhatsApp message here. Use variables above to insert dynamic data.', 'captain-funnel-for-whatsapp')}
                            />
                            <div className="capfw-char-count">
                                {currentBody.length} {__('characters', 'captain-funnel-for-whatsapp')}
                                {isSaved && <span className="capfw-tmpl-saved-label">✓ {__('Saved', 'captain-funnel-for-whatsapp')}</span>}
                            </div>

                            {/* Save row */}
                            <div className="capfw-tmpl-save-row">
                                {saveStatus === 'saved' && <span className="capfw-msg capfw-msg--success">✓ {__('Template saved!', 'captain-funnel-for-whatsapp')}</span>}
                                {saveStatus === 'error' && <span className="capfw-msg capfw-msg--error">✗ {__('Failed to save.', 'captain-funnel-for-whatsapp')}</span>}
                                <button
                                    className="capfw-btn-primary"
                                    onClick={handleSave}
                                    disabled={saveStatus === 'saving'}
                                >
                                    {saveStatus === 'saving'
                                        ? <><Spinner size={14} color="#fff" /> {__('Saving…', 'captain-funnel-for-whatsapp')}</>
                                        : __('Save Template', 'captain-funnel-for-whatsapp')
                                    }
                                </button>
                            </div>
                        </>
                    ) : (
                        <div className="capfw-tmpl-empty-editor">
                            <p>{__('Select a trigger from the list to edit its template.', 'captain-funnel-for-whatsapp')}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Templates;
