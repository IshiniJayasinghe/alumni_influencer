# Alumni Influencer

## Project Overview

Alumni Influencer is a CodeIgniter 4 coursework project that supports alumni account management, profile enrichment, blind bidding for daily featured placement, university analytics dashboards, and developer-facing APIs with scoped API keys.

The project combines the main CW1 profile and bidding features with CW2 analytics, API access, exports, reporting, and documentation.

## Key Features

### CW1 Features

- alumni registration and login
- university email verification
- forgot-password and reset-password flow
- profile management with bio, LinkedIn, image, qualifications, and employment history
- blind bidding system
- increase-only bid updates
- monthly bidding limit tracking
- automated featured winner selection

### CW2 Features

- university analytics dashboard
- alumni filtering and exports to CSV/PDF
- analytics charts and downloadable images
- custom reports
- scoped API key generation
- Swagger / OpenAPI documentation
- analytics API with filters
- featured alumnus API
- alumni directory API
- API usage logging for developers

## Setup And Run

### Requirements

- XAMPP with Apache and MySQL
- PHP 8.2+
- MySQL database named `alumni_influencer`

### Steps

1. Place the project folder in:
   `C:\xampp\htdocs\alumni_influencer`
2. Start Apache and MySQL in XAMPP.
3. Import your existing coursework database into MySQL.
4. If any expected columns or tables are missing, run:
   [DATABASE_PATCH.sql](C:/xampp/htdocs/alumni_influencer/DATABASE_PATCH.sql:1)
5. Check database credentials in:
   [app/Config/Database.php](C:/xampp/htdocs/alumni_influencer/app/Config/Database.php:1)
