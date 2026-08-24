# Leezofood NG.Export Project Roadmap

This document provides a consolidated roadmap based on the backend integration requirements for Procurement, Shipment Tracking, and General System Architecture.

## 1. Architecture & Core Responsibilities
*   **Backend as Single Source of Truth:** The backend will handle users, authentication, procurements, shipments, quote requests, and data ownership.
*   **Frontend Role:** The existing frontend design will remain the visual foundation, transitioning from static/sample data to dynamic data via API integration.
*   **Security & Authorization:** The backend must strictly enforce ownership, ensuring users can only access their own records (procurements, shipments). Authorized administrators will manage records globally.

## 2. Implementation Priorities

### Priority 1: Authentication
*   User registration, login, logout, and password reset.
*   Session/Token management and protected routes/APIs.

### Priority 2: User/Account Management
*   User profiles and dashboard access.
*   Establishing user data ownership.

### Priority 3: Procurement System
*   **Creating Procurements:** Users initiate new procurement requests from the **Homepage**, not the Dashboard.
*   **Viewing Procurements:** Logged-in users access their existing "Procurement History" and detailed views exclusively through the **Dashboard**.
*   **Backend Logic:** Generate unique Procurement IDs, associate requests with authenticated users, and manage statuses.

### Priority 4: Shipment Tracking System
*   **Shipment Creation (Admin Only):** Administrators create shipments, generate Tracking IDs (e.g., CAR8739020892), and assign them to users. Frontend does not generate official shipments.
*   **Public Tracking:** A public API endpoint to allow anyone to enter a Tracking ID on the homepage and view the current shipment details and progress timeline.
*   **User Dashboard:** Logged-in users can view their shipment history, detailed tracking information, and statistics (Total, Delivered, Pending, In Transit).
*   **Admin Management:** Update statuses, current locations, expected/actual delivery dates, and tracking history events.

### Priority 5: Quote System
*   Quote submission and quote management, including admin access.

### Priority 6: Additional Services
*   Newsletter integration, contact forms, and future third-party integrations.

## 3. Key Workflow Summaries

### Procurement Workflow
*   **New Request:** Homepage -> Procurement Form -> Submit -> Backend Saves & Generates ID -> Appears in History.
*   **View Existing:** Dashboard -> View My Procurement -> Procurement History -> View Details.

### Shipment Tracking Workflow
*   **Public Track:** Homepage -> Track Shipment -> Enter ID -> Backend API Returns Details.
*   **User Track:** Dashboard -> View My Shipments -> View History & Statistics -> Search/View Details.
*   **Admin Management:** Admin Dashboard -> Create Shipment -> Generate ID & Assign -> Update Status/Location.
