# Hostel-only residents

The 62 remaining spreadsheet entries belong in `hostel_residents`, a separate accommodation register. They do not create school students, admissions, classes, login accounts, fees, academic results, or school-student allocation rows. All 62 are male, as confirmed by the user. Their names, dates of birth, and room group labels are retained.

Wardens can open **Hostel Residents** from their sidebar or the Hostel Rooms / Room Allocations screens. The list supports search, CSV import, adding residents, editing details and room groups, and marking residents inactive. Wardens can see and change only their own hostel's residents. Existing `view_hostel_room_allocations` and `assign_students_to_rooms` permissions govern access.

Room groups identify accommodation from the spreadsheet. Active residents count toward room occupancy and appear in the room's occupant list when their hostel and room-group label match an inventory room. Moving a resident's room group updates those counts. School-student allocations also count toward room capacity, but importing residents does not create school-student allocations. Night attendance and leave screens continue to use school-student allocations.

## Deploy and import

Private data files are prepared locally at `.runtime/hostel-residents/hostel-only-boys.csv` and `.runtime/hostel-residents/add-62-hostel-only-residents.sql`. They are excluded from Git. A CSV copy is also staged at `storage/app/private/hostel/hostel-only-boys.csv`.

Back up the production database, then run on the server:

```bash
cd /path/to/nsms
git pull --ff-only origin main
php artisan migrate --path=database/migrations/2026_11_04_000100_create_hostel_residents_table.php --force
php artisan optimize:clear
```

Upload the CSV from local PowerShell, replacing the SSH details and project path:

```powershell
scp -P 22 '.runtime/hostel-residents/hostel-only-boys.csv' 'USER@HOST:/path/to/nsms/storage/app/private/hostel/hostel-only-boys.csv'
```

Then use the Hostel Residents upload screen, or run over SSH:

```bash
php artisan hostel:import-residents storage/app/private/hostel/hostel-only-boys.csv --hostel=2 --dry-run
php artisan hostel:import-residents storage/app/private/hostel/hostel-only-boys.csv --hostel=2
```

Hostel ID 2 is Jinnah House in the provided export. Verify it on the live server. Warden accounts must be assigned to their hostel before accessing its residents. Rebuild frontend assets and refresh normal application caches using the existing deployment procedure.

Alternatively, after the migration, import the prepared SQL through phpMyAdmin. It writes only to `hostel_residents` and requires hostel ID 2 to be named Jinnah House. The CSV command and SQL share import keys; repeating either leaves existing imported resident details unchanged. No live database changes were made while preparing these files.

## Verification

Six isolated tests cover resident-only creation, repeat imports preserving edits, hostel scoping, forbidden cross-hostel edits/imports, CSV validation, and transactional rollback. Importing the actual private CSV into an in-memory database created 62 male residents; a second import created zero. No school-student table was created or written.