6. Open the application:
   [http://localhost/alumni_influencer/public/](http://localhost/alumni_influencer/public/)

### Environment File

- A real `.env` file is not required for submission.
- Use [.env.example](C:/xampp/htdocs/alumni_influencer/.env.example:1) as the reference template if local overrides are needed.
- Do not submit real secrets, SMTP passwords, or production credentials.

## API Usage

API keys are generated from the developer portal:
[http://localhost/alumni_influencer/public/developer](http://localhost/alumni_influencer/public/developer)

### Available API Scopes

- `read:alumni`
- `read:analytics`
- `read:alumni_of_day`

### Scope Enforcement

- `/api/alumni` requires `read:alumni`
- `/api/analytics/*` requires `read:analytics`
- `/api/featured` requires `read:alumni_of_day`

If a valid key does not have the required scope, the API returns `403 Forbidden`.

### Example Alumni API Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" ^
  "http://localhost/alumni_influencer/public/api/alumni?search=Jane&graduation_year=2026"
```

### Example Analytics API Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" ^
  "http://localhost/alumni_influencer/public/api/analytics/summary?programme=BSc%20Computer%20Science&industry_sector=Information%20Technology%20(IT)"
```

### Example Featured Alumnus API Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" ^
  "http://localhost/alumni_influencer/public/api/featured"
```

## Cron Job Winner Selection

Winner selection is configured to run once per day at **6 PM**.

Route:
- `/cron/pick-winner`

Protection:
- requires the `X-Cron-Secret` header
- secret value should be stored in local configuration, using `.env.example` as the template

Example cron entry:

```bash
0 18 * * * curl -s -H "X-Cron-Secret: YOUR_SECRET" http://localhost/alumni_influencer/public/cron/pick-winner
```

How it works:

1. the cron route checks the secret header
2. it finds the highest active bid for the current day
3. it records the winner in `featured_winners`
4. it marks bids as won or lost
5. it sends result notifications to bidders

## Architecture

### System Architecture

```text
+--------------------+        HTTP Requests         +----------------------+
| Frontend Views     | ---------------------------> | Controllers          |
| - Auth pages       |                              | - AuthController     |
| - Profile pages    | <--------------------------- | - ProfileController  |
| - Bids UI          |      HTML / JSON / Files     | - BidController      |
| - Dashboard UI     |                              | - DashboardController|
| - Developer UI     |                              | - ApiController      |
+--------------------+                              | - AnalyticsApi...    |
                                                    | - DeveloperController|
                                                    | - CronController     |
                                                    +----------+-----------+
                                                               |
                                                               v
                                                    +----------------------+
                                                    | Models / Query Layer |
                                                    | - UserModel          |
                                                    | - BidModel           |
                                                    | - ApiKeyModel        |
                                                    | - ApiUsageLogModel   |
                                                    | - FeaturedWinner...  |
                                                    +----------+-----------+
                                                               |
                                                               v
                                                    +----------------------+
                                                    | MySQL Database       |
                                                    | alumni_influencer    |
                                                    +----------------------+
```

### API Flow

```text
Client Request
   |
   v
Routes.php
   |
   v
Controller method
   |
   +--> authenticate session or Bearer token
   +--> validate scope
   +--> query database
   +--> optionally log API usage
   |
   v
JSON / HTML Response
```

### Database Schema Diagram

```text
users
 |- id (PK)
 |- role
 |- email
 |- programme
 |- graduation_year
 |
 +--< degrees
 |    |- id (PK)
 |    |- user_id (FK -> users.id)
 |
 +--< certifications
 |    |- id (PK)
 |    |- user_id (FK -> users.id)
 |
 +--< professional_licences
 |    |- id (PK)
 |    |- user_id (FK -> users.id)
 |
 +--< short_courses
 |    |- id (PK)
 |    |- user_id (FK -> users.id)
 |
 +--< employment_history
 |    |- id (PK)
 |    |- user_id (FK -> users.id)
 |
 +--< bids
 |    |- id (PK)
 |    |- user_id (FK -> users.id)
 |
 +--< api_keys
      |- id (PK)
      |- user_id (FK -> users.id)
      |
      +--< api_usage_logs
           |- id (PK)
           |- api_key_id (FK -> api_keys.id)

featured_winners
 |- id (PK)
 |- user_id (FK -> users.id)
 |- bid_id (FK -> bids.id)

email_verifications
 |- user_id (FK -> users.id)

password_resets
 |- user_id (FK -> users.id)
```

## Database Schema Documentation

### Main Tables

- `users`
  Stores core account and profile data such as role, email, name, programme, graduation details, bio, LinkedIn URL, and current job title.
- `degrees`
  Stores alumni degree records, including institution and completion date.
- `certifications`
  Stores professional certifications and related completion data.
- `professional_licences`
  Stores licences and professional registrations.
- `short_courses`
  Stores short course information.
- `employment_history`
  Stores employer, job title, industry sector, and employment dates.
- `bids`
  Stores daily bids submitted by alumni for featured placement.
- `featured_winners`
  Stores the winning bid and user for each feature date.
- `api_keys`
  Stores developer-generated API keys and their permission scopes.
- `api_usage_logs`
  Stores API usage activity by key, endpoint, method, IP, and timestamp.
- `email_verifications`
  Stores secure verification tokens for account activation.
- `password_resets`
  Stores secure password reset tokens.

### Relationships

- one `user` can have many `degrees`
- one `user` can have many `certifications`
- one `user` can have many `professional_licences`
- one `user` can have many `short_courses`
- one `user` can have many `employment_history` records
- one `user` can have many `bids`
- one `user` can have many `api_keys`
- one `api_key` can have many `api_usage_logs`
- one `bid` can become one `featured_winners` record for a date

### Key Field Purposes

- `users.role`
  Distinguishes alumnus and developer access.
- `users.is_verified`
  Tracks whether email verification is complete.
- `users.programme`
  Supports alumni directory filtering and analytics grouping.
- `users.graduation_year`
  Supports graduation-year analytics and filtering.
- `api_keys.api_key`
  The Bearer token value used by external clients.
- `api_keys.permissions`
  JSON list of allowed scopes such as `read:alumni`, `read:analytics`, and `read:alumni_of_day`.
- `api_keys.is_active`
  Used to revoke keys without deleting them.
- `api_usage_logs.endpoint`
  Records which API route was called.
- `api_usage_logs.used_at`
  Records when the API request happened.
- `email_verifications.token_hash`
  Stores the secure verification token hash.
- `password_resets.token_hash`
  Stores the secure reset token hash.
- `featured_winners.feature_date`
  Stores the date for which the alumnus is featured.

## Submission Notes

- The project should be submitted without a real `.env` file.
- Use `.env.example` as the safe template.
- Do not include real secrets or personal credentials.
- The project does not require `.git` metadata for runtime or grading.
- OpenAPI documentation is available at:
  [http://localhost/alumni_influencer/public/openapi.json](http://localhost/alumni_influencer/public/openapi.json)
