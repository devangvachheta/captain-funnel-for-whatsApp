import React, { useEffect, useState, useCallback } from 'react';
import { useDispatch, useSelector }                 from 'react-redux';
import { __ }                                       from '@wordpress/i18n';
import { setFunnels, addFunnel, updateFunnel, removeFunnel } from '../../redux/slice.js';
import StatusBadge  from '../../components/StatusBadge/StatusBadge.jsx';
import EmptyState   from '../../components/EmptyState/EmptyState.jsx';
import Spinner      from '../../components/Spinner/Spinner.jsx';
import './funnels.scss';

const EMPTY_FORM = {
    id:           0,
    funnel_name:  '',
    trigger_event:'',
    status:       'active',
};

const ajaxData = () => ({
    ajax_url: typeof capfw_data !== 'undefined' ? capfw_data.ajax_url : '',
    nonce:    typeof capfw_data !== 'undefined' ? capfw_data.nonce    : '',
});

// ── Icons ─────────────────────────────────────────────────────────────────────
const IconAdd = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
        <line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />
    </svg>
);
const IconEdit = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
    </svg>
);
const IconTrash = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
        <path d="M10 11v6"/><path d="M14 11v6"/>
        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
    </svg>
);
const IconFunnelEmpty = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
    </svg>
);

