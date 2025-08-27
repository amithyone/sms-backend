# SMS Backend API

A Laravel-based backend API for SMS, VTU, and Proxy services with authentication and transaction management.

## Features

- **Authentication System**: User registration, login, and logout with Sanctum
- **SMS Services**: SMS ordering and management
- **VTU Services**: Virtual Top-Up services
- **Proxy Services**: Proxy purchase and management
- **Service Management**: CRUD operations for services
- **Transaction Tracking**: Complete transaction history
- **Mobile-First Design**: Optimized for mobile applications

## Tech Stack

- **Framework**: Laravel 10.x
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **Containerization**: Docker
- **Web Server**: Nginx
- **Process Manager**: Supervisor

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Docker (optional, for containerized deployment)

## Installation

### Local Development

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd sms-backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   - Update `.env` file with your database credentials
   - Run migrations: `php artisan migrate`
   - Seed the database: `php artisan db:seed`

5. **Start the development server**
   ```bash
   php artisan serve
   ```

### Docker Deployment

1. **Build and run with Docker**
   ```bash
   docker build -t sms-backend .
   docker run -p 8000:8000 sms-backend
   ```

2. **Using Docker Compose (recommended)**
   ```bash
   docker-compose up -d
   ```

## API Endpoints

### Public Endpoints

- `GET /api/test` - Test API connectivity
- `GET /api/services` - Get all services
- `POST /api/register` - User registration
- `POST /api/login` - User login

### Protected Endpoints (Require Authentication)

#### Service Management
- `POST /api/services` - Create new service
- `PUT /api/services/{id}` - Update service
- `DELETE /api/services/{id}` - Delete service

#### SMS Services
- `GET /api/sms/services` - Get SMS services
- `POST /api/sms/order` - Create SMS order
- `GET /api/sms/orders` - Get user's SMS orders
- `GET /api/sms/orders/{id}` - Get specific SMS order

#### VTU Services
- `GET /api/vtu/services` - Get VTU services
- `POST /api/vtu/purchase` - Purchase VTU service
- `GET /api/vtu/transactions` - Get VTU transactions

#### Proxy Services
- `GET /api/proxy/services` - Get proxy services
- `POST /api/proxy/purchase` - Purchase proxy service
- `GET /api/proxy/transactions` - Get proxy transactions

#### Authentication
- `POST /api/logout` - User logout
- `GET /api/user` - Get authenticated user info

## Authentication

The API uses Laravel Sanctum for authentication. Include the bearer token in the Authorization header:

```
Authorization: Bearer <your-token>
```

## Database Schema

### Users Table
- id, name, email, password, created_at, updated_at

### Services Table
- id, name, description, price, category, created_at, updated_at

### Transactions Table
- id, user_id, service_id, amount, status, created_at, updated_at

### SMS Services Table
- id, name, description, price, provider, created_at, updated_at

### SMS Orders Table
- id, user_id, sms_service_id, phone_number, message, status, created_at, updated_at

## Environment Variables

Key environment variables to configure:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=newsms_db
DB_USERNAME=root
DB_PASSWORD=

CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
```

## Testing

Run the test suite:

```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support, please contact the development team or create an issue in the repository.
