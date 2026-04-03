# Admin Dashboard - Laravel Backend Implementation

Complete Laravel backend implementation for the Admin Dashboard with RBAC, audit logging, and Cloudflare R2 integration.

## Project Structure

```
/workspace
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── UserController.php      # User CRUD with bulk operations
│   │   │   │   ├── CarController.php       # Car management
│   │   │   │   ├── ExpenseController.php   # Expense tracking
│   │   │   │   ├── RoleController.php      # Roles & permissions
│   │   │   │   ├── AuditLogController.php  # Audit log viewer
│   │   │   │   └── StaticDataController.php # Brands, models, fuel types, etc.
│   │   │   └── Controller.php              # Base controller
│   │   └── Middleware/
│   │       ├── CheckPermission.php         # Permission check middleware
│   │       ├── CheckRole.php               # Role check middleware
│   │       └── AuditLogMiddleware.php      # Audit logging middleware
│   └── Models/
│       ├── User.php                        # User model with roles/permissions
│       ├── Role.php                        # Role model
│       ├── Permission.php                  # Permission model
│       ├── Car.php                         # Car model
│       ├── Expense.php                     # Expense model
│       ├── AuditLog.php                    # Audit log model
│       ├── Brand.php                       # Car brand
│       ├── Model.php                       # Car model
│       ├── Year.php                        # Manufacturing year
│       ├── Color.php                       # Car color
│       ├── Shape.php                       # Body shape
│       ├── FuelType.php                    # Fuel type with pricing
│       ├── MaintenanceCategory.php         # Maintenance categories
│       ├── ExpenseCategory.php             # Other expense categories
│       ├── Cylinder.php                    # Cylinder configurations
│       └── RefreshToken.php                # JWT refresh tokens
├── config/
│   └── filesystems.php                     # Cloudflare R2 configuration
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_roles_and_permissions_tables.php
│   │   ├── 2024_01_01_000002_add_admin_fields_to_users_table.php
│   │   ├── 2024_01_01_000003_create_car_static_data_tables.php
│   │   └── 2024_01_01_000004_create_cars_and_expenses_tables.php
│   └── seeders/
│       └── RolesAndPermissionsSeeder.php   # Default roles & permissions
├── routes/
│   └── admin.php                           # Admin API routes
├── resources/views/admin/                  # Blade templates (optional)
├── storage/app/public                      # Local file storage
└── .env.example                            # Environment configuration template
```

## Installation

### Prerequisites
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Cloudflare R2 account (for file storage)

### Setup Steps

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Update `.env` with your settings:**
   ```env
   # Database
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=admin_dashboard
   DB_USERNAME=root
   DB_PASSWORD=secret

   # Cloudflare R2
   FILESYSTEM_DISK=r2
   R2_ACCESS_KEY_ID=your_access_key
   R2_SECRET_ACCESS_KEY=your_secret_key
   R2_BUCKET=your-bucket-name
   R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
   ```

4. **Run migrations:**
   ```bash
   php artisan migrate
   ```

5. **Seed default roles and permissions:**
   ```bash
   php artisan db:seed --class=RolesAndPermissionsSeeder
   ```

6. **Start the server:**
   ```bash
   php artisan serve
   ```

## Default Credentials

After seeding:
- **Email:** admin@example.com
- **Password:** password123

**⚠️ Change this immediately in production!**

## API Endpoints

All endpoints require JWT/Sanctum authentication and are prefixed with `/api/admin`.

### Dashboard
| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/dashboard` | - | Dashboard statistics |

### Users
| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/users` | users.read | List all users (paginated) |
| GET | `/users/{id}` | users.read | Get user details |
| POST | `/users` | users.create | Create new user |
| PUT | `/users/{id}` | users.update | Update user |
| DELETE | `/users/{id}` | users.delete | Soft delete user |
| POST | `/users/bulk-delete` | users.delete | Bulk delete users |
| POST | `/users/{id}/reset-password` | users.update | Reset user password |
| GET | `/users/export` | users.read | Export users to CSV |

