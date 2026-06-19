import React from 'react';
import './StatsCard.scss';

/**
 * @param {string|number} value  - Main big number
 * @param {string}        label  - Description below number
 * @param {string}        type   - 'sent' | 'failed' | 'pending'
 * @param {React.node}    icon   - SVG icon
 */
const StatsCard = ({ value = 0, label = '', type = 'sent', icon }) => (
    <div className={`capfw-stats-card capfw-stats-card--${type}`}>
        <div className="capfw-stats-icon">{icon}</div>
        <div className="capfw-stats-info">
            <span className="capfw-stats-value">{Number(value).toLocaleString()}</span>
            <span className="capfw-stats-label">{label}</span>
        </div>
    </div>
);

export default StatsCard;