// ── Funnel Form Modal ─────────────────────────────────────────────────────────
const FunnelFormModal = ({ form, onChange, onSave, onClose, saving, error, availableTriggers }) => {
    // Group triggers by category for grouped <optgroup>
    const grouped = {};
    (availableTriggers || []).forEach(t => {
        if (!grouped[t.category]) grouped[t.category] = [];
        grouped[t.category].push(t);
    });

    React.useEffect(() => {
        const handler = (e) => { if (e.key === 'Escape' && !saving) onClose(); };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [onClose, saving]);

    return (
    <div className="capfw-modal-overlay" onClick={onClose}>
        <div className="capfw-modal" onClick={e => e.stopPropagation()}>
            <div className="capfw-modal-header">
                <h3 className="capfw-modal-title">
                    {form.id ? __('Edit Funnel', 'captain-funnel-for-whatsapp') : __('New Funnel', 'captain-funnel-for-whatsapp')}
                </h3>
                <button className="capfw-modal-close" onClick={onClose} type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div className="capfw-modal-body">
                <div className="capfw-form-group">
                    <label>{__('Funnel Name', 'captain-funnel-for-whatsapp')} <span className="capfw-required">*</span></label>
                    <input
                        type="text"
                        className="capfw-input"
                        value={form.funnel_name}
                        onChange={e => onChange('funnel_name', e.target.value)}
                        placeholder={__('e.g. Post-Purchase Follow-up', 'captain-funnel-for-whatsapp')}
                        autoFocus
                    />
                </div>

                <div className="capfw-form-group">
                    <label>{__('Trigger Event', 'captain-funnel-for-whatsapp')}</label>
                    <select
                        className="capfw-select"
                        value={form.trigger_event}
                        onChange={e => onChange('trigger_event', e.target.value)}
                    >
                        <option value="">{__('— Select a trigger —', 'captain-funnel-for-whatsapp')}</option>
                        {Object.entries(grouped).map(([category, triggers]) => (
                            <optgroup key={category} label={category}>
                                {triggers.map(t => (
                                    <option key={t.key} value={t.key}>
                                        {t.int_label} — {t.label}
                                    </option>
                                ))}
                            </optgroup>
                        ))}
                    </select>
                    <span className="capfw-desc">
                        {__('WooCommerce not showing? Enable it in Integrations first.', 'captain-funnel-for-whatsapp')}
                    </span>
                </div>

                <div className="capfw-form-group">
                    <label>{__('Status', 'captain-funnel-for-whatsapp')}</label>
                    <div className="capfw-radio-group">
                        {['active', 'inactive'].map(s => (
                            <label key={s} className={`capfw-radio-pill${form.status === s ? ' capfw-radio-pill--on' : ''}`}>
                                <input
                                    type="radio"
                                    name="funnel_status"
                                    value={s}
                                    checked={form.status === s}
                                    onChange={() => onChange('status', s)}
                                />
                                {s === 'active' ? __('Active', 'captain-funnel-for-whatsapp') : __('Inactive', 'captain-funnel-for-whatsapp')}
                            </label>
                        ))}
                    </div>
                </div>

                {error && <p className="capfw-form-error">{error}</p>}
            </div>

            <div className="capfw-modal-footer">
                <button className="capfw-btn-secondary" onClick={onClose} type="button">
                    {__('Cancel', 'captain-funnel-for-whatsapp')}
                </button>
                <button
                    className="capfw-btn-primary"
                    onClick={onSave}
                    disabled={saving}
                    type="button"
                >
                    {saving
                        ? <><Spinner size={14} color="#fff" /> {__('Saving…', 'captain-funnel-for-whatsapp')}</>
                        : __('Save Funnel', 'captain-funnel-for-whatsapp')
                    }
                </button>
            </div>
        </div>
    </div>
    );
};

// ── Main Funnels Component ────────────────────────────────────────────────────
const Funnels = () => {
    const dispatch = useDispatch();
    const funnels  = useSelector(s => s.capfw.funnels);

    const [loading,          setLoading]          = useState(true);
    const [fetchError,       setFetchError]        = useState('');
    const [showModal,        setShowModal]         = useState(false);
    const [form,             setForm]              = useState(EMPTY_FORM);
    const [saving,           setSaving]            = useState(false);
    const [formError,        setFormError]         = useState('');
    const [deletingId,       setDeletingId]        = useState(null);
    // Dynamic triggers from registry
    const [availableTriggers,setAvailableTriggers] = useState([]);

    // ── Fetch funnels ─────────────────────────────────────────────────────────
    const fetchFunnels = useCallback(async () => {
        const { ajax_url, nonce } = ajaxData();

        const makeReq = (type) => {
            const fd = new FormData();
            fd.append('action', 'capfw_react_ajax');
            fd.append('nonce',  nonce);
            fd.append('type',   type);
            return fetch(ajax_url, { method: 'POST', body: fd }).then(r => r.json());
        };

        try {
            const [funnelsRes, triggersRes] = await Promise.all([
                makeReq('get_funnels'),
                makeReq('get_available_triggers'),
            ]);
            if (funnelsRes.success  && funnelsRes.data)  dispatch(setFunnels(funnelsRes.data));
            else setFetchError(__('Failed to load funnels.', 'captain-funnel-for-whatsapp'));
            if (triggersRes.success && triggersRes.data) setAvailableTriggers(triggersRes.data);
        } catch {
            setFetchError(__('Network error. Please refresh and try again.', 'captain-funnel-for-whatsapp'));
        } finally {
            setLoading(false);
        }
    }, [dispatch]);

    useEffect(() => { fetchFunnels(); }, [fetchFunnels]);

    // ── Open modal ────────────────────────────────────────────────────────────
    const openAdd = () => {
        setForm(EMPTY_FORM);
        setFormError('');
        setShowModal(true);
    };

    const openEdit = (funnel) => {
        setForm({
            id:           funnel.id,
            funnel_name:  funnel.funnel_name,
            trigger_event:funnel.trigger_event,
            status:       funnel.status,
        });
        setFormError('');
        setShowModal(true);
    };

    const closeModal = () => {
        if (!saving) setShowModal(false);
    };

    const handleFormChange = (key, value) => setForm(f => ({ ...f, [key]: value }));

    // ── Save funnel ───────────────────────────────────────────────────────────
    const handleSave = async () => {
        if (!form.funnel_name.trim()) {
            setFormError(__('Funnel name is required.', 'captain-funnel-for-whatsapp'));
            return;
        }
        setSaving(true);
        setFormError('');
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action',        'capfw_react_ajax');
        fd.append('nonce',         nonce);
        fd.append('type',          'save_funnel');
        fd.append('funnel_id',     form.id);
        fd.append('funnel_name',   form.funnel_name);
        fd.append('trigger_event', form.trigger_event);
        fd.append('funnel_status', form.status);

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();

            if (result.success) {
                    const saved = {
                        ...form,
                        id:         result.data?.funnel_id  || form.id,
                        // FIX Critical #5: use server-returned created_at so table shows correct date
                        created_at: result.data?.created_at || new Date().toISOString(),
                    };
                    // FIX High #5: Number() comparison in Redux updateFunnel handles type mismatch
                    Number(form.id) > 0 ? dispatch(updateFunnel(saved)) : dispatch(addFunnel(saved));
                    setShowModal(false);
                } else {
                    setFormError(result.data?.message || __('Failed to save funnel.', 'captain-funnel-for-whatsapp'));
                }
        } catch {
            setFormError(__('Network error. Please try again.', 'captain-funnel-for-whatsapp'));
        } finally {
            setSaving(false);
        }
    };

    // ── Delete funnel ─────────────────────────────────────────────────────────
    const handleDelete = async (id) => {
        if (!window.confirm(__('Are you sure you want to delete this funnel?', 'captain-funnel-for-whatsapp'))) return;
        setDeletingId(id);
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action',    'capfw_react_ajax');
        fd.append('nonce',     nonce);
        fd.append('type',      'delete_funnel');
        fd.append('funnel_id', id);

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();
            if (result.success) dispatch(removeFunnel(id));
        } catch (e) {
            console.error('CAPFW delete funnel error:', e);
        } finally {
            setDeletingId(null);
        }
    };

    if (loading) {
        return (
            <div className="capfw-funnels-page">
                <div className="capfw-page-header">
                    <h2 className="capfw-page-title">{__('Funnels', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                <div style={{ display:'flex', justifyContent:'center', padding:'48px' }}>
                    <Spinner size={32} />
                </div>
            </div>
        );
    }

    if (fetchError) {
        return (
            <div className="capfw-funnels-page">
                <div className="capfw-page-header--flex capfw-page-header">
                    <h2 className="capfw-page-title">{__('Funnels', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                <div className="capfw-card">
                    <p className="capfw-error-msg">⚠ {fetchError}</p>
                    <button className="capfw-btn-secondary" style={{marginTop:'12px'}} onClick={fetchFunnels}>
                        {__('Retry', 'captain-funnel-for-whatsapp')}
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="capfw-funnels-page">

            {/* Header */}
            <div className="capfw-page-header capfw-page-header--flex">
                <div>
                    <h2 className="capfw-page-title">{__('Funnels', 'captain-funnel-for-whatsapp')}</h2>
                    <p className="capfw-page-subtitle">
                        {__('Automated WhatsApp message sequences triggered by WooCommerce events.', 'captain-funnel-for-whatsapp')}
                    </p>
                </div>
                <button className="capfw-btn-primary" onClick={openAdd} type="button">
                    <span className="capfw-btn-icon"><IconAdd /></span>
                    {__('Add New Funnel', 'captain-funnel-for-whatsapp')}
                </button>
            </div>

            {/* Empty state */}
            {funnels.length === 0 ? (
                <div className="capfw-card">
                    <EmptyState
                        icon={<IconFunnelEmpty />}
                        title={__('No funnels yet', 'captain-funnel-for-whatsapp')}
                        description={__('Create your first WhatsApp funnel to automate customer follow-ups after WooCommerce order events.', 'captain-funnel-for-whatsapp')}
                        action={openAdd}
                        actionLabel={__('+ Add New Funnel', 'captain-funnel-for-whatsapp')}
                    />
                </div>
            ) : (
                <div className="capfw-card capfw-funnels-card">
                    <table className="capfw-table capfw-funnels-table">
                        <thead>
                            <tr>
                                <th>{__('FUNNEL NAME', 'captain-funnel-for-whatsapp')}</th>
                                <th>{__('TRIGGER', 'captain-funnel-for-whatsapp')}</th>
                                <th>{__('STATUS', 'captain-funnel-for-whatsapp')}</th>
                                <th>{__('CREATED', 'captain-funnel-for-whatsapp')}</th>
                                <th>{__('ACTIONS', 'captain-funnel-for-whatsapp')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {funnels.map(funnel => (
                                <tr key={funnel.id} className={deletingId === funnel.id ? 'capfw-row-deleting' : ''}>
                                    <td>
                                        <span
                                            className="capfw-funnel-name"
                                            onClick={() => openEdit(funnel)}
                                            title={__('Click to edit', 'captain-funnel-for-whatsapp')}
                                        >
                                            {funnel.funnel_name}
                                        </span>
                                    </td>
                                    <td>
                                        <span className="capfw-trigger-pill">
                                            {availableTriggers.find(t => t.key === funnel.trigger_event)?.label || funnel.trigger_event}
                                        </span>
                                        {availableTriggers.find(t => t.key === funnel.trigger_event)?.int_label && (
                                            <span className="capfw-int-badge">
                                                {availableTriggers.find(t => t.key === funnel.trigger_event).int_label}
                                            </span>
                                        )}
                                    </td>
                                    <td><StatusBadge status={funnel.status} /></td>
                                    <td className="capfw-date-cell">
                                        {funnel.created_at
                                            ? new Date(funnel.created_at).toLocaleDateString()
                                            : '—'
                                        }
                                    </td>
                                    <td>
                                        <div className="capfw-action-btns">
                                            <button
                                                className="capfw-action-btn capfw-action-btn--edit"
                                                onClick={() => openEdit(funnel)}
                                                title={__('Edit', 'captain-funnel-for-whatsapp')}
                                                type="button"
                                            >
                                                <IconEdit />
                                            </button>
                                            <button
                                                className="capfw-action-btn capfw-action-btn--delete"
                                                onClick={() => handleDelete(funnel.id)}
                                                title={__('Delete', 'captain-funnel-for-whatsapp')}
                                                disabled={deletingId === funnel.id}
                                                type="button"
                                            >
                                                {deletingId === funnel.id
                                                    ? <Spinner size={14} />
                                                    : <IconTrash />
                                                }
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {showModal && (
                <FunnelFormModal
                    form={form}
                    onChange={handleFormChange}
                    onSave={handleSave}
                    onClose={closeModal}
                    saving={saving}
                    error={formError}
                    availableTriggers={availableTriggers}
                />
            )}

        </div>
    );
};

export default Funnels;