### Cars
| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/cars` | cars.read | List all cars |
| GET | `/cars/{id}` | cars.read | Get car details |
| POST | `/cars` | cars.create | Create car |
| PUT | `/cars/{id}` | cars.update | Update car |
| DELETE | `/cars/{id}` | cars.delete | Delete car |
| POST | `/cars/bulk-delete` | cars.delete | Bulk delete |
| GET | `/cars/export` | cars.read | Export to CSV |

### Expenses
| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/expenses` | expenses.read | List expenses |
| GET | `/expenses/{id}` | expenses.read | Get expense details |
| POST | `/expenses` | expenses.create | Create expense |
| PUT | `/expenses/{id}` | expenses.update | Update expense |
| DELETE | `/expenses/{id}` | expenses.delete | Delete expense |
| GET | `/expenses/statistics` | expenses.read | Get expense stats |
| GET | `/expenses/export` | expenses.read | Export to CSV |

### Roles & Permissions
| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/roles` | roles.read | List roles |
| GET | `/roles/{id}` | roles.read | Get role details |
| POST | `/roles` | roles.create | Create role |
| PUT | `/roles/{id}` | roles.update | Update role |
| DELETE | `/roles/{id}` | roles.delete | Delete role |
| GET | `/permissions` | roles.read | List all permissions |
| POST | `/roles/{id}/permissions` | roles.update | Assign permissions |

### Static Data (Brands, Models, Fuel Types, etc.)
| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/static/{model}` | static_data.read | List items |
| GET | `/static/{model}/{id}` | static_data.read | Get item |
| POST | `/static/{model}` | static_data.create | Create item |
| PUT | `/static/{model}/{id}` | static_data.update | Update item |
| DELETE | `/static/{model}/{id}` | static_data.delete | Delete item |
| POST | `/static/{model}/import` | static_data.create | Import CSV |

Supported models: `brands`, `models`, `years`, `colors`, `shapes`, `fuel_types`, `maintenance_categories`, `expense_categories`, `cylinders`

### Audit Logs
| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/audit-logs` | audit_logs.read | List audit logs |
| GET | `/audit-logs/{id}` | audit_logs.read | Get log details |
| GET | `/audit-logs/statistics` | audit_logs.read | Get statistics |
| GET | `/audit-logs/export` | audit_logs.read | Export to CSV |

## Roles & Permissions

### Default Roles

| Role | Permissions |
|------|-------------|
| **Super Admin** | All permissions |
| **Admin** | All except roles management |
| **Editor** | Read + Create + Update (no delete) |
| **Viewer** | Read-only access |

### Permission Format

Permissions follow the pattern: `{module}.{action}`

Examples:
- `users.read`, `users.create`, `users.update`, `users.delete`
- `cars.read`, `cars.create`, `cars.update`, `cars.delete`
- `expenses.read`, `expenses.create`, `expenses.update`, `expenses.delete`

## Features

### Role-Based Access Control (RBAC)
- Server-side permission enforcement on every request
- Middleware checks JWT token against user permissions
- Super Admin bypasses all permission checks
- Least privilege principle enforced

### Audit Logging
- Every CRUD operation is logged
- Logs include: user, action, module, old/new values, IP, timestamp
- Filterable and exportable via admin interface
- Accessible only to users with `audit_logs.read` permission

### File Storage (Cloudflare R2)
- All uploads stored on Cloudflare R2
- S3-compatible driver configured in `config/filesystems.php`
- Supports: profile photos, car images, license scans, receipts
- Files can be private or public based on bucket settings

### Bulk Operations
- Multi-select rows for bulk actions
- Bulk delete with confirmation
- CSV export for all major modules
- CSV import for static data

### Security Features
- JWT access tokens (short-lived) + refresh tokens
- Rate limiting on sensitive endpoints
- Input validation on all requests
- Soft deletes for data recovery
- HTTPS recommended for production

## Query Parameters

Most list endpoints support:
- `search` - Text search
- `per_page` - Items per page (default: 15)
- `page` - Page number
- Module-specific filters (e.g., `user_id`, `type`, `start_date`, `end_date`)

Example:
```
GET /api/admin/cars?user_id=5&brand_id=3&per_page=25
GET /api/admin/expenses?type=fuel&start_date=2024-01-01&end_date=2024-12-31
```

## License

MIT License
