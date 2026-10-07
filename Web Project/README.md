# WareTrack - Enterprise Warehouse Inventory & Logistics Management System

> Complete dynamic warehouse management web application powered by **PHP and MySQL** (with alternative **Java Servlets** reference support and offline static demo mode).

---

## 🏛️ System Architecture & Navigation Tree

The application is architected around a unified **User / Logistics Officer** lifecycle connected to **MySQL**:

```
User
 │
 ├── Login (Role-based access: Admin, Manager, Operator)
 │
 ├── Dashboard (Live KPIs, Stock Health, Movement Feeds)
 │
 ├── Products
 │     ├── Add (Catalog item, auto-generate SKU, assign supplier)
 │     ├── Edit (Update specifications, prices, locations)
 │     ├── Delete (Safe removal with confirmation & audit log)
 │     └── Search (Multi-criteria category, status & debounce text search)
 │
 ├── Suppliers (Vendor directory, contacts, supply lines, status)
 │
 ├── Stock In (Inward consignment intake, PO reference, stock increment)
 │
 ├── Stock Out (Outward sales release, purpose tracking, negative stock prevention)
 │
 └── Reports (Movement ledger, category valuations, reorder alerts, CSV export, Print)
        │
        ▼
      MySQL (`waretrack_db`)
```

---

## 🌟 Highlights & Key Features

- **🔐 User Authentication (`login.php`, `logout.php`, `login.html`)**:
  - Secure PHP session-based authentication backed by MySQL `users` table.
  - Password hashing with fallback support and 1-click quick-fill demo roles:
    - **Admin Officer** (`admin` / `admin123`) - Chief Logistics
    - **Sarah Jenkins** (`manager` / `manager123`) - Warehouse Manager
    - **Rajesh Kumar** (`operator` / `operator123`) - Stock Controller
  - Persistent profile chip with dropdown and sign-out controls.

- **📊 Central Dashboard (`index.php`, `index.html`)**:
  - Real-time KPI telemetry: Total Active SKUs (`COUNT`), Total Stock Units (`SUM`), Low Stock Alerts, and Total Inventory Asset Value (₹ INR).
  - Rapid Operations Grid providing 1-click access to Stock In, Stock Out, Add Product, Suppliers, Reports, and Catalogue.
  - Live recent inventory health meters with instant restock triggers.
  - Recent Movements stream tracking incoming and outgoing shipments.

- **📦 Products Master Catalogue (`products.php`, `add-product.php`, `edit-product.php`)**:
  - **Add**: Automated SKU generator (`PRD-XXXX`), supplier assignment, price, min stock threshold, and warehouse location.
  - **Edit**: Dedicated item editor with live real-time preview card.
  - **Delete**: Item removal with database audit logging and modal confirmation.
  - **Search & Filters**: Real-time debounce search filter by name, SKU, bin location, and category.

- **🏢 Suppliers Directory (`suppliers.php`, `suppliers.html`, `api/suppliers.php`)**:
  - Vendor management directory with contact persons, email, phone, and physical addresses.
  - Partnership status tracking (`Active`, `Preferred`, `On Hold`).
  - Direct 1-click "Stock In" order trigger from any supplier card.
  - Modal form to register new vendors directly into MySQL `suppliers` table.

- **📥 Stock In - Inward Receiving (`stock-in.php`, `stock-in.html`, `api/stock-in.php`)**:
  - Inbound shipment intake logging: Product SKU, Origin Supplier, Quantity, Purchase Order #, Unit Cost, and Remarks.
  - Automatically increments MySQL `products.quantity` atomically and inserts a `STOCK_IN` record into `stock_transactions`.
  - Live Stock Impact Telemetry preview showing current vs projected stock and total consignment value.
  - Chronological Inward Ledger table.

- **📤 Stock Out - Outward Dispatch (`stock-out.php`, `stock-out.html`, `api/stock-out.php`)**:
  - Dispatch authorization for sales orders, internal transfers, scrap write-offs, samples, and returns.
  - **Strict Negative Stock Prevention**: Verifies requested units against current available warehouse stock. If requested quantity exceeds stock, dispatch is safely blocked.
  - Automatically decrements MySQL `products.quantity` and records `STOCK_OUT` in `stock_transactions`.
  - Automatic warning when stock falls below reorder threshold.

- **📈 Warehouse Reports & Audit (`reports.php`, `reports.html`, `api/export-report.php`)**:
  - Tab 1: **Stock Movement Ledger** - Complete chronologic transaction log of all IN and OUT movements with reference IDs, timestamps, and quantities.
  - Tab 2: **Category Valuations** - Breakdown by category showing item counts, unit totals, low stock count, asset value, and portfolio share.
  - Tab 3: **Critical Reorder Plan** - Identifies all items at or below minimum threshold, computes stock deficit, recommended replenishment quantity, and estimated restock budget.
  - Tab 4: **Supplier Performance** - Inward delivery counts, units delivered, and total procurement value per vendor.
  - **Export to CSV**: Direct stream from MySQL to Excel-compatible `.csv` format.
  - **Print View**: Printer-optimized styling for reports and auditing.

