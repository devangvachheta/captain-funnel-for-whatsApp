import React from 'react';
import './Spinner.scss';

const Spinner = ({ size = 24, color = '#25D366' }) => (
    <svg
        className="capfw-spinner"
        viewBox="0 0 24 24"
        width={size}
        height={size}
        style={{ color }}
    >
        <circle
            cx="12" cy="12" r="10"
            stroke="currentColor"
            strokeWidth="3"
            fill="none"
            strokeDasharray="50"
            strokeDashoffset="15"
        />
    </svg>
);

export default Spinner;
