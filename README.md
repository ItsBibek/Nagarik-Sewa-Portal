# Nagarik Sewa Portal

A Laravel-based municipal e-governance portfolio project demonstrating an end-to-end digital workflow for one local-government service: a residential recommendation letter handled by a selected Kathmandu Metropolitan City ward office.

The scope is intentionally focused on one service and one demo ward office. The fee, required documents, and processing time in the seeded data are examples only; they are not verified official KMC requirements, prices, or service-level commitments.

## Features

- Citizen registration, login, dashboard, service application, document upload, and tracking ID.
- Officer queue scoped to their department, application review, correction requests, approval, and rejection.
- Append-only status history for an application audit trail.
- Admin dashboard to review requests and edit the active service's example name, fee, and processing time.
- Role-based access control and application policies.
- Versioned Laravel Sanctum API, safe JSON resources, and public rate-limited tracking.
- Private application-document storage and server-side upload validation.
- Automated PHPUnit feature coverage for authentication, authorization, application submission, workflow, API, and document handling.

## Tech stack

- PHP 8.3+ and Laravel 13
- MySQL 8 / compatible MariaDB (XAMPP can provide the local database)
- Laravel Sanctum for API bearer tokens
- PHPUnit 12 for automated tests
- Blade, Vite, and Tailwind CSS 4 for the UI
- Composer and Node.js/npm for dependencies and assets

## Local installation

### Requirements

Install PHP 8.3+, Composer, MySQL or MariaDB, and Node.js/npm. On Windows, XAMPP may be used for MySQL/MariaDB and phpMyAdmin; PHP and Composer may come from Laravel Herd or another PHP installation.

### 1. Get the code and install dependencies

From the project directory:

```powershell
composer install
npm install
```

### 2. Configure the environment

Create `.env` from the example if it does not already exist:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

On macOS/Linux, use `cp .env.example .env` instead. Edit `.env` and configure the app URL and database. For a typical local XAMPP MySQL setup:

```dotenv
APP_NAME="Nagarik Sewa Portal"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://nagarik-sewa-portal.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nagarik_sewa_portal
DB_USERNAME=root
DB_PASSWORD=
```

Create the `nagarik_sewa_portal` database in phpMyAdmin (or MySQL) before migrating. Use the actual username/password configured for your local database; do not commit `.env`.

Generate the application key:

```powershell
php artisan key:generate
```

### 3. Migrate, seed, and link storage

```powershell
php artisan migrate --seed
php artisan storage:link
```

The symbolic storage link is included for Laravel's public disk. Application documents are intentionally stored on the private `local` disk and are not exposed through that link; authorized downloads go through the application.

### 4. Build frontend assets

```powershell
npm run build
```

### 5. Open the application

With Laravel Herd, configure the project as a site and open `http://nagarik-sewa-portal.test` (or the URL configured in `APP_URL`). Alternatively, start Laravel's local development server:

```powershell
php artisan serve
```

Then visit `http://127.0.0.1:8000`.

## Seeded accounts

`php artisan migrate --seed` creates a demo admin and officer:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@nagarik-sewa.test` | `password` |
| Officer (selected demo ward) | `officer-kmc-ward-01@nagarik-sewa.test` | `password` |

Citizens can register through the web UI; new registrations default to the citizen role. These credentials are for local demonstration only. Change or remove them before deploying anywhere accessible by others.

## Test suite

Run all tests:

```powershell
php artisan test
```

Run a focused feature test:

```powershell
php artisan test --filter=ApplicationSubmissionTest
```

The test environment uses an in-memory SQLite database and fake storage for uploads; tests do not require the XAMPP database or real uploaded files.

## API

The versioned API base path is `/api/v1`. Sanctum bearer tokens protect private endpoints; service discovery and status tracking are public. See [API.md](API.md) for endpoints, access rules, example payloads, responses, error format, and a Postman/Insomnia walkthrough.

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| `GET` | `/api/v1/services` | Public | List active service information and example document requirements |
| `POST` | `/api/v1/tokens` | Public, login throttled | Create a Sanctum bearer token |
| `GET` | `/api/v1/track/{tracking_id}` | Public, 20 requests/minute/IP | Return safe public status and history |
| `GET` | `/api/v1/user` | Sanctum | Return the authenticated profile |
| `GET` | `/api/v1/applications` | Sanctum | List applications visible to the user's role |
| `GET` | `/api/v1/applications/{application}` | Sanctum + policy | View an authorized application |
| `POST` | `/api/v1/applications` | Citizen token | Submit a request and required documents |
| `POST` | `/api/v1/applications/{application}/corrections` | Owning citizen token | Resubmit requested documents |
| `PATCH` | `/api/v1/applications/{application}/status` | Officer/admin token + policy | Update an allowed status |
| `DELETE` | `/api/v1/tokens/current` | Sanctum | Revoke the current token |

API responses use a consistent `{ "data": ..., "message": "..." }` envelope. Validation errors return HTTP 422; authentication and authorization errors return 401 and 403; missing resources return 404. Resources omit private file paths and internal officer remarks.

## Project phases

1. **Environment and database setup** — Laravel project, local PHP/MySQL configuration, and schema foundation.
2. **Database schema** — users, departments, service types, applications, documents, and status history.
3. **Authentication and authorization** — citizen registration, login, Sanctum, role/department middleware, and policies.
4. **Citizen applications** — service catalog, validated document uploads, tracking ID generation, and public tracking.
5. **Staff workflow** — department-scoped officer review, state transitions, audit history, and correction resubmission.
6. **REST API** — versioned Sanctum API, API resources, standardized errors, and public tracking.
7. **Automated testing** — PHPUnit feature tests for submission, authorization, staff workflow, API, and storage behavior.
8. **Documentation and handover** — installation, operational notes, API examples, and review-ready project context.

## Git handover

The project directory may need to be initialized as a Git repository before pushing. From the project root, initialize it only if it is not already a repository:

```powershell
git init
git branch -M main
```

Check that local credentials remain ignored, then stage the final project, review staged changes, and commit using a Conventional Commit message:

```powershell
git check-ignore -v .env
git add -A
git status
git commit -m "docs: prepare project handover"
```

After creating an empty repository on your Git hosting provider, connect it and push (replace the URL with your own repository URL):

```powershell
git remote add origin "https://github.com/YOUR-USERNAME/YOUR-REPOSITORY.git"
git push -u origin main
```

Replace the example URL before running the command. If `origin` already exists, check it with `git remote -v` and use `git remote set-url origin "YOUR-REPOSITORY-URL"` rather than adding it again. Never stage `.env`, API tokens, or real user documents.

## Project license

No project-specific license has been declared. Add one before inviting reuse or contributions; do not assume that Laravel's framework license automatically applies to this project.
