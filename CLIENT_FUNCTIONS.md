# Client-Side Architecture & Functions

This document provides a technical overview of the client-side logic, utility functions, and architectural patterns used in the **SmartQ** system.

---

## 1. Core Component Loader
The system uses a custom-built, lightweight component loader to manage UI modularity without the overhead of a full framework like React or Vue.

**File:** [component-loader.js](file:///c:/xampp/htdocs/SmartQ/client/scripts/component-loader.js)

### How it Works
1. Place a placeholder `<div>` with `data-component` in any PHP page.
2. The loader fetches the corresponding PHP partial from `client/components/`.
3. Props can be passed via `data-props` (JSON), which are converted to `$_GET` parameters for the PHP partial.

### API Reference
*   `SmartQ.scan()`: Re-scans the DOM for uninitialized components. Call this after dynamically injecting HTML.
*   `SmartQ.load($el)`: Manually reloads a specific component element.
*   `SmartQ.onLoad(name, callback)`: Registers a hook that fires after a specific component is rendered.
    ```javascript
    SmartQ.onLoad('sidebar', function($el) {
        console.log('Sidebar is ready!');
    });
    ```

---

## 2. Data Visualization (Charts)
SmartQ utilizes [Chart.js](https://www.chartjs.org/) for real-time analytics. Reusable factory functions are abstracted into a centralized utility.

**File:** [chart-widgets.js](file:///c:/xampp/htdocs/SmartQ/client/scripts/chart-widgets.js)

### Factory Functions
*   `SmartQ.charts.createBarChart(canvasId, labels, data)`
    *   **Purpose:** Visualizes student distribution across colleges.
    *   **Features:** Staggered rising animations, college-branded color mapping.
*   `SmartQ.charts.createDoughnutChart(canvasId, labels, data)`
    *   **Purpose:** Displays validation status percentages.
    *   **Features:** Custom "Center Text" plugin showing real-time completion rates; interactive segments that update center labels on click.

---

## 3. Global UI Interactivity
Common UI behaviors are handled through global event delegation to ensure compatibility with dynamically loaded components.

### Sidebar Management
*   **Toggle:** Handles mobile view expansion/retraction via the `#sidebar-toggle` ID.
*   **Auto-Close:** Closes the sidebar when a user clicks outside the menu area on mobile devices.

### Navigation Effects
*   **Navbar Scroll:** The landing page navbar (`#navbar`) adds a `.scrolled` class when the user scrolls past 50px, enabling background blur and shadow effects.
*   **Smooth Scroll:** Implemented for all internal anchor links (`a[href^="#"]`) for a premium navigation experience.

---

## 4. Common Component Logic
Several UI components contain internal logic to handle user state and specific features.

### Topbar Component
*   **Dynamic Identity:** Automatically determines user role (Super Admin vs. Student) based on session and URL path.
*   **Global Search:** Implements a client-side fuzzy search across system services for administrators.
*   **Avatar Upload:** Handles asynchronous profile picture updates using `FormData` and jQuery AJAX, instantly refreshing all avatar instances on the page.

### Toast Notification System
Located in `client/index.php` and `client/pages/login.php`.
*   `showToast(title, message, type)`: Spawns a floating notification (Success/Error) with smooth CSS transitions and auto-dismissal.

---

## 5. Security & Auth Handlers
Client-side integration with external security providers.

### Google OAuth
*   **Credential Handling:** Uses the Google Identity Services library (`gsi/client`).
*   `handleCredentialResponse(response)`: Captured the JWT from Google and forwards it to the backend `google_handler.php` for session creation.

### Google reCAPTCHA v2
*   **Lazy Loading:** The reCAPTCHA widget is hidden by default and only becomes visible (`.recaptcha-outer.visible`) once a user begins typing their password, reducing initial page weight and friction.

---

## 6. CSS Design System
The application follows a custom CSS architecture defined in:
*   `main.css`: Core tokens (colors, typography, shadows).
*   `components.css`: Layout system and reusable UI patterns (cards, badges, buttons).
*   `landing.css`: Specific high-fidelity styles for the student-facing roadmap and hero sections.
