# ProjectRim — Complete Development Plan

> **Online Research/Project Marketplace**
> Where sellers upload and buyers purchase or download academic projects for free.
> Generated: March 24, 2026

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Architecture Overview](#3-architecture-overview)
4. [Database Schema](#4-database-schema)
5. [Roles & Permissions](#5-roles--permissions)
6. [Module Breakdown](#6-module-breakdown)
   - 6.1 [Authentication & Social Login](#61-authentication--social-login)
   - 6.2 [Public Website (SPA — Vue/Inertia)](#62-public-website-spa--vueinertia)
   - 6.3 [User Dashboard (SPA — Vue/Inertia)](#63-user-dashboard-spa--vueinertia)
   - 6.4 [Seller/Author Module (SPA — Vue/Inertia)](#64-sellerauthor-module-spa--vueinertia)
   - 6.5 [Product System](#65-product-system)
   - 6.6 [Co-Author & Collaboration System](#66-co-author--collaboration-system)
   - 6.7 [Search & Discovery](#67-search--discovery)
   - 6.8 [Cart & Orders](#68-cart--orders)
   - 6.9 [Revenue, Smart Links & Monetization](#69-revenue-smart-links--monetization)
   - 6.10 [Payments & Payouts](#610-payments--payouts)
   - 6.11 [Reviews, Ratings & Likes](#611-reviews-ratings--likes)
   - 6.12 [Messaging System](#612-messaging-system)
   - 6.13 [Email Notifications](#613-email-notifications)
   - 6.14 [Newsletter](#614-newsletter)
   - 6.15 [Admin Dashboard (Blade)](#615-admin-dashboard-blade)
   - 6.16 [Admin CMS — Dynamic Pages](#616-admin-cms--dynamic-pages)
7. [Theme & Design System](#7-theme--design-system)
8. [API Routes Structure](#8-api-routes-structure)
9. [File & Folder Structure](#9-file--folder-structure)
10. [Deployment & Environment](#10-deployment--environment)
11. [Implementation Phases](#11-implementation-phases)

---

## 1. Project Overview

**ProjectRim** is an online marketplace for academic research papers and projects. Key concepts:

- **Products are free by default.** Admin can toggle a global setting to allow paid products.
- **Revenue comes from smart links.** Downloads go through monetized short-link services. Authors earn per view ($0.10) and per download ($1.00) — rates configurable by admin.
- **Multi-author support.** Up to 10 co-authors per product with contribution percentages that drive revenue sharing.
- **Three user tiers:** Visitor (guest), Registered User, Seller/Author (upgraded user role).
- **Admin dashboard is Blade-based** (server-rendered, separate from the Vue SPA).
- **User-facing site is Vue 3 + Inertia SPA** (landing page, user dashboard, seller dashboard).

### Brand Assets

| Asset | File | Dimensions |
|-------|------|------------|
| Logo | `public/images/logo.png` | 300 × 64 px (transparent) |
| Icon | `public/images/icon.png` | 71 × 64 px (transparent) |

---

## 2. Tech Stack

### Backend

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 (PHP 8.3+) |
| Auth | Laravel Fortify + Laravel Socialite |
| Admin Panel | Blade templates + Tailwind CSS (separate layout) |
| Queue/Jobs | Laravel Queue (database driver) |
| Email | Laravel Mail (SMTP — provider decided later) |
| File Storage | Local disk (`storage/app/public`) |
| Search | Laravel Scout (database driver initially, Meilisearch later if needed) |
| Caching | Database (upgradeable to Redis) |
| Testing | Pest PHP |

### Frontend (User-facing SPA)

| Layer | Technology |
|-------|-----------|
| Framework | Vue 3.5 + TypeScript |
| Routing | Inertia.js v2 + Wayfinder |
| Styling | Tailwind CSS v4 |
| UI Components | shadcn-vue (Reka UI) — already installed |
| Rich Text Editor | Tiptap (Vue 3 compatible) |
| Carousel | Embla Carousel (via shadcn-vue carousel component) |
| Icons | Lucide Vue |
| Build | Vite 8 |
| SSR | Inertia SSR (already configured) |

### Payment Gateways (admin toggle per gateway)

| Gateway | Region |
|---------|--------|
| Stripe | Global |
| PayPal | Global |
| Paystack | Africa |
| Flutterwave | Africa |
| Manual/Bank Transfer | Global |

### Social Login Providers

| Provider | Package |
|----------|---------|
| Google | laravel/socialite |
| Facebook | laravel/socialite |
| Twitter/X | laravel/socialite |

---

## 3. Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    PUBLIC WEBSITE (Vue SPA)              │
│  Landing Page · Product Pages · Search · Cart · Auth    │
├─────────────────────────────────────────────────────────┤
│               USER DASHBOARD (Vue SPA)                  │
│  Overview · Downloads · Orders · Messages · Profile     │
│  ┌────────────────────────────────────────────────────┐ │
│  │         SELLER MODULE (within same SPA)            │ │
│  │  Products · Orders · Revenue · Payouts · Profile   │ │
│  └────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────┤
│             ADMIN DASHBOARD (Blade/Server)              │
│  Statistics · Users · Products · Orders · Settings      │
│  Pages CMS · Payments · Newsletters · Notifications     │
├─────────────────────────────────────────────────────────┤
│                  LARAVEL BACKEND (API)                   │
│  Fortify Auth · Socialite · Controllers · Services      │
│  Queue Jobs · Mail Notifications · Eloquent Models      │
├─────────────────────────────────────────────────────────┤
│                    DATA LAYER                           │
│  MySQL · Local File Storage · Cache (DB)                │
└─────────────────────────────────────────────────────────┘
```

### Route Prefixes

| Prefix | Auth | Tech | Purpose |
|--------|------|------|---------|
| `/` | Public | Inertia/Vue | Landing, browse, search, product pages |
| `/auth/*` | Guest | Inertia/Vue | Login, register, social auth, password reset |
| `/dashboard/*` | Auth | Inertia/Vue | User dashboard + seller module |
| `/admin/*` | Auth+Admin | Blade | Admin panel |
| `/api/*` | Mixed | JSON | AJAX endpoints (cart, likes, search autocomplete) |

---

## 4. Database Schema

### 4.1 Users & Roles

```
users (extend existing)
├── id (bigint, PK)
├── name (string)
├── email (string, unique)
├── email_verified_at (timestamp, nullable)
├── password (string, nullable — for social login users)
├── avatar (string, nullable)
├── role (enum: 'user', 'seller', 'admin', default: 'user')
├── is_seller_approved (boolean, default: false)
├── provider (string, nullable — google/facebook/twitter)
├── provider_id (string, nullable)
├── two_factor_secret (text, nullable) — already exists
├── two_factor_recovery_codes (text, nullable) — already exists
├── two_factor_confirmed_at (timestamp, nullable) — already exists
├── remember_token
├── created_at
└── updated_at
```

```
seller_profiles
├── id (bigint, PK)
├── user_id (bigint, FK → users, unique)
├── bio (longText, nullable) — rich text
├── company (string, nullable)
├── phone (string, nullable)
├── country (string, nullable)
├── region_state (string, nullable)
├── preferred_payment_method_id (bigint, FK → payment_methods, nullable)
├── bank_account_details (text, nullable) — bank info or digital wallet/email
├── company_logo (string, nullable)
├── banner (string, nullable)
├── created_at
└── updated_at
```

### 4.2 Products & Categories

```
faculties
├── id (bigint, PK)
├── name (string)
├── slug (string, unique)
├── created_at
└── updated_at
```

```
departments
├── id (bigint, PK)
├── faculty_id (bigint, FK → faculties)
├── name (string)
├── slug (string, unique)
├── created_at
└── updated_at
```

```
products
├── id (bigint, PK)
├── user_id (bigint, FK → users) — primary author
├── faculty_id (bigint, FK → faculties)
├── department_id (bigint, FK → departments)
├── title (string) — project topic
├── slug (string, unique)
├── abstract (longText, nullable) — rich text
├── table_of_content (longText, nullable) — rich text
├── chapter_one (longText, nullable) — rich text
├── meta_description (text, nullable)
├── meta_keywords (text, nullable)
├── document_type (string, nullable)
├── class_of_degree (string, nullable)
├── institution (string, nullable)
├── location_country (string, nullable)
├── location_region (string, nullable)
├── date_available (date, nullable)
├── price (decimal 10,2, default: 0.00) — 0 = free
├── is_paid (boolean, default: false)
├── status (enum: 'draft', 'pending', 'published', 'rejected', default: 'draft')
├── views_count (unsignedBigInteger, default: 0)
├── downloads_count (unsignedBigInteger, default: 0)
├── likes_count (unsignedBigInteger, default: 0)
├── is_featured (boolean, default: false)
├── published_at (timestamp, nullable)
├── created_at
└── updated_at

indexes: slug, user_id, faculty_id, department_id, institution, status
fulltext: title, abstract, meta_keywords, institution
```

```
product_images
├── id (bigint, PK)
├── product_id (bigint, FK → products)
├── path (string)
├── sort_order (tinyInteger, default: 0)
├── created_at
└── updated_at
```

```
product_files
├── id (bigint, PK)
├── product_id (bigint, FK → products)
├── file_path (string)
├── file_name (string)
├── file_size (unsignedBigInteger) — bytes
├── file_type (string)
├── created_at
└── updated_at
```

```
tags
├── id (bigint, PK)
├── name (string, unique)
├── slug (string, unique)
├── created_at
└── updated_at
```

```
product_tag (pivot)
├── product_id (bigint, FK → products)
├── tag_id (bigint, FK → tags)
```

### 4.3 Co-Authors & Collaboration

```
product_authors (pivot — multi-author system)
├── id (bigint, PK)
├── product_id (bigint, FK → products)
├── user_id (bigint, FK → users)
├── is_primary (boolean, default: false)
├── contribution_percentage (decimal 5,2) — e.g., 55.00
├── created_at
└── updated_at

unique: [product_id, user_id]
check: contribution_percentage >= 0 AND contribution_percentage <= 100
```

### 4.4 Cart & Orders

```
carts
├── id (bigint, PK)
├── user_id (bigint, FK → users, nullable) — null for guest carts
├── session_id (string, nullable) — for guest carts
├── created_at
└── updated_at
```

```
cart_items
├── id (bigint, PK)
├── cart_id (bigint, FK → carts)
├── product_id (bigint, FK → products)
├── price (decimal 10,2)
├── created_at
└── updated_at

unique: [cart_id, product_id]
```

```
orders
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── order_number (string, unique)
├── status (enum: 'pending', 'completed', 'failed', 'refunded', default: 'pending')
├── subtotal (decimal 10,2)
├── total (decimal 10,2)
├── payment_gateway (string, nullable)
├── payment_reference (string, nullable)
├── paid_at (timestamp, nullable)
├── created_at
└── updated_at
```

```
order_items
├── id (bigint, PK)
├── order_id (bigint, FK → orders)
├── product_id (bigint, FK → products)
├── price (decimal 10,2)
├── created_at
└── updated_at
```

### 4.5 Downloads & Access

```
downloads
├── id (bigint, PK)
├── user_id (bigint, FK → users, nullable)
├── product_id (bigint, FK → products)
├── ip_address (string, nullable)
├── created_at
└── updated_at
```

### 4.6 Revenue & Payouts

```
revenues
├── id (bigint, PK)
├── product_id (bigint, FK → products)
├── user_id (bigint, FK → users) — the author who earns
├── type (enum: 'view', 'download', 'sale')
├── amount_usd (decimal 10,4)
├── visitor_ip (string, nullable)
├── created_at
└── updated_at
```

```
payout_requests
├── id (bigint, PK)
├── user_id (bigint, FK → users)
├── amount_usd (decimal 10,2)
├── status (enum: 'pending', 'approved', 'paid', 'rejected', default: 'pending')
├── payment_method_id (bigint, FK → payment_methods, nullable)
├── payment_details (text, nullable)
├── admin_note (text, nullable)
├── processed_at (timestamp, nullable)
├── created_at
└── updated_at
```

```
payments_given (admin records of actual payments to authors)
├── id (bigint, PK)
├── payout_request_id (bigint, FK → payout_requests)
├── user_id (bigint, FK → users)
├── amount_usd (decimal 10,2)
├── payment_method (string)
├── reference (string, nullable)
├── created_at
└── updated_at
```

### 4.7 Reviews & Likes

```
reviews
├── id (bigint, PK)
├── product_id (bigint, FK → products)
├── user_id (bigint, FK → users)
├── rating (tinyInteger) — 1 to 5
├── comment (text, nullable)
├── is_approved (boolean, default: true)
├── created_at
└── updated_at

unique: [product_id, user_id]
```

```
likes
├── id (bigint, PK)
├── product_id (bigint, FK → products)
├── user_id (bigint, FK → users)
├── created_at

unique: [product_id, user_id]
```

### 4.8 Messaging

```
messages
├── id (bigint, PK)
├── product_id (bigint, FK → products)
├── sender_name (string)
├── sender_email (string)
├── subject (string)
├── body (text)
├── is_read (boolean, default: false)
├── created_at
└── updated_at
```

```
message_recipients (delivered to each author/co-author)
├── id (bigint, PK)
├── message_id (bigint, FK → messages)
├── user_id (bigint, FK → users)
├── is_read (boolean, default: false)
├── created_at
└── updated_at
```

### 4.9 Newsletter

```
newsletter_subscribers
├── id (bigint, PK)
├── email (string, unique)
├── name (string, nullable)
├── is_active (boolean, default: true)
├── subscribed_at (timestamp)
├── unsubscribed_at (timestamp, nullable)
├── created_at
└── updated_at
```

```
newsletter_campaigns
├── id (bigint, PK)
├── subject (string)
├── body (longText) — rich text HTML
├── status (enum: 'draft', 'scheduled', 'sent', default: 'draft')
├── scheduled_at (timestamp, nullable)
├── sent_at (timestamp, nullable)
├── recipients_count (unsignedInteger, default: 0)
├── created_at
└── updated_at
```

### 4.10 Admin CMS — Dynamic Pages

```
pages
├── id (bigint, PK)
├── title (string)
├── slug (string, unique)
├── body (longText) — rich text HTML
├── position (enum: 'nav', 'footer', 'both', 'none', default: 'none')
├── sort_order (integer, default: 0)
├── is_published (boolean, default: true)
├── meta_description (text, nullable)
├── meta_keywords (text, nullable)
├── created_at
└── updated_at
```

### 4.11 Admin Settings

```
settings (key-value store)
├── id (bigint, PK)
├── group (string) — e.g., 'general', 'monetization', 'payment', 'seller'
├── key (string, unique)
├── value (text, nullable)
├── type (string, default: 'string') — string, boolean, integer, decimal, json
├── created_at
└── updated_at
```

**Default settings entries:**

| Group | Key | Default | Description |
|-------|-----|---------|-------------|
| general | site_name | ProjectRim | Site display name |
| general | site_description | Knowledge Working for You | Tagline |
| general | contact_email | info@projectrim.com | Contact email |
| seller | auto_approve_sellers | true | Auto-approve seller applications |
| seller | allow_paid_products | false | Enable/disable paid products globally |
| monetization | smart_link_url | (admin sets) | Smart link base URL pattern |
| monetization | smart_link_enabled | true | Enable/disable smart links |
| monetization | view_reward_usd | 0.10 | Revenue per product view |
| monetization | download_reward_usd | 1.00 | Revenue per product download |
| payment | currency_conversion_rates | {"NGN":1500,"GHS":15} | JSON map of currency rates from USD |
| payment | stripe_enabled | false | Toggle Stripe |
| payment | paypal_enabled | false | Toggle PayPal |
| payment | paystack_enabled | false | Toggle Paystack |
| payment | flutterwave_enabled | false | Toggle Flutterwave |
| payment | bank_transfer_enabled | true | Toggle manual bank transfer |
| carousel | slides | [] | JSON array of carousel slide data |

### 4.12 Payment Methods (for seller payout selection)

```
payment_methods
├── id (bigint, PK)
├── name (string) — e.g., "Bank Transfer", "PayPal", "Paystack"
├── is_active (boolean, default: true)
├── created_at
└── updated_at
```

### 4.13 Countries (reference data)

```
countries
├── id (bigint, PK)
├── name (string)
├── code (string, 2 chars) — ISO 3166-1 alpha-2
├── created_at
└── updated_at
```

---

## 5. Roles & Permissions

| Role | Access |
|------|--------|
| **Guest** | Browse products, search, view product pages, add to cart (session-based) |
| **User** | Everything Guest + dashboard, downloads, orders, profile, messaging, apply for seller |
| **Seller/Author** | Everything User + product management, seller profile, revenue/payouts, order management |
| **Admin** | Full Blade admin panel — manage everything |

### Middleware

| Middleware | Purpose |
|-----------|---------|
| `auth` | Require authentication |
| `verified` | Require email verification |
| `role:seller` | Require seller role |
| `role:admin` | Require admin role |
| `guest` | Only unauthenticated users |

---

## 6. Module Breakdown

---

### 6.1 Authentication & Social Login

#### Packages to Install

```
composer require laravel/socialite
```

#### Implementation

- **Extend Fortify** (already configured) for standard email/password auth
- **Add Socialite routes** for Google, Facebook, Twitter/X
- **Social login flow:**
  1. User clicks social button → redirected to provider
  2. Callback: find or create user by `provider` + `provider_id`
  3. If email matches existing account, link social provider
  4. Redirect to `/dashboard`

#### Routes

```
GET  /auth/social/{provider}/redirect   → SocialAuthController@redirect
GET  /auth/social/{provider}/callback   → SocialAuthController@callback
```

#### Vue Components

- Update `Login.vue` — add social login buttons (Google, Facebook, Twitter/X)
- Update `Register.vue` — add social login buttons
- Social buttons styled with brand colors

#### Migration

- Add `provider` and `provider_id` columns to `users` table
- Make `password` nullable (social-only users may not have one)

---

### 6.2 Public Website (SPA — Vue/Inertia)

All public pages are rendered via Inertia with Vue components.

#### Pages

| Page | Route | Description |
|------|-------|-------------|
| Landing/Home | `/` | Hero carousel, featured products, categories, newsletter signup, CTA |
| Browse/Catalog | `/products` | Grid of products with filters/sorting |
| Product Detail | `/products/{slug}` | Full product page with tabs |
| Search Results | `/search` | Advanced search results |
| Faculty Page | `/faculty/{slug}` | Products in a faculty |
| Department Page | `/department/{slug}` | Products in a department |
| Institution Page | `/institution/{name}` | Products from an institution |
| Author Page | `/author/{id}` | Author profile + their products |
| Country Page | `/country/{code}` | Products from a country |
| Tag Page | `/tags/{slug}` | Products with a specific tag |
| Dynamic CMS Page | `/page/{slug}` | Admin-created pages |
| 404 Page | `*` | Custom branded 404 |
| Cart | `/cart` | Shopping cart |

#### Landing Page Components

```
pages/
  Welcome.vue (revamp)
  ├── HeroCarousel.vue — image slider (Embla Carousel), admin-managed slides
  ├── SearchBar.vue — prominent advanced search
  ├── FeaturedProducts.vue — featured/trending products grid
  ├── CategoryBrowser.vue — browse by faculty/department
  ├── RecentProducts.vue — latest uploads
  ├── StatsCounter.vue — total products, authors, downloads
  ├── NewsletterSignup.vue — email subscription form
  ├── BecomeSellerCTA.vue — call to action to become a seller
  └── Footer.vue — dynamic links from CMS pages + newsletter
```

#### Product Detail Page Tabs

```
pages/products/Show.vue
  ├── Tab: Description — abstract, meta info
  ├── Tab: Project Preview — table of content, chapter 1
  ├── Tab: Reviews — star ratings + comments
  ├── Tab: Author's Bio — all authors/co-authors with bios + "View Papers" link
  └── Tab: Messenger — send message to author(s)
```

**Product detail features:**
- Like button with counter: `LIKE (99)`
- Download button (through smart link)
- Add to cart (if paid)
- Clickable links: institution → institution page, author name → author page, country → country page
- All co-authors displayed with contribution info
- Share buttons

#### 404 Page

- ProjectRim branded with logo
- Friendly message
- Search bar
- Links to home and popular categories
- Mobile responsive

---

### 6.3 User Dashboard (SPA — Vue/Inertia)

Accessible at `/dashboard/*` after login. Uses sidebar layout (existing `AppSidebarLayout.vue`).

#### Menu Items (User)

| Menu | Route | Description |
|------|-------|-------------|
| Overview | `/dashboard` | Stats overview — downloads, orders, activity |
| Explore Products | `/dashboard/explore` | Browse/search products (embedded) |
| My Downloads | `/dashboard/downloads` | List of downloaded products |
| My Orders | `/dashboard/orders` | Order history |
| Messages | `/dashboard/messages` | Inbox — messages received |
| Cart | `/dashboard/cart` | Current cart |
| Profile | `/dashboard/profile` | Edit user profile |
| Security | `/dashboard/security` | Password, 2FA (existing) |
| *Become a Seller* | `/dashboard/apply-seller` | CTA button/page to apply as seller |

#### Overview Dashboard Widgets

- Total downloads (count)
- Total orders (count)
- Recent activity feed
- Quick search bar
- "Become a Seller" banner (if not seller)

---

### 6.4 Seller/Author Module (SPA — Vue/Inertia)

When user has `role = 'seller'`, additional menu items appear in the same sidebar.

#### Additional Seller Menu Items

| Menu | Route | Description |
|------|-------|-------------|
| **Seller Section** | — | Section divider |
| Seller Overview | `/dashboard/seller` | Seller stats — revenue, views, downloads |
| Seller Profile | `/dashboard/seller/profile` | Edit seller profile (bio, company, payment info) |
| Products | `/dashboard/seller/products` | Product management (CRUD) |
| Product Create | `/dashboard/seller/products/create` | Two-tab product form |
| Product Edit | `/dashboard/seller/products/{id}/edit` | Edit product |
| Orders | `/dashboard/seller/orders` | Orders for seller's products |
| Transactions | `/dashboard/seller/transactions` | Revenue breakdown (views, downloads, sales) |
| Payments Given | `/dashboard/seller/payments` | History of admin payments to seller |
| Payout Requests | `/dashboard/seller/payouts` | Request payout + history |
| Payment Method | `/dashboard/seller/payment-method` | Select preferred payout method |

#### Seller Application Flow

1. User clicks "Become a Seller"
2. If `settings.auto_approve_sellers = true` → instant approval, role changes to `seller`
3. If `settings.auto_approve_sellers = false` → application saved as pending, admin notified
4. After approval, seller menu items appear in sidebar

#### Seller Profile Form Fields

- Login Email (read-only, auto-filled)
- Bio (rich text — Tiptap editor)
- Company (optional)
- Phone
- Country (select dropdown from `countries` table)
- Region / State
- Preferred Method of Payment (select from active `payment_methods`)
- Bank Account Details / Digital Wallet Email (textarea)
- Company Logo (image upload)
- Banner (image upload)

---

### 6.5 Product System

#### Product Create/Edit Form — Two Tabs

**Tab 1: General**

*Name Section:*
- Project Topic (text input, required)
- Select Faculty (dropdown from `faculties`)
- Select Department (dropdown from `departments`, filtered by faculty)

*File Section:*
- Images (multiple image upload, sortable)
- Upload Project File (single file upload — PDF, DOC, DOCX, etc.)

*Description / Preview Section:*
- Abstract (rich text — Tiptap)
- Table of Content (rich text — Tiptap)
- Chapter 1 (rich text — Tiptap)
- Meta Tag Description (textarea)
- Meta Tag Keywords (textarea)
- Tags (tag input — comma-separated or autocomplete)

**Tab 2: Data**

- Author/Authors — Co-author management panel (see §6.6)
- Document Type (text input or select)
- Class of Degree (select: First Class, Second Class Upper, Second Class Lower, Third Class, Pass, Distinction, Credit, Merit)
- Institution (text input with autocomplete)
- Location — Country (select) + Region/State
- Date Available (date picker)
- Price (number input — only shown if `settings.allow_paid_products = true`, default: 0)

#### Product Status Flow

```
Draft → Pending Review → Published
                       → Rejected (with reason)
```

- Sellers save as draft first
- Submit for review → status becomes `pending`
- Admin approves → `published` (or auto-publish configurable in settings)
- Admin rejects → `rejected` with note

---

### 6.6 Co-Author & Collaboration System

#### Core Rules

1. Primary author = the uploader (auto-assigned, name pulled from account)
2. Primary author starts at **100% contribution**
3. Up to **10 co-authors** can be added
4. Co-authors must be **registered users** — validated by email
5. Adding a co-author with X% reduces primary author's share dynamically
6. **Total must always equal 100%**
7. Revenue is distributed per contribution percentage

#### UI Component: `CoAuthorManager.vue`

```
┌──────────────────────────────────────────────────┐
│ Primary Author: John Doe (john@email.com)        │
│ Contribution: [55%] (auto-calculated)            │
├──────────────────────────────────────────────────┤
│ Co-Author 1: [email input] → [validated name]    │
│ Contribution: [25%]                              │
│ [Remove]                                         │
├──────────────────────────────────────────────────┤
│ Co-Author 2: [email input] → [validated name]    │
│ Contribution: [20%]                              │
│ [Remove]                                         │
├──────────────────────────────────────────────────┤
│ [+ Add Co-Author]                                │
│ Total: 100% ✓                                    │
└──────────────────────────────────────────────────┘
```

#### Validation Logic

- Co-author email must exist in `users` table
- AJAX lookup on email input → return user name or error
- Sum of all percentages must = 100
- Primary author percentage auto-adjusts as co-authors are added/removed
- Minimum percentage per author: 1%

#### Revenue Distribution

When revenue is recorded (view/download/sale):
1. Calculate total revenue amount
2. For each author in `product_authors`:
   - `author_revenue = total * (contribution_percentage / 100)`
   - Insert row into `revenues` table for each author

---

### 6.7 Search & Discovery

#### Advanced Search Fields

| Field | Type | Searches Against |
|-------|------|-----------------|
| Keyword | Text | Product title, abstract, meta keywords |
| Author Email | Text | Author/co-author email |
| Author Name | Text | Author/co-author name |
| Institution | Text/Autocomplete | `products.institution` |
| Faculty | Select | `faculties` |
| Department | Select | `departments` |
| Class of Degree | Select | `products.class_of_degree` |
| Country | Select | `products.location_country` |
| Document Type | Select | `products.document_type` |
| Tags | Multi-select | `tags` via pivot |
| Date Range | Date range | `products.date_available` |
| Sort By | Select | Relevance, Newest, Most Downloaded, Most Viewed, Most Liked |

#### Implementation

- **Frontend:** `AdvancedSearchForm.vue` component with collapsible filters
- **Backend:** `ProductSearchController` using Eloquent query builder with conditional where clauses
- **Search bar:** Present on landing page header, navigation bar, user dashboard
- **Autocomplete:** AJAX endpoint for institution names, tags, author names
- **URL-based filters:** All filters encoded in URL query params for shareable search links

#### API Endpoints

```
GET /api/search/products       → Full search with all filters
GET /api/search/autocomplete   → Quick autocomplete suggestions
GET /api/search/institutions   → Institution name autocomplete
GET /api/search/authors        → Author name autocomplete
```

---

### 6.8 Cart & Orders

#### Cart Features

- Session-based for guests (converted on login)
- User-based for logged-in users
- Add/remove products
- Cart persists across sessions
- Cart icon with item count in header
- Cart page with item list, totals, checkout button

#### Order Flow

1. User adds paid products to cart
2. Proceeds to checkout → selects payment gateway
3. Payment processed via selected gateway
4. On success: order created, download access granted
5. Seller notified of new order
6. Revenue distributed to author(s)

#### Free Products

- No cart needed — direct download through smart link
- Download recorded in `downloads` table
- Revenue recorded (download reward)

---

### 6.9 Revenue, Smart Links & Monetization

#### Smart Link Integration

- Admin configures smart link URL pattern in settings (e.g., `https://omg10.com/4/10693706`)
- When user clicks download:
  1. System generates a smart link wrapping the actual download URL
  2. User passes through smart link (views ads/completes action)
  3. Redirected to actual file download
  4. Download is recorded

#### Revenue Model

| Action | Rate (Default) | Admin Configurable |
|--------|---------------|-------------------|
| Product View | $0.10 | Yes — `settings.view_reward_usd` |
| Product Download | $1.00 | Yes — `settings.download_reward_usd` |
| Product Sale | Sale price | Based on product price |

- Revenue is **split among all authors** per contribution percentage
- Revenue recorded in `revenues` table per author
- Admin can view total revenue per product/author
- Currency conversion rates set by admin (e.g., 1 USD = 1500 NGN)

#### Anti-Fraud

- View revenue: max 1 view reward per IP per product per 24 hours
- Download revenue: max 1 download reward per user per product per 24 hours
- IP-based rate limiting for guests

---

### 6.10 Payments & Payouts

#### Payment Gateways (for purchasing paid products)

Admin can toggle each gateway on/off independently.

| Gateway | Integration |
|---------|------------|
| Stripe | `stripe/stripe-php` SDK |
| PayPal | PayPal REST SDK |
| Paystack | Paystack PHP SDK |
| Flutterwave | Flutterwave PHP SDK |
| Bank Transfer | Manual — admin confirms |

#### Payout System (paying authors)

1. Author accumulates revenue from views/downloads/sales
2. Author checks balance on `/dashboard/seller/transactions`
3. Author submits payout request with amount
4. Admin reviews request in admin panel
5. Admin approves → marks as `approved`
6. Admin pays via selected method → records in `payments_given`
7. Author notified of payment

#### Author Balance Calculation

```
balance = SUM(revenues.amount_usd WHERE user_id = ?)
        - SUM(payments_given.amount_usd WHERE user_id = ?)
```

---

### 6.11 Reviews, Ratings & Likes

#### Reviews

- Authenticated users can leave one review per product
- Star rating: 1–5 stars (required)
- Comment text (optional)
- Reviews shown under "Reviews" tab on product page
- Average rating displayed on product cards and detail page

#### Likes

- One like per user per product (toggle)
- Like count shown on product: `LIKE (99)`
- AJAX endpoint for like/unlike
- No authentication required to view count, auth required to like

#### Admin Analytics

- Admin can view likes/reviews statistics
- Filter by timeframe (7 days, 30 days, 90 days, custom)
- Most liked products chart
- Most reviewed products chart

---

### 6.12 Messaging System

#### Product Messenger Tab

A "Messenger" tab on each product detail page.

**Form fields:**
- Your Email Address (input)
- Your Name (input)
- Subject (input)
- Message (textarea)
- Send button

**Delivery logic:**
- Single author → email sent to that author only
- Multiple authors → email sent to ALL authors/co-authors
- Emails sent via platform email (from `info@projectrim.com`)
- Message stored in `messages` + `message_recipients` tables
- Author can view received messages in `/dashboard/messages`

**Security:**
- Rate limiting: max 5 messages per IP per hour
- Honeypot field for bot prevention
- No personal email addresses exposed

---

### 6.13 Email Notifications

All emails sent via Laravel queue jobs for performance.

#### Notifications to Author/Co-Authors

| Event | Trigger |
|-------|---------|
| Product liked | User likes their product |
| Product rated/reviewed | User leaves a review |
| Product ordered | User places an order for their product |
| Product downloaded | User downloads their product |
| Milestone reached | Views or orders hit: 1, 10, 100, 1K, 10K, 100K, 1M, 10M |
| Profile updated | Author updates their profile |
| Message received | User sends a message via messenger tab |

#### Notifications to Admin

| Event | Trigger |
|-------|---------|
| Payout request | Author requests payout |
| New user registered | New account created |
| Product uploaded | New product submitted |
| Product ordered | New order placed |
| Message sent | User sends message to author(s) |

#### Implementation

- Use Laravel `Notification` system with `mail` channel
- Each notification is a separate class (e.g., `ProductLikedNotification`)
- Queue all email notifications via `ShouldQueue`
- Milestone notifications use a helper that checks thresholds after incrementing counts

---

### 6.14 Newsletter

#### Subscriber Management

- Subscribe form on landing page footer + dedicated section
- Email validation + duplicate prevention
- Unsubscribe link in every email
- Admin can view/export subscriber list

#### Campaign Management (Admin — Blade)

- Create campaigns with rich text editor
- Send to all active subscribers
- Schedule campaigns for future send
- Track recipients count
- Campaigns dispatched via queued jobs (batched for large lists)

---

### 6.15 Admin Dashboard (Blade)

Server-rendered using Blade templates + Tailwind CSS. Separate layout from the Vue SPA.

#### Admin Layout

```
resources/views/admin/
├── layouts/
│   └── app.blade.php — admin master layout (sidebar + topbar)
├── dashboard.blade.php — overview/statistics
├── users/
│   ├── index.blade.php — user list
│   ├── show.blade.php — user detail + stats
│   └── edit.blade.php — edit user
├── products/
│   ├── index.blade.php — product list (with filters)
│   ├── show.blade.php — product detail + stats
│   └── edit.blade.php — edit/approve/reject
├── orders/
│   ├── index.blade.php — all orders
│   └── show.blade.php — order detail
├── seller-applications/
│   ├── index.blade.php — pending applications
│   └── show.blade.php — review application
├── payouts/
│   ├── index.blade.php — payout requests
│   └── show.blade.php — process payout
├── pages/
│   ├── index.blade.php — CMS pages list
│   ├── create.blade.php — create page
│   └── edit.blade.php — edit page
├── categories/
│   ├── faculties.blade.php — manage faculties
│   └── departments.blade.php — manage departments
├── reviews/
│   └── index.blade.php — all reviews (moderate)
├── newsletter/
│   ├── subscribers.blade.php — subscriber list
│   ├── campaigns/
│   │   ├── index.blade.php — campaign list
│   │   ├── create.blade.php — create campaign
│   │   └── show.blade.php — campaign detail
├── messages/
│   └── index.blade.php — all messages
├── settings/
│   ├── general.blade.php — site name, contact, etc.
│   ├── monetization.blade.php — smart links, view/download rates
│   ├── payment.blade.php — toggle gateways, currency rates
│   ├── seller.blade.php — auto-approve, paid products toggle
│   ├── carousel.blade.php — manage landing page carousel slides
│   └── payment-methods.blade.php — manage payout methods
└── analytics/
    ├── products.blade.php — product stats
    └── users.blade.php — user stats
```

#### Admin Dashboard Overview

| Widget | Data |
|--------|------|
| Total Users | Count + new this month |
| Total Products | Count + new this month |
| Total Downloads | Count + this month |
| Total Orders | Count + revenue this month |
| Total Revenue | Sum + this month |
| Recent Users | Last 10 registrations |
| Recent Products | Last 10 uploads |
| Recent Orders | Last 10 orders |
| Popular Products | Top 10 by views/downloads |
| Pending Actions | Pending: reviews, seller apps, payouts |

#### Admin Statistics — Products

| Stat | Description |
|------|-------------|
| Upload time | Timestamp of upload |
| Downloads | Total download count |
| Views | Total view count |
| Orders | Count + email addresses of buyers |
| Likes | Total likes |
| Ratings | Average + count |
| Revenue | Total $ made |

#### Admin Statistics — Users

| Stat | Description |
|------|-------------|
| Registration time | Timestamp |
| Products uploaded | Count |
| Products downloaded | Count |
| Revenue earned | Total $ |
| Payments received | Total $ paid out |
| Balance remaining | Earned − paid |

---

### 6.16 Admin CMS — Dynamic Pages

#### Feature

- Admin can create/edit/delete custom pages (About Us, Terms, Privacy, FAQ, etc.)
- Each page has:
  - Title
  - Slug (auto-generated, editable)
  - Body (rich text editor)
  - Position: **Nav**, **Footer**, **Both**, or **None**
  - Sort order (for menu ordering)
  - Published toggle
  - Meta description + keywords (SEO)

#### Navigation Integration

- Pages with `position = 'nav'` or `'both'` appear in the main navigation
- Pages with `position = 'footer'` or `'both'` appear in the footer
- Sorted by `sort_order`
- Shared via Inertia middleware (loaded once, cached)

#### Frontend Route

```
GET /page/{slug} → PageController@show → renders pages/CmsPage.vue
```

---

## 7. Theme & Design System

### Color Palette

| Token | Hex | Usage |
|-------|-----|-------|
| Primary | `#0a4b76` | Primary brand color, headings, nav bg |
| Primary Light | `#337ab7` | Links, secondary buttons |
| Accent | `#1f90bb` | Good/positive buttons, CTAs |
| Danger | `#da3539` | Bad/delete/error buttons |
| Danger Dark | `#a94442` | Error text, alerts |
| White | `#ffffff` | Backgrounds, text on dark |

### Tailwind CSS Configuration

Update `resources/css/app.css` to map CSS custom properties:

```css
--primary: #0a4b76;
--primary-light: #337ab7;
--accent: #1f90bb;
--danger: #da3539;
--danger-dark: #a94442;
```

### Button Styles

| Type | Color | Usage |
|------|-------|-------|
| Primary Action | `#1f90bb` (accent) | Submit, Save, Download, Approve |
| Destructive Action | `#da3539` (danger) | Delete, Reject, Cancel |
| Secondary | `#337ab7` (primary-light) | Secondary actions |
| Ghost/Outline | Transparent + border | Tertiary actions |

### Responsive Design

- **Mobile-first** approach with Tailwind breakpoints
- Sidebar collapses to hamburger menu on mobile
- Product grid: 1 col (mobile) → 2 cols (tablet) → 3-4 cols (desktop)
- Search filters collapse to accordion on mobile
- Carousel is touch-enabled via Embla

---

## 8. API Routes Structure

### Web Routes (Inertia — `routes/web.php`)

```php
// Public
Route::get('/', [HomeController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/faculty/{slug}', [FacultyController::class, 'show']);
Route::get('/department/{slug}', [DepartmentController::class, 'show']);
Route::get('/institution/{name}', [InstitutionController::class, 'show']);
Route::get('/author/{id}', [AuthorController::class, 'show']);
Route::get('/country/{code}', [CountryController::class, 'show']);
Route::get('/tags/{slug}', [TagController::class, 'show']);
Route::get('/search', [SearchController::class, 'index']);
Route::get('/page/{slug}', [PageController::class, 'show']);
Route::get('/cart', [CartController::class, 'index']);

// Social Auth
Route::get('/auth/social/{provider}/redirect', [SocialAuthController::class, 'redirect']);
Route::get('/auth/social/{provider}/callback', [SocialAuthController::class, 'callback']);

// Authenticated (User Dashboard)
Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/downloads', [DownloadController::class, 'index']);
    Route::get('/orders', [UserOrderController::class, 'index']);
    Route::get('/messages', [UserMessageController::class, 'index']);
    Route::get('/explore', [ExploreController::class, 'index']);
    Route::get('/apply-seller', [SellerApplicationController::class, 'create']);
    Route::post('/apply-seller', [SellerApplicationController::class, 'store']);

    // Seller routes
    Route::middleware('role:seller')->prefix('seller')->group(function () {
        Route::get('/', [SellerDashboardController::class, 'index']);
        Route::resource('products', SellerProductController::class);
        Route::get('/profile', [SellerProfileController::class, 'edit']);
        Route::put('/profile', [SellerProfileController::class, 'update']);
        Route::get('/orders', [SellerOrderController::class, 'index']);
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/payments', [PaymentHistoryController::class, 'index']);
        Route::get('/payouts', [PayoutController::class, 'index']);
        Route::post('/payouts', [PayoutController::class, 'store']);
        Route::get('/payment-method', [PaymentMethodController::class, 'edit']);
        Route::put('/payment-method', [PaymentMethodController::class, 'update']);
    });
});
```

### API Routes (`routes/api.php`)

```php
// Public API
Route::get('/search/products', [ApiSearchController::class, 'products']);
Route::get('/search/autocomplete', [ApiSearchController::class, 'autocomplete']);
Route::get('/search/institutions', [ApiSearchController::class, 'institutions']);
Route::get('/search/authors', [ApiSearchController::class, 'authors']);
Route::get('/departments/{faculty}', [ApiDepartmentController::class, 'byFaculty']);

// Cart
Route::post('/cart/add', [ApiCartController::class, 'add']);
Route::delete('/cart/{item}', [ApiCartController::class, 'remove']);
Route::get('/cart/count', [ApiCartController::class, 'count']);

// Auth-required API
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{product}/like', [ApiLikeController::class, 'toggle']);
    Route::post('/products/{product}/review', [ApiReviewController::class, 'store']);
    Route::post('/products/{product}/message', [ApiMessageController::class, 'store']);
    Route::get('/users/search-by-email', [ApiUserController::class, 'searchByEmail']);
});

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);

// Download (with smart link)
Route::get('/download/{product}', [DownloadController::class, 'download'])->name('download.product');
```

### Admin Routes (`routes/admin.php`)

```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', AdminUserController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::resource('products', AdminProductController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::post('products/{product}/approve', [AdminProductController::class, 'approve'])->name('products.approve');
    Route::post('products/{product}/reject', [AdminProductController::class, 'reject'])->name('products.reject');

    Route::resource('orders', AdminOrderController::class)->only(['index', 'show']);
    Route::resource('pages', AdminPageController::class);
    Route::resource('faculties', AdminFacultyController::class);
    Route::resource('departments', AdminDepartmentController::class);

    Route::get('/seller-applications', [AdminSellerApplicationController::class, 'index'])->name('seller-applications.index');
    Route::post('/seller-applications/{user}/approve', [AdminSellerApplicationController::class, 'approve'])->name('seller-applications.approve');
    Route::post('/seller-applications/{user}/reject', [AdminSellerApplicationController::class, 'reject'])->name('seller-applications.reject');

    Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/{payout}', [AdminPayoutController::class, 'show'])->name('payouts.show');
    Route::post('/payouts/{payout}/approve', [AdminPayoutController::class, 'approve'])->name('payouts.approve');
    Route::post('/payouts/{payout}/pay', [AdminPayoutController::class, 'pay'])->name('payouts.pay');
    Route::post('/payouts/{payout}/reject', [AdminPayoutController::class, 'reject'])->name('payouts.reject');

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/newsletter/subscribers', [AdminNewsletterController::class, 'subscribers'])->name('newsletter.subscribers');
    Route::resource('newsletter/campaigns', AdminCampaignController::class);
    Route::post('newsletter/campaigns/{campaign}/send', [AdminCampaignController::class, 'send'])->name('campaigns.send');

    Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');

    // Settings
    Route::get('/settings/general', [AdminSettingController::class, 'general'])->name('settings.general');
    Route::get('/settings/monetization', [AdminSettingController::class, 'monetization'])->name('settings.monetization');
    Route::get('/settings/payment', [AdminSettingController::class, 'payment'])->name('settings.payment');
    Route::get('/settings/seller', [AdminSettingController::class, 'seller'])->name('settings.seller');
    Route::get('/settings/carousel', [AdminSettingController::class, 'carousel'])->name('settings.carousel');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::resource('payment-methods', AdminPaymentMethodController::class)->only(['index', 'store', 'update', 'destroy']);

    // Analytics
    Route::get('/analytics/products', [AdminAnalyticsController::class, 'products'])->name('analytics.products');
    Route::get('/analytics/users', [AdminAnalyticsController::class, 'users'])->name('analytics.users');
});
```

---

## 9. File & Folder Structure

### Backend (Laravel)

```
app/
├── Actions/
│   └── Fortify/ (existing)
├── Concerns/
│   ├── PasswordValidationRules.php (existing)
│   └── ProfileValidationRules.php (existing)
├── Http/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── ProductController.php
│   │   ├── SearchController.php
│   │   ├── FacultyController.php
│   │   ├── DepartmentController.php
│   │   ├── InstitutionController.php
│   │   ├── AuthorController.php
│   │   ├── CountryController.php
│   │   ├── TagController.php
│   │   ├── PageController.php
│   │   ├── CartController.php
│   │   ├── DownloadController.php
│   │   ├── NewsletterController.php
│   │   ├── Auth/
│   │   │   └── SocialAuthController.php
│   │   ├── Dashboard/
│   │   │   ├── DashboardController.php
│   │   │   ├── UserOrderController.php
│   │   │   ├── UserMessageController.php
│   │   │   ├── ExploreController.php
│   │   │   └── SellerApplicationController.php
│   │   ├── Seller/
│   │   │   ├── SellerDashboardController.php
│   │   │   ├── SellerProductController.php
│   │   │   ├── SellerProfileController.php
│   │   │   ├── SellerOrderController.php
│   │   │   ├── TransactionController.php
│   │   │   ├── PaymentHistoryController.php
│   │   │   ├── PayoutController.php
│   │   │   └── PaymentMethodController.php
│   │   ├── Api/
│   │   │   ├── ApiSearchController.php
│   │   │   ├── ApiCartController.php
│   │   │   ├── ApiLikeController.php
│   │   │   ├── ApiReviewController.php
│   │   │   ├── ApiMessageController.php
│   │   │   ├── ApiUserController.php
│   │   │   └── ApiDepartmentController.php
│   │   ├── Admin/
│   │   │   ├── AdminDashboardController.php
│   │   │   ├── AdminUserController.php
│   │   │   ├── AdminProductController.php
│   │   │   ├── AdminOrderController.php
│   │   │   ├── AdminPageController.php
│   │   │   ├── AdminFacultyController.php
│   │   │   ├── AdminDepartmentController.php
│   │   │   ├── AdminSellerApplicationController.php
│   │   │   ├── AdminPayoutController.php
│   │   │   ├── AdminReviewController.php
│   │   │   ├── AdminNewsletterController.php
│   │   │   ├── AdminCampaignController.php
│   │   │   ├── AdminMessageController.php
│   │   │   ├── AdminSettingController.php
│   │   │   ├── AdminPaymentMethodController.php
│   │   │   └── AdminAnalyticsController.php
│   │   └── Settings/ (existing)
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php (existing — extend to share nav/footer pages)
│   │   ├── HandleAppearance.php (existing)
│   │   ├── EnsureUserHasRole.php (new)
│   │   └── TrackProductView.php (new)
│   └── Requests/
│       ├── StoreProductRequest.php
│       ├── UpdateProductRequest.php
│       ├── StoreReviewRequest.php
│       ├── StoreMessageRequest.php
│       ├── SellerProfileRequest.php
│       ├── PayoutRequest.php
│       └── Admin/
│           ├── StorePageRequest.php
│           ├── UpdateSettingRequest.php
│           └── ...
├── Models/
│   ├── User.php (extend)
│   ├── SellerProfile.php
│   ├── Product.php
│   ├── ProductImage.php
│   ├── ProductFile.php
│   ├── ProductAuthor.php
│   ├── Faculty.php
│   ├── Department.php
│   ├── Tag.php
│   ├── Cart.php
│   ├── CartItem.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Download.php
│   ├── Revenue.php
│   ├── PayoutRequest.php
│   ├── PaymentGiven.php
│   ├── Review.php
│   ├── Like.php
│   ├── Message.php
│   ├── MessageRecipient.php
│   ├── NewsletterSubscriber.php
│   ├── NewsletterCampaign.php
│   ├── Page.php
│   ├── Setting.php
│   ├── PaymentMethod.php
│   └── Country.php
├── Notifications/
│   ├── ProductLikedNotification.php
│   ├── ProductReviewedNotification.php
│   ├── ProductOrderedNotification.php
│   ├── ProductDownloadedNotification.php
│   ├── MilestoneReachedNotification.php
│   ├── ProfileUpdatedNotification.php
│   ├── MessageReceivedNotification.php
│   ├── Admin/
│   │   ├── PayoutRequestedNotification.php
│   │   ├── NewUserRegisteredNotification.php
│   │   ├── ProductUploadedNotification.php
│   │   ├── ProductOrderedAdminNotification.php
│   │   └── MessageSentAdminNotification.php
├── Services/
│   ├── RevenueService.php — calculate & distribute revenue
│   ├── SmartLinkService.php — generate monetized download links
│   ├── SearchService.php — advanced search query builder
│   ├── CartService.php — cart management
│   ├── MilestoneService.php — check & trigger milestone notifications
│   └── SettingService.php — cached settings helper
├── Jobs/
│   ├── SendNewsletterCampaignJob.php
│   ├── ProcessRevenueJob.php
│   └── SendMilestoneNotificationJob.php
└── Providers/
    ├── AppServiceProvider.php (existing)
    └── FortifyServiceProvider.php (existing)
```

### Frontend (Vue/Inertia)

```
resources/js/
├── app.ts (existing)
├── ssr.ts (existing)
├── pages/
│   ├── Welcome.vue (revamp — landing page)
│   ├── Dashboard.vue (revamp — user overview)
│   ├── auth/
│   │   ├── Login.vue (extend — social buttons)
│   │   ├── Register.vue (extend — social buttons)
│   │   ├── ForgotPassword.vue (existing)
│   │   ├── ResetPassword.vue (existing)
│   │   ├── VerifyEmail.vue (existing)
│   │   ├── TwoFactorChallenge.vue (existing)
│   │   └── ConfirmPassword.vue (existing)
│   ├── products/
│   │   ├── Index.vue — product catalog/browse
│   │   ├── Show.vue — product detail with tabs
│   │   └── Search.vue — search results
│   ├── browse/
│   │   ├── Faculty.vue — faculty products
│   │   ├── Department.vue — department products
│   │   ├── Institution.vue — institution products
│   │   ├── Author.vue — author profile + products
│   │   ├── Country.vue — country products
│   │   └── Tag.vue — tag products
│   ├── cart/
│   │   ├── Index.vue — cart page
│   │   └── Checkout.vue — checkout page
│   ├── dashboard/
│   │   ├── Index.vue — overview widgets
│   │   ├── Downloads.vue — download history
│   │   ├── Orders.vue — order history
│   │   ├── Messages.vue — message inbox
│   │   ├── Explore.vue — explore products
│   │   ├── ApplySeller.vue — seller application
│   │   └── seller/
│   │       ├── Index.vue — seller overview
│   │       ├── Profile.vue — seller profile form
│   │       ├── products/
│   │       │   ├── Index.vue — product list
│   │       │   ├── Create.vue — create product (2 tabs)
│   │       │   └── Edit.vue — edit product
│   │       ├── Orders.vue — seller orders
│   │       ├── Transactions.vue — revenue breakdown
│   │       ├── Payments.vue — payments received
│   │       ├── Payouts.vue — payout requests
│   │       └── PaymentMethod.vue — payment method selection
│   ├── pages/
│   │   └── CmsPage.vue — dynamic CMS page renderer
│   ├── errors/
│   │   └── NotFound.vue — custom 404
│   └── settings/ (existing)
├── components/
│   ├── app/ (existing)
│   ├── ui/ (existing — shadcn-vue)
│   ├── landing/
│   │   ├── HeroCarousel.vue
│   │   ├── FeaturedProducts.vue
│   │   ├── CategoryBrowser.vue
│   │   ├── RecentProducts.vue
│   │   ├── StatsCounter.vue
│   │   ├── NewsletterSignup.vue
│   │   ├── BecomeSellerCTA.vue
│   │   └── Footer.vue
│   ├── products/
│   │   ├── ProductCard.vue
│   │   ├── ProductGrid.vue
│   │   ├── ProductTabs.vue
│   │   ├── LikeButton.vue
│   │   ├── ReviewForm.vue
│   │   ├── ReviewList.vue
│   │   ├── AuthorBioTab.vue
│   │   ├── MessengerTab.vue
│   │   └── DownloadButton.vue
│   ├── search/
│   │   ├── AdvancedSearchForm.vue
│   │   ├── SearchBar.vue
│   │   ├── SearchFilters.vue
│   │   └── SearchAutocomplete.vue
│   ├── cart/
│   │   ├── CartIcon.vue
│   │   ├── CartItem.vue
│   │   └── CartSummary.vue
│   ├── seller/
│   │   ├── CoAuthorManager.vue
│   │   ├── ProductFormGeneral.vue
│   │   ├── ProductFormData.vue
│   │   ├── RevenueChart.vue
│   │   └── PayoutRequestForm.vue
│   ├── shared/
│   │   ├── SocialLoginButtons.vue
│   │   ├── RichTextEditor.vue (Tiptap wrapper)
│   │   ├── ImageUpload.vue
│   │   ├── FileUpload.vue
│   │   ├── TagInput.vue
│   │   ├── StarRating.vue
│   │   ├── Pagination.vue
│   │   └── EmptyState.vue
│   └── navigation/
│       ├── MainNav.vue — site header with dynamic CMS pages
│       └── SiteFooter.vue — footer with dynamic CMS pages
├── composables/
│   ├── useAppearance.ts (existing)
│   ├── useTwoFactorAuth.ts (existing)
│   ├── useCurrentUrl.ts (existing)
│   ├── useInitials.ts (existing)
│   ├── useCart.ts (new)
│   ├── useSearch.ts (new)
│   ├── useLike.ts (new)
│   └── useSettings.ts (new — access shared settings)
├── layouts/
│   ├── AppLayout.vue (existing — extend for seller menu)
│   ├── AuthLayout.vue (existing)
│   ├── PublicLayout.vue (new — for public pages with nav + footer)
│   └── app/
│       └── AppSidebarLayout.vue (existing — extend sidebar menu)
├── types/
│   ├── index.ts (extend)
│   ├── auth.ts (extend — add role, provider fields)
│   ├── product.ts (new — Product, ProductImage, ProductFile, etc.)
│   ├── seller.ts (new — SellerProfile, Revenue, Payout, etc.)
│   ├── cart.ts (new — Cart, CartItem, Order, etc.)
│   ├── review.ts (new — Review, Like)
│   ├── message.ts (new — Message)
│   ├── page.ts (new — CMS Page)
│   └── settings.ts (new — site settings)
├── lib/
│   └── utils.ts (existing)
└── routes/ (wayfinder generated)
```

### Admin Blade Views

```
resources/views/
├── app.blade.php (existing — Inertia root)
├── admin/
│   ├── layouts/
│   │   ├── app.blade.php — admin master layout
│   │   └── partials/
│   │       ├── sidebar.blade.php
│   │       ├── topbar.blade.php
│   │       └── footer.blade.php
│   ├── dashboard.blade.php
│   ├── users/
│   │   ├── index.blade.php
│   │   ├── show.blade.php
│   │   └── edit.blade.php
│   ├── products/
│   │   ├── index.blade.php
│   │   ├── show.blade.php
│   │   └── edit.blade.php
│   ├── orders/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── seller-applications/
│   │   └── index.blade.php
│   ├── payouts/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── pages/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   ├── categories/
│   │   ├── faculties.blade.php
│   │   └── departments.blade.php
│   ├── reviews/
│   │   └── index.blade.php
│   ├── newsletter/
│   │   ├── subscribers.blade.php
│   │   └── campaigns/
│   │       ├── index.blade.php
│   │       ├── create.blade.php
│   │       └── show.blade.php
│   ├── messages/
│   │   └── index.blade.php
│   ├── settings/
│   │   ├── general.blade.php
│   │   ├── monetization.blade.php
│   │   ├── payment.blade.php
│   │   ├── seller.blade.php
│   │   ├── carousel.blade.php
│   │   └── payment-methods.blade.php
│   └── analytics/
│       ├── products.blade.php
│       └── users.blade.php
```

---

## 10. Deployment & Environment

### Required `.env` Variables (to add)

```env
# Social Auth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL=${APP_URL}/auth/social/google/callback

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URL=${APP_URL}/auth/social/facebook/callback

TWITTER_CLIENT_ID=
TWITTER_CLIENT_SECRET=
TWITTER_REDIRECT_URL=${APP_URL}/auth/social/twitter/callback

# Payment Gateways
STRIPE_KEY=
STRIPE_SECRET=

PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_MODE=sandbox

PAYSTACK_PUBLIC_KEY=
PAYSTACK_SECRET_KEY=

FLUTTERWAVE_PUBLIC_KEY=
FLUTTERWAVE_SECRET_KEY=

# Mail (configure when provider chosen)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@projectrim.com
MAIL_FROM_NAME="ProjectRim"
```

### Composer Packages to Install

```bash
composer require laravel/socialite
composer require stripe/stripe-php
composer require unicodeveloper/laravel-paystack
composer require kingflamez/laravelrave
```

### NPM Packages to Install

```bash
npm install @tiptap/vue-3 @tiptap/starter-kit @tiptap/extension-link @tiptap/extension-image @tiptap/extension-placeholder
npm install embla-carousel-vue
npm install @vueuse/core
```

---

## 11. Implementation Phases

### Phase 1: Foundation (Week 1–2)

| # | Task | Priority |
|---|------|----------|
| 1.1 | Update theme colors in Tailwind config (CSS custom properties) | High |
| 1.2 | Create all database migrations | High |
| 1.3 | Create all Eloquent models with relationships | High |
| 1.4 | Create seeders (countries, faculties, departments, default settings, admin user) | High |
| 1.5 | Implement `EnsureUserHasRole` middleware | High |
| 1.6 | Implement `Setting` model with caching service | High |
| 1.7 | Create `PublicLayout.vue` (header + footer with dynamic CMS pages) | High |
| 1.8 | Share CMS nav/footer pages via `HandleInertiaRequests` middleware | High |

### Phase 2: Authentication & User System (Week 2–3)

| # | Task | Priority |
|---|------|----------|
| 2.1 | Install & configure Laravel Socialite | High |
| 2.2 | Implement `SocialAuthController` (Google, Facebook, Twitter/X) | High |
| 2.3 | Add social login buttons to Login.vue and Register.vue | High |
| 2.4 | Extend User model (role, provider, provider_id, is_seller_approved) | High |
| 2.5 | Implement seller application flow | High |
| 2.6 | Create `SellerProfile` model and migration | High |

### Phase 3: Product System (Week 3–5)

| # | Task | Priority |
|---|------|----------|
| 3.1 | Build product CRUD (backend controllers + form requests) | High |
| 3.2 | Build product create/edit form — General tab | High |
| 3.3 | Build product create/edit form — Data tab | High |
| 3.4 | Implement `RichTextEditor.vue` (Tiptap wrapper) | High |
| 3.5 | Implement `ImageUpload.vue` and `FileUpload.vue` | High |
| 3.6 | Implement `CoAuthorManager.vue` with email validation + percentage logic | High |
| 3.7 | Build product detail page with all tabs | High |
| 3.8 | Implement `TagInput.vue` component | Medium |
| 3.9 | Implement product status flow (draft → pending → published/rejected) | High |

### Phase 4: Public Website (Week 5–7)

| # | Task | Priority |
|---|------|----------|
| 4.1 | Revamp landing page with carousel, featured products, categories | High |
| 4.2 | Build product catalog/browse page with grid + filters | High |
| 4.3 | Build advanced search system (backend + frontend) | High |
| 4.4 | Build browse-by pages (faculty, department, institution, author, country, tag) | Medium |
| 4.5 | Build `SearchBar.vue` + `SearchAutocomplete.vue` | High |
| 4.6 | Build custom 404 page | Medium |
| 4.7 | Implement newsletter subscription (frontend + backend) | Medium |
| 4.8 | Build site navigation with dynamic CMS pages | High |
| 4.9 | Ensure full mobile responsiveness | High |

### Phase 5: User Dashboard (Week 7–8)

| # | Task | Priority |
|---|------|----------|
| 5.1 | Revamp dashboard overview with stats widgets | High |
| 5.2 | Build downloads history page | High |
| 5.3 | Build orders history page | High |
| 5.4 | Build messages inbox | Medium |
| 5.5 | Build explore products page | Medium |
| 5.6 | Build "Become a Seller" CTA + application page | High |
| 5.7 | Extend sidebar navigation for user + seller menus | High |

### Phase 6: Seller Dashboard (Week 8–10)

| # | Task | Priority |
|---|------|----------|
| 6.1 | Build seller overview page with revenue stats | High |
| 6.2 | Build seller profile edit page | High |
| 6.3 | Build seller product list (manage products) | High |
| 6.4 | Build seller orders page | High |
| 6.5 | Build transactions/revenue page | High |
| 6.6 | Build payout request system | High |
| 6.7 | Build payment history page | Medium |
| 6.8 | Build payment method selection | Medium |

### Phase 7: Cart, Orders & Payments (Week 10–12)

| # | Task | Priority |
|---|------|----------|
| 7.1 | Implement cart system (session + user-based) | High |
| 7.2 | Build cart page + cart icon in header | High |
| 7.3 | Build checkout flow | High |
| 7.4 | Integrate Stripe payment gateway | High |
| 7.5 | Integrate PayPal payment gateway | High |
| 7.6 | Integrate Paystack payment gateway | Medium |
| 7.7 | Integrate Flutterwave payment gateway | Medium |
| 7.8 | Implement manual bank transfer flow | Medium |

### Phase 8: Engagement & Monetization (Week 12–13)

| # | Task | Priority |
|---|------|----------|
| 8.1 | Implement like system (frontend + backend) | High |
| 8.2 | Implement review/rating system | High |
| 8.3 | Implement smart link integration for downloads | High |
| 8.4 | Implement revenue tracking (views + downloads) | High |
| 8.5 | Implement revenue distribution to co-authors | High |
| 8.6 | Implement anti-fraud measures (IP rate limiting) | High |
| 8.7 | Build messaging/messenger tab | Medium |

### Phase 9: Email Notifications (Week 13–14)

| # | Task | Priority |
|---|------|----------|
| 9.1 | Create all author notification classes | High |
| 9.2 | Create all admin notification classes | High |
| 9.3 | Implement milestone notification system (1, 10, 100, 1K, etc.) | Medium |
| 9.4 | Set up queue worker for email dispatch | High |
| 9.5 | Create email templates (branded with ProjectRim design) | Medium |

### Phase 10: Admin Dashboard — Blade (Week 14–17)

| # | Task | Priority |
|---|------|----------|
| 10.1 | Build admin Blade layout (sidebar, topbar, footer) | High |
| 10.2 | Build admin dashboard overview with all stats widgets | High |
| 10.3 | Build user management (list, detail, edit, stats) | High |
| 10.4 | Build product management (list, detail, approve/reject) | High |
| 10.5 | Build order management | High |
| 10.6 | Build seller application management | High |
| 10.7 | Build payout management (review, approve, pay, record) | High |
| 10.8 | Build CMS pages management (CRUD + position) | High |
| 10.9 | Build category management (faculties + departments) | Medium |
| 10.10 | Build review moderation | Medium |
| 10.11 | Build newsletter management (subscribers + campaigns) | Medium |
| 10.12 | Build all settings pages (general, monetization, payment, seller, carousel, payment methods) | High |
| 10.13 | Build analytics pages (product stats, user stats) | Medium |
| 10.14 | Build messages overview | Low |

### Phase 11: Testing & Polish (Week 17–18)

| # | Task | Priority |
|---|------|----------|
| 11.1 | Write Pest tests for auth flows (standard + social) | High |
| 11.2 | Write Pest tests for product CRUD + co-author logic | High |
| 11.3 | Write Pest tests for cart/order flow | High |
| 11.4 | Write Pest tests for revenue distribution | High |
| 11.5 | Write Pest tests for admin operations | Medium |
| 11.6 | Cross-browser testing (Chrome, Firefox, Safari, Edge) | High |
| 11.7 | Mobile responsive testing on real devices | High |
| 11.8 | Performance optimization (eager loading, caching, image optimization) | Medium |
| 11.9 | Security audit (OWASP top 10 review) | High |
| 11.10 | SEO setup (meta tags, sitemap, robots.txt) | Medium |

---

## Summary

| Metric | Count |
|--------|-------|
| Database Tables | ~25 |
| Laravel Models | ~20 |
| Vue Pages | ~35 |
| Vue Components | ~45 |
| Blade Views (Admin) | ~35 |
| Controllers | ~30 |
| Notifications | ~10 |
| Estimated Phases | 11 |

This plan covers every feature requested: multi-author collaboration with revenue sharing, advanced search, smart link monetization, cart & payment gateways, engagement features (likes/reviews/messaging), comprehensive admin dashboard with analytics and CMS, email notifications with milestone tracking, and a fully responsive public website with dynamic navigation.
