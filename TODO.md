# Laravel-like Folder Structure Restructuring TODO

## Completed Tasks
- [x] Create `app/` folder for application logic (classes, models)
- [x] Move `classes/` to `app/classes/`
- [x] Create `config/` folder and move `shared/db.php` to `config/database.php`
- [x] Create `public/` folder for public assets and entry points (move `index.html`, `assets/`, `uploads/`)
- [x] Create `resources/views/` for view files (move `auth/`, `admin/`, `client/`, `tasker/`, `includes/`, `shared/` excluding db.php)
- [x] Move `client/` folder to `resources/views/` (corrected oversight)
- [x] Create `routes/` folder with `web.php` for route definitions
- [x] Update file paths in all moved files to reflect new locations (require_once, includes, redirects, links)
- [x] Update links in public/index.html to point to new auth paths
- [x] Create `app/Http/Controllers/` for future controllers (placeholder)

## Followup Steps
- [ ] Test login functionality to ensure authentication works correctly with new paths
- [ ] Test registration functionality to ensure new users can register with new paths
- [ ] Test logout functionality to ensure sessions are properly destroyed with new paths
- [ ] Verify database connections and session handling in the new structure
- [ ] Update any remaining hardcoded paths in other files (admin, client, tasker, etc.)
- [ ] Consider creating an index.php in public/ as the entry point if needed
