# CLAUDE.md - AI Development Guide

## Project Overview

Minimal GA4 Tracker is a lightweight WordPress plugin for Google Analytics 4 tracking. It prioritizes simplicity and performance over feature bloat.

## File Structure

```
minimal-ga4-tracker/
├── .github/
│   └── workflows/
│       └── release.yml       # Auto-builds ZIP on tag push
├── minimal-ga4-tracker.php   # Main plugin file (singleton pattern)
├── class-updater.php         # GitHub auto-updater (MGA4_Updater class)
├── uninstall.php             # Cleanup on plugin deletion
├── readme.txt                # WordPress.org-style readme
├── README.md                 # GitHub documentation
├── LICENSE                   # GPL-2.0 license
└── .gitignore
```

## Key Constants

- `MGA4_VERSION` - Current plugin version
- `MGA4_PLUGIN_FILE` - Path to main plugin file (for updater)
- `MGA4_OPTION_KEY` - Options table key (`mga4_settings`)

## Version Locations

When releasing a new version, update these three locations:

| File | Location | Format |
|------|----------|--------|
| `minimal-ga4-tracker.php` | Header comment (line 5) | `Version: X.Y.Z` |
| `minimal-ga4-tracker.php` | Constant (line 18) | `define( 'MGA4_VERSION', 'X.Y.Z' );` |
| `readme.txt` | Stable tag (line 6) | `Stable tag: X.Y.Z` |

## Release Process

**IMPORTANT**: Follow these steps exactly. Skipping steps causes duplicate plugins and failed updates.

### Step 1: Update version numbers

Update the version in **all three** locations (see table above):

```
1. minimal-ga4-tracker.php line 5:  Version: X.Y.Z
2. minimal-ga4-tracker.php line 18: define( 'MGA4_VERSION', 'X.Y.Z' );
3. readme.txt line 6:               Stable tag: X.Y.Z
```

### Step 2: Add changelog entry

Add a new section at the **top** of the Changelog in `readme.txt`:

```
= X.Y.Z =
* Description of changes
```

### Step 3: Commit and tag

```bash
git add minimal-ga4-tracker.php readme.txt
git commit -m "Bump version to X.Y.Z"
git tag vX.Y.Z
git push origin main --tags
```

### Step 4: Wait for GitHub Actions

The `.github/workflows/release.yml` workflow will automatically:
- Build a ZIP with the correct `minimal-ga4-tracker/` folder structure
- Create a GitHub Release with the ZIP attached

**Do NOT manually create the release** — the workflow handles this.

### Step 5: Verify

1. Go to the GitHub repo → Releases and confirm the new release exists with a `.zip` asset
2. On your WordPress site, clear the update cache: `/wp-admin/?clear_mga4_update_cache=1`
3. Go to Plugins → the update notification should appear
4. Test the update — it should update in-place without creating duplicates

### Why the ZIP structure matters

GitHub's auto-generated "zipball" creates a folder named `breonwilliams-minimal-ga4-tracker-{hash}` inside the ZIP. WordPress uses the folder name to determine the plugin directory. If it doesn't match `minimal-ga4-tracker`, WordPress installs it as a **separate plugin** — creating duplicates. The GitHub Actions workflow builds a ZIP with the correct folder name so this never happens.

## Architecture Notes

- **Singleton Pattern**: `MGA4_Tracker` uses singleton for single instance
- **Settings API**: Uses WordPress Settings API for admin options
- **Async Loading**: gtag script loads with `async` strategy
- **Smart Exclusions**: Tracking skipped for admin, REST, AJAX, cron, feeds, previews

## Filter Hook

`mga4_gtag_config` - Modify gtag config array before output. Receives `$config` array and `$measurement_id` string.

## Testing Updates

1. Push a new release tag to GitHub (e.g., `v1.1.1`)
2. Clear cache: `/wp-admin/?clear_mga4_update_cache=1`
3. Go to Plugins page - update notification should appear
4. Test the update installation
5. Verify no phantom notifications remain after update
