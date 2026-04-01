ALUMNI INFLUENCER - FULL MARKS UPGRADE

This version keeps the same project URL and database name:
- URL: http://localhost/alumni_influencers_fixed/public/
- Database: alumni_influencer

How to run:
1. Put the folder inside htdocs as alumni_influencers_fixed
2. Start Apache and MySQL in XAMPP
3. Import your existing database alumni_influencer
4. If any page gives column errors, run DATABASE_PATCH.sql in phpMyAdmin
5. Open: http://localhost/alumni_influencers_fixed/public/

What was added/fixed:
- Registration with university email validation
- Email verification flow using secure random tokens
- Login/logout and session handling
- Forgot password and reset password flow
- Full profile management with image upload
- Degree, certification, licence, short course, and employment history forms
- Blind bidding with increase-only updates
- Monthly featured limit enforcement (3, or 4 if event participation exists)
- Winner selection route: /cron/pick-winner
- Developer API key generation and revocation
- API usage logging
- Bearer token API endpoint: /api/featured
- API docs page: /api-docs
- openapi.json generated from the app

Demo tip:
When testing locally, the app shows verification/reset links on screen as flash messages instead of sending real email.
