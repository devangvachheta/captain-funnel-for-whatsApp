import React, { useEffect, useState, useCallback } from 'react';
import { useDispatch, useSelector }                 from 'react-redux';
import { __ }                                       from '@wordpress/i18n';
import { setLogs, setLogsTotal }                    from '../../redux/slice.js';
import StatusBadge  from '../../components/StatusBadge/StatusBadge.jsx';
import EmptyState   from '../../components/EmptyState/EmptyState.jsx';
import Spinner      from '../../components/Spinner/Spinner.jsx';
import './logs.scss';

const PER_PAGE = 20;

const ajaxData = () => ({
    ajax_url: typeof capfw_data !== 'undefined' ? capfw_data.ajax_url : '',
    nonce:    typeof capfw_data !== 'undefined' ? capfw_data.nonce    : '',
});

// ── Icons ─────────────────────────────────────────────────────────────────────
const IconLogEmpty = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
    </svg>
);

const IconChevronLeft = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" width="14" height="14">
        <polyline points="15 18 9 12 15 6"/>
    </svg>
);

const IconChevronRight = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" width="14" height="14">
        <polyline points="9 18 15 12 9 6"/>
    </svg>
);

// ── Message Preview Modal ─────────────────────────────────────────────────────
const MessageModal = ({ log, onClose }) => (
    <div className="capfw-modal-overlay" onClick={onClose}>
        <div className="capfw-modal capfw-msg-modal" onClick={e => e.stopPropagation()}>
            <div className="capfw-modal-header">
                <h3 className="capfw-modal-title">
                    {__('Message Preview', 'captain-funnel-for-whatsapp')}
                    {log.order_id ? ` — #${log.order_id}` : ''}
                </h3>
                <button className="capfw-modal-close" onClick={onClose} type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" width="16" height="16">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div className="capfw-modal-body">
                <div className="capfw-log-meta">
                    <span className="capfw-log-meta-row">
                        <strong>{__('Phone:', 'captain-funnel-for-whatsapp')}</strong>
                        <span>{log.customer_phone || '—'}</span>
                    </span>
                    <span className="capfw-log-meta-row">
                        <strong>{__('Status:', 'captain-funnel-for-whatsapp')}</strong>
                        <StatusBadge status={log.status} />
                    </span>
                    <span className="capfw-log-meta-row">
                        <strong>{__('Sent At:', 'captain-funnel-for-whatsapp')}</strong>
                        <span>{log.created_at || '—'}</span>
                    </span>
                </div>
                <div className="capfw-msg-bubble">
                    <pre className="capfw-msg-text">{log.message}</pre>
                </div>
                {log.response && (
                    <details className="capfw-api-response">
                        <summary>{__('API Response', 'captain-funnel-for-whatsapp')}</summary>
                        <pre className="capfw-api-pre">{log.response}</pre>
                    </details>
                )}
            </div>
        </div>
    </div>
);

// ── Clear Logs Confirm Modal ──────────────────────────────────────────────────
const ClearLogsModal = ({ onClose, onConfirm, clearing }) => {
    const [mode, setMode] = useState('all');
    const [days, setDays] = useState(30);

    return (
        <div className="capfw-modal-overlay" onClick={onClose}>
            <div className="capfw-modal" onClick={e => e.stopPropagation()}>
                <div className="capfw-modal-header">
                    <h3 className="capfw-modal-title">{__('Clear Logs', 'captain-funnel-for-whatsapp')}</h3>
                    <button className="capfw-modal-close" onClick={onClose} type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" width="16" height="16">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div className="capfw-modal-body">
                    <div className="capfw-status-checkboxes" style={{ marginBottom: '16px' }}>
                        <label className={`capfw-status-chip ${mode === 'all' ? 'capfw-status-chip--on' : ''}`}>
                            <input type="radio" name="capfw_clear_mode" checked={mode === 'all'} onChange={() => setMode('all')} />
                            <span className="capfw-status-chip-dot" />
                            {__('Clear all logs', 'captain-funnel-for-whatsapp')}
                        </label>
                        <label className={`capfw-status-chip ${mode === 'older_than' ? 'capfw-status-chip--on' : ''}`}>
                            <input type="radio" name="capfw_clear_mode" checked={mode === 'older_than'} onChange={() => setMode('older_than')} />
                            <span className="capfw-status-chip-dot" />
                            {__('Older than…', 'captain-funnel-for-whatsapp')}
                        </label>
                    </div>

                    {mode === 'older_than' && (
                        <div className="capfw-form-group" style={{ marginBottom: '16px' }}>
                            <label>{__('Days', 'captain-funnel-for-whatsapp')}</label>
                            <input
                                type="number" min="1" className="capfw-input" style={{ maxWidth: '120px' }}
                                value={days}
                                onChange={e => setDays(e.target.value)}
                            />
                            <span className="capfw-desc">
                                {__('Logs older than this many days will be permanently deleted.', 'captain-funnel-for-whatsapp')}
                            </span>
                        </div>
                    )}

                    <p className="capfw-error-msg" style={{ marginTop: 0 }}>
                        ⚠ {__('This action cannot be undone.', 'captain-funnel-for-whatsapp')}
                    </p>
                </div>
                <div className="capfw-settings-footer" style={{ padding: '0 20px 20px' }}>
                    <button className="capfw-btn-secondary" onClick={onClose} type="button" disabled={clearing}>
                        {__('Cancel', 'captain-funnel-for-whatsapp')}
                    </button>
                    <button
                        className="capfw-btn-primary"
                        onClick={() => onConfirm(mode, parseInt(days, 10) || 30)}
                        type="button"
                        disabled={clearing}
                    >
                        {clearing
                            ? <><Spinner size={14} color="#fff" /> {__('Clearing…', 'captain-funnel-for-whatsapp')}</>
                            : __('Clear Logs', 'captain-funnel-for-whatsapp')
                        }
                    </button>
                </div>
            </div>
        </div>
    );
};

