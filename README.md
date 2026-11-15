# VenueBook: Multi-Destination Venue Booking & Scheduling Platform

> Enterprise scheduling and venue monitoring platform built for PT Taman Wisata Candi (TWC / InJourney) to coordinate event venue rentals across Candi Prambanan, Keraton Ratu Boko, and Candi Borobudur without scheduling collisions.

- **Live Demo**: [https://venue.andiportofolio.my.id](https://venue.andiportofolio.my.id)
- **Author**: Andi Ziyadh Ghafa Ramadhan ([Portfolio](https://andiportofolio.my.id) · [LinkedIn](https://linkedin.com/in/andighafa))
- **Demo Credentials**:
  - **Sales**: `sales.prambanan@venuetracker.test` / `password` (Scoped to Prambanan venues)

---

## Why I Built This

Before this platform, venue reservations across destination complexes (such as Lapangan Garuda for music festivals or Trimurti Open Stage for cultural performances) were coordinated through detached spreadsheets and chat threads.

This caused frequent field problems:
1. **Double-Booking Collisions**: Multiple sales teams confirming overlapping slots for the same venue without shared real-time status.
2. **Ignored Setup / Breakdown Windows**: Event organizers only registered the main show hours, ignoring the 1 to 2 days required by sound, stage, and lighting vendors for setup (*load-in*) and teardown (*tear-down*).
3. **Approval Bottlenecks**: No clear pipeline (`hold` → `tentative` → `fix` / `cancelled`) or audit history for unit management.

I built this system to centralize booking availability, automate multi-phase conflict validation, handle tiered seating allocations, and restrict access based on destination units.

---

## Core Architecture

### 1. Multi-Phase Conflict Detection
Reservations reserve the complete operational footprint, not just event showtimes:

```text
[ Load-in / Setup ] ──► [ Event Hours ] ──► [ Tear-down / Cleanup ]
|◄──────────────── Total Operational Reservation Window ─────────────►|
```

Before saving or updating a reservation, the system validates time overlaps atomically:
```sql
(new_load_in_start < existing_tear_down_end) AND (new_tear_down_end > existing_load_in_start)
```
If an active booking overlaps, the transaction rolls back and returns the conflicting event title and assigned PIC.

### 2. Ramayana Ballet Seating Grid
For performance auditoriums (Prambanan Open Stage and Trimurti Indoor Theater):
- Tiered layout: **VIP**, **Special**, **Class 1**, and **Class 2**.
- Row and column coordinates enforced with a composite unique key on `(booking_id, seat_id)` to prevent duplicate seat allocations for the same show.

### 3. Destination-Scoped RBAC
- **Sales Staff**: Restricted to their designated destination (e.g. Prambanan sales only manage bookings at Prambanan).
- **Unit Admin**: Cross-destination overview to review, approve, reassign PICs, or adjust venue schedules.
- **Superadmin**: Global management, user credential control, master venue configurations, and audit exports.

### 4. Reporting & Live Status
- Filtered exports (Excel/CSV) segmented by date ranges and destination.
- Notification ledger (`NotificationRead` model) to track state changes and approvals per user.

---

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3), Eloquent ORM, Seeders, Form Requests
- **Frontend**: Blade Components, Tailwind CSS v4, Vite, Flatpickr
- **Database**: MySQL 8.0 (indexed date ranges, composite constraints)
- **Infrastructure**: Ubuntu Linux VPS, Nginx, PHP-FPM 8.3, Let's Encrypt SSL

---

## Local Development

### Requirements
- PHP 8.3+
- Composer
- Node.js (v18+) & npm
- MySQL 8.0+

### Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/andiZghafa/VenueBook.git
   cd VenueBook
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Update `.env` with your database credentials:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=venue_tracker
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_password
   ```

4. **Run migrations and seed demo data**:
   ```bash
   php artisan migrate --seed
   ```

5. **Build assets and start development server**:
   ```bash
   npm run build
   php artisan serve
   ```
   Open `http://localhost:8000` and sign in with `admin@venuetracker.test` / `password`.
