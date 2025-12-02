# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Asalny is a Laravel 10 REST API for a location-based mobile service with SMS-verified authentication. No password-based login - users authenticate via Twilio SMS verification codes.

**Tech Stack:** PHP 8.1+, Laravel 10, Filament 2.17 (admin), Laravel Sanctum (API auth), Twilio SDK (SMS), MySQL

## Common Commands

```bash
# Development server
php artisan serve

# Database migrations
php artisan migrate
php artisan migrate:fresh          # Reset and re-run all migrations

# Testing
./vendor/bin/phpunit               # All tests
./vendor/bin/phpunit tests/Feature # Feature tests only
./vendor/bin/phpunit tests/Unit    # Unit tests only
./vendor/bin/phpunit --filter=TestName  # Single test

# Code style
./vendor/bin/pint                  # Fix code style (Laravel Pint)
./vendor/bin/pint --test           # Check without fixing

# Frontend (if needed)
npm run dev                        # Vite dev server
npm run build                      # Production build
```

## Architecture

### Request Flow
```
Client → routes/api.php → Middleware (Sanctum) → Controller → Service/Model → JSON Response
```

### Key Directories
- `app/Http/Controllers/Api/` - API controllers (Auth, Locations, Questions)
- `app/Http/Requests/` - Form request validation with Arabic error messages
- `app/Services/` - Business logic (SmsService for Twilio)
- `app/Models/` - Eloquent models with relationships
- `routes/api.php` - All API endpoint definitions
- `config/services.php` - Third-party service configuration (Twilio credentials)

### Database Models
- **User** - Phone-based auth, no password required. Fields: name, phone (unique), email (unique), gender, is_asker (default: true), is_active
- **VerificationCode** - SMS codes with 1-minute expiry. Indexed by phone
- **UserLocation** - Saved locations with GPS coordinates. Has Haversine formula for nearby search
- **UserQuestion** - Asker questions with price. Related to location
- **QuestionView** - Tracks who viewed which questions (for asker analytics)

### Authentication Flow
1. Register via `/api/register` (name, phone, email, gender - no password)
2. Request code via `/api/login/send-code` (triggers Twilio SMS)
3. Verify via `/api/login/verify-code` (returns Bearer token)
4. Use `Authorization: Bearer <token>` header for authenticated requests

### API Endpoints
**Auth:** POST `/api/register`, `/api/login/send-code`, `/api/login/verify-code`, `/api/logout`, `/api/logout-all`, GET `/api/me`

**Locations:** GET/POST `/api/locations`, GET/PUT/DELETE `/api/locations/{id}`, POST `/api/locations/{id}/set-default`, `/api/locations/search/nearby`, GET `/api/locations/nearby-users`, POST `/api/locations/nearby-users/search`

**Questions (Askers only):** GET/POST/DELETE `/api/questions`, PUT `/api/questions/{id}`, DELETE `/api/questions`, GET `/api/questions/{id}/views`

**Questions (Responders only):** GET `/api/questions/nearby/all`, GET `/api/questions/{id}` (auto-records view)

### User Roles
The system has two user types differentiated by `is_asker` boolean (note: naming is `is_asker` in DB but represents "asker"):
- **Askers** (`is_asker = true`, DEFAULT) - Can create/manage questions, view question analytics. These are users asking for services.
- **Responders** (`is_asker = false`) - Can view nearby questions, viewing auto-records analytics. These are regular users who respond to questions.

### Location System
- Users can have multiple saved locations
- One location is marked as "default" (current location)
- Duplicate location detection: Adding same coordinates activates existing location instead of creating duplicate
- Nearby search uses Haversine formula with configurable radius (default 1km for users, 10km for general search)
- Location search uses `scopeNearby()` Eloquent scope in UserLocation model

### Question System
- Askers create questions linked to their current location
- Questions have: text, price, is_active status, location_id, user_id
- Responders within 1km can view questions
- Views are tracked in `question_views` table (question_id, viewer_id, driver_id, viewed_at)
- Each viewer is counted only once per question (using `firstOrCreate`)
- Askers can see who viewed their questions via `/api/questions/{id}/views`

## Environment Variables

Key variables in `.env`:
- `TWILIO_SID`, `TWILIO_TOKEN`, `TWILIO_VERIFY_SID` - Required for SMS verification
- Standard Laravel DB config (`DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, etc.)

## Notes

- Validation messages are in Arabic
- Phone numbers formatted for Egyptian numbers (+20 prefix)
- Location search uses Haversine formula with configurable radius
- Verification codes expire after 1 minute
- When verification code is verified, it's marked as `is_used = true` to prevent reuse
- Old verification codes are deleted when new code is sent for same phone
- Duplicate location detection uses 0.0001 degree tolerance (~11 meters)