// ── Logs Component ────────────────────────────────────────────────────────────
const Logs = () => {
    const dispatch = useDispatch();
    const logs     = useSelector(s => s.capfw.logs);
    const total    = useSelector(s => s.capfw.logs_total);

    const [loading,    setLoading]    = useState(true);
    const [paged,      setPaged]      = useState(1);
    const [filterStatus, setFilterStatus] = useState('all');
    const [previewLog,   setPreviewLog]   = useState(null);
    const [showClearModal, setShowClearModal] = useState(false);
    const [clearing,       setClearing]       = useState(false);

    const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

    // ── Fetch logs ────────────────────────────────────────────────────────────
    const fetchLogs = useCallback(async (page = 1, status = 'all') => {
        setLoading(true);
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action', 'capfw_react_ajax');
        fd.append('nonce',  nonce);
        fd.append('type',   'get_logs');
        fd.append('paged',  page);
        fd.append('per_page', PER_PAGE);
        fd.append('filter_status', status);

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();
            if (result.success && result.data) {
                dispatch(setLogs(result.data.logs || []));
                dispatch(setLogsTotal(result.data.total || 0));
            }
        } catch (e) {
            console.error('CAPFW fetch logs error:', e);
        } finally {
            setLoading(false);
        }
    }, [dispatch]);

    useEffect(() => {
        fetchLogs(paged, filterStatus);
    }, [fetchLogs, paged, filterStatus]);

    const handleFilterChange = (status) => {
        setFilterStatus(status);
        setPaged(1);
    };

    const handlePageChange = (newPage) => {
        if (newPage < 1 || newPage > totalPages) return;
        setPaged(newPage);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleClearLogs = async (mode, days) => {
        setClearing(true);
        const { ajax_url, nonce } = ajaxData();
        const fd = new FormData();
        fd.append('action', 'capfw_react_ajax');
        fd.append('nonce',  nonce);
        fd.append('type',   'clear_logs');
        fd.append('mode',   mode);
        if (mode === 'older_than') fd.append('days', days);

        try {
            const res    = await fetch(ajax_url, { method: 'POST', body: fd });
            const result = await res.json();
            if (result.success) {
                setShowClearModal(false);
                setPaged(1);
                fetchLogs(1, filterStatus);
            }
        } catch (e) {
            console.error('CAPFW clear logs error:', e);
        } finally {
            setClearing(false);
        }
    };

    const STATUS_FILTERS = [
        { key: 'all',     label: __('All', 'captain-funnel-for-whatsapp') },
        { key: 'sent',    label: __('Sent', 'captain-funnel-for-whatsapp') },
        { key: 'failed',  label: __('Failed', 'captain-funnel-for-whatsapp') },
        { key: 'pending', label: __('Pending', 'captain-funnel-for-whatsapp') },
    ];

    return (
        <div className="capfw-logs-page">

            {/* Header */}
            <div className="capfw-page-header capfw-page-header--flex">
                <div>
                    <h2 className="capfw-page-title">{__('Message Logs', 'captain-funnel-for-whatsapp')}</h2>
                    <p className="capfw-page-subtitle">
                        {__('Full history of WhatsApp messages sent by your store.', 'captain-funnel-for-whatsapp')}
                    </p>
                </div>
                <div style={{ display: 'flex', gap: '10px' }}>
                    <button
                        className="capfw-btn-secondary"
                        onClick={() => fetchLogs(paged, filterStatus)}
                        type="button"
                        title={__('Refresh', 'captain-funnel-for-whatsapp')}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" width="14" height="14">
                            <polyline points="23 4 23 10 17 10"/>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                        </svg>
                        {__('Refresh', 'captain-funnel-for-whatsapp')}
                    </button>
                    <button
                        className="capfw-btn-secondary"
                        onClick={() => setShowClearModal(true)}
                        type="button"
                        title={__('Clear Logs', 'captain-funnel-for-whatsapp')}
                        disabled={total === 0}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" width="14" height="14">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        {__('Clear Logs', 'captain-funnel-for-whatsapp')}
                    </button>
                </div>
            </div>

            {/* Filter bar */}
            <div className="capfw-log-filters">
                {STATUS_FILTERS.map(f => (
                    <button
                        key={f.key}
                        type="button"
                        className={`capfw-filter-btn${filterStatus === f.key ? ' capfw-filter-btn--active' : ''}`}
                        onClick={() => handleFilterChange(f.key)}
                    >
                        {f.label}
                        {f.key !== 'all' && (
                            <span className={`capfw-filter-dot capfw-filter-dot--${f.key}`} />
                        )}
                    </button>
                ))}
                <span className="capfw-log-total">
                    {total} {__('total', 'captain-funnel-for-whatsapp')}
                </span>
            </div>

            {/* Table card */}
            <div className="capfw-card capfw-logs-card">
                {loading ? (
                    <div style={{ display:'flex', justifyContent:'center', padding:'48px' }}>
                        <Spinner size={32} />
                    </div>
                ) : logs.length === 0 ? (
                    <EmptyState
                        icon={<IconLogEmpty />}
                        title={__('No messages logged yet', 'captain-funnel-for-whatsapp')}
                        description={__('WhatsApp messages sent by your store will appear here.', 'captain-funnel-for-whatsapp')}
                    />
                ) : (
                    <>
                        <table className="capfw-table capfw-logs-table">
                            <thead>
                                <tr>
                                    <th style={{width:'60px'}}>{__('ID', 'captain-funnel-for-whatsapp')}</th>
                                    <th style={{width:'80px'}}>{__('ORDER', 'captain-funnel-for-whatsapp')}</th>
                                    <th style={{width:'130px'}}>{__('PHONE', 'captain-funnel-for-whatsapp')}</th>
                                    <th>{__('MESSAGE', 'captain-funnel-for-whatsapp')}</th>
                                    <th style={{width:'90px'}}>{__('STATUS', 'captain-funnel-for-whatsapp')}</th>
                                    <th style={{width:'150px'}}>{__('SENT AT', 'captain-funnel-for-whatsapp')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {logs.map(log => (
                                    <tr
                                        key={log.id}
                                        className="capfw-log-row"
                                        onClick={() => setPreviewLog(log)}
                                        title={__('Click to preview message', 'captain-funnel-for-whatsapp')}
                                    >
                                        <td className="capfw-log-id">#{log.id}</td>
                                        <td>
                                            {log.order_id
                                                ? <span className="capfw-order-link">#{log.order_id}</span>
                                                : <span className="capfw-dash">—</span>
                                            }
                                        </td>
                                        <td className="capfw-phone-cell">{log.customer_phone || '—'}</td>
                                        <td className="capfw-msg-cell capfw-msg-cell--truncate">
                                            {log.message
                                                ? log.message.length > 80
                                                    ? log.message.substring(0, 80) + '…'
                                                    : log.message
                                                : '—'
                                            }
                                        </td>
                                        <td><StatusBadge status={log.status} /></td>
                                        <td className="capfw-date-cell">{log.created_at || '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {totalPages > 1 && (
                            <div className="capfw-pagination">
                                <span className="capfw-page-info">
                                    {__('Page', 'captain-funnel-for-whatsapp')} {paged} {__('of', 'captain-funnel-for-whatsapp')} {totalPages}
                                </span>
                                <div className="capfw-page-btns">
                                    <button
                                        className="capfw-page-btn"
                                        onClick={() => handlePageChange(paged - 1)}
                                        disabled={paged <= 1}
                                        type="button"
                                    >
                                        <IconChevronLeft />
                                    </button>
                                    {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                                        let page = i + 1;
                                        if (totalPages > 5) {
                                            page = Math.max(1, Math.min(paged - 2, totalPages - 4)) + i;
                                        }
                                        return (
                                            <button
                                                key={page}
                                                className={`capfw-page-btn${paged === page ? ' capfw-page-btn--active' : ''}`}
                                                onClick={() => handlePageChange(page)}
                                                type="button"
                                            >
                                                {page}
                                            </button>
                                        );
                                    })}
                                    <button
                                        className="capfw-page-btn"
                                        onClick={() => handlePageChange(paged + 1)}
                                        disabled={paged >= totalPages}
                                        type="button"
                                    >
                                        <IconChevronRight />
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            {/* Message preview modal */}
            {previewLog && (
                <MessageModal
                    log={previewLog}
                    onClose={() => setPreviewLog(null)}
                />
            )}

            {/* Clear logs confirm modal */}
            {showClearModal && (
                <ClearLogsModal
                    onClose={() => setShowClearModal(false)}
                    onConfirm={handleClearLogs}
                    clearing={clearing}
                />
            )}

        </div>
    );
};

export default Logs;
