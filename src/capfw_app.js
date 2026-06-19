import React from 'react';
import { HashRouter, Routes, Route } from 'react-router-dom';
import routes     from './router/routes.js';
import Navigation from './page/navigation/navigation.jsx';

const AppContent = () => (
    <div className="capfw-app">
        <Navigation />
        <div className="capfw-app-main-content">
            <Routes>
                {routes.map((route, i) => (
                    <Route key={i} path={route.path} element={route.element} />
                ))}
            </Routes>
        </div>
    </div>
);

const CapfwApp = () => (
    <HashRouter>
        <AppContent />
    </HashRouter>
);

export default CapfwApp;
