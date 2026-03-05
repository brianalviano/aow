# AOW (Aowenak.com)

A professional Pre-Order (PO) food ordering system designed for streamlined delivery to designated **Drop Points**. AOW simplifies the lunch ordering process for schools, campuses, and offices by providing a robust platform where quality food meets systematic distribution.

## 🚀 Mission & Vision

To bridge the gap between quality culinary providers and busy environments by implementing a reliable H-1 pre-order system with centralized drop-off locations.

---

## ✨ Key Features

### 👤 Multi-Role Experience

- **Customers**: Browse menus, place pre-orders, manage addresses, and track real-time fulfillment via WhatsApp.
- **Chefs**: Dedicated dashboard to manage incoming orders, track daily production, and handle shipping handovers.
- **Admin**: Full control over products, categories, drop points, user management, and comprehensive financial reporting.

### 🍱 Pre-Order (PO) System

- **H-1 Planning**: Orders must be placed at least one day in advance, allowing chefs for better preparation and zero-waste management.
- **Drop Point Focused**: Optimized delivery to specific locations like schools, campuses, and offices.
- **Instant vs PO**: Flexibility to handle both minimum quota pre-orders and instant delivery via third-party couriers.

### 💰 Integrated Payments & Shipping

- **Midtrans**: Secure and diverse payment methods (QRIS, Bank Transfer, etc.).
- **Biteship**: Real-time shipping tracking and automated courier coordination.
- **Fonnte**: Automated order status updates sent directly to your WhatsApp.

---

## 🛠 Technology Stack

- **Backend**: [Laravel 12](https://laravel.com/) (PHP 8.2+)
- **Frontend**: [Svelte 5](https://svelte.dev/) with [Inertia.js](https://inertiajs.com/)
- **Styling**: [Tailwind CSS 4](https://tailwindcss.com/)
- **Database**: [PostgreSQL](https://www.postgresql.org/)
- **State Management**: Svelte Stores
- **Charting**: [Chart.js](https://www.chartjs.org/)
- **Reporting**: [DomPDF](https://github.com/barryvdh/laravel-dompdf) & Excel via Maatwebsite
- **API Integrations**: TomTom Maps, Midtrans, Biteship, Fonnte

---

## ⚙️ Installation & Setup

### Prerequisites

- PHP 8.2+
- Composer
- Bun (JS Runtime)
- PostgreSQL

### Step-by-Step Guide

1. **Clone the Repository**

    ```bash
    git clone <repository-url>
    cd aowenak.com
    ```

2. **Install PHP Dependencies**

    ```bash
    composer install
    ```

3. **Install JS Dependencies**

    ```bash
    bun install
    ```

4. **Environment Configuration**

    ```bash
    cp .env.example .env
    # Update your DB_DATABASE, DB_USERNAME, DB_PASSWORD
    # Configure MIDTRANS_DEV_ID and BITESHIP_DEV_KEY
    ```

5. **Generate App Key**

    ```bash
    php artisan key:generate
    ```

6. **Run Migrations & Seeders**

    ```bash
    php artisan migrate --seed
    ```

7. **Development Server**
   Start both the Laravel server and Vite for frontend HMR:
    ```bash
    bun run dev
    ```

---

## 🔒 Governance & Privacy

We value user data and security. The platform is built with:

- **Privacy Policy**: Transparent data collection focused on delivery fulfillment.
- **Terms & Conditions**: Clear rules regarding H-1 order cut-offs and drop point picks.
- Support for **CSRF protection**, **Inertia security headers**, and **Enforced Eloquent Strict Mode**.

---

## 📄 License

This project is proprietary and all rights are reserved.
