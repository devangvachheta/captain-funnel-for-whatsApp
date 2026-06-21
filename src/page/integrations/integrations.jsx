import React, { useEffect, useState, useCallback, useRef } from 'react';
import { __ } from '@wordpress/i18n';
import Spinner from '../../components/Spinner/Spinner.jsx';
import './integrations.scss';

const ajaxData = () => ({
    ajax_url: typeof capfw_data !== 'undefined' ? capfw_data.ajax_url : '',
    nonce:    typeof capfw_data !== 'undefined' ? capfw_data.nonce    : '',
});

const CATEGORY_ORDER = [
    'E-commerce', 'Form Submissions', 'User Registration',
    'Booking Systems', 'Membership', 'LMS', 'Custom Automation',
];

const CATEGORY_ICONS = {
    'E-commerce':        '🛒',
    'Form Submissions':  '📝',
    'User Registration': '👤',
    'Booking Systems':   '📅',
    'Membership':        '🔑',
    'LMS':               '🎓',
    'Custom Automation': '⚙️',
};

const DEFAULT_MSG_SETTINGS = {
    message_type:          'text',
    template_name:         '',
    template_language:     'en_US',
    template_no_variables: false,
};

// ─── Gear Icon SVG ────────────────────────────────────────────────────────────
const GearIcon = () => (
    <svg width="15" height="15" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M8.325 2.317a1.75 1.75 0 0 1 3.35 0l.23.887a1.75 1.75 0 0 0 2.608.977l.783-.484a1.75 1.75 0 0 1 2.369 2.369l-.484.783a1.75 1.75 0 0 0 .977 2.608l.887.23a1.75 1.75 0 0 1 0 3.35l-.887.23a1.75 1.75 0 0 0-.977 2.608l.484.783a1.75 1.75 0 0 1-2.369 2.369l-.783-.484a1.75 1.75 0 0 0-2.608.977l-.23.887a1.75 1.75 0 0 1-3.35 0l-.23-.887a1.75 1.75 0 0 0-2.608-.977l-.783.484a1.75 1.75 0 0 1-2.369-2.369l.484-.783A1.75 1.75 0 0 0 2.842 12.4l-.887-.23a1.75 1.75 0 0 1 0-3.35l.887-.23a1.75 1.75 0 0 0 .977-2.608l-.484-.783a1.75 1.75 0 0 1 2.369-2.369l.783.484a1.75 1.75 0 0 0 2.608-.977l.23-.887ZM10 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="currentColor"/>
    </svg>
);

