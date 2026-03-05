# Company Contact Form

A modern WordPress Gutenberg block plugin with REST API, AJAX submission, anti-spam protection, email notifications, and HubSpot integration.

## ✨ Features

- **🧩 Gutenberg Block** - Native WordPress block editor support with JSX
- **⚡ AJAX Submission** - No page reload, instant feedback
- **🛡️ Anti-Spam** - Honeypot + Time-trap + Rate-limiting (5 req/min)
- **📧 Email Notifications** - Configurable recipient with SMTP support
- **🔗 HubSpot Integration** - Automatic CRM contact creation (MOCK mode for dev)
- **🔒 REST API** - Secure endpoint with nonce verification
- **🎨 Customizable** - Block settings via Inspector Controls

## 🏗️ Architecture

### Data Flow

**1. Frontend (WordPress)**
- Gutenberg Block (JSX) → Editor UI
- frontend.js → AJAX form submission
- Form template → User-facing HTML

**2. REST API** (`POST /wp-json/company/v1/contact`)
- Nonce verification
- Anti-spam checks (honeypot, time-trap, rate-limit)
- Email validation (RFC)
- Data sanitization

**3. Processing (Parallel)**
- Email notification → Admin via wp_mail()
- HubSpot integration → API call (MOCK in dev)
- Database storage → wp_ccf_submissions table
- Logger → wp_ccf_logs table (30-day rotation)

**4. Storage**
- wp_posts (Gutenberg block content)
- wp_ccf_submissions (form submissions)
- wp_ccf_logs (activity logs)

### Components

| Component | File | Responsibility |
|-----------|------|----------------|
| Gutenberg Block | `src/edit.js` | Block editor UI with JSX |
| Frontend Script | `src/frontend.js` | AJAX form submission |
| REST API | `includes/class-api.php` | Endpoint, validation, anti-spam |
| Database | `includes/class-database.php` | CRUD for wp_ccf_submissions |
| Logger | `includes/class-logger.php` | Logging with 30-day rotation |
| Admin UI | `includes/class-admin.php` | Submissions & Logs tabs |
| WP-CLI | `includes/class-cli.php` | CLI commands for verification |

### Security Layers

1. **Nonce verification** - REST API nonce (`X-WP-Nonce` header)
2. **Input sanitization** - `sanitize_text_field()`, `sanitize_email()`
3. **Output escaping** - `esc_html()`, `esc_attr()` in templates
4. **Capability checks** - `current_user_can('manage_options')` in admin
5. **Prepared statements** - `$wpdb->prepare()` for all SQL queries
6. **Anti-spam** - Honeypot + Time-trap + Rate-limit (5 req/min)

## 📦 Installation

### Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+

### Steps

1. Upload the plugin to `/wp-content/plugins/company-contact-form`
2. Activate the plugin in WordPress Admin → Plugins
3. Add the block "Company Contact Form" to any page/post
4. Configure settings in the block Inspector Controls

## ⚙️ Configuration

### Block Settings (in Gutenberg Editor)

Open the block and find settings in the right sidebar:

- **Recipient Email** - Email address to receive notifications (default: admin email)
- **Subject Prefix** - Custom prefix for email subject line
- **Enable HubSpot Integration** - Toggle CRM integration

### Server Configuration (wp-config.php)

Add these constants to `wp-config.php` for production:

```php
/**
 * HubSpot Integration
 * Get credentials from: https://app.hubspot.com/developer
 */
define('CCF_HUBSPOT_TOKEN', 'pat-na1-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
define('CCF_HUBSPOT_PORTAL_ID', '12345678');
define('CCF_HUBSPOT_FORM_ID', 'abcd-1234-efgh-5678');
define('CCF_HUBSPOT_USE_CONSTANTS', true); // true = ignore block attributes

/**
 * SMTP Configuration (Email delivery)
 */
define('SMTP_HOST', 'smtp.yourserver.com');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'Your Site Name');
