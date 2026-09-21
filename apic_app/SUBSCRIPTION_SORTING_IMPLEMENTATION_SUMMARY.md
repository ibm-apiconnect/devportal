# Subscription Sorting - Implementation Summary

## Scope
The subscriptions list on an application page needed reliable sorting by the **displayed** Product and Plan names (not URLs). The display titles previously came from product nodes at render time, so sorting by `product_url`/`plan` produced unexpected ordering.

## Core Design
Denormalize the display titles into the subscriptions table and sort on those columns.

### Data Model (apic_app_application_subs)
New columns:
- `product_title` (nullable string)
- `plan_title` (nullable string)
- `product_title_missing` (int, 0/1)
- `plan_title_missing` (int, 0/1)

Indexes:
- `(plan_title_missing, plan_title)`
- `(product_title_missing, product_title)`

Missing flags are used to push unresolved titles to the bottom of the list.

### Backfill (Update Hook)
`apic_app_update_11005`:
- Adds the new columns via entity field storage definitions.
- Adds the composite indexes above.
- Backfills existing rows by resolving product node data from `product_url`:
  - Product title: `node_field_data.title`
  - Plan titles: decoded from `node__product_plans.product_plans_value`
- Uses `accessCheck(FALSE)` so products resolve even when running in non-admin contexts.
- Runs as admin when available (`account_switcher`) to avoid access issues.

If a product or plan cannot be resolved, titles remain `NULL` and `*_missing` is set to `1`.

### Runtime Write-Path
`SubscriptionService::create()` now resolves and stores `product_title`/`plan_title` (and missing flags) when a subscription is created or updated.
To avoid access issues in background calls, resolver uses `accessCheck(FALSE)`.

### View + Sorting Behavior
View: `application_subscriptions`
- Exposed sorts:
  - `id` (default)
  - `plan_title` (label: **Plan**)
  - `product_title` (label: **Product**)
- Missing-title sorts (`*_missing`) are hidden but applied alongside the selected field.

Query order enforcement:
`apic_app_views_query_alter()` explicitly rebuilds ORDER BY for the view, ensuring:
- `id` only when `sort_by=id`
- `plan_title_missing ASC, plan_title {ASC|DESC}` for `sort_by=plan_title`
- `product_title_missing ASC, product_title {ASC|DESC}` for `sort_by=product_title`

This avoids earlier issues where `initHandlers()` locked in an empty ORDER BY.

### View Update Hook (Config Import)
`apic_app_update_11003`:
- Imports the latest `views.view.application_subscriptions.yml` into config.
- Ensures `better_exposed_filters` is listed in view dependencies.
- Updates the `default` display `sorts` and `exposed_form`, and aligns `block_1` exposed settings.
- Keeps the update scoped to those keys to avoid overwriting local changes.

### UI + Test Updates
Behat scenario assertions updated to expect:
- `sort_by=plan_title`
- `sort_by=product_title`
Label change from “Plan Name” to “Plan”.

## Files Touched
- `devportal-modules/apic_app/apic_app.install`
  - `apic_app_update_11005` (schema + backfill)
  - `apic_app_update_11006` (view label “Plan”)
- `devportal-modules/apic_app/config/install/views.view.application_subscriptions.yml`
- `devportal-modules/apic_app/apic_app.module` (views query alter sort enforcement)
- `devportal-modules/apic_app/src/Plugin/Block/SubscriptionsBlock.php` (exposed input handling)
- `devportal-modules/apic_app/src/Service/SubscriptionService.php` (title resolver + accessCheck(FALSE))
- `devportal-modules/apic_app/tests/src/Unit/SubscriptionServiceTest.php` (DI update)
- `devportal-modules/apic_app/features/SubscriptionWizard.feature` (sort_by expectations)

## Verification Snippets
Check backfill results:
```sql
SELECT id, product_title, product_title_missing, plan_title, plan_title_missing
FROM apic_app_application_subs
WHERE product_title_missing=1 OR plan_title_missing=1
ORDER BY product_title_missing DESC, plan_title_missing DESC, id DESC;
```

Check sort params in URL after UI actions:
- Plan sort: `sort_by=plan_title`
- Product sort: `sort_by=product_title`

## Notes / Pitfalls
- Missing titles are intentionally **not** replaced with URLs to avoid confusing UI order.
- If backfill runs without admin access, titles may remain NULL; `accessCheck(FALSE)` is required.
- When running update hooks via `drush php:eval`, `apic_app.install` must be loaded first.
