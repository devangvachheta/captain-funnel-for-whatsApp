import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useSelector, useDispatch } from 'react-redux';
import { __ } from '@wordpress/i18n';
import { toggleDarkMode } from '../../redux/slice';

// ── Sync sidebar left offset with WP admin menu (expand/collapse) ─────────────
const useWPAdminOffset = () => {
    React.useEffect(() => {
        const sidebar = document.querySelector('.capfw-sidebar');
        const spacer  = document.querySelector('.capfw-sidebar-spacer');
        if (!sidebar) return;

        const update = () => {
            // Measure actual WP admin menu width from the DOM
            const wpMenu = document.querySelector('#adminmenuwrap') ||
                           document.querySelector('#adminmenu');
            const menuWidth = wpMenu ? wpMenu.offsetWidth : 160;
            sidebar.style.left = menuWidth + 'px';
            if (spacer) spacer.style.width = '220px'; // keep spacer fixed
        };

        // Small delay so WP menu finishes its own CSS transition first
        const run = () => setTimeout(update, 50);

        run();

        // Watch body class changes (folded / auto-fold / wp-menu-open etc.)
        const observer = new MutationObserver(run);
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

        window.addEventListener('resize', run);
        return () => {
            observer.disconnect();
            window.removeEventListener('resize', run);
        };
    }, []);
};
import './navigation.scss';

const NAV_ITEMS = [
    {
        name: __('Dashboard', 'captain-funnel-for-whatsapp'),
        path: '/',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" />
                <rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" />
            </svg>
        ),
    },
    {
        name: __('Settings', 'captain-funnel-for-whatsapp'),
        path: '/settings',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="3" />
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
            </svg>
        ),
    },
    {
        name: __('Integrations', 'captain-funnel-for-whatsapp'),
        path: '/integrations',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
            </svg>
        ),
    },
    {
        name: __('Templates', 'captain-funnel-for-whatsapp'),
        path: '/templates',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
            </svg>
        ),
    },
    {
        name: __('Funnels', 'captain-funnel-for-whatsapp'),
        path: '/funnels',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
            </svg>
        ),
    },
    {
        name: __('Logs', 'captain-funnel-for-whatsapp'),
        path: '/logs',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" />
                <polyline points="10 9 9 9 8 9" />
            </svg>
        ),
    },
];

