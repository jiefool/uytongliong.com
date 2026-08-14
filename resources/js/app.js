import './bootstrap';
import './event-posters';
import { createApp } from 'vue';

const appElement = document.getElementById('app');

if (appElement) {
    import('./components/FamilyTreeApp.vue').then(({ default: FamilyTreeApp }) => {
        createApp(FamilyTreeApp).mount(appElement);
    });
}
