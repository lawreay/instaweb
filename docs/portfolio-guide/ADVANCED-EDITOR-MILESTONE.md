# Advanced Portfolio Editor: Implementation Milestone

## Reference and boundaries

The root `idea.php` is the functional/UI reference for the original customizable portfolio dashboard. It remains unchanged, and the separate `Custom-portfolios` repository is not modified. InstaWeb owns authentication, website selection, data storage, preview, and publication.

## Implemented in this milestone

- Expanded the advanced content editor to include Goals, Interests, Challenges, Work Experience, Education, Skills, News and Updates, Partnerships, and CV / Resume.
- Added section-jump navigation to make the long editor easier to use.
- Connected the section list to the dynamic public portfolio renderer and public navigation.
- Kept the existing dedicated Hero/About and Projects editors intact.
- Continued using InstaWeb's owner-scoped model operations, CSRF protection, and namespaced `portfolio_advanced_*` tables.

## Existing controls

The advanced editor already exposes accent, background, and text colours; typography; hero layout; and project/contact visibility. Section entries support title, organisation, period, details, optional link, display order, hide/enable, update, and delete.

## Not yet ported from idea.php

Do not consider the full original dashboard integrated yet. Remaining parity work includes profile/avatar/favicon uploads, section background assets, icon selection, richer project metadata and project imagery, popups, bookings/requests/comments, visitor analytics, and direct CV file uploads. These require separate owner-scoped storage and validation rather than copying the original standalone helpers or database configuration.

## Required local verification before merge

1. Apply the advanced editor migration against the local database.
2. Create, update, hide, re-enable, and delete items in each section.
3. Confirm the same saved content appears in preview and the published dynamic renderer.
4. Test cross-user access attempts by changing website and item IDs.
5. Test CSRF rejection, external-link handling, and output escaping.
6. Check the editor on mobile and desktop.

This branch is a feature milestone and must not be merged until the local runtime and security checks pass.