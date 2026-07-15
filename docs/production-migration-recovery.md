# Production Migration Recovery

## `blocks` table already exists

If `php artisan migrate` fails on `2026_07_08_000003_create_blocks_table` with:

```text
SQLSTATE[42P07]: Duplicate table: relation "blocks" already exists
```

Laravel has found a physical `blocks` table that is not recorded in the `migrations`
table. Do not drop the table on a live database unless you have confirmed it is safe
to lose its data.

First inspect the current table:

```sql
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name = 'blocks'
ORDER BY ordinal_position;
```

The expected columns from `2026_07_08_000003_create_blocks_table` are:

- `id`
- `farm_id`
- `name`
- `size_acres`
- `soil_type`
- `boundary_geojson`
- `created_at`
- `updated_at`

If the table matches the expected migration and should be kept, record the missing
migration row, then continue migrating:

```sql
INSERT INTO migrations (migration, batch)
SELECT '2026_07_08_000003_create_blocks_table',
       COALESCE((SELECT MAX(batch) FROM migrations), 1)
WHERE NOT EXISTS (
    SELECT 1
    FROM migrations
    WHERE migration = '2026_07_08_000003_create_blocks_table'
);
```

```bash
php artisan migrate
```

If the existing `blocks` table is wrong and contains no data you need to keep, drop
only that table and rerun the migration:

```sql
DROP TABLE blocks;
```

```bash
php artisan migrate
```

If the table has data but the schema differs, add the missing columns or constraints
with an explicit repair migration instead of editing the already deployed create
migration.
