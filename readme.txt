=== ForgeHub Frictionless Checkout ===
Contributors: wpforgehub
Tags: woocommerce, checkout, frictionless, digital downloads, saas checkout
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ultra-fast 4-field checkout for digital products and SaaS. Removes address friction, auto-detects country, and enables 1-click $0 lead capture.

== Description ==

Standard WooCommerce checkouts require up to 14 separate form fields including physical billing addresses, shipping fields, company names, and phone numbers. For digital products, software plugins, ebooks, and SaaS downloads, this friction causes up to 70% of potential buyers to abandon their cart.

**WPFH Frictionless Checkout** solves this permanently by stripping all unnecessary physical fields and presenting a lightning-fast, high-converting 4-field checkout form:

* **First Name**
* **Last Name**
* **Country** (Visible, auto-detected from IP / Cloudflare `CF-IPCountry` / GeoIP)
* **Email Address**

### Key Features (Free Version)
* **4-Field Ultra-Fast Layout**: Eliminates street address, apartment, city, state, postcode, company, phone, and order notes.
* **Smart GeoIP Detection**: Automatically pre-selects the customer's country using Cloudflare headers or WooCommerce GeoIP while keeping the selector visible for tax compliance.
* **1-Click $0 Free Lead Capture**: Instantly completes free orders and digital downloads without awkward redirect chains or useless payment gateway selectors.
* **High-Converting Dark/Light Responsive UI**: Clean, mobile-friendly 2x2 grid layout designed to maximize completions.
* **Trust Badges Bar**: Displays security and instant delivery badges directly under the checkout action button.
* **HPOS Compatible**: 100% compatible with WooCommerce High-Performance Order Storage (HPOS).

### Pro Features
* **1-Click Buy Links**: Direct buy links that bypass cart and open pre-filled checkout.
* **EU B2B VAT Reverse Charge**: Instant VIES VAT ID validation and automatic zero-rating.
* **Cart Abandonment Recovery**: Live capture of customer email on blur to trigger recovery webhooks.
* **Custom Trust Badge Designer**: Upload your own SVG badges and custom guarantee copy.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wpfh-frictionless-checkout` directory, or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **WooCommerce > Settings > Frictionless Checkout** to configure options.

== Frequently Asked Questions ==

= Does this work with physical products? =
This plugin is specifically optimized for digital, downloadable, and virtual products. If your store sells physical goods requiring shipping, we recommend disabling the 4-field layout.

= Is it compatible with WooCommerce HPOS? =
Yes! It is fully tested and declares full compatibility with WooCommerce High-Performance Order Storage.

== Changelog ==

= 1.1.5 =
* Standardize Upgrade to Pro links to canonical `#pricing` product URL.

= 1.1.4 =
* Fix paid order numeric total check in checkout.js, preserve Pro EU VAT field, fix Pro Abandoned Cart token, wire GeoIP/Free Capture & physical shipping guards


= 1.1.3 =
* Rename: plugin is now "ForgeHub Frictionless Checkout" (ForgeHub naming standard).
* Fix: Plugins screen now shows a consistent "Settings" link.
= 1.1.0 =
* Feature (Pro): 1-Click Buy Links engine (?wpfh_buy=ID&coupon=CODE) with direct express checkout redirect.
* Feature (Pro): EU B2B VIES VAT live validation with automated cross-border zero-rating (reverse charge).
* Feature (Pro): On-blur cart abandonment capture with 1-click cart restoration tokens and webhook notifications.
* Feature (Pro): Custom Trust Badge designer with configurable security guarantees and SVG badges.
* Enhancement: Freemius license filter integration (fs_can_use_premium_code__wpfh-frictionless-checkout).

= 1.0.0 =
* Initial public release.
* 4-field digital checkout layout.
* GeoIP / Cloudflare country auto-detection.
* 1-click zero-dollar order completion.
* Built-in conversion trust badges.
