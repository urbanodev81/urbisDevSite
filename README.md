# UrbisDev Site

Landing page da **UrbisDev** — Software que resolve problemas reais.

## Stack

- **Laravel 12** — PHP 8.3
- **Docker** — Nginx + PHP-FPM + Mailpit
- **Turnstile** — Cloudflare (proteção anti-spam no formulário)

## Rodar localmente

```bash
docker compose up -d --build
```

Acesse: http://localhost:8100

Mailpit (emails de teste): http://localhost:8027

## Estrutura

```
.
├── docker-compose.yml    # Nginx + PHP-FPM + Mailpit
├── Dockerfile             # PHP 8.3-FPM Alpine
├── nginx.conf             # Proxy reverso
└── src/                   # Laravel 12
    ├── app/Http/Controllers/
    ├── routes/web.php
    ├── resources/views/
    │   └── welcome.blade.php
    └── public/
```

## Licença

MIT
