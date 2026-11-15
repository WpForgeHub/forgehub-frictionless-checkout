# WPFH Frictionless Checkout for WooCommerce

[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce HPOS](https://img.shields.io/badge/WooCommerce%20HPOS-Compatible-green.svg)](https://woocommerce.com/)
[![Release](https://img.shields.io/badge/Release-v1.1.0-purple.svg)](https://github.com/WpForgeHub/wpfh-frictionless-checkout/releases/tag/v1.1.0)

**WPFH Frictionless Checkout** transforms bloated WooCommerce checkouts into ultra-fast, 4-field checkout experiences optimized for digital products, WordPress plugins, software downloads, and SaaS subscriptions.

---

## ⚡ Why WPFH Frictionless Checkout?

Default WooCommerce checkouts ask for 14+ fields—including street address, apartment, city, state, postal code, phone number, and company name.

For software and digital products, this friction kills sales.

**WPFH Frictionless Checkout replaces that bloated form with 4 essential fields:**
1. **First Name**
2. **Last Name**
3. **Country** (Visible, auto-detected from IP / Cloudflare `CF-IPCountry` / GeoIP)
4. **Email Address**

---

## 🚀 Key Features

* **4-Field Ultra-Fast Layout**: Zero street, city, state, or phone friction.
* **Smart GeoIP Country Auto-Detection**: Pre-selects country automatically from Cloudflare or server GeoIP headers without hiding the selector (vital for tax/VAT compliance).
* **1-Click $0 Free Lead Capture**: Instant zero-dollar order completion bypassing payment gateway redirects.
* **Responsive Dark/Light UI**: Built for modern conversion rates.
* **Built-in Trust Badges**: Instant access, 256-bit SSL, and money-back guarantee badges.
* **HPOS Ready**: Tested with WooCommerce 7.0 - 9.4+.

---

## 📦 Pro Features

* **1-Click Buy Links**: Instant checkout links (`?wpfh_buy=123`).
* **EU B2B VAT Reverse Charge**: Real-time VIES VAT ID verification with automatic zero-rating.
* **Cart Abandonment Recovery**: Live capture of emails on input blur to trigger recovery sequences.
* **Custom Trust Badge Designer**: Add your own SVG badges and branding.

---

## 🛠️ Installation

```bash
git clone https://github.com/WpForgeHub/wpfh-frictionless-checkout.git
```

Or download the latest release `.zip` from [Releases](https://github.com/WpForgeHub/wpfh-frictionless-checkout/releases) and upload via **Plugins > Add New > Upload Plugin** in WordPress.

---

## 📄 License

GPL-2.0+ &copy; [WPForgeHub](https://wpforgehub.com).
