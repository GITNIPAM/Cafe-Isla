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
2. Migrate and seed all 16 normalized domain tables:
   ```powershell
   C:\xampp\php\php.exe artisan migrate:fresh --seed
   ```
3. Launch the local server:
   ```powershell
   C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
   ```
4. Run automated feature tests:
   ```powershell
   C:\xampp\php\php.exe artisan test
   ```

---

## Pre-Seeded RBAC Demo Accounts (`Password: Isla2026!`)
| Role | Email | Capabilities |
|---|---|---|
| **Customer** | `customer@cafeisla.ph` | Persistent bag, `ISLA10` / `FREESIP` coupons, checkout, tracking, email inbox, cancel/return requests |
| **Admin** | `admin@cafeisla.ph` | Full CRUD + product image uploads, analytics, order dispatch, return restock approvals, RBAC management |
| **Staff** | `barista@cafeisla.ph` | Order confirmation/prep, product & batch stock updates, return processing |
| **Courier** | `rider@cafeisla.ph` | Fulfillment-only dispatch view (`In Transit` -> `Delivered`) |

---

## Presentation & Documentation Routes
- `/` — Editorial storefront & interactive **Pour by Island Series** bar
- `/menu` — Searchable, multi-facet filterable catalog (`island`, `category`, `type`, `serve_temp`, `sort`)
- `/diagrams` — Interactive 16-table **Crow's Foot ERD**, 5-actor/7-stage **UML Use Case Diagram**, and **Brand & Logo Defense Notes**
- `/admin` — Role-scoped Operations Console
