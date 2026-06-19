import React from 'react';
import './EmptyState.scss';

const EmptyState = ({ icon, title, description, action, actionLabel }) => (
    <div className="capfw-empty-state">
        {icon && <div className="capfw-empty-icon">{icon}</div>}
        <p className="capfw-empty-title">{title}</p>
        {description && <p className="capfw-empty-desc">{description}</p>}
        {action && (
            <button className="capfw-btn-primary" onClick={action}>
                {actionLabel}
            </button>
        )}
    </div>
);

export default EmptyState;