// ─── Message Settings Modal ───────────────────────────────────────────────────
const MsgSettingsModal = ({ slug, label, value, onChange, onClose, onSave, saving }) => {
    const cfg     = { ...DEFAULT_MSG_SETTINGS, ...value };
    const set     = (key, val) => onChange({ ...cfg, [key]: val });
    const modalRef = useRef(null);

    // Close on Escape
    useEffect(() => {
        const handler = (e) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [onClose]);

    // Close on backdrop click
    const handleBackdrop = (e) => {
        if (e.target === e.currentTarget) onClose();
    };

    return (
        <div className="capfw-modal-backdrop" onClick={handleBackdrop}>
            <div className="capfw-modal" ref={modalRef} role="dialog" aria-modal="true">

                {/* Header */}
                <div className="capfw-modal-header">
                    <div className="capfw-modal-title">
                        <GearIcon />
                        <span>{__('Message Settings', 'captain-funnel-for-whatsapp')}</span>
                        <span className="capfw-modal-integration-label">{label}</span>
                    </div>
                    <button className="capfw-modal-close" onClick={onClose} aria-label="Close">✕</button>
                </div>

                {/* Body */}
                <div className="capfw-modal-body">

                    {/* Message Type */}
                    <div className="capfw-modal-field">
                        <label className="capfw-modal-field-label">
                            {__('Message Type', 'captain-funnel-for-whatsapp')}
                        </label>
                        <div className="capfw-msg-type-pills">
                            {[
                                { val: 'text',     icon: '💬', text: __('Text', 'captain-funnel-for-whatsapp'),     desc: __('Free-form · 24h window', 'captain-funnel-for-whatsapp') },
                                { val: 'template', icon: '📋', text: __('Template', 'captain-funnel-for-whatsapp'), desc: __('Approved · Always delivers', 'captain-funnel-for-whatsapp') },
                            ].map(({ val, icon, text, desc }) => (
                                <label
                                    key={val}
                                    className={`capfw-msg-type-pill${cfg.message_type === val ? ' capfw-msg-type-pill--active' : ''}`}
                                >
                                    <input
                                        type="radio"
                                        name={`capfw_modal_type_${slug}`}
                                        value={val}
                                        checked={cfg.message_type === val}
                                        onChange={() => set('message_type', val)}
                                        style={{ display: 'none' }}
                                    />
                                    <span className="capfw-msg-type-pill-icon">{icon}</span>
                                    <span className="capfw-msg-type-pill-text">{text}</span>
                                    <span className="capfw-msg-type-pill-desc">{desc}</span>
                                </label>
                            ))}
                        </div>
                        {cfg.message_type === 'text' && (
                            <p className="capfw-modal-warn">
                                ⚠️ {__('Text messages only deliver if the customer messaged you in the last 24 hours. Use Template for guaranteed delivery.', 'captain-funnel-for-whatsapp')}
                            </p>
                        )}
                    </div>

                    {/* Template fields */}
                    {cfg.message_type === 'template' && (
                        <>
                            <div className="capfw-modal-row-2">
                                <div className="capfw-modal-field">
                                    <label className="capfw-modal-field-label">
                                        {__('Template Name', 'captain-funnel-for-whatsapp')}
                                    </label>
                                    <input
                                        type="text"
                                        className="capfw-input"
                                        value={cfg.template_name}
                                        onChange={e => set('template_name', e.target.value)}
                                        placeholder="hello_world"
                                        autoFocus
                                    />
                                    <span className="capfw-desc">{__('Exact name from Meta Business Manager.', 'captain-funnel-for-whatsapp')}</span>
                                </div>
                                <div className="capfw-modal-field">
                                    <label className="capfw-modal-field-label">
                                        {__('Template Language', 'captain-funnel-for-whatsapp')}
                                    </label>
                                    <input
                                        type="text"
                                        className="capfw-input"
                                        value={cfg.template_language}
                                        onChange={e => set('template_language', e.target.value)}
                                        placeholder="en_US"
                                    />
                                    <span className="capfw-desc">{__('e.g. en_US, hi, en', 'captain-funnel-for-whatsapp')}</span>
                                </div>
                            </div>

                            <div className="capfw-modal-field capfw-modal-checkbox-field">
                                <label className="capfw-modal-checkbox-label">
                                    <input
                                        type="checkbox"
                                        className="capfw-modal-checkbox"
                                        checked={!!cfg.template_no_variables}
                                        onChange={e => set('template_no_variables', e.target.checked)}
                                    />
                                    <div>
                                        <span className="capfw-modal-checkbox-title">
                                            {__('Template has no variables', 'captain-funnel-for-whatsapp')}
                                        </span>
                                        <span className="capfw-desc" style={{ display: 'block', marginTop: '2px' }}>
                                            {__('Enable if your template has zero placeholders (e.g. hello_world). Leave unchecked if it has a {{1}} body variable.', 'captain-funnel-for-whatsapp')}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </>
                    )}
                </div>

                {/* Footer */}
                <div className="capfw-modal-footer">
                    <button className="capfw-btn-secondary" onClick={onClose}>
                        {__('Cancel', 'captain-funnel-for-whatsapp')}
                    </button>
                    <button className="capfw-btn-primary" onClick={onSave} disabled={saving}>
                        {saving
                            ? <><Spinner size={13} color="#fff" /> {__('Saving…', 'captain-funnel-for-whatsapp')}</>
                            : __('Save Settings', 'captain-funnel-for-whatsapp')
                        }
                    </button>
                </div>
            </div>
        </div>
    );
};

// ─── Main Page ────────────────────────────────────────────────────────────────
const Integrations = () => {
    const [integrations, setIntegrations] = useState([]);
    const [enabled,      setEnabled]      = useState([]);
    const [msgSettings,  setMsgSettings]  = useState({});
    const [modalSlug,    setModalSlug]    = useState(null);   // which integration's modal is open
    const [modalDraft,   setModalDraft]   = useState({});     // working copy while modal open
    const [modalSaving,  setModalSaving]  = useState(false);
    const [loading,      setLoading]      = useState(true);
    const [saveStatus,   setSaveStatus]   = useState('');
    const [fetchError,   setFetchError]   = useState('');

    const fetchAll = useCallback(async () => {
        setFetchError('');
        const { ajax_url, nonce } = ajaxData();
        const post = (type) => {
            const fd = new FormData();
            fd.append('action', 'capfw_react_ajax');
            fd.append('nonce', nonce);
            fd.append('type', type);
            return fetch(ajax_url, { method: 'POST', body: fd }).then(r => r.json());
        };
        try {
            const [intRes, msgRes] = await Promise.all([
                post('get_integrations'),
                post('get_integration_msg_settings'),
            ]);
            if (intRes.success && intRes.data) {
                setIntegrations(intRes.data);
                setEnabled(intRes.data.filter(i => i.enabled).map(i => i.slug));
            } else {
                setFetchError(__('Failed to load integrations.', 'captain-funnel-for-whatsapp'));
            }
            if (msgRes.success && msgRes.data) setMsgSettings(msgRes.data);
        } catch {
            setFetchError(__('Network error. Please refresh.', 'captain-funnel-for-whatsapp'));
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { fetchAll(); }, [fetchAll]);

    const toggleIntegration = (slug) => {
        setEnabled(prev =>
            prev.includes(slug) ? prev.filter(s => s !== slug) : [...prev, slug]
        );
    };

    // Open modal with a draft copy of current settings
    const openModal = (slug) => {
        setModalDraft(msgSettings[slug] || { ...DEFAULT_MSG_SETTINGS });
        setModalSlug(slug);
    };

    const closeModal = () => {
        setModalSlug(null);
        setModalDraft({});
        setModalSaving(false);
    };

    // Save just this integration's msg settings + commit to state
    const saveModal = async () => {
        setModalSaving(true);
        const newSettings = { ...msgSettings, [modalSlug]: modalDraft };
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action', 'capfw_react_ajax');
        fd.append('nonce', nonce);
        fd.append('type', 'save_integration_msg_settings');
        fd.append('integration_settings', JSON.stringify(newSettings));
        try {
            const res = await fetch(ajax_url, { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                setMsgSettings(newSettings);
                closeModal();
            }
        } catch {}
        setModalSaving(false);
    };

    // Save integration toggles
    const handleSave = async () => {
        setSaveStatus('saving');
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action', 'capfw_react_ajax');
        fd.append('nonce', nonce);
        fd.append('type', 'save_integrations');
        fd.append('enabled', JSON.stringify(enabled));
        try {
            const res = await fetch(ajax_url, { method: 'POST', body: fd }).then(r => r.json());
            setSaveStatus(res.success ? 'saved' : 'error');
        } catch {
            setSaveStatus('error');
        }
        setTimeout(() => setSaveStatus(''), 3000);
    };

    // Group by category
    const grouped = {};
    CATEGORY_ORDER.forEach(cat => { grouped[cat] = []; });
    integrations.forEach(int => {
        if (!grouped[int.category]) grouped[int.category] = [];
        grouped[int.category].push(int);
    });

    const activeModal = integrations.find(i => i.slug === modalSlug);

    if (loading) {
        return (
            <div className="capfw-integrations-page">
                <div className="capfw-page-header">
                    <h2 className="capfw-page-title">{__('Integrations', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                <div style={{ display: 'flex', justifyContent: 'center', padding: '48px' }}>
                    <Spinner size={32} />
                </div>
            </div>
        );
    }

    return (
        <div className="capfw-integrations-page">

            {/* Page Header */}
            <div className="capfw-page-header capfw-page-header--flex">
                <div>
                    <h2 className="capfw-page-title">{__('Integrations', 'captain-funnel-for-whatsapp')}</h2>
                    <p className="capfw-page-subtitle">
                        {__('Enable integrations and click ⚙ to configure per-integration WhatsApp message settings.', 'captain-funnel-for-whatsapp')}
                    </p>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    {saveStatus === 'saved' && <span className="capfw-msg capfw-msg--success">✓ {__('Saved!', 'captain-funnel-for-whatsapp')}</span>}
                    {saveStatus === 'error'  && <span className="capfw-msg capfw-msg--error">✗ {__('Failed.', 'captain-funnel-for-whatsapp')}</span>}
                    <button className="capfw-btn-primary" onClick={handleSave} disabled={saveStatus === 'saving'}>
                        {saveStatus === 'saving'
                            ? <><Spinner size={14} color="#fff" /> {__('Saving…', 'captain-funnel-for-whatsapp')}</>
                            : __('Save Integrations', 'captain-funnel-for-whatsapp')
                        }
                    </button>
                </div>
            </div>

            {fetchError && <p className="capfw-error-msg">⚠ {fetchError}</p>}

            {/* Integration Cards */}
            {CATEGORY_ORDER.map(category => {
                const items = grouped[category] || [];
                if (!items.length) return null;
                return (
                    <div key={category} className="capfw-int-section">
                        <div className="capfw-int-section-header">
                            <span className="capfw-int-section-icon">{CATEGORY_ICONS[category]}</span>
                            <h3 className="capfw-int-section-title">{category}</h3>
                        </div>
                        <div className="capfw-int-grid">
                            {items.map(integration => {
                                const { slug }  = integration;
                                const isEnabled   = enabled.includes(slug);
                                const isAvailable = integration.available;
                                const cfg         = msgSettings[slug] || DEFAULT_MSG_SETTINGS;
                                const msgBadge    = cfg.message_type === 'template'
                                    ? (cfg.template_name || 'template')
                                    : 'text';

                                return (
                                    <div
                                        key={slug}
                                        className={`capfw-int-card${isEnabled ? ' capfw-int-card--on' : ''}${!isAvailable ? ' capfw-int-card--unavailable' : ''}`}
                                    >
                                        {/* Top row: name + gear + toggle */}
                                        <div className="capfw-int-card-top">
                                            <div className="capfw-int-card-info">
                                                <span className="capfw-int-name">{integration.label}</span>
                                                <span className={`capfw-int-status-pill ${isAvailable ? 'capfw-int-status-pill--installed' : 'capfw-int-status-pill--missing'}`}>
                                                    {isAvailable
                                                        ? __('Installed', 'captain-funnel-for-whatsapp')
                                                        : __('Plugin not installed', 'captain-funnel-for-whatsapp')
                                                    }
                                                </span>
                                            </div>

                                            <div className="capfw-int-card-actions">
                                                {/* Gear icon — only when enabled */}
                                                {isEnabled && isAvailable && (
                                                    <button
                                                        type="button"
                                                        className="capfw-int-gear-btn"
                                                        onClick={() => openModal(slug)}
                                                        title={__('Message Settings', 'captain-funnel-for-whatsapp')}
                                                    >
                                                        <GearIcon />
                                                    </button>
                                                )}

                                                {/* Toggle */}
                                                <label className={`capfw-toggle${!isAvailable ? ' capfw-toggle--disabled' : ''}`}>
                                                    <input
                                                        type="checkbox"
                                                        checked={isEnabled}
                                                        disabled={!isAvailable}
                                                        onChange={() => isAvailable && toggleIntegration(slug)}
                                                    />
                                                    <span className="capfw-toggle-slider" />
                                                </label>
                                            </div>
                                        </div>

                                        {integration.plugin_file && (
                                            <p className="capfw-int-plugin-file">{integration.plugin_file}</p>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );
            })}

            {/* Modal */}
            {modalSlug && activeModal && (
                <MsgSettingsModal
                    slug={modalSlug}
                    label={activeModal.label}
                    value={modalDraft}
                    onChange={setModalDraft}
                    onClose={closeModal}
                    onSave={saveModal}
                    saving={modalSaving}
                />
            )}
        </div>
    );
};

export default Integrations;
