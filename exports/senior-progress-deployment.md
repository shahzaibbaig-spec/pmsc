# Mirpur senior progress reports

This feature uses the supplied senior-school Word template. It is restricted to the Principal role and grades 7–12, including numeric, word, Roman numeral, and first/second-year class names. It is disabled by default on every installation sharing this repository.

## Enable on KORTEDU only

From the server's KORTEDU application directory:

```sh
cd ~/public_html/kortedu
git pull origin main
php artisan migrate --force --path=database/migrations/2026_10_23_000002_create_senior_progress_report_drafts_table.php
```

Edit this installation's `.env` and set these values once (update existing entries if present):

```dotenv
SENIOR_PROGRESS_REPORTS_ENABLED=true
SENIOR_PROGRESS_REPORTS_CAMPUS=mirpur
```

Then run:

```sh
php artisan optimize:clear
```

PHP must have the ZIP and DOM extensions enabled. No new Composer or front-end dependencies are required. Do not enable these settings on PMSC, Swabi, or a database containing multiple campuses; this application's student records do not provide a campus field for filtering.

## Principal workflow

Open **Senior Progress Reports** in the Principal sidebar. Select the session and class, load students, then select an assessment and load again. Open **Review / complete** for a student. Review the recorded identity details and grades, enter progress, effort, seven skills ratings, and class-teacher/head-teacher remarks, and save the review. Download a Word file for the student or return to the class and download the ZIP. Every student in the selected roster gets a separate `.docx`; saved reviews are included and missing marks/ratings remain blank.

The student comments page remains in the document with its identity fields filled. Student comments, signatures, and signature dates remain blank for their respective authors.

## Data and isolation

- Identity comes from student records and the selected class; age uses date of birth, falling back to the recorded age.
- Session-specific class-teacher assignments take priority over the class teacher on the class record.
- Subject marks are scoped to student, class, session, assessment type, group, label, and sequence. Saved grades are used when available; numeric marks use the existing result service's grade calculation. No marks are updated and no results are published.
- An existing final report comment for the student/session/assessment type prefills class-teacher remarks. Progress, effort, skills, and head remarks are never inferred from marks.
- Principal reviews live only in the new `senior_progress_report_drafts` table. Changes to a draft do not update marks, report comments, or other existing records.
- Class/session history takes priority over current class membership. Current active students are included when they have no history for the selected session.
- The template has ten subject rows. Exports with more subjects or duplicate subject entries fail with an explanatory message instead of silently omitting or averaging data.
- Temporary documents are generated under private storage with unique names and removed after download. The supplied template, logos, styles, page setup, and document package assets are retained.

To hide the feature again, set `SENIOR_PROGRESS_REPORTS_ENABLED=false` and run `php artisan optimize:clear`. Existing software continues operating and saved review data remains available.

## Validation

Focused tests cover role/campus/grade restrictions, session and class boundaries, review validation and persistence, preservation of marks, single DOCX and class ZIP downloads, missing data, and preservation of the Word template's package assets. Run tests against an isolated SQLite in-memory connection. The feature test uses a small fixture schema because unrelated historical migrations contain MySQL-only SQL.
