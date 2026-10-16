# Merge Record: Client Dashboard Refresh

**Date:** 2026-10-16  
**Repository:** `lawreay/instaweb`  
**Pull request:** [#1 - Refresh InstaWeb client workspace and site management](https://github.com/lawreay/instaweb/pull/1)  
**Status:** Merged into `main`  
**Merge commit:** `98da388224a85d4284ea5a7162d46a3a50f9456d`  
**Source branch:** `feature/client-dashboard-refresh`  
**Source head:** `f885eec64c1dcac59394d7fea8020fdd310a005c`

## Summary

The pull request was merged using a regular merge commit, preserving the individual commits from the feature branch.

### Changes included

- Added shared client dashboard header and footer navigation for Dashboard, Manage My Sites, Account, Terms, and Privacy.
- Improved the client dashboard layout and responsive behavior.
- Added per-site Manage, File Manager, and Visit Site actions to the dashboard and websites list.
- Added a Visit Live Site action in the file manager for published websites.
- Kept dashboard-specific styles in `public/assets/css/client-dashboard.css`.
- Added visible keyboard focus styles and reduced-motion support.

### Files changed

- `app/Controllers/FileManagerController.php`
- `app/Views/dashboard/index.php`
- `app/Views/filemanager/index.php`
- `app/Views/layouts/dashboard.php`
- `app/Views/websites/index.php`
- `public/assets/css/client-dashboard.css`

## Validation status

The changes received a structural review before merge. **PHP linting and browser/device testing were not run as part of the merge.** A successful GitHub merge confirms that GitHub accepted the merge; it does not prove the application behavior is correct.

## Post-merge checklist

- [ ] Run PHP syntax checks on the changed PHP files.
- [ ] Test the client dashboard at desktop and mobile widths.
- [ ] Verify header/footer links and navigation destinations.
- [ ] Verify Manage and File Manager actions for each site.
- [ ] Verify Visit Site / Visit Live Site behavior for published and unpublished sites.
- [ ] Check keyboard focus visibility and reduced-motion behavior.
- [ ] Review application logs for errors during these checks.

## Scope note

The pull request did not intentionally change database schema, billing, authentication, or hosting behavior. Complete the post-merge checks before treating this release as production-verified.
