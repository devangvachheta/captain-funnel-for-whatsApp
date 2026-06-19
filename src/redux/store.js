import { configureStore } from '@reduxjs/toolkit';
import capfwReducer from './slice.js';

export const store = configureStore({
    reducer: {
        capfw: capfwReducer,
    },
});
