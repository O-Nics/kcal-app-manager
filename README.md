# Kcal App Manager

Backend API for a nutrition coaching / calorie-tracking application. Nutrition professionals manage their customers, generate personalized nutrition programs, and customers log their food, water intake and weight over time.

Built with **Laravel 12** and **PHP 8.4**.

## ✨ Features

### Customer management
- Create and manage customers (clients)
- Track weight history and daily water intake
- Per-customer password/account management

### Nutrition program engine
- Configurable rule/policy-based engine (`ProgramPlanner`) that computes macro ratios and calorie distribution to generate a personalized nutrition program per customer

### Food & ingredient tracking
- Barcode lookup via the [Open Food Facts](https://world.openfoodfacts.org/) database
- Custom ingredients, brands, and favorites per customer
- Food diary entries per customer

### Engineering
- Feature-based architecture (`app/Features/{Feature}`), each with its own Controllers/Data/Services
- DTOs via Spatie Laravel Data
- Sanctum token authentication with ability-scoped middleware
- Image processing (Intervention Image) for document/avatar handling
- Tested with Pest/PHPUnit

## 📋 Requirements

- PHP 8.3 or 8.4
- ext-imagick
- MySQL 8.0+ / PostgreSQL 14+ / SQLite 3.35+

## 🚀 Installation

```bash
git clone git@github.com:O-Nics/kcal-app-manager.git
cd kcal-app-manager

composer install
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then:
php artisan migrate

php artisan serve
```

## 📡 Main API Endpoints

```
POST   /api/auth/login
GET    /api/app/initial-data

GET    /api/customers
GET    /api/customers/{customer}
PATCH  /api/customers/{customer}
POST   /api/customers/{customer}/program
POST   /api/customers/{customer}/weight
POST   /api/customers/{customer}/water

GET    /api/foods/ingredients/{customer}/{barcode}
POST   /api/foods/ingredients/{customer}
POST   /api/foods/ingredients/{customer}/{ingredient}/favorite

GET    /api/foods/brands/{customer}
POST   /api/foods/brands/{customer}
```

> Some endpoints are still under active development.

## 🏗️ Architecture

```
app/
├── Features/
│   ├── Auth/
│   ├── Customer/            Customers, weight, water tracking
│   ├── Food/                Ingredients, brands, Open Food Facts lookup
│   ├── ProgramPlanner/
│   │   └── Nutrition/       Macro/calorie engine (Domain + Policy + Engine)
│   ├── Document/
│   └── User/
└── Support/                 Shared base classes, enums, middlewares, repositories
```

## 🧪 Testing

```bash
composer test
```

## 📝 License

MIT
