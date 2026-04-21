ALUMNI INFLUENCER - QUICK START

Project URL:
- http://localhost/alumni_influencer/public/

Database:
- alumni_influencer

How to run:
1. Put the folder inside htdocs as alumni_influencer
2. Start Apache and MySQL in XAMPP
3. Import your existing alumni_influencer database
4. If any table/column is missing, run DATABASE_PATCH.sql
5. Open http://localhost/alumni_influencer/public/

Configuration:
- You do not need to submit a real .env file
- Use .env.example as the reference template
- Do not place real passwords, SMTP credentials, or secrets in the submission

Cron:
- Winner selection runs once per day at 6 PM
- Endpoint: /cron/pick-winner
- It must be called with the X-Cron-Secret header
