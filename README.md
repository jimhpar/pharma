# Adorzotno — E-Commerce & Pharmacy POS Stack

A full-stack enterprise Pharmacy Management & E-Commerce ecosystem comprising a modern Next.js customer storefront, a Laravel REST API backend, and a comprehensive Laravel POS & Inventory Management system.

---

## 🏛️ Architecture & Services

| Component | Technology | Directory | Default Port | Description |
| :--- | :--- | :--- | :--- | :--- |
| **Storefront** | Next.js 14, React, TailwindCSS, Redux | `adorzotno/` | `http://localhost:3000` | Customer-facing medicine & personal care shop |
| **POS & Admin** | Laravel 11, Blade, Bootstrap, DataTables | `adorzotno-pos/pos/` | `http://localhost:8001` | Pharmacy Point of Sale, multi-branch inventory & reports |
| **Storefront API** | Laravel 11 REST API, Sanctum | `adorzotno-pos/ecommerce-api/` | `http://localhost:8000` | High-performance catalog, cart, and order API |
| **Database** | MySQL 8.x | `database/` | `3306` (`adorzotno`) | Complete pre-seeded database with 6,003 products & stocks |

---

## 📋 Prerequisites / প্রয়োজনীয় সফটওয়্যার

Before running the setup, ensure your system has the following installed:
1. **PHP 8.2 or 8.3** (with `pdo_mysql`, `curl`, `mbstring`, `zip`, `fileinfo` extensions enabled)
2. **Composer 2.x**
3. **Node.js 18+ or 20+** & **npm**
4. **MySQL 8.x** (running on default port 3306)

> **💡 Tip for Windows Users:** You can use [Laragon](https://laragon.org/) (Full version) which includes PHP, Composer, Node.js, and MySQL out-of-the-box. Just start Laragon and MySQL!

---

## 🚀 Quick Start (Automated 1-Command Setup)

### English:
1. Clone this repository:
   ```powershell
   git clone https://github.com/jimhpar/pharma.git
   cd pharma
   ```
2. Run the automated setup script in PowerShell:
   ```powershell
   .\setup.ps1
   ```

### বাংলা নির্দেশনা:
১. রিপোজিটরিটি ক্লোন করে প্রোজেক্ট ফোল্ডারে PowerShell ওপেন করুন।
২. শুধুমাত্র নিচের কমান্ডটি দিয়ে এন্টার দিন:
   ```powershell
   .\setup.ps1
   ```

### What `setup.ps1` does automatically:
- Checks all prerequisites (PHP, Composer, Node, MySQL).
- Connects to MySQL, creates the database `adorzotno`, and imports the complete database dump (`database/adorzotno_complete.sql`) containing all 6,003 products, 4,000 seeded inventory stocks, batches, categories, and admin accounts without any data loss.
- Sets up `.env` files for `ecommerce-api` and `pos`, and `.env.local` for `adorzotno`.
- Runs `composer install` for both Laravel backends.
- Generates application encryption keys (`php artisan key:generate`).
- Runs `npm install` and builds assets (`npm run build`).

---

## ⚡ Running the Applications (1-Click Run)

### Option A: One-Click Runner (Recommended)
Simply **double-click `start.bat`** in the project folder, or run:

```powershell
.\run.ps1
```

This concurrently launches:
- **Ecommerce API** on `http://localhost:8000`
- **POS Admin** on `http://localhost:8001`
- **Next.js Storefront** on `http://localhost:3000`

And automatically opens `http://localhost:3000` in your default browser.

### Option B: Stopping the Applications
To stop all three running servers cleanly at any time, run:

```powershell
.\stop.ps1
```

---

## 🔑 Default Credentials & Access URLs

| Portal | URL | Credentials / Notes |
| :--- | :--- | :--- |
| **Storefront Website** | `http://localhost:3000` | Browse & purchase products directly |
| **POS / Admin Dashboard** | `http://localhost:8001` | **Email:** `admin@gmail.com`<br>**Password:** `12345678` |
| **API Server** | `http://localhost:8000` | REST API endpoints for storefront |

---

## 📦 Key Modules in POS & Admin

- **Available Stock Management** (`/available-stock/show`):
  - View real-time **Central Stock** (aggregated across all warehouses) and individual branch breakdowns (**Mirpur**, **Banani**, **Uttara**).
  - Search by Product Name, SKU ID / Code, or Barcode.
  - Track **Manufacture Dates** & **Expiry Dates** for each batch.
  - **Add, Edit, and Delete** stock batches with instant live reflection on the online storefront.
- **Point of Sale (POS)** (`/pos`):
  - Rapid barcode scanning, strip/medicine unit calculations, and receipt printing.
- **Inventory Documents**:
  - Purchase Orders, Opening Stock, Stock Issues, Adjustments, and Stock Transfers between branches.

---

## 📄 License
This project is proprietary software developed for Adorzotno Limited.
