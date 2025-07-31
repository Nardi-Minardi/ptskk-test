# PTSKK Test - Laravel API

Repositori ini adalah backend API berbasis Laravel yang digunakan untuk project **PTSKK Test**.

## Clone Project

```bash
git clone https://github.com/Nardi-Minardi/ptskk-test.git
cd ptskk-test


# Install dependencies
composer install

# Copy .env.example to .env
cp .env.example .env

# Generate application key
php artisan key:generate

# Sebelum menjalankan migrasi, pastikan database sudah dibuat dan konfigurasi pada file .env sudah benar.
# Run migrations
php artisan migrate

# Start the development server
php artisan serve
```
## Testing Endpoint
Untuk melakukan testing endpoint, Anda dapat menggunakan Postman atau alat serupa. Berikut adalah beberapa endpoint yang tersedia:
### Auth
- **POST** `/api/register` - Registrasi pengguna baru
- **POST** `/api/verify-email` - Verifikasi email pengguna
- **POST** `/api/login` - Login untuk mendapatkan token
- **POST** `/api/refresh-token` - Refresh token untuk mendapatkan token baru
- **GET** `/api/user` - Mendapatkan informasi pengguna yang sedang login
- **POST** `/api/logout` - Logout untuk menghapus token