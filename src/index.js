import { createRoot } from 'react-dom/client';
import { Provider }   from 'react-redux';
import { store }      from './redux/store.js';
import CapfwApp         from './capfw_app.js';
import './style/global.scss';

const container = document.getElementById('capfw-react-app');

if (container) {
    const root = createRoot(container);
    root.render(
        <Provider store={store}>
            <CapfwApp />
        </Provider>
    );
}
