<p align="center">
  <img src="docs/assets/basalt-logo.png" alt="Basalt" width="360">
</p>

<h2 align="center">Basalt Website</h2>

<p align="center">
  <strong>The official site of the Basalt project, served at <a href="https://basaltmc.org">basaltmc.org</a>.</strong>
</p>

<p align="center">
  <img alt="Astro" src="https://img.shields.io/badge/Astro-7.2-FF5D01?logo=astro&logoColor=white">
  <img alt="TypeScript" src="https://img.shields.io/badge/TypeScript-strict-3178C6?logo=typescript&logoColor=white">
  <img alt="Node" src="https://img.shields.io/badge/Node-%E2%89%A522.12-5FA04E?logo=nodedotjs&logoColor=white">
  <img alt="Output" src="https://img.shields.io/badge/output-static-62B47A">
  <img alt="Deploy" src="https://img.shields.io/badge/deploy-on%20push%20to%20main-62B47A?logo=githubactions&logoColor=white">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green">
</p>

## Overview

A static site built with [Astro](https://astro.build). Pages are rendered at
build time into plain HTML, so there is no backend, no database and no CMS
behind it.

It is the web component of the **Basalt** project, a Minecraft server rewritten
from scratch in Kotlin/Native, with no JVM anywhere in the runtime.

| | |
|---|---|
| Framework | Astro 7, static output |
| Language | TypeScript, strict |
| Routing | File based, from `src/pages` |
| Deploys | Automatically, on every push to `main` |
| License | MIT |

## Getting started

Requires Node 22.12 or newer.

```sh
git clone https://github.com/BasaltProject/Website.git
cd Website
npm install
npm run dev
```

| Command | Action |
| :--- | :--- |
| `npm install` | Install dependencies |
| `npm run dev` | Start the dev server at `localhost:4321` |
| `npm run build` | Build the production site into `./dist/` |
| `npm run preview` | Serve the built site locally |
| `npm run astro check` | Type check the project |
| `npm run astro -- --help` | Astro CLI help |

## Project structure

```text
/
├── docs/assets/          Images used by this README
├── public/               Static files, served as they are
├── src/
│   └── pages/            One route per file
└── astro.config.mjs
```

## Related repositories

| Repository | What it is |
|---|---|
| [Server](https://github.com/BasaltProject/Server) | The Minecraft server itself, in Kotlin/Native |
| [Proxy](https://github.com/BasaltProject/Proxy) | The proxy component |

## License

The Basalt website is distributed under the [MIT License](LICENSE).

Basalt is not affiliated with Mojang Studios or Microsoft. Minecraft is a
trademark of Mojang Studios.
