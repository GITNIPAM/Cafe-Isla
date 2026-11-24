# Café Isla

> *Slow sips from the archipelago.*

Full-stack specialty Philippine island café and coastal lifestyle e-commerce platform built with **Laravel 12**, **Blade**, **Vanilla CSS/JS**, and **MySQL** (`cafe_isla_db`) for the **Philippine Christian University — College of Informatics Final Project**. Evolved from the team's original coastal apparel storefront (**Suot-Isla**) into a four-shore drink menu and curated coastal merch experience.

---

## Featured Island Chapters & Catalog (25 Products)
- **La Union Series (`16.6631° N, 120.3208° E`)**: Urbiztondo Morning Matcha, Monaliza Caramel Matcha, Carille Coco Matcha, Tangadan Berry Matcha, San Juan Spanish Hojicha.
- **Siargao Series (`9.8482° N, 126.0458° E`)**: Cloud 9 Sea Salt Latte, Kalamansi Espresso Tonic, Catangnan Coco Cold Brew, Tablea Cacao Mocha.
- **Boracay Series (`11.9674° N, 121.9248° E`)**: Halaya Dirty Ube Latte, Pandan Buko Cloud, Golden Hour Mango Fizz, Barako Tres Leches Latte.
- **El Nido Series (`11.1956° N, 119.4180° E`)**: Big Lagoon Butterfly Pea Limonada, Nacpan Hibiscus Ginger Cooler, Palawan Golden Turmeric Latte, Miniloc Lychee Rose White Tea.
- **Isla Merch Capsule (8 Suot-Isla Goods)**: Corduroy Café Cap, Salt-Washed Oversized Tee, San Juan Camp-Collar Shirt, Woven Buri & Pandan Tote, Golden Hour Linen Polo, Waffle-Weave Travel Towel, Nacpan Round Woven Throw, Reef-Walker Dry Bag 15L.

---

## Local Setup (XAMPP PHP 8.2+ & MySQL)

1. Start **Apache** and **MySQL** in XAMPP Control Panel (`cafe_isla_db` created on `127.0.0.1:3306`, user `root`, empty password).
2. From a new checkout, run `composer install`, copy `.env.example` to `.env`, and run `C:\xampp\php\php.exe artisan key:generate`. Set `APP_URL` to the address used to open the site so password reset links reach the correct host.
3. Apply migrations to an existing database with `C:\xampp\php\php.exe artisan migrate`. For a new, empty demo database, migrate and seed with:
   ```powershell
   C:\xampp\php\php.exe artisan migrate --seed
   ```
4. Launch the local server:
   ```powershell
   C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
   ```
5. Run automated feature tests:
   ```powershell
   C:\xampp\php\php.exe artisan test
   ```

The GitHub **Café Isla checks** workflow runs on PRs to `main` and pushes to `main`. To require it before a merge, add both `Laravel tests` checks to the `main` branch ruleset in GitHub.

### Demo checkout and customer help

- `CAFE_ISLA_DEMO_MODE=true` enables demo credentials, seed data, and the diagrams. Use a separate empty database for a public deployment and keep demo mode off there.
- `CAFE_ISLA_LOCAL_DELIVERY_AREAS` is a semicolon-separated list of city and province pairs. The class demo covers `Dasmariñas, Cavite;San Juan, La Union`.
- Checkout offers simulated GCash payments and Cash on Delivery. Refunds are simulated. `MAIL_MAILER=log` writes mail, including reset links, to the local Laravel log. Use sample addresses and account details during the defense.
- Newsletter signups record explicit consent. Confirm the contact mailbox, customer policies, live payments, and email delivery before accepting real orders or sending campaigns.
- Catalog images are stored in `public/uploads/products` by default. If PHP cannot write there on Windows, set `CAFE_ISLA_UPLOAD_DIR` to an absolute writable directory. Existing catalog image URLs stay the same.

---

## Pre-Seeded RBAC Demo Accounts (`Password: Isla2026!`)
| Role | Email | Capabilities |
|---|---|---|
| **Customer** | `customer@cafeisla.ph` | Persistent bag, `ISLA10` / `FREESIP` coupons, checkout, tracking, email inbox, cancel/return requests |
| **Admin** | `admin@cafeisla.ph` | Full CRUD + product image uploads, analytics, order dispatch, return restock approvals, RBAC management |
| **Courier** | `rider@cafeisla.ph` | Fulfillment-only dispatch view (`In Transit` -> `Delivered`) |

---

## Presentation & Documentation Routes
- `/` — Editorial storefront & interactive **Pour by Island Series** bar
- `/menu` — Searchable, multi-facet filterable catalog (`island`, `category`, `type`, `serve_temp`, `sort`)
- `/diagrams` — Interactive 16-table **Crow's Foot ERD**, 5-actor/7-stage **UML Use Case Diagram**, and **Brand & Logo Defense Notes**
- `/admin` — Role-scoped Operations Console
