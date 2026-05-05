import './bootstrap';

import Alpine from 'alpinejs';

import './bootstrap';

import { createIcons, icons } from 'lucide';


window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    createIcons({
        icons,
        attrs: {
            'stroke-width': 2,
        },
    });
});