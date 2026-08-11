import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://basaltmc.org',
  vite: {
    preview: {
      allowedHosts: ['basaltmc.org', 'www.basaltmc.org'],
    },
  },
});
