# SmartMove Transport Management System

SmartMove is a PHP transport-management application backed by Oracle for operational records and PL/SQL reporting, with MongoDB for vehicle documents, passenger reviews, and travel announcements. The passenger-facing site supports trip discovery and booking workflows; the admin area provides operational pages and business reports. The landing and authentication pages include a Three.js scene.

## Technology Stack

- **PHP 8.1+** for the frontend and JSON APIs
- **Oracle Database** for vehicles, drivers, routes, passengers, trips, tickets, payments, and maintenance
- **PL/SQL and OCI8** for database procedures, functions, cursors, and reports
- **MongoDB** for document-oriented collections, accessed through the official PHP library
- **Three.js** for the interactive 3D scene
- **Composer** for PHP dependencies
- **XAMPP/Apache** as a convenient local PHP web server on Windows

## Requirements

- PHP 8.1 or newer, with the **OCI8** and **MongoDB** extensions enabled
- Oracle Database 12c or newer (Oracle XE is suitable); the sequence defaults use `sequence.NEXTVAL` column defaults
- MongoDB Community Server and `mongosh`
- Composer 2
- Node.js is optional and is only needed for JavaScript syntax checks or future frontend tooling

On Windows, download OCI8 and MongoDB PHP extension builds that match your PHP version, thread-safety mode, and architecture. OCI8 also requires Oracle Instant Client. Enable the extensions in the `php.ini` used by XAMPP, restart Apache, and confirm them with `php -m` (`oci8`, `mongodb`).

## Local Setup (XAMPP)

1. Place or clone this repository at `C:\xampp\htdocs\smartmove-transport` (or the matching `htdocs` directory in your XAMPP installation).
2. Start Apache and Oracle Database, and start MongoDB if it is not already running as a Windows service.
3. Create an Oracle schema user for the application. For a local Oracle XE database, an administrator can create a dedicated user and grant the required development privileges:

   ```sql
   CREATE USER smartmove IDENTIFIED BY "choose-a-local-password";
   GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE, CREATE PROCEDURE, CREATE TRIGGER TO smartmove;
   ALTER USER smartmove QUOTA UNLIMITED ON USERS;
   ```

4. Connect to the `smartmove` schema with SQL Developer or SQL*Plus, then run the Oracle scripts in this order:

   ```text
   database/oracle/01_tables.sql
   database/oracle/02_sequences.sql
   database/oracle/03_sample_data.sql
   database/oracle/04_procedures.sql
   database/oracle/05_functions.sql
   database/oracle/06_cursors.sql
   ```

   `database/oracle/07_reports.sql` contains three standalone SQL*Plus/SQL Developer report queries. Change its `report_start_date` and `report_end_date` defines to the reporting period before running it. `03_sample_data.sql` is the canonical seed script; `05_sample_data.sql` is retained as a pointer for older project layouts.

5. Set database connection values in the Apache environment. For a local XAMPP install, add values to Apache's `httpd.conf` (or an included local-only configuration) and restart Apache:

   ```apache
   SetEnv SITE_NAME SmartMove
   SetEnv SITE_URL http://localhost/SmartMove/smartmove-transport/frontend
   SetEnv SESSION_NAME SMARTMOVESESSID
   SetEnv DB_ORACLE_HOST localhost
   SetEnv DB_ORACLE_PORT 1521
   SetEnv DB_ORACLE_SERVICE_NAME XEPDB1
   SetEnv DB_ORACLE_USERNAME smartmove
   SetEnv DB_ORACLE_PASSWORD choose-a-local-password
   SetEnv DB_ORACLE_CHARSET AL32UTF8
   SetEnv DB_MONGODB_URI mongodb://127.0.0.1:27017
   SetEnv DB_MONGODB_DATABASE smartmove
   ```

   The application reads these values in `backend/config/constants.php`. Oracle connects with an Easy Connect string built from host, port, and service name. `DB_ORACLE_SERVICE` is also accepted; the existing `DB_ORACLE_SERVICE_NAME` setting remains supported. The defaults target a local Oracle XE service named `XEPDB1` and a MongoDB database named `smartmove`. Do not commit real credentials.

6. From the project root, install the MongoDB PHP library:

   ```powershell
   cd C:\xampp\htdocs\smartmove-transport
   composer install
   ```

   Composer creates the root `vendor/` directory used by `backend/config/database-mongodb.php`.

7. Create MongoDB collections and load repeatable sample documents. Run from the project root:

   ```powershell
   mongosh "mongodb://127.0.0.1:27017/smartmove" --file database/mongodb/01_collections.js
   mongosh "mongodb://127.0.0.1:27017/smartmove" --file database/mongodb/02_sample_data.js
   ```

   The sample document IDs refer to vehicle, passenger, route, and trip IDs from the Oracle sample data. The MongoDB query examples are in `database/mongodb/03_queries.js` and can be run with the same database connection.

8. Open the application in a browser:

   ```text
   http://localhost/smartmove-transport/frontend/
   ```

   The admin reports page is `http://localhost/smartmove-transport/frontend/admin/reports.php`.

## Reports

The admin reports page provides:

- Route popularity ranked by non-cancelled ticket bookings
- Completed-payment revenue for a chosen date range (the selected end date is included)
- A passenger travel-history lookup joining passenger, ticket, trip, and route records

The page calls `backend/api/report-api.php`, which uses OCI8 and parameterized report inputs. Revenue is grouped by `PAYMENT.paid_at`, not ticket booking date, and counts completed payments only.

## Repository Layout

```text
frontend/                 PHP pages, admin UI, CSS, JavaScript, and assets
backend/api/               JSON endpoints
backend/config/            Oracle and MongoDB connection configuration
backend/helpers/           Shared API, validation, upload, and CRUD helpers
backend/models/            PHP domain models
database/oracle/           Oracle schema, seed data, PL/SQL, and report queries
database/mongodb/          Collection setup, sample documents, and query examples
docs/                      Project documentation and screenshots
```

## Development Notes

- APIs return JSON with a `success` field and either `data` or `error`.
- Keep credentials outside tracked files; configure them through environment variables.
- The existing login flow creates passenger sessions. Admin role-based authentication is not yet implemented, so do not expose the admin pages or report endpoint to an untrusted network until administrative authorization is added.
- The 3D scene may require internet access if its runtime assets are loaded from a CDN.
- Run `composer validate` after changing `composer.json`. Use Oracle SQL Developer or SQL*Plus to compile and execute Oracle scripts, and `mongosh` to run MongoDB scripts.

## License

No license has been specified for this repository yet.
