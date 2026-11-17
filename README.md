# AquaKelola — kode sumber (Laravel 11)

File-file di folder ini adalah **overlay** untuk proyek Laravel 11 baru.

## Pemasangan

```bash
composer create-project laravel/laravel aquakelola
cd aquakelola

# salin seluruh isi paket ini ke dalam proyek (timpa file yang sama):
#   app/  bootstrap/app.php  database/  resources/views/  routes/web.php
# hapus view bawaan (welcome.blade.php) bila mau

# .env  -> atur database (MySQL/SQLite) dan:
#   APP_LOCALE=id
#   APP_TIMEZONE=Asia/Jakarta     (di config/app.php: 'timezone' => env('APP_TIMEZONE','Asia/Jakarta'))
#   SESSION_DRIVER=database

php artisan migrate:fresh --seed
php artisan serve
```

Akun demo (kata sandi `password`):
- Pengelola: `andi@aquakelola.test`
- Mitra / Pemilik: `pemilik@aquakelola.test`

Tailwind CSS, Alpine.js, dan Chart.js dimuat lewat CDN supaya langsung jalan tanpa build.
Untuk produksi, pindahkan ke Vite.
