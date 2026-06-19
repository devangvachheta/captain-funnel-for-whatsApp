import React from 'react';
import './StatusBadge.scss';

/**
 * @param {string} status  - 'sent' | 'failed' | 'pending' | 'active' | 'inactive'
 * @param {string} label   - optional override label
 */
const StatusBadge = ({ status = 'pending', label }) => {
    const displayLabel = label || status;
    return (
        <span className={`capfw-badge capfw-badge--${status}`}>
            {displayLabel.toUpperCase()}
        </span>
    );
};

export default StatusBadge;
