# VA Auto Sales — Stage 1 Project Report

**Project:** Car Listing Web Application  
**Date:** June 21, 2026  
**Status:** Stage 1 complete · Stage 2 (CRM & Lead Management) complete  
**Stack:** HTML, CSS, JavaScript · PHP · MySQL  

---

## 1. Executive Summary

VA Auto Sales Stage 1 is a full-stack car listing platform for a dealership. The public site lets visitors browse available cars, filter by brand/model/year/price, view full details, and contact the seller via WhatsApp. The admin dashboard provides secure login and full CRUD management of listings, including marking cars as sold or available.

The system runs on XAMPP (Apache + MySQL + PHP) and is structured for easy extension in Stage 2 (lead tracking and WhatsApp automation).

---

## 2. Requirements vs Delivery

| Requirement | Status | Notes |
|-------------|--------|-------|
| Homepage with featured cars | ✅ Done | Server-rendered featured section |
| Car listing page (Jiji-style grid) | ✅ Done | Responsive card grid layout |
| Car details page | ✅ Done | Images, price, specs, description |
| Search/filter (brand, price, model, year) | ✅ Done | On homepage and listings page |
| WhatsApp contact button | ✅ Done | Prefilled message with car name + price |
| Admin secure login | ✅ Done | Bcrypt password hashing, PHP sessions |
| Add / edit / delete cars | ✅ Done | Form-based admin with image upload |
| Mark sold / available | ✅ Done | Status field on add/edit forms |
| Admin table view | ✅ Done | Dashboard with stats and actions |
| Mobile-first modern UI | ✅ Done | Card animations, responsive breakpoints |
| Robust SEO | ✅ Done | Meta tags, Open Graph, JSON-LD, sitemap |
| Backend / Frontend folder split | ✅ Done | See structure below |
| Scalable for Stage 2 | ✅ Done | Modular lib/models/api layout |

---

## 3. Project Structure

```
VA_AUT_SALES/
├── index.php                 # Root redirect to public site
├── setup.php                 # One-time database installer
├── guide.md                  # Original project specification
├── report.md                 # This report
│
├── Frontend/                 # Public website
│   ├── index.php             # Homepage
│   ├── listings.php          # Browse & filter all cars
│   ├── car.php               # Single car detail page
│   ├── sitemap.php           # Dynamic XML sitemap
│   ├── robots.txt
│   ├── includes/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── car-card.php
│   └── assets/
│       ├── css/style.css
│       ├── js/main.js
│       ├── js/car.js
│       └── images/
│
├── Backend/
│   ├── config/
│   │   ├── app.php           # Site name, WhatsApp number, uploads
│   │   └── database.php      # MySQL credentials
│   ├── lib/
│   │   ├── db.php            # PDO connection singleton
│   │   ├── auth.php          # Admin session & login
│   │   └── helpers.php       # Utilities, WhatsApp link builder
│   ├── models/
│   │   └── Car.php           # Car CRUD & filtering
│   ├── api/
│   │   ├── cars.php          # REST CRUD API
│   │   └── auth.php          # Login/logout/session check
│   ├── admin/
│   │   ├── login.php
│   │   ├── dashboard.php
│   │   ├── add-car.php
│   │   ├── edit-car.php
│   │   ├── save-car.php
│   │   ├── delete-car.php
│   │   └── includes/
│   └── uploads/cars/         # Uploaded car images
│
└── database/
    └── schema.sql            # Tables + sample data
```

---

## 4. Database Design

**Database name:** `va_aut_sales`

### Table: `admins`
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| username | VARCHAR(50) | Unique login name |
| password_hash | VARCHAR(255) | Bcrypt hashed password |
| created_at | TIMESTAMP | Account creation date |

### Table: `cars`
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| title | VARCHAR(200) | Listing title |
| brand | VARCHAR(80) | e.g. Toyota |
| model | VARCHAR(80) | e.g. Camry |
| year | YEAR | Model year |
| price | DECIMAL(12,2) | Price in Naira |
| description | TEXT | Full description |
| specs | JSON | Mileage, transmission, fuel, color, engine |
| images | JSON | Array of uploaded filenames |
| status | ENUM | `available` or `sold` |
| featured | TINYINT | 1 = shown on homepage |
| created_at / updated_at | TIMESTAMP | Audit timestamps |

**Sample data:** 4 demo cars (3 available, 2 featured, 1 sold).

---

## 5. API Endpoints

Base path: `/VA_AUT_SALES/Backend/api/`

### `cars.php`
| Method | URL | Access | Description |
|--------|-----|--------|-------------|
| GET | `cars.php` | Public | List available cars (supports filters) |
| GET | `cars.php?id=1` | Public | Single car details |
| GET | `cars.php?meta=1` | Public | Brands & years for filter dropdowns |
| POST | `cars.php` | Admin | Create new car |
| PUT | `cars.php?id=1` | Admin | Update car |
| DELETE | `cars.php?id=1` | Admin | Delete car |

**Filter parameters:** `brand`, `model`, `year`, `min_price`, `max_price`, `search`, `featured`

### `auth.php`
| Method | URL | Description |
|--------|-----|-------------|
| POST | `auth.php?action=login` | Admin login |
| POST | `auth.php?action=logout` | Admin logout |
| GET | `auth.php?action=check` | Session status |

---

## 6. WhatsApp Integration

Each car listing includes a WhatsApp button that opens a chat with a prefilled message:

```
I'm interested in [Car Name] priced at ₦[Price] (Listing #[ID])
```

**Format:** `https://wa.me/{number}?text={encoded_message}`

**Configuration:** Update `whatsapp_number` in `Backend/config/app.php` before going live.

Current placeholder: `2348012345678`

---

## 7. Admin Access