// ── Captain Funnel brand logo SVG (inline, currentColor = theme-aware)
const CapfwLogoSVG = () => (
    <svg viewBox="0 0 106 102" fill="none" xmlns="http://www.w3.org/2000/svg">
    <mask id="mask0_66_5704" style={{maskType:"luminance"}} maskUnits="userSpaceOnUse" x="0" y="2" width="86" height="97">
    <path d="M85.9305 26.1733C80.779 17.314 73.0031 10.4176 63.7626 6.51231C54.522 2.60698 44.3113 1.90181 34.6526 4.50194C24.9938 7.10209 16.4044 12.8682 10.1646 20.9409C3.92491 29.0135 0.369071 38.9603 0.0271856 49.2983C-0.3147 59.6366 2.57569 69.8124 8.26745 78.309C13.9593 86.8055 22.1476 93.1682 31.6119 96.4477C41.0761 99.7272 51.3093 99.7483 60.7866 96.5083C70.2635 93.268 78.4763 86.94 84.2009 78.4671L71.5547 69.2859C67.7395 74.9327 62.266 79.1499 55.9501 81.3094C49.6343 83.4685 42.8143 83.4543 36.5071 81.2689C30.1997 79.083 24.7427 74.843 20.9495 69.1804C17.1562 63.5179 15.23 56.7365 15.4578 49.8469C15.6856 42.957 18.0554 36.3282 22.2138 30.9482C26.3722 25.5683 32.0966 21.7255 38.5337 19.9926C44.9702 18.2598 51.7752 18.7297 57.9338 21.3324C64.0918 23.9351 69.2738 28.5311 72.707 34.4353L85.9305 26.1733Z" fill="currentColor"/>
    </mask>
    <g mask="url(#mask0_66_5704)">
    <path d="M85.9305 26.1733C80.779 17.314 73.0031 10.4176 63.7626 6.51231C54.522 2.60698 44.3113 1.90181 34.6526 4.50194C24.9938 7.10209 16.4044 12.8682 10.1646 20.9409C3.92491 29.0135 0.369071 38.9603 0.0271856 49.2983C-0.3147 59.6366 2.57569 69.8124 8.26745 78.309C13.9593 86.8055 22.1476 93.1682 31.6119 96.4477C41.0761 99.7272 51.3093 99.7483 60.7866 96.5083C70.2635 93.268 78.4763 86.94 84.2009 78.4671L71.5547 69.2859C67.7395 74.9327 62.266 79.1499 55.9501 81.3094C49.6343 83.4685 42.8143 83.4543 36.5071 81.2689C30.1997 79.083 24.7427 74.843 20.9495 69.1804C17.1562 63.5179 15.23 56.7365 15.4578 49.8469C15.6856 42.957 18.0554 36.3282 22.2138 30.9482C26.3722 25.5683 32.0966 21.7255 38.5337 19.9926C44.9702 18.2598 51.7752 18.7297 57.9338 21.3324C64.0918 23.9351 69.2738 28.5311 72.707 34.4353L85.9305 26.1733Z" fill="currentColor"/>
    </g>
    <path d="M63.1178 39H42.8081C40.7049 39 39 40.0231 39 41.2852V62.7148C39 63.9769 40.7049 65 42.8081 65C44.9112 65 46.6162 63.9769 46.6162 62.7148V54.2852H59.0559C61.1591 54.2852 62.864 53.2621 62.864 52C62.864 50.7379 61.1591 49.7148 59.0559 49.7148H46.6162V43.5703H63.1178C65.221 43.5703 66.9259 42.5472 66.9259 41.2852C66.9259 40.0231 65.221 39 63.1178 39Z" fill="currentColor"/>
    <path d="M83.839 45.4L85.6074 39.8335L88.7231 49.7887C88.8726 49.8549 89.0126 49.8872 89.144 49.8872C90.0608 49.8872 90.8183 48.0313 91.4178 44.3207C92.0161 40.6102 92.513 38.6445 92.905 38.4264C93.2983 38.2083 93.8218 38.0985 94.4768 38.0985C94.9448 38.0985 95.572 38.1365 96.3573 38.2125C97.1437 38.2899 97.5731 38.3264 97.6491 38.3264C99.3897 38.3264 100.55 38.3701 101.13 38.4573C101.711 38.5446 102 38.7514 102 39.0778C101.532 40.0347 101.116 41.0576 100.75 42.1453C100.385 43.233 100.203 44.3967 100.203 45.6364C100.109 46.7241 99.9784 47.2672 99.8095 47.2672C99.7154 47.2672 99.5851 47.0435 99.4163 46.596C99.2474 46.1486 99.1075 45.7503 98.9953 45.4014L94.139 66H88.6362L85.605 56.6343L82.5737 66H77.0745L72.0204 44.6472C71.6091 45.6955 71.3232 46.399 71.164 46.7592C71.0048 47.1195 70.8407 47.2996 70.6731 47.2996C70.4487 47.2996 70.2702 46.7423 70.1399 45.6293C70.1399 44.9525 70.1158 44.1561 70.07 43.2387C70.0229 42.3212 70 41.8301 70 41.7654C70 40.848 70.1592 40.1444 70.4777 39.6533C70.7961 39.1623 71.4981 38.7641 72.5837 38.4587C73.6693 38.1534 74.6609 38 75.5595 38C76.083 38 76.5788 38.0549 77.0468 38.1632C77.5148 38.273 78.0576 38.4686 78.6752 38.7528C79.2735 39.1243 79.7041 40.2598 79.9671 42.158C80.0973 43.8395 80.2385 45.5196 80.388 47.2011C80.5376 48.8826 80.7897 49.7226 81.1455 49.7226C81.3699 49.7226 81.6835 49.3849 82.0864 48.7081C82.4881 48.0313 82.9706 46.9731 83.5327 45.5322C83.5881 45.445 83.6497 45.4014 83.7148 45.4014C83.7787 45.4 83.821 45.4 83.839 45.4Z" fill="currentColor"/>
    <g clip-path="url(#clip0_66_5704)">
    <path d="M101.92 5.03305C100.605 3.72279 98.855 3.00075 96.993 3C95.1351 3 93.3827 3.72141 92.0589 5.03134C90.7327 6.34352 90.0017 8.08733 90 9.93517V9.9373V9.93858C90.0002 11.057 90.2941 12.1853 90.8518 13.2151L90.0191 17L93.8476 16.1292C94.8172 16.6178 95.8991 16.8755 96.9903 16.8759H96.9931C98.8506 16.8759 100.603 16.1544 101.927 14.8443C103.254 13.5311 103.986 11.7895 103.987 9.94051C103.987 8.10452 103.253 6.36168 101.92 5.03305ZM96.993 15.7832H96.9905C96.0107 15.7828 95.0399 15.5368 94.1832 15.0716L94.0021 14.9734L91.4564 15.5524L92.0093 13.0393L91.9027 12.8555C91.3727 11.9415 91.0927 10.9326 91.0927 9.93741C91.0947 6.71629 93.7413 4.09268 96.9927 4.09268C98.5635 4.09332 100.039 4.70226 101.149 5.80711C102.274 6.92906 102.894 8.39687 102.894 9.94019C102.892 13.162 100.245 15.7832 96.993 15.7832Z" fill="currentColor"/>
    <path d="M95.0908 6.87891H94.7843C94.6776 6.87891 94.5043 6.91885 94.3578 7.07832C94.2111 7.2379 93.7979 7.6236 93.7979 8.40802C93.7979 9.19244 94.3711 9.95038 94.451 10.0569C94.531 10.1633 95.5576 11.8244 97.1835 12.4634C98.5347 12.9945 98.8098 12.8889 99.103 12.8623C99.3963 12.8358 100.049 12.4767 100.183 12.1044C100.316 11.7322 100.316 11.4131 100.276 11.3464C100.236 11.28 100.129 11.2401 99.9694 11.1604C99.8094 11.0807 99.0254 10.6884 98.8788 10.6351C98.7321 10.5821 98.6255 10.5555 98.5188 10.7151C98.4121 10.8745 98.098 11.2432 98.0046 11.3496C97.9114 11.4561 97.818 11.4695 97.658 11.3897C97.498 11.3097 96.9881 11.1383 96.377 10.5953C95.9014 10.1727 95.5714 9.63368 95.478 9.47411C95.3848 9.31464 95.4681 9.22833 95.5483 9.14876C95.6202 9.07741 95.7173 8.97978 95.7973 8.88675C95.8772 8.79361 95.8999 8.72717 95.9533 8.62079C96.0066 8.5144 95.9799 8.42126 95.94 8.34158C95.8999 8.2618 95.5929 7.47342 95.4507 7.15811H95.4508C95.3309 6.89258 95.2048 6.88361 95.0908 6.87891Z" fill="currentColor"/>
    </g>
    <defs>
    <clipPath id="clip0_66_5704">
    <rect width="14" height="14" fill="currentColor" transform="translate(90 3)"/>
    </clipPath>
    </defs>
    </svg>
);

