#### Backend Technologies
1. **Programming Language**  
   - PHP: Serves as the core server-side language, handling business logic, database interactions, and request processing.

2. **Database Integration**  
   - MySQL: Connected and managed via PDO (PHP Data Objects) with prepared statements to prevent SQL injection.
   - Centralized database configuration in `config.php` (host, username, password, charset).

3. **Encryption & Security**  
   - SRP6 Protocol: Implements secure password storage using GMP extension for big integer operations, generating salts and verifiers.
   - SHA1 Hashing: Used in the SRP6 implementation for password processing.
   - Cloudflare Turnstile: Integrates human verification through API calls to prevent automated registrations.
   - Input Validation: Utilizes PHP `filter` extension and regular expressions for data sanitization.

4. **Core Functions**  
   - Database connection management via `getDbConnection()`.
   - Real-time validation (username/email uniqueness) through AJAX handlers in `handleAjaxRequest()`.
   - Account creation workflow in `createAccount()`, including SRP6 calculations and database insertion.


#### Frontend Technologies
1. **Basic Stack**  
   - HTML5: Structures the registration form, status displays, and information sections.
   - CSS3: Implements custom World of Warcraft-themed styling with variables (e.g., `--wow-gold`), responsive layouts, and animations.
   - JavaScript: Handles form validation, AJAX requests with debouncing, clipboard operations, and dynamic UI feedback.

2. **External Resources**  
   - Bootstrap 5.3.0: Provides responsive grid systems and UI components.
   - Font Awesome 4: Supplies icons for buttons, status indicators, and interface elements.
   - Cloudflare Turnstile API: Loads verification widgets for bot protection.


#### Multilingual Support
- **Implementation**: Bilingual content (Chinese/English) stored in `config.php` under the `text` array.
- **Switching Mechanism**: Controlled via URL parameter (`lang=zh` or `lang=en`) with persistence of other query parameters.
- **Dynamic Rendering**: Text content is dynamically loaded using the `$text` variable based on the selected language.


#### System Architecture
- **Modular Design**:
  - `core.php`: Encapsulates core functionalities (database, encryption, validation).
  - `config.php`: Centralizes all configurable parameters (database, language, server info).
  - `index.php`: Handles frontend rendering and user interactions.
- **Dependency Management**: Mandatory PHP extensions include `pdo_mysql`, `openssl`, `json`, `gmp`, and `filter`, with pre-flight checks in `checkPhpExtensions()`.


#### Key Features
- **Security**: Secure password storage (SRP6), bot protection (Turnstile), and input sanitization.
- **User Experience**: Real-time validation, responsive design, and interactive feedback (e.g., copy confirmation).
- **Maintainability**: Centralized configuration and modular code structure for easy updates.