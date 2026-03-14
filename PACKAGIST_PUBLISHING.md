# Publishing Polygen PHP on Packagist

This guide explains how to publish the `procionegobbo/polygen-php` package on Packagist.

## Prerequisites

✅ **Already Verified**
- `composer.json` - Properly configured with correct package name
- `README.md` - Complete documentation
- `LICENSE` - MIT license file
- `src/` directory - PSR-4 autoloading configured
- Git tags - `v1.0.0` release tag created
- Tests - 138 tests passing

## Step-by-Step Publication Process

### 1. Create a Packagist Account

1. Go to https://packagist.org
2. Click "Create an account" (or sign in if you already have one)
3. Register with GitHub account or email
4. Verify your email

### 2. Log in to Packagist

1. Visit https://packagist.org/login
2. Sign in with your credentials

### 3. Submit Your Package

1. Click the "Submit Package" button (top right or navigation)
2. Enter your GitHub repository URL:
   ```
   https://github.com/Procionegobbo/polygen-php
   ```
3. Click "Check"
4. Click "Submit"

### 4. Connect Your GitHub Account

Packagist will ask for GitHub credentials to:
- Automatically update your package when you push to GitHub
- Sync releases and tags

1. Click "Connect GitHub" if prompted
2. Authorize the Packagist GitHub integration
3. This enables automatic updates whenever you push

### 5. Verify Package Details

Once submitted, Packagist will show:

```
Name: procionegobbo/polygen-php
Type: library
Description: PHP 8.4+ implementation of Polygen - random sentence generator with BNF-like grammars
License: MIT
PHP: >=8.4
Homepage: https://github.com/Procionegobbo/polygen-php
```

### 6. Test Installation

After publication (usually within minutes), test that the package works:

```bash
composer create-project procionegobbo/polygen-php test-install
cd test-install
php simple_test.php
```

Or add to an existing project:

```bash
composer require procionegobbo/polygen-php
```

## Package Details

| Property | Value |
|----------|-------|
| **Name** | procionegobbo/polygen-php |
| **Repository** | https://github.com/Procionegobbo/polygen-php |
| **Type** | library |
| **License** | MIT |
| **PHP Version** | >= 8.4 |
| **Dependencies** | None (zero external dependencies) |
| **Latest Version** | 1.0.0 |

## What Gets Published

From this repository, Packagist will use:

- **Source code**: `src/` directory with PSR-4 autoloading
- **Tests**: Optional but good to keep
- **Documentation**: README.md, composer.json
- **License**: MIT
- **Git tags**: Automatic version detection from tags

## Automatic Updates

Once connected, Packagist will automatically update:
- When you push commits to master
- When you create new git tags (versions)
- When you update composer.json

No manual re-publishing needed!

## Versioning

Current version structure:
- `v1.0.0` - First stable release

Future versions should follow [Semantic Versioning](https://semver.org/):
- Patch releases: `v1.0.1`, `v1.0.2` (bug fixes)
- Minor releases: `v1.1.0`, `v1.2.0` (new features)
- Major releases: `v2.0.0` (breaking changes)

To create a new release:

```bash
git tag -a vX.Y.Z -m "Release X.Y.Z"
git push origin vX.Y.Z
```

## Packagist Statistics

After publication, you'll be able to see:
- Download statistics
- Dependent packages
- Stargazers on GitHub
- Version history

## Troubleshooting

### Package not appearing

- Wait 5-10 minutes for Packagist to process
- Check that `composer.json` is valid: `composer validate`
- Ensure your repository is public on GitHub

### Can't find package

- Search on https://packagist.org
- Search for "polygen-php" or "Polygen PHP"
- Check exact name: `procionegobbo/polygen-php`

### Update not automatic

- Verify GitHub integration is connected
- Check Packagist account settings
- Force update via package page > "Force Update" button

## Post-Publication

### Promote Your Package

Share your newly published package:
- GitHub releases page
- PHP community forums
- Social media (#PHP #Packagist #Polygen)
- Relevant Discord/Slack communities

### Update README.md Installation

Update your README with official Composer installation:

```bash
composer require procionegobbo/polygen-php
```

(This is already in the current README ✅)

### Monitor Usage

On Packagist page, you can see:
- Total downloads
- Daily/monthly download trends
- List of dependent packages
- Package issues and feedback

## References

- **Packagist.org**: https://packagist.org
- **Composer Documentation**: https://getcomposer.org/doc/
- **Semantic Versioning**: https://semver.org/
- **PHP Package Guidelines**: https://www.php-fig.org/

---

**Status**: Ready for publication! ✅

All prerequisites met. You can submit the package to Packagist immediately.