// Moon icon (shown in light mode)
const MoonIcon = () => (
    <svg className="capfw-dm-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
    </svg>
);

// Sun icon (shown in dark mode)
const SunIcon = () => (
    <svg className="capfw-dm-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="5" />
        <line x1="12" y1="1" x2="12" y2="3" />
        <line x1="12" y1="21" x2="12" y2="23" />
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
        <line x1="1" y1="12" x2="3" y2="12" />
        <line x1="21" y1="12" x2="23" y2="12" />
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
    </svg>
);

const Navigation = () => {
    const location  = useLocation();
    const dispatch  = useDispatch();
    const darkMode  = useSelector((state) => state.capfw.darkMode);
    useWPAdminOffset();

    const isActive = (path) => {
        if (path === '/') return location.pathname === '/';
        return location.pathname.startsWith(path);
    };

    const handleToggleDark = () => dispatch(toggleDarkMode());

    return (
        <aside className="capfw-sidebar">
            {/* Logo */}
            <div className="capfw-sidebar-logo">
                <CapfwLogoSVG />
                <div className="capfw-sidebar-logo-text">
                    <span className="capfw-sidebar-logo-name">Captain Funnel</span>
                    <span className="capfw-sidebar-logo-sub">for WhatsApp</span>
                </div>
            </div>

            {/* Nav Menu */}
            <nav className="capfw-sidebar-menu">
                {NAV_ITEMS.map((item) => (
                    <Link
                        key={item.path}
                        to={item.path}
                        className={`capfw-sidebar-link${isActive(item.path) ? ' capfw-sidebar-link--active' : ''}`}
                    >
                        <span className="capfw-sidebar-icon">{item.icon}</span>
                        <span className="capfw-sidebar-label">{item.name}</span>
                    </Link>
                ))}

                {/* Docs section */}
                <div className="capfw-sidebar-divider">{__('Docs', 'captain-funnel-for-whatsapp')}</div>
                <Link
                    to="/docs/credentials"
                    className={`capfw-sidebar-link${isActive('/docs/credentials') ? ' capfw-sidebar-link--active' : ''}`}
                >
                    <span className="capfw-sidebar-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </span>
                    <span className="capfw-sidebar-label">{__('API Credentials', 'captain-funnel-for-whatsapp')}</span>
                </Link>
            </nav>

            {/* Footer — version + dark-mode toggle */}
            <div className="capfw-sidebar-footer">
                <span className="capfw-sidebar-version">
                    v{typeof capfw_data !== 'undefined' ? capfw_data.version : '1.0.0'}
                </span>
                <button
                    className="capfw-dm-toggle"
                    onClick={handleToggleDark}
                    title={darkMode ? __('Switch to Light Mode', 'captain-funnel-for-whatsapp') : __('Switch to Dark Mode', 'captain-funnel-for-whatsapp')}
                >
                    <MoonIcon />
                    <SunIcon />
                </button>
            </div>
        </aside>
    );
};

export default Navigation;