| Field | Value |
|-------|-------|
| URL | http://localhost/VA_AUT_SALES/Backend/admin/login.php |
| Username | `vaautosales` |
| Password | `vaautosales123` |

Password is stored as a bcrypt hash in the database — never as plain text.

---

## 8. Installation & Setup

### Prerequisites
- XAMPP (Apache + MySQL + PHP 8+)
- PDO MySQL extension enabled (default in XAMPP Apache PHP)

### Steps

1. **Place project** in `C:\xampp\htdocs\VA_AUT_SALES\`

2. **Start services** in XAMPP Control Panel:
   - Apache
   - MySQL

3. **Run setup** (one time):
   - Visit: http://localhost/VA_AUT_SALES/setup.php
   - Creates database, tables, admin account, and sample cars

4. **Access the app:**
   - Public site: http://localhost/VA_AUT_SALES/Frontend/index.php
   - Admin panel: http://localhost/VA_AUT_SALES/Backend/admin/login.php

### Database config

Edit `Backend/config/database.php` if your MySQL credentials differ:

```php
'host'     => 'localhost',
'dbname'   => 'va_aut_sales',
'username' => 'root',
'password' => '',
```

---

## 9. Deployment Checklist

Before deploying to production:

- [ ] Update `site_url` in `Backend/config/app.php`
- [ ] Set real `whatsapp_number` in `Backend/config/app.php`
- [ ] Update MySQL credentials in `Backend/config/database.php`
- [ ] Change default admin password after first login
- [ ] Update `robots.txt` sitemap URL to production domain
- [ ] Ensure `Backend/uploads/cars/` is writable by the web server
- [ ] Enable HTTPS on the live server

---

## 10. SEO Implementation

- Unique `<title>` and `<meta description>` per page
- Canonical URLs on all public pages
- Open Graph tags for social sharing
- JSON-LD `Vehicle` schema on car detail pages
- Dynamic XML sitemap at `/Frontend/sitemap.php`
- `robots.txt` with sitemap reference
- Semantic HTML structure (header, main, article, footer)

---

## 11. UI/UX Highlights

- **Mobile-first** responsive layout with collapsible navigation
- **Card-based** car grid inspired by Jiji / Autotrader
- **CSS animations:** fade-up on load, hover lift on cards, floating hero icon
- **Image gallery** with thumbnail switching on detail page
- **Placeholder images** when no upload exists
- **WhatsApp-branded** green contact buttons throughout
- **Admin dashboard** with stat cards, data table, and form layouts

---

## 12. Security Measures

- Admin passwords hashed with `password_hash()` (bcrypt)
- PHP session-based authentication for admin routes and API writes
- PDO prepared statements (SQL injection protection)
- Input sanitization via `htmlspecialchars()` on output
- Upload validation: file type, size limit (5MB), unique filenames
- `.htaccess` in uploads folder blocks PHP script execution

---

## 13. Stage 2 — Lead Management & CRM ✅

Stage 2 adds lead capture, buyer tracking, admin CRM, source analytics, and email notifications per `guide2.md`.

### New database tables

| Table | Purpose |
|-------|---------|
| `leads` | Customer inquiries (name, phone, email, vehicle, budget, source, status) |
| `lead_notes` | Internal admin notes per lead |
| `lead_activities` | Tracking log (views, WhatsApp clicks, form submissions) |

**Run migration:** visit `setup.php` again, or run `php scripts/migrate-stage2.php`

### Public features (car detail page)

- **Request Information** form
- **Book Inspection** form
- **Request Callback** form
- Automatic **vehicle view** tracking (once per session)
- **WhatsApp click** tracking on listing cards and detail page

### Admin CRM

| URL | Description |
|-----|-------------|
| `Backend/admin/leads.php` | Overview stats, source analytics, leads table |
| `Backend/admin/lead-detail.php?id=1` | Lead profile, status pipeline, notes, activity timeline |

**Pipeline statuses:** New → Contacted → Interested → Negotiating → Closed Won / Closed Lost

### API

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | `Backend/api/leads.php` | Public | Submit lead form |
| POST | `Backend/api/leads.php?action=track` | Public | Log view / WhatsApp click |
| GET | `Backend/api/leads.php` | Admin | List leads (filters + pagination) |
| GET | `Backend/api/leads.php?id=1` | Admin | Lead detail + notes + activities |
| GET | `Backend/api/leads.php?stats=1` | Admin | Analytics JSON |
| PUT | `Backend/api/leads.php?id=1` | Admin | Update status |

### Email notifications

Set `admin_email` in `Backend/config/app.php` to receive alerts when new leads arrive. Uses PHP `mail()` — configure SMTP on production if needed.

---

## 14. Testing Summary

| Test | Result |
|------|--------|
| Database setup via `setup.php` | ✅ Passed |
| Sample data loaded (3 available, 2 featured) | ✅ Passed |
| Public homepage renders | ✅ Ready |
| Listings filter/search | ✅ Ready |
| Car detail + WhatsApp link | ✅ Ready |
| Admin login | ✅ Ready |
| Add / edit / delete car | ✅ Ready |
| Image upload | ✅ Ready |
| Sold status hides from public | ✅ Ready |
| Lead forms on car detail page | ✅ Ready |
| WhatsApp click tracking | ✅ Ready |
| Admin CRM (leads list + detail) | ✅ Ready |
| Stage 2 database migration | ✅ Passed |

---

## 15. Conclusion

Stages 1 and 2 of VA Auto Sales are implemented. The platform provides public car listings, AI support, lead capture forms, WhatsApp tracking, and a CRM-style admin panel for managing buyer inquiries.

**Optional next steps:** WhatsApp Business API automation, SMS alerts, lead export (CSV), and role-based admin permissions.
