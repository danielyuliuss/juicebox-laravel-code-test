# Laravel REST API Code Test

A RESTful API built with Laravel 13. It includes Sanctum authentication, read-only user endpoints, CRUD endpoints for posts, and an Open-Meteo integration for current weather in Perth, Australia. Weather responses are cached, welcome email delivery uses queued jobs, weather refresh is scheduled hourly, and the API is covered by automated feature tests.

## Requirements

- Docker / Docker Desktop
- WSL2 when using Windows
- Git

PHP and MySQL run in Laravel Sail containers; local PHP and MySQL installations are not required.

## Setup

```bash
git clone https://github.com/danielyuliuss/juicebox-laravel-code-test.git
cd juicebox-laravel-code-test
cp .env.example .env
```

If `vendor/` is missing, install Composer dependencies in a container:

```bash
docker run --rm -v "$PWD:/app" -w /app composer:2 install
```

Configure `.env` to use the MySQL service defined by Sail:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

Start Sail, generate the application key, and apply migrations:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

## Environment

MySQL runs in the Sail `mysql` container. Open-Meteo does not require an API key, and no weather API secret or additional weather environment variable is needed.

## API authentication

Register or log in to receive a Sanctum access token. Send it on protected requests using the `Authorization: Bearer <token>` header. Logout revokes the current token. Register and login are each limited to 5 requests per minute per client.

## API endpoints

All paths are prefixed with `/api`. Endpoints marked **Yes** require a Sanctum Bearer token.

| Method | Path | Auth required | Purpose |
| --- | --- | --- | --- |
| POST | `/api/register` | No | Create an account and issue a token. |
| POST | `/api/login` | No | Verify credentials and issue a token. |
| POST | `/api/logout` | Yes | Revoke the current access token. |
| GET | `/api/users` | Yes | List users with pagination. |
| GET | `/api/users/{user}` | Yes | Retrieve a user. |
| GET | `/api/posts` | No | List posts with their users and pagination. |
| GET | `/api/posts/{post}` | No | Retrieve a post with its user. |
| POST | `/api/posts` | Yes | Create a post for the authenticated user. |
| PATCH | `/api/posts/{post}` | Yes | Update a post owned by the authenticated user. |
| DELETE | `/api/posts/{post}` | Yes | Delete a post owned by the authenticated user. |
| GET | `/api/weather` | No | Retrieve current Perth weather. |

### Request examples

**Register** (`POST /api/register`):

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secret-password",
  "password_confirmation": "secret-password"
}
```

**Login** (`POST /api/login`):

```json
{
  "email": "jane@example.com",
  "password": "secret-password"
}
```

**Create a post** (`POST /api/posts`, Bearer token required):

```json
{
  "title": "A day in Perth",
  "body": "A short post about the day."
}
```

**Update a post** (`PATCH /api/posts/{post}`, Bearer token required):

```json
{
  "title": "An updated title"
}
```

## Pagination

`GET /api/posts` and `GET /api/users` use Laravel pagination with 15 items per page by default.

## Weather

The weather endpoint uses Open-Meteo for the fixed location Perth, Australia. Successful current weather data is cached for 15 minutes. A queued refresh is scheduled hourly. If the provider cannot return weather data, the API responds with HTTP 502.

## Queue

The application uses Laravel's database queue driver. A welcome email is queued after registration. Run a worker to process queued jobs:

```bash
./vendor/bin/sail artisan queue:work
```

## Manual welcome email

The command requires an existing user's email address and queues the same welcome email job used after registration:

```bash
./vendor/bin/sail artisan app:send-welcome-email user@example.com
```

## Scheduler

Run Laravel's scheduler locally to dispatch the hourly weather refresh:

```bash
./vendor/bin/sail artisan schedule:work
```

`RefreshWeather` is scheduled to run every hour.

## Testing

Run the automated test suite with:

```bash
./vendor/bin/sail artisan test
```

The feature tests cover authentication, users, posts, authorization, weather integration, weather caching, and provider failure handling.

## Architecture notes

The API uses Eloquent relationships, Form Requests, Sanctum, Laravel's HTTP client and cache, database queues, and scheduled jobs.
