<p align="center"><img src="docs/assets/basalt-logo.png" alt="Basalt" width="360"></p>

<h2 align="center">Basalt Website</h2>

<p align="center"><strong>The official site of the Basalt project, served at <a href="https://basaltmc.org">basaltmc.org</a>.</strong></p>

<p align="center">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white">
  <img alt="Livewire" src="https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-%E2%89%A58.3-777BB4?logo=php&logoColor=white">
  <img alt="Deploy" src="https://img.shields.io/badge/deploy-on%20push%20to%20main-62B47A?logo=githubactions&logoColor=white">
</p>

## Overview

The Basalt website is built with Laravel, Blade and Livewire. Every push to
`main` is deployed automatically by a self-hosted GitHub Actions runner.

## Getting started

Requires PHP 8.3 or newer, Composer and Node.js.

```sh
git clone https://github.com/BasaltProject/Website.git
cd Website
composer setup
composer dev
```

| Command | Action |
| :--- | :--- |
| `composer dev` | Start Laravel and Vite for development |
| `composer test` | Run the automated test suite |
| `npm run build` | Build production frontend assets |
| `vendor/bin/pint` | Format PHP files |

## Related repositories

| Repository | What it is |
|---|---|
| [Server](https://github.com/BasaltProject/Server) | The Minecraft server itself, in Kotlin/Native |
| [Proxy](https://github.com/BasaltProject/Proxy) | The proxy component |

## License

The Basalt website is distributed under the [MIT License](LICENSE).

Basalt is not affiliated with Mojang Studios or Microsoft. Minecraft is a
trademark of Microsoft.
