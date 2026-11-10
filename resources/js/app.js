import './bootstrap';
import './event-posters';
import './events';
import { createApp } from 'vue';

const appElement = document.getElementById('app');

if (appElement) {
    import('./components/FamilyTreeApp.vue').then(({ default: FamilyTreeApp }) => {
        createApp(FamilyTreeApp).mount(appElement);
    });
}

const treeViewElement = document.getElementById('tree-view-app');

if (treeViewElement) {
    import('./components/FamilyTreeView.vue').then(({ default: FamilyTreeView }) => {
        createApp(FamilyTreeView, { pinViewer: treeViewElement.dataset.pinViewer === 'true' }).mount(treeViewElement);
    });
}
