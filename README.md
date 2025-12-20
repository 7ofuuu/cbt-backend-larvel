# CBT Backend - Laravel API

A Laravel-based backend API for Computer-Based Test (CBT) system with role-based user management.

## Features

- **Role-Based User Management**: Support for three user roles:
  - **Admin**: System administrators
  - **Guru** (Teacher): Teachers who manage tests
  - **Siswa** (Student): Students who take tests
- **User API**: RESTful API to retrieve all users with their profiles
- **Database Relations**: Properly structured relationships between users and their role-specific profiles

## Tech Stack

- **Framework**: Laravel 12.x
- **PHP**: 8.2+
- **Database**: MySQL
- **API**: RESTful API

## Database Schema

The system uses a multi-table structure with the following tables:

### Users Table
- `id`: Primary key
- `username`: Unique username
- `password`: Hashed password
- `role`: ENUM ('admin', 'guru', 'siswa')
- `status_aktif`: Boolean (default: true)
- `createdAt`: Timestamp
- `updatedAt`: Timestamp

### Role-Specific Tables
- **admins**: Links to users with admin role
- **gurus**: Links to users with guru role
- **siswas**: Links to users with siswa role (includes kelas, tingkat, jurusan)

All role tables have a foreign key `userId` that references `users.id` with CASCADE delete.

## Prerequisites

Before you begin, ensure you have the following installed:
- PHP 8.2 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js & NPM (optional, for frontend assets)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd cbt-backend-larvel
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit the `.env` file and configure your database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cbt_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Create Database

Create a MySQL database with the name you specified in `.env`:

```sql
CREATE DATABASE cbt_database;
```

### 6. Run Migrations

If you're using migrations (recommended), run:

```bash
php artisan migrate
```

**Note**: If you're importing from an existing Prisma migration SQL, you can import it directly:

```bash
mysql -u your_username -p cbt_database < your_migration.sql
```

### 7. Start Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## API Documentation

### Get All Users

Retrieve all users with their role-specific profiles.

**Endpoint**: `GET /api/users`

**Response Example**:

```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": [
    {
      "id": 1,
      "username": "admin1",
      "role": "admin",
      "status_aktif": true,
      "createdAt": "2025-12-20T10:00:00.000000Z",
      "updatedAt": "2025-12-20T10:00:00.000000Z",
      "profile": {
        "admin_id": 1,
        "nama_lengkap": "Administrator"
      }
    },
    {
      "id": 2,
      "username": "guru1",
      "role": "guru",
      "status_aktif": true,
      "createdAt": "2025-12-20T10:00:00.000000Z",
      "updatedAt": "2025-12-20T10:00:00.000000Z",
      "profile": {
        "guru_id": 1,
        "nama_lengkap": "Budi Santoso"
      }
    },
    {
      "id": 3,
      "username": "siswa1",
      "role": "siswa",
      "status_aktif": true,
      "createdAt": "2025-12-20T10:00:00.000000Z",
      "updatedAt": "2025-12-20T10:00:00.000000Z",
      "profile": {
        "siswa_id": 1,
        "nama_lengkap": "Ani Wijaya",
        "kelas": "A",
        "tingkat": "12",
        "jurusan": "IPA"
      }
    }
  ]
}
```

**Testing with Postman**:
1. Create a new GET request
2. URL: `http://localhost:8000/api/users`
3. Send the request

**Testing with cURL**:

```bash
curl http://localhost:8000/api/users
```

## Project Structure

```
cbt-backend-larvel/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── UsersController.php    # User API controller
│   └── Models/
│       ├── User.php                   # User model with relationships
│       ├── Admin.php                  # Admin model
│       ├── Guru.php                   # Guru model
│       └── Siswa.php                  # Siswa model
├── routes/
│   ├── api.php                        # API routes
│   └── web.php                        # Web routes
├── database/
│   └── migrations/                    # Database migrations
└── .env                               # Environment configuration
```

## Models & Relationships

### User Model
- Has one relationship with `Admin`, `Guru`, or `Siswa` based on role
- Custom timestamp columns: `createdAt`, `updatedAt`
- Hidden field: `password`

### Admin, Guru, Siswa Models
- Belongs to `User`
- Primary keys: `admin_id`, `guru_id`, `siswa_id` respectively
- No timestamps

## Development Tips

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### View Routes
```bash
php artisan route:list
```

### Database Seeding (Optional)

Create seeders for testing:

```bash
php artisan make:seeder UserSeeder
```

### Testing

Run tests:

```bash
php artisan test
```

## Troubleshooting

### 404 Error on API Routes
- Ensure `routes/api.php` is registered in `bootstrap/app.php`
- Clear route cache: `php artisan route:clear`
- Restart development server

### Database Connection Issues
- Verify database credentials in `.env`
- Ensure MySQL service is running
- Check database exists: `SHOW DATABASES;`

### Class Not Found Errors
- Run: `composer dump-autoload`
- Clear cache: `php artisan cache:clear`

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/new-feature`
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
