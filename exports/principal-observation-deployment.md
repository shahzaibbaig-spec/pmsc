# Principal observation reports: server deployment

Inspection of `sdb-89_hosting_stackcp_net.sql` found the Principal role, but no lesson/notebook observation tables, observation permissions, or May 14 observation migration entries. A permission-only update is insufficient for this database snapshot.

Deploy the current application code, including the updated role seeder and observation controllers, services, models, views, routes, and migrations. Then run these commands from the application directory on the server, using its existing database configuration and PHP 8.3 or newer:

```sh
php artisan migrate --force --path=database/migrations/2026_05_14_000100_create_section_head_assignments_table.php
php artisan migrate --force --path=database/migrations/2026_05_14_000200_create_lesson_observation_tables.php
php artisan migrate --force --path=database/migrations/2026_05_14_000300_create_notebook_observation_tables.php
php artisan migrate --force --path=database/migrations/2026_05_14_000400_create_teacher_performance_events_table.php
php artisan migrate --force --path=database/migrations/2026_05_14_000500_create_principal_teacher_communication_tables.php
php artisan migrate --force --path=database/migrations/2026_05_14_000600_add_section_head_observation_permissions.php
php artisan migrate --force --path=database/migrations/2026_10_23_000001_restore_principal_observation_report_access.php
php artisan permission:cache-reset
php artisan view:clear
php artisan route:clear
```

Stop if a command fails and resolve its error before continuing. These commands target the observation feature instead of running unrelated pending migrations. Do not rerun the full role seeder on the server for this repair.

Sign in as Principal and check the sidebar links **Lesson Observations** and **Notebook Observations**. Verify each list opens and, after an observation is submitted, that its details and print page open. Principals can view records from all observers. The supplied dump contains no existing records for either observation type, so initial lists will be empty.

The supplied SQL dump was inspected without modification. These deployment commands have not been executed on the server.
