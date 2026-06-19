import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector }    from 'react-redux';
import { __ }                          from '@wordpress/i18n';
import { setSettings, updateSetting }  from '../../redux/slice.js';
import Spinner                         from '../../components/Spinner/Spinner.jsx';
import './settings.scss';

const ALL_STATUSES = [
    { key: 'pending',    label: __('Pending Payment', 'captain-funnel-for-whatsapp') },
    { key: 'processing', label: __('Processing',      'captain-funnel-for-whatsapp') },
    { key: 'on-hold',    label: __('On Hold',          'captain-funnel-for-whatsapp') },
    { key: 'completed',  label: __('Completed',        'captain-funnel-for-whatsapp') },
    { key: 'cancelled',  label: __('Cancelled',        'captain-funnel-for-whatsapp') },
    { key: 'refunded',   label: __('Refunded',         'captain-funnel-for-whatsapp') },
    { key: 'failed',     label: __('Failed',           'captain-funnel-for-whatsapp') },
];

const ajaxData = () => ({
    ajax_url: typeof capfw_data !== 'undefined' ? capfw_data.ajax_url : '',
    nonce:    typeof capfw_data !== 'undefined' ? capfw_data.nonce    : '',
});

const Settings = () => {
    const dispatch  = useDispatch();
    const settings  = useSelector(s => s.capfw.settings);

    const [loading,    setLoading]    = useState(true);
    const [fetchError, setFetchError] = useState('');
    // FIX High #1: Local save_status — not global Redux state
    const [saveStatus, setSaveStatus] = useState('');
    const [testing,    setTesting]    = useState(false);
    const [testMsg,    setTestMsg]    = useState({ text: '', ok: true });
    const [showToken,  setShowToken]  = useState(false);

    useEffect(() => {
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action', 'capfw_react_ajax');
        fd.append('nonce',  nonce);
        fd.append('type',   'get_settings');

        fetch(ajax_url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) dispatch(setSettings(res.data));
                else setFetchError(__('Failed to load settings.', 'captain-funnel-for-whatsapp'));
            })
            .catch(() => setFetchError(__('Network error loading settings.', 'captain-funnel-for-whatsapp')))
            .finally(() => setLoading(false));
    }, [dispatch]);

    const handleSave = async () => {
        setSaveStatus('saving');
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action',   'capfw_react_ajax');
        fd.append('nonce',    nonce);
        fd.append('type',     'save_settings');
        fd.append('settings', JSON.stringify(settings));

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();
            setSaveStatus(result.success ? 'saved' : 'error');
            if (result.success) setShowToken(false);
        } catch {
            setSaveStatus('error');
        }
        setTimeout(() => setSaveStatus(''), 3000);
    };

    // FIX Critical #3: Send live form credentials with test request
    const handleTest = async () => {
        setTesting(true);
        setTestMsg({ text: '', ok: true });
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action',          'capfw_react_ajax');
        fd.append('nonce',           nonce);
        fd.append('type',            'test_connection');
        fd.append('access_token',    settings.access_token    || '');
        fd.append('phone_number_id', settings.phone_number_id || '');

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();
            setTestMsg({ text: result.data?.message || '', ok: result.success });
        } catch {
            setTestMsg({ text: __('Network error.', 'captain-funnel-for-whatsapp'), ok: false });
        } finally {
            setTesting(false);
        }
    };

    const toggleStatus = (key) => {
        const current = settings.enabled_statuses || [];
        const updated = current.includes(key)
            ? current.filter(s => s !== key)
            : [...current, key];
        dispatch(updateSetting({ key: 'enabled_statuses', value: updated }));
    };

    if (loading) {
        return (
            <div className="capfw-settings-page">
                <div className="capfw-page-header">
                    <h2 className="capfw-page-title">{__('WhatsApp Settings', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                <div className="capfw-settings-spinner"><Spinner size={32} /></div>
            </div>
        );
    }

    if (fetchError) {
        return (
            <div className="capfw-settings-page">
                <div className="capfw-page-header">
                    <h2 className="capfw-page-title">{__('WhatsApp Settings', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                <div className="capfw-card">
                    <p className="capfw-error-msg">⚠ {fetchError}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="capfw-settings-page">
            <div className="capfw-page-header">
                <h2 className="capfw-page-title">{__('WhatsApp Settings', 'captain-funnel-for-whatsapp')}</h2>
                <p className="capfw-page-subtitle">
                    {__('Configure your WhatsApp Cloud API credentials.', 'captain-funnel-for-whatsapp')}
                    {' '}
                    <a href="#/docs/credentials" className="capfw-settings-doc-link">
                        {__('📖 How to get credentials?', 'captain-funnel-for-whatsapp')}
                    </a>
                </p>
            </div>

            {/* API Credentials */}
            <div className="capfw-card">
                <h3 className="capfw-card-title">{__('WhatsApp Cloud API Credentials', 'captain-funnel-for-whatsapp')}</h3>

                <div className="capfw-form-group">
                    <label>{__('Access Token', 'captain-funnel-for-whatsapp')}</label>
                    <div className="capfw-input-wrap">
                        <input
                            type={showToken ? 'text' : 'password'}
                            className="capfw-input"
                            value={settings.access_token || ''}
                            onChange={e => dispatch(updateSetting({ key: 'access_token', value: e.target.value }))}
                            placeholder="EAAxxxxxxxxxxxxxxx"
                            autoComplete="off"
                        />
                        <button type="button" className="capfw-eye-btn" onClick={() => setShowToken(v => !v)}>
                            {showToken ? (
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            ) : (
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            )}
                        </button>
                    </div>
                    <span className="capfw-desc">{__('Enter your permanent access token from Meta Business Manager.', 'captain-funnel-for-whatsapp')}</span>
                </div>

                <div className="capfw-settings-row-2">
                    <div className="capfw-form-group">
                        <label>{__('Phone Number ID', 'captain-funnel-for-whatsapp')}</label>
                        <input
                            type="text" className="capfw-input"
                            value={settings.phone_number_id || ''}
                            onChange={e => dispatch(updateSetting({ key: 'phone_number_id', value: e.target.value }))}
                            placeholder="1234567890"
                        />
                    </div>
                    <div className="capfw-form-group">
                        <label>{__('Business Account ID', 'captain-funnel-for-whatsapp')}</label>
                        <input
                            type="text" className="capfw-input"
                            value={settings.business_account_id || ''}
                            onChange={e => dispatch(updateSetting({ key: 'business_account_id', value: e.target.value }))}
                            placeholder="9876543210"
                        />
                    </div>
                </div>

                <div className="capfw-test-row">
                    <button type="button" className="capfw-btn-secondary" onClick={handleTest} disabled={testing}>
                        {testing ? <><Spinner size={14} />{' '}{__('Testing…', 'captain-funnel-for-whatsapp')}</> : __('Test Connection', 'captain-funnel-for-whatsapp')}
                    </button>
                    {testMsg.text && (
                        <span className={`capfw-msg ${testMsg.ok ? 'capfw-msg--success' : 'capfw-msg--error'}`}>
                            {testMsg.ok ? '✓' : '✗'} {testMsg.text}
                        </span>
                    )}
                </div>
            </div>

            {/* Admin Notification Phone — FIX High #6 */}
            <div className="capfw-card">
                <h3 className="capfw-card-title">{__('Admin Notifications', 'captain-funnel-for-whatsapp')}</h3>
                <div className="capfw-form-group">
                    <label>{__('Admin WhatsApp Number', 'captain-funnel-for-whatsapp')}</label>
                    <input
                        type="text" className="capfw-input" style={{maxWidth:'320px'}}
                        value={settings.admin_phone || ''}
                        onChange={e => dispatch(updateSetting({ key: 'admin_phone', value: e.target.value }))}
                        placeholder="919876543210"
                    />
                    <span className="capfw-desc">{__('Receive notifications for new, failed, and high-value orders. Use E.164 format (country code + number).', 'captain-funnel-for-whatsapp')}</span>
                </div>
            </div>

            {/* Enabled Order Statuses */}
            <div className="capfw-card">
                <h3 className="capfw-card-title">{__('Order Status Notifications', 'captain-funnel-for-whatsapp')}</h3>
                <p className="capfw-settings-desc">{__('Select which order statuses trigger a WhatsApp notification to the customer.', 'captain-funnel-for-whatsapp')}</p>
                <div className="capfw-status-checkboxes">
                    {ALL_STATUSES.map(({ key, label }) => {
                        const checked = (settings.enabled_statuses || []).includes(key);
                        return (
                            <label key={key} className={`capfw-status-chip ${checked ? 'capfw-status-chip--on' : ''}`}>
                                <input type="checkbox" checked={checked} onChange={() => toggleStatus(key)} />
                                <span className="capfw-status-chip-dot" />
                                {label}
                            </label>
                        );
                    })}
                </div>
            </div>

            {/* Footer */}
            <div className="capfw-settings-footer">
                {saveStatus === 'saved' && <span className="capfw-msg capfw-msg--success">✓ {__('Settings saved!', 'captain-funnel-for-whatsapp')}</span>}
                {saveStatus === 'error' && <span className="capfw-msg capfw-msg--error">✗ {__('Failed to save. Please try again.', 'captain-funnel-for-whatsapp')}</span>}
                <button className="capfw-btn-primary" onClick={handleSave} disabled={saveStatus === 'saving'}>
                    {saveStatus === 'saving'
                        ? <><Spinner size={14} color="#fff" /> {__('Saving…', 'captain-funnel-for-whatsapp')}</>
                        : __('Save Settings', 'captain-funnel-for-whatsapp')
                    }
                </button>
            </div>
        </div>
    );
};

export default Settings;
