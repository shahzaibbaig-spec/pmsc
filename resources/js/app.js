import './bootstrap';
import { AjaxTable } from './ajaxTable';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.AjaxTable = AjaxTable;
window.NSMS = window.NSMS || {};
window.NSMS.debounce = window.NSMS.debounce || function (callback, wait = 300) {
    let timer = null;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), wait);
    };
};
window.NSMS.escapeHtml = window.NSMS.escapeHtml || function (value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

Alpine.start();
