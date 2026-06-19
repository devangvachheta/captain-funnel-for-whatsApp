import React, { useEffect, useState, useCallback } from 'react';
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

const Integrations = () => {
    const [integrations, setIntegrations] = useState([]);
    const [enabled,      setEnabled]      = useState([]);
    const [loading,      setLoading]      = useState(true);
    const [saveStatus,   setSaveStatus]   = useState('');
    const [fetchError,   setFetchError]   = useState('');

    const fetchIntegrations = useCallback(async () => {
        setFetchError('');
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action', 'capfw_react_ajax');
        fd.append('nonce',  nonce);
        fd.append('type',   'get_integrations');

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();
            if (result.success && result.data) {
                setIntegrations(result.data);
                setEnabled(result.data.filter(i => i.enabled).map(i => i.slug));
            } else {
                setFetchError(__('Failed to load integrations.', 'captain-funnel-for-whatsapp'));
            }
        } catch {
            setFetchError(__('Network error. Please refresh.', 'captain-funnel-for-whatsapp'));
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { fetchIntegrations(); }, [fetchIntegrations]);

    const toggleIntegration = (slug) => {
        setEnabled(prev =>
            prev.includes(slug) ? prev.filter(s => s !== slug) : [...prev, slug]
        );
    };

    const handleSave = async () => {
        setSaveStatus('saving');
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action',  'capfw_react_ajax');
        fd.append('nonce',   nonce);
        fd.append('type',    'save_integrations');
        fd.append('enabled', JSON.stringify(enabled));

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();
            setSaveStatus(result.success ? 'saved' : 'error');
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

    if (loading) {
        return (
            <div className="capfw-integrations-page">
                <div className="capfw-page-header">
                    <h2 className="capfw-page-title">{__('Integrations', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                <div style={{display:'flex',justifyContent:'center',padding:'48px'}}>
                    <Spinner size={32} />
                </div>
            </div>
        );
    }

    return (
        <div className="capfw-integrations-page">
            <div className="capfw-page-header capfw-page-header--flex">
                <div>
                    <h2 className="capfw-page-title">{__('Integrations', 'captain-funnel-for-whatsapp')}</h2>
                    <p className="capfw-page-subtitle">
                        {__('Enable integrations to send WhatsApp messages from any WordPress plugin. Only installed plugins are available.', 'captain-funnel-for-whatsapp')}
                    </p>
                </div>
                <div style={{display:'flex',alignItems:'center',gap:'12px'}}>
                    {saveStatus === 'saved' && <span className="capfw-msg capfw-msg--success">✓ {__('Saved!', 'captain-funnel-for-whatsapp')}</span>}
                    {saveStatus === 'error' && <span className="capfw-msg capfw-msg--error">✗ {__('Failed.', 'captain-funnel-for-whatsapp')}</span>}
                    <button
                        className="capfw-btn-primary"
                        onClick={handleSave}
                        disabled={saveStatus === 'saving'}
                    >
                        {saveStatus === 'saving'
                            ? <><Spinner size={14} color="#fff" /> {__('Saving…', 'captain-funnel-for-whatsapp')}</>
                            : __('Save Integrations', 'captain-funnel-for-whatsapp')
                        }
                    </button>
                </div>
            </div>

            {fetchError && <p className="capfw-error-msg">⚠ {fetchError}</p>}

            {CATEGORY_ORDER.map(category => {
                const items = grouped[category] || [];
                if (items.length === 0) return null;
                return (
                    <div key={category} className="capfw-int-section">
                        <div className="capfw-int-section-header">
                            <span className="capfw-int-section-icon">{CATEGORY_ICONS[category]}</span>
                            <h3 className="capfw-int-section-title">{category}</h3>
                        </div>
                        <div className="capfw-int-grid">
                            {items.map(integration => {
                                const isEnabled   = enabled.includes(integration.slug);
                                const isAvailable = integration.available;
                                return (
                                    <div
                                        key={integration.slug}
                                        className={`capfw-int-card${isEnabled ? ' capfw-int-card--on' : ''}${!isAvailable ? ' capfw-int-card--unavailable' : ''}`}
                                    >
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
                                            <label className={`capfw-toggle${!isAvailable ? ' capfw-toggle--disabled' : ''}`}>
                                                <input
                                                    type="checkbox"
                                                    checked={isEnabled}
                                                    disabled={!isAvailable}
                                                    onChange={() => isAvailable && toggleIntegration(integration.slug)}
                                                />
                                                <span className="capfw-toggle-slider" />
                                            </label>
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
        </div>
    );
};

export default Integrations;
