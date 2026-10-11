# Web Security Lab

A full-stack web application security learning project built with Vue 3 and Laravel. The project focuses on session-based authentication, secure API design, and automated security testing.

## Features

- User registration with server-side validation
- Password hashing and duplicate-email validation
- Login and logout using Laravel Sanctum session authentication
- CSRF protection for stateful SPA requests
- Protected dashboard and authenticated user profile
- Vue Router guards and centralized Pinia authentication state
- Rate limiting for login and registration
- Automated Laravel feature tests
- Email-verification notifications and signed verification links
- Separate MySQL development and test databases
- Docker Compose for local development services

## Technology Stack

| Category | Technologies |
|---|---|
| Frontend | Vue 3, TypeScript, Vite |
| State Management | Pinia |
| Routing | Vue Router |
| HTTP Client | Axios |
| Backend | PHP, Laravel 13 |
| Authentication | Laravel Sanctum |
| Database | MySQL 8 |
| Development Services | Docker Compose, Redis |
| Testing | PHPUnit, Laravel Feature Tests |

## Project Structure

```text
web-security-lab/
├── backend/          # Laravel API, models, services, and tests
├── frontend/         # Vue 3 application
├── docs/             # Project documentation
├── security-tests/   # Security testing resources
├── docker-compose.yml
├── .env.example
├── .gitignore
└── README.md
```

## Local Development

### Requirements

- PHP and Composer
- Node.js and npm
- Docker Engine and Docker Compose

### 1. Configure Docker environment

From the project root:

```bash
cp .env.example .env
```

Set strong local values for `MYSQL_PASSWORD` and `MYSQL_ROOT_PASSWORD` in the root `.env` file.

Do not commit `.env` files or real credentials.

### 2. Start development services

```bash
docker compose up -d
```

This starts the MySQL and Redis containers.

### 3. Configure the Laravel backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
```

Configure the backend `.env` to use the local MySQL instance:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_security_lab
DB_USERNAME=lab_user
DB_PASSWORD=your_local_mysql_password
```

Set the local email-testing configuration:

```dotenv
MAIL_MAILER=log
FRONTEND_URL=http://localhost:5173
```

Use the same database password configured as `MYSQL_PASSWORD` in the root `.env`.

Run the migrations:

```bash
php artisan migrate
```

Start the backend server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

### 4. Configure the Vue frontend

Open another terminal:

```bash
cd frontend
npm install
npm run dev
```

Open the development URL printed by Vite, normally:

http://localhost:5173

### 5. Run the automated tests

Configure a separate MySQL test database named `web_security_lab_test` before running the Laravel test suite.

Run the backend tests:

```bash
cd backend
php artisan test
```

Build the frontend:

```bash
cd frontend
npm run build
```

## API Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/health` | API health check |
| POST | `/api/v1/auth/register` | Register a new account |
| POST | `/api/v1/auth/login` | Authenticate a user |
| POST | `/api/v1/auth/logout` | End the authenticated session |
| GET | `/api/user` | Retrieve the authenticated user's profile |
| GET | `/email/verify/{id}/{hash}` | Verify an email using a signed link |

Protected endpoints require the appropriate Laravel authentication session. Registration and login requests are rate-limited.

## Security Measures

- Password hashing using Laravel's password-hashing functionality
- Server-side input validation
- Duplicate-email prevention
- CSRF protection for stateful SPA requests
- Laravel Sanctum session authentication
- Authentication middleware for protected endpoints
- Rate limiting for login and registration
- Signed and expiring email-verification links
- Automated tests for authentication and verification behavior

## Testing Coverage

The automated Laravel tests cover:

- Successful user registration
- Duplicate-email rejection
- Password hashing
- Password confirmation validation
- Minimum password length validation
- Successful and unsuccessful login
- Logout behavior
- Authenticated profile access
- Guest access denial
- Login and registration rate limiting
- Email-verification notifications
- Valid, tampered, and expired verification links

## Current Status

This project is under active development.

The authentication and automated testing foundation is implemented. Email-verification functionality is in progress, including the Vue verification result page and Laravel signed verification endpoint.

## Security Notes

- Never commit `.env` files, application keys, database passwords, API tokens, session cookies, or private user data.
- Use only systems you own or are authorized to test.
- Use HTTPS and secure session cookies in production.
- Review dependencies and configuration before deployment.

## Purpose

This project demonstrates practical full-stack development, secure authentication, API security fundamentals, and automated testing as part of a cybersecurity portfolio.
