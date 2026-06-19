import React, { useEffect, useCallback } from 'react';
import { useDispatch, useSelector }       from 'react-redux';
import { useNavigate }                    from 'react-router-dom';
import { __ }                             from '@wordpress/i18n';
import { setStats }                       from '../../redux/slice.js';
import StatsCard                          from '../../components/StatsCard/StatsCard.jsx';
import StatusBadge                        from '../../components/StatusBadge/StatusBadge.jsx';
import Spinner                            from '../../components/Spinner/Spinner.jsx';
import './dashboard.scss';

// ── Icons ─────────────────────────────────────────────────────────────────────
const IconSent = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <polyline points="20 6 9 17 4 12" />
    </svg>
);
const IconFailed = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" />
    </svg>
);
const IconPending = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
    </svg>
);
const IconFunnel = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
    </svg>
);
const IconSettings = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="3" />
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
    </svg>
);
const IconLogs = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" />
    </svg>
);

// ── Helpers ───────────────────────────────────────────────────────────────────
const ajaxCall = async (type) => {
    const fd = new FormData();
    fd.append('action', 'capfw_react_ajax');
    fd.append('nonce',  typeof capfw_data !== 'undefined' ? capfw_data.nonce : '');
    fd.append('type',   type);
    const res  = await fetch(typeof capfw_data !== 'undefined' ? capfw_data.ajax_url : '', { method: 'POST', body: fd });
    return res.json();
};