---

## 🗄️ MySQL Database Architecture (`database.sql`)

The database schema is provided in [`database.sql`](file:///Users/tajmalhussain/Web%20Project/database.sql) and [`sql/database.sql`](file:///Users/tajmalhussain/Web%20Project/sql/database.sql).

### 1. `users` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT PRIMARY KEY | User identifier |
| `username` | VARCHAR(50) | NOT NULL UNIQUE | Login username |
| `password_hash`| VARCHAR(255) | NOT NULL | Password hash |
| `full_name` | VARCHAR(100) | NOT NULL | Operator display name |
| `role` | VARCHAR(50) | NOT NULL | Job role / permissions |
| `email` | VARCHAR(150) | NOT NULL | Work email address |
| `avatar_initials`| VARCHAR(5)| DEFAULT 'AD' | Display initials avatar |

### 2. `suppliers` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | VARCHAR(50) | PRIMARY KEY | Unique vendor ID (e.g. `SUP-101`) |
| `name` | VARCHAR(255) | NOT NULL | Company name |
| `contact_name` | VARCHAR(100) | NOT NULL | Lead representative |
| `email` | VARCHAR(150) | NOT NULL | Procurement email |
| `phone` | VARCHAR(50) | NOT NULL | Telephone / Mobile |
| `category` | VARCHAR(100) | NOT NULL | Primary category of supply |
| `address` | VARCHAR(255) | NOT NULL | Office / warehouse location |
| `status` | ENUM | 'Active','Preferred','On Hold'| Partnership status |

### 3. `products` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | VARCHAR(50) | PRIMARY KEY | Unique SKU identifier (e.g. `PRD-1001`) |
| `name` | VARCHAR(255) | NOT NULL | Item name |
| `category` | VARCHAR(100) | NOT NULL | Category (Furniture, Electronics, Storage, etc.) |
| `quantity` | INT | NOT NULL DEFAULT 0 | Current warehouse stock level |
| `min_stock` | INT | NOT NULL DEFAULT 10 | Reorder threshold limit |
| `price` | DECIMAL(12,2)| NOT NULL DEFAULT 0.00| Unit price in INR (₹) |
| `location` | VARCHAR(150) | DEFAULT 'General Storage' | Warehouse aisle and bay bin |
| `icon` | VARCHAR(50) | DEFAULT '📦' | Display emoji / icon |
| `supplier_id` | VARCHAR(50) | NULL | Linked supplier reference (`suppliers.id`) |

### 4. `stock_transactions` Table (Ledger)
| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT PRIMARY KEY | Movement transaction ID |
| `transaction_type`| ENUM('IN','OUT')| NOT NULL | Inward intake vs Outward dispatch |
| `product_id` | VARCHAR(50) | NOT NULL | Target item SKU |
| `supplier_id`| VARCHAR(50) | NULL | Origin supplier (for Stock In) |
| `quantity` | INT | NOT NULL | Units moved (+ for IN, - for OUT) |
| `reference_no`| VARCHAR(100)| NOT NULL | Purchase Order / Sales Dispatch # |
| `reason` | VARCHAR(100) | NOT NULL | Movement justification |
| `unit_price` | DECIMAL(12,2)| NOT NULL | Transaction unit price |
| `recipient` | VARCHAR(150) | NULL | Customer / Department (for Stock Out)|
| `notes` | TEXT | NULL | Inspection remarks / carrier notes |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Transaction timestamp |

### 5. `activity_log` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT PRIMARY KEY | Audit ID |
| `product_id` | VARCHAR(50) | NULL | Related product SKU |
| `action` | VARCHAR(50) | NOT NULL | Action type (`LOGIN`, `CREATE`, `STOCK_IN`, `STOCK_OUT`, etc.) |
| `details` | TEXT | NOT NULL | Description of the action taken |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Audit log timestamp |

---

## 🚀 Quick Setup Instructions

### Option 1: Running with XAMPP / WAMP / MAMP (Recommended)

1. **Move files to web server root**:
   - Copy or symlink this folder into your web server's `htdocs` directory:
     ```bash
     # macOS XAMPP:
     /Applications/XAMPP/xamppfiles/htdocs/waretrack
     
     # Windows XAMPP:
     C:\xampp\htdocs\waretrack
     ```

2. **Start Apache & MySQL**:
   - Open **XAMPP Control Panel** and start both **Apache** and **MySQL**.

3. **Import the Database**:
   - Open your browser to `http://localhost/phpmyadmin/`.
   - Click the **Import** tab.
   - Choose [`database.sql`](file:///Users/tajmalhussain/Web%20Project/database.sql).
   - Click **Import**. The `waretrack_db` database, tables, and sample seed records will be created automatically!
   *(Note: The system also includes an automated seeder that initializes all 5 tables if MySQL starts empty!)*

4. **Access the Application**:
   - Visit: `http://localhost/waretrack/login.php` (or `http://localhost/waretrack/index.php`)
   - Default login: `admin` / `admin123`

---

### Option 2: Running with Built-in PHP Server

```bash
# 1. Import schema to MySQL
mysql -u root -p < database.sql

# 2. Launch the PHP development server inside the project folder
php -S localhost:8000

# 3. Open in your browser:
# http://localhost:8000/login.php
```

---

### Option 3: Static / Browser-Only Execution

For demonstration or offline preview without PHP/MySQL configured:
- Open [`login.html`](file:///Users/tajmalhussain/Web%20Project/login.html) or [`index.html`](file:///Users/tajmalhussain/Web%20Project/index.html) directly in any modern browser!
- All operations (Authentication, Stock In, Stock Out, Suppliers, Products, and Reports) operate dynamically using client-side `localStorage` data syncing.

---

## 📁 Complete File Structure

```
Web Project/
├── login.php                 # User authentication portal with demo login pills
├── logout.php                # Session termination handler
├── index.php                 # Central Dashboard with live telemetry & recent movements
├── products.php              # Products Catalogue with search, stock health & actions
├── add-product.php           # Catalog New Item with SKU generator & supplier linking
├── edit-product.php          # Update Item with prefilled fields & supplier dropdown
├── suppliers.php             # Supplier Directory with registration modal & partner cards
├── stock-in.php              # Inward Consignment intake with stock impact preview
├── stock-out.php             # Outward Dispatch with strict negative stock validation
├── reports.php               # Analytics Suite: Movements Ledger, Valuations, Reorder Plan
├── database.sql              # Complete MySQL schema & seed data (root copy)
├── config/
│   └── db.php                # PDO connection with auto-database creation & error handling
├── includes/
│   ├── header.php            # Global navigation, live DB status indicator, user badge
│   ├── footer.php            # Toast notifications container & script tags
│   └── functions.php         # Auth, Products, Suppliers, In/Out transactions & reporting queries
├── api/
│   ├── products.php          # REST API for products query and insertion
│   ├── suppliers.php         # REST API for suppliers directory CRUD
│   ├── stock-in.php          # REST API for inward stock reception
│   ├── stock-out.php         # REST API for outward stock dispatch
│   ├── restock.php           # REST API for quick restock increments
│   ├── delete.php            # REST API for item removal and audit log
│   ├── stats.php             # REST API for KPI statistics
│   ├── export-csv.php        # Dynamic CSV stream of inventory catalogue
│   └── export-report.php     # Dynamic CSV stream of transaction ledgers & reports
├── sql/
│   └── database.sql          # Dedicated SQL schema file (synchronized copy)
├── css/
│   └── style.css             # Glassmorphic Cyber Slate dark/light theme stylesheet
├── js/
│   └── script.js             # Real-time AJAX operations, DOM updates, theme switcher
├── login.html                # Static counterpart of login page
├── index.html                # Static counterpart of dashboard
├── products.html             # Static counterpart of catalogue
├── add-product.html          # Static counterpart of add product
├── suppliers.html            # Static counterpart of suppliers directory
├── stock-in.html             # Static counterpart of stock in
├── stock-out.html            # Static counterpart of stock out
├── reports.html              # Static counterpart of reports & ledger
├── servlets/                 # Java Servlets alternative implementation (Jakarta EE)
│   ├── pom.xml               # Maven configuration
│   └── src/                  # Model, Servlet, and DBConnection sources
└── README.md                 # Complete documentation
```

---

## 📡 REST API Reference

| Endpoint | Method | Payload / Params | Description |
|---|---|---|---|
| `api/products.php` | `GET` | `?category=...&search=...&status=...` | Returns JSON product array & statistics |
| `api/products.php` | `POST` | JSON / Form Body | Inserts a new product into MySQL |
| `api/suppliers.php` | `GET` | `?category=...&status=...&search=...` | Returns list of suppliers |
| `api/suppliers.php` | `POST` | JSON / Form Body | Registers a new supplier in MySQL |
| `api/suppliers.php` | `DELETE` | `?id=SUP-101` | Removes a supplier from MySQL |
| `api/stock-in.php` | `POST` | `{"product_id": "PRD-1001", "quantity": 25, "reference_no": "PO-901"}` | Increments stock and records IN transaction |
| `api/stock-out.php` | `POST` | `{"product_id": "PRD-1001", "quantity": 5, "reason": "Sales"}` | Decrements stock with validation & records OUT |
| `api/export-report.php` | `GET` | `?type=movements` \| `valuation` \| `low_stock` | Streams dynamic CSV report from MySQL |
| `api/restock.php` | `POST` | `{"id": "PRD-1001", "amount": 20}` | Increments product stock by `amount` |
| `api/delete.php` | `POST` / `DELETE` | `{"id": "PRD-1001"}` | Deletes item and logs audit entry |
| `api/stats.php` | `GET` | None | Returns latest KPI statistics |
