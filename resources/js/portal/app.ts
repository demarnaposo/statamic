import './portal.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, type DefineComponent } from 'vue';

const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue', { eager: true });

createInertiaApp({
  title: (title) => (title ? `${title} · Portal Sertifika` : 'Portal Sertifika'),
  resolve: (name) => {
    const page = pages[`./pages/${name}.vue`];
    if (!page) throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
    return page;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
  progress: { color: '#f2b84b' },
});
