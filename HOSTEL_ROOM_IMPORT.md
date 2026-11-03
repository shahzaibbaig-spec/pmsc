# Hostel room list

The warden's Hostel Rooms and Room Allocations screens now link to **Import Room List** at `/warden/hostel/room-list/import`. The importer requires both room management and student allocation permissions. Wardens can import into their own hostel only.

The supplied workbook is staged privately at `storage/app/private/hostel/room-wise-list.xlsx` and loads automatically for review. This private file is not tracked by Git; copy it to the same private path on deployment, or upload the workbook through the screen. It contains 20 paired room groups and 214 student entries. Labels such as `J 3-4` are retained as one group because the source does not specify individual beds or which half of the pair each student occupies.

Ground-floor rooms are identified automatically. The second worksheet's combined `1st & 2nd FLOOR` heading does not identify each room's floor, so these floors must be selected before import. Capacity for new rooms initially equals the number of entries; review against actual capacity. Existing rooms retain their capacity.

The review selects only unique normalized name matches, requiring matching birth dates where the source supplies one. Unresolved entries can be mapped to an existing student record ID or skipped. The import reports skipped entries. It never creates student admission records from names alone. Importing an existing assignment to the same room is repeatable; moving an assignment within the same hostel preserves history. Capacity, hostel policy, duplicate selections, and cross-hostel checks apply. Any failure rolls back the entire import.

Use **Edit / Change Room** on an allocation or a room's student list to edit the current allocation's date and remarks or move the student. Choose the movement date when changing rooms; earlier movement dates are rejected. Existing Add Room, Edit Room, Allocate Student, and Remove controls remain available.

## Pending application of data

The configured SQLite database path points to a missing file under `C:/Users/Shahzaib/Desktop/nsms/database/`. The local SQLite copies contain no students and have no hostel tables. No application database or environment settings were changed. Applying the list requires the populated database and the upper-floor mapping.

## Verification

Eight isolated database tests cover workbook parsing, uncertain floors, student matching, repeat imports, history, transaction rollback, capacity, hostel access, and allocation editing. Run without touching application data:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
$env:APP_CONFIG_CACHE = 'bootstrap/cache/hostel-test-no-config.php'
.\php.cmd artisan test --compact --filter=HostelRoomListImportTest
```

Routes register successfully and Blade templates compile. Live browser verification requires a working application database.

## Deploy to the live server

Replace the SSH username, hostname, port, and project path with the real values. Keep the server's production `.env` and database in place. Take a backup using the hosting provider or the existing database backup procedure before importing.

Run from local PowerShell to prepare private storage and copy the workbook:

```powershell
ssh -p 22 USER@HOST 'mkdir -p /ABSOLUTE/PROJECT/PATH/storage/app/private/hostel'
scp -P 22 'C:\Users\KORT OFFICAL\Downloads\HOSTEL ROOM WISE LIST.xlsx' 'USER@HOST:/ABSOLUTE/PROJECT/PATH/storage/app/private/hostel/room-wise-list.xlsx'
ssh -p 22 USER@HOST
```

Then run in the server's SSH shell:

```bash
cd /ABSOLUTE/PROJECT/PATH
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan route:list --path=warden/hostel/room-list
```

Run commands as the normal application deployment user, with the server's PHP version and Composer executable. Storage must remain readable and writable by the application using the host's existing permissions scheme. The update adds no database migrations; it uses the existing hostel tables and permissions. If those earlier hostel features have not been deployed, inspect `php artisan migrate:status` and complete the site's normal migration procedure first.

Sign in to the live site as the appropriate warden or admin, then open **Hostel Rooms → Import Room List**. The privately uploaded workbook loads automatically. Select the correct hostel, review matches, set the upper floors and capacities, and click **Import Rooms and Selected Allocations**. This final step writes to the production database. Uploading the workbook alone does not create allocations. Review the completion counts and resolve any skipped students.

The workbook includes students who may conflict with the existing age/gender hostel policy. The importer reports these errors and rolls back; correct the selected hostel or student records as appropriate before retrying.
