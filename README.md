# Squeaky Clean Washing Bay Management System

**Squeaky Clean Management System** is a secure, internal web-based application built to automate daily operations, eliminate revenue leakage, and optimize workflow efficiency for a local commercial vehicle cleaning facility handling saloon cars, SUVs, and commercial vans.

---

## Key Features & Roles

* **Role-Based Access Control (RBAC):** Strict boundaries separating **Attendant** operational workflows from **Manager** administrative privileges.
* **Attendant Desk (`attendant.php`):** 
  * Vehicle check-in logging (license plate normalization and vehicle type selection)[cite: 74, 75].
  * Real-time bay allocation and staff/washer assignment with busy-status validation[cite: 78].
  * Dynamic billing calculations mapped across Sedan, SUV, and Van pricing tiers[cite: 76].
  * Payment tracking (handling cash and mobile money integrations) and digital receipt generation.
* **Manager Console (`manager.php`):**
  * Real-time operational pulse dashboard tracking daily and weekly revenue summaries[cite: 70].
  * Comprehensive revenue reporting with daily, weekly, and monthly filtering and PDF/CSV export options[cite: 70, 83].
  * Attendant account management (creating, resetting passwords, or deactivating accounts)[cite: 71, 83].
  * Service pricing adjustments across vehicle tiers[cite: 71].
  * High-value machinery/equipment status tracking (toggling between Active and Under Repair)[cite: 72].
  * Staff productivity and washer performance auditing[cite: 72].

---

## Tech Stack & Architecture

* **Backend Logic:** PHP (structured around clean modular controller patterns and explicit database interaction layers).
* **Database:** Oracle MySQL relational database (`car_washing_bay_db`) consisting of normalized tables for users, washers, bays, services, vehicles, transactions, and machinery[cite: 59, 61, 73].
* **Frontend:** HTML5, CSS3, Bootstrap framework for a responsive dashboard layout, and vanilla JavaScript[cite: 73].
* **Local Development Environment:** Powered by **Laravel Herd** and PHP runtime hosting, utilizing Nginx for optimized local execution[cite: 73].
* **Security & Reliability:** 
  * Password encryption and secure hashing.
  * Robust form validation and concurrency checks to prevent double-booking of bays or washers.
  * Transactional integrity designed to withstand unexpected local desktop power interruptions.