// ── Dashboard Component ───────────────────────────────────────────────────────
const Dashboard = () => {
    const dispatch  = useDispatch();
    const navigate  = useNavigate();
    const stats     = useSelector(s => s.capfw.stats);

    // FIX High #2: Use LOCAL state for dashboard recent logs — don't pollute shared Redux logs[]
    const [recentLogs, setRecentLogs] = React.useState([]);
    const [loading,    setLoading]    = React.useState(true);
    const [fetchError, setFetchError] = React.useState('');

    const fetchData = useCallback(async () => {
        setFetchError('');
        try {
            const [statsRes, logsRes] = await Promise.all([
                ajaxCall('get_stats'),
                ajaxCall('get_recent_logs'),
            ]);
            if (statsRes.success && statsRes.data) dispatch(setStats(statsRes.data));
            // FIX High #2: set local recentLogs — not shared Redux logs state
            if (logsRes.success  && logsRes.data)  setRecentLogs(logsRes.data);
            if (!statsRes.success && !logsRes.success) {
                setFetchError(__('Failed to load dashboard data.', 'captain-funnel-for-whatsapp'));
            }
        } catch (e) {
            // FIX Low #4: Show error instead of silently failing
            setFetchError(__('Network error. Please refresh the page.', 'captain-funnel-for-whatsapp'));
        } finally {
            setLoading(false);
        }
    }, [dispatch]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    const QUICK_ACTIONS = [
        {
            label: __('WhatsApp Settings', 'captain-funnel-for-whatsapp'),
            desc:  __('Configure API credentials', 'captain-funnel-for-whatsapp'),
            path:  '/settings',
            icon:  <IconSettings />,
            color: '#2271b1',
        },
        {
            label: __('Manage Funnels', 'captain-funnel-for-whatsapp'),
            desc:  __('Create automation sequences', 'captain-funnel-for-whatsapp'),
            path:  '/funnels',
            icon:  <IconFunnel />,
            color: '#25D366',
        },
        {
            label: __('View Logs', 'captain-funnel-for-whatsapp'),
            desc:  __('Message activity history', 'captain-funnel-for-whatsapp'),
            path:  '/logs',
            icon:  <IconLogs />,
            color: '#d97706',
        },
    ];

    if (loading) {
        return (
            <div className="capfw-dashboard">
                <div className="capfw-dashboard-header">
                    <h2 className="capfw-page-title">{__('Dashboard', 'captain-funnel-for-whatsapp')}</h2>
                </div>
                {/* Stats skeleton */}
                <div className="capfw-stats-grid">
                    {[1,2,3].map(i => (
                        <div key={i} className="capfw-stats-card capfw-stats-card--sent">
                            <div className="capfw-stats-icon capfw-skeleton-text" style={{width:48,height:48}} />
                            <div className="capfw-stats-info">
                                <span className="capfw-stats-value capfw-skeleton-text">000</span>
                                <span className="capfw-stats-label capfw-skeleton-text">Loading</span>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="capfw-dashboard-spinner"><Spinner size={32} /></div>
            </div>
        );
    }

    return (
        <div className="capfw-dashboard">

            {/* Header */}
            <div className="capfw-dashboard-header">
                <h2 className="capfw-page-title">{__('Dashboard', 'captain-funnel-for-whatsapp')}</h2>
                <p className="capfw-page-subtitle">
                    {__('WhatsApp automation overview for your WooCommerce store.', 'captain-funnel-for-whatsapp')}
                </p>
            </div>

            {/* Stats Grid */}
            <div className="capfw-stats-grid">
                <StatsCard
                    value={stats.sent}
                    label={__('Messages Sent', 'captain-funnel-for-whatsapp')}
                    type="sent"
                    icon={<IconSent />}
                />
                <StatsCard
                    value={stats.failed}
                    label={__('Failed Messages', 'captain-funnel-for-whatsapp')}
                    type="failed"
                    icon={<IconFailed />}
                />
                <StatsCard
                    value={stats.pending}
                    label={__('Pending Messages', 'captain-funnel-for-whatsapp')}
                    type="pending"
                    icon={<IconPending />}
                />
            </div>

            {/* Quick Actions */}
            <div className="capfw-card capfw-quick-section">
                <h3 className="capfw-card-title">{__('Quick Actions', 'captain-funnel-for-whatsapp')}</h3>
                <div className="capfw-quick-grid">
                    {QUICK_ACTIONS.map((action) => (
                        <button
                            key={action.path}
                            className="capfw-quick-card"
                            onClick={() => navigate(action.path)}
                            style={{ '--accent': action.color }}
                        >
                            <span className="capfw-quick-icon">{action.icon}</span>
                            <span className="capfw-quick-label">{action.label}</span>
                            <span className="capfw-quick-desc">{action.desc}</span>
                        </button>
                    ))}
                </div>
            </div>

            {/* Recent Logs */}
            <div className="capfw-card">
                <div className="capfw-section-head">
                    <h3 className="capfw-card-title" style={{marginBottom:0}}>
                        {__('Recent Messages', 'captain-funnel-for-whatsapp')}
                    </h3>
                    <button className="capfw-btn-secondary capfw-btn-sm" onClick={() => navigate('/logs')}>
                        {__('View All', 'captain-funnel-for-whatsapp')}
                    </button>
                </div>

                {fetchError ? (
                    <p className="capfw-error-msg">⚠ {fetchError}</p>
                ) : recentLogs.length === 0 ? (
                    <p className="capfw-no-data">{__('No messages sent yet.', 'captain-funnel-for-whatsapp')}</p>
                ) : (
                    <table className="capfw-table">
                        <thead>
                            <tr>
                                <th>{__('Order', 'captain-funnel-for-whatsapp')}</th>
                                <th>{__('Phone', 'captain-funnel-for-whatsapp')}</th>
                                <th>{__('Message', 'captain-funnel-for-whatsapp')}</th>
                                <th>{__('Status', 'captain-funnel-for-whatsapp')}</th>
                                <th>{__('Sent At', 'captain-funnel-for-whatsapp')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentLogs.slice(0, 5).map((log) => (
                                <tr key={log.id}>
                                    <td>
                                        {log.order_id
                                            ? <span className="capfw-order-link">#{log.order_id}</span>
                                            : '—'
                                        }
                                    </td>
                                    <td className="capfw-phone-cell">{log.customer_phone || '—'}</td>
                                    <td className="capfw-msg-cell">
                                        {log.message
                                            ? log.message.length > 60
                                                ? log.message.substring(0, 60) + '…'
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
                )}
            </div>

        </div>
    );
};

export default Dashboard;
