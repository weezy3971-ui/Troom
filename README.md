# Deploy Notes

## Migrations

This release adds a `phone` column to the `users` and `approved_emails` tables. On deploy, run:

```bash
php artisan migrate
```

## SMS Setup

SMS features (registration OTP, self-service password reset, admin password reset texts)
won't work until these are done.

**Blocking:**

1. Set the SMS env vars on the server:

   ```env
   BONGA_SMS_API_KEY=<api key here>
   BONGA_SMS_API_SECRET=<api secret here>
   BONGA_SMS_API_URL=http://167.172.14.50:4002/v1/send-sms
   SMS_API_CLIENT_ID=858
   SMS_SERVICE_ID=1
   ```

   Re-run `php artisan config:cache` afterward if you cache config.

2. Run migrations: `php artisan migrate` (adds `phone` to `users` and `approved_emails`).

3. Confirm the production server's IP can reach `167.172.14.50:4002`.
