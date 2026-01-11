# HelpDesk - Support Ticket Management System

A Laravel-based help desk ticketing system with intelligent ticket suggestion capabilities. This application allows employees to create support tickets and agents to manage, assign, and resolve them efficiently.

## 🚀 Features

- **Role-Based Access Control**: Separate permissions for employees and agents
- **Complete Ticket Lifecycle**: Open → In Progress → Resolved → Closed
- **File Attachments**: Upload and manage multiple attachments per ticket
- **Advanced Filtering**: Search and filter tickets by status, assignment, and keywords
- **Smart Ticket Suggestions** (Lane 1): similar ticket detection to reduce duplicates
- **Secure Authorization**: Policy-based access control throughout the application

---

## 📋 Setup Instructions

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL/PostgreSQL

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yash9373/HelpDesk.git
   cd HelpDesk
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   
   The application uses SQLite by default. For MySQL/PostgreSQL, update `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=helpdesk
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Create storage symlink**
   ```bash
   php artisan storage:link
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

10. **Access the application**
    
    Open your browser and navigate to: `http://localhost:8000`

---

## 🗄️ Database Migrations & Seeders

### Running Migrations

To create all database tables:
```bash
php artisan migrate
```

To reset and re-run all migrations:
```bash
php artisan migrate:fresh
```

To reset and re-run with seeders:
```bash
php artisan migrate:fresh --seed
```

### Database Schema

The application creates the following main tables:

- **users**: User accounts with roles (employee/agent)
- **tickets**: Support tickets with status tracking
- **ticket_attachments**: File attachments linked to tickets
- **sessions**: User session management
- **cache**: Application cache storage

### Seeders

The `DatabaseSeeder` creates demo accounts for testing:

```bash
php artisan db:seed
```

This will create:
- 1 employee account
- 1 agent account
- Sample tickets for demonstration

---

## 🔐 Demo Credentials

### Employee Account
- **Email**: `employee@example.com`
- **Password**: `password`
- **Permissions**: Create tickets, view own tickets, edit own open tickets

### Agent Account
- **Email**: `agent@example.com`
- **Password**: `password`
- **Permissions**: View all tickets, claim/assign tickets, resolve and close tickets

---

## 🎯 Lane 1 Implementation: Similar Ticket Suggestions

### Overview

The Similar Ticket Suggestion feature (Lane 1) helps reduce duplicate tickets by suggesting existing similar tickets as users type their ticket subject. This is implemented using a **hybrid token-based similarity algorithm** that balances accuracy with performance.

### How It Works

#### 1. **User Experience Flow**

```
User types ticket subject
    ↓
Client debounces input (250ms)
    ↓
AJAX call to /tickets/suggestions
    ↓
Backend processes and scores candidates
    ↓
Top 5 suggestions returned
    ↓
Displayed to user in real-time
```

#### 2. **Backend Architecture**

**Components:**
- `SuggestionController`: Handles HTTP requests
- `SuggestionService`: Core business logic for scoring
- `EloquentTicketRepository`: Data access with smart shortlisting
- `TextNormalizer`: Text preprocessing utility
- `TicketPolicy`: Authorization enforcement

#### 3. **Algorithm Details**

The suggestion algorithm uses a **three-factor scoring system**:

##### **Factor 1: Token Overlap (50% weight)**
- Tokenizes input and candidate text (subject + description)
- Calculates Jaccard similarity: `intersection / union`
- Normalizes text (lowercase, removes punctuation, splits on whitespace)

```php
// Example
Input: "laptop won't boot"
Tokens: ["laptop", "won't", "boot"]

Candidate: "Laptop not booting up"
Tokens: ["laptop", "not", "booting", "up"]

Overlap: ["laptop"] → 1/5 = 0.20
```

##### **Factor 2: Subject Similarity (30% weight)**
- Uses PHP's `similar_text()` function
- Compares full subject strings (not tokenized)
- Provides percentage similarity (0-100%)

```php
// Example
Input: "laptop won't boot"
Candidate: "laptop won't start"

Similarity: ~75%
```

##### **Factor 3: Recency Score (20% weight)**
- Prioritizes recent tickets
- Uses decay function: `1 / (1 + days_old / 30)`
- Recent tickets get higher scores

```php
// Example
Ticket created 5 days ago: 1 / (1 + 5/30) = 0.857
Ticket created 60 days ago: 1 / (1 + 60/30) = 0.333
```

##### **Final Score Calculation**

```php
final_score = (0.5 × token_overlap) + (0.3 × subject_similarity) + (0.2 × recency)
```

Only suggestions with `final_score >= 0.15` are returned.

#### 4. **Performance Optimizations**

To prevent full database scans, the system uses a **shortlist mechanism**:

**Shortlist Filters:**
1. ❌ Exclude closed tickets (no point suggesting resolved issues)
2. ✅ Prefer same category (if provided)
3. 📅 Limit to last 365 days (configurable)
4. 🔍 Pre-filter by token presence in subject/description
5. 🎯 Cap at 200 candidates maximum

**Query Example:**
```sql
SELECT * FROM tickets
WHERE status != 'closed'
  AND category = 'hardware'  -- if category provided
  AND created_at >= NOW() - INTERVAL 365 DAY
  AND (
    LOWER(subject) LIKE '%laptop%' OR
    LOWER(description) LIKE '%laptop%'
  )
ORDER BY created_at DESC
LIMIT 200
```

#### 5. **Authorization & Privacy**

**Critical Security Feature:** Suggestions respect visibility rules.

- **Employees**: Only see their own tickets in suggestions
- **Agents**: See all tickets they have permission to view

This is enforced using Laravel's Gate system:

```php
$candidates = $candidates->filter(
    fn($ticket) => Gate::forUser($user)->allows('view', $ticket)
)->values();
```

**Example:**
- User A creates ticket: "Laptop won't boot"
- User B types: "laptop not starting"
- User B will **NOT** see User A's ticket in suggestions
- Agent C will see **BOTH** tickets

#### 6. **API Endpoint**

**Endpoint:** `GET /tickets/suggestions`

**Parameters:**
- `subject` (required): Partial ticket subject
- `category` (optional): Ticket category for better filtering

**Response Example:**
```json
[
  {
    "ticket": {
      "id": 42,
      "subject": "Laptop won't boot - black screen",
      "description": "My laptop shows a black screen on startup...",
      "category": "hardware",
      "severity": 4
    },
    "snippet": "My laptop shows a black screen on startup. I've tried restarting multiple times but nothing works. The power light is on...",
    "score": 0.756
  },
  {
    "ticket": {
      "id": 38,
      "subject": "Computer not starting",
      "description": "Desktop computer won't turn on...",
      "category": "hardware",
      "severity": 3
    },
    "snippet": "Desktop computer won't turn on. Checked power cable and it's plugged in properly...",
    "score": 0.432
  }
]
```

#### 7. **Frontend Integration**

The create ticket form includes JavaScript that:
- Debounces input (250ms delay)
- Makes AJAX requests to the suggestions endpoint
- Displays results in a dropdown
- Allows users to view suggested tickets
- Non-intrusive: users can ignore suggestions

---

## 🔄 Trade-offs & Limitations

### Design Trade-offs

#### ✅ **Chosen Approach: Token-Based Matching**

**Pros:**
- Fast and efficient (no external API calls)
- Works offline/on-premises
- Predictable performance
- No API costs
- Privacy-friendly (data stays local)

**Cons:**
- Not semantic (doesn't understand meaning)
- May miss conceptually similar tickets with different wording
- Requires tuning threshold for optimal results

#### ❌ **Not Chosen: AI/RAG (Lane 2)**

**Why not implemented:**
- Requires external AI service (OpenAI, etc.) or local LLM
- Higher latency (API calls)
- Cost considerations
- Complexity in setup and maintenance
- Privacy concerns with external APIs

**Future Enhancement:** Could implement Lane 2 as an optional feature for organizations willing to use AI services.

### Current Limitations

1. **Language Support**: Only works well with English text
   - Non-English tickets may have poor matching
   - No stemming or lemmatization

2. **Threshold Tuning**: Fixed threshold (0.15) may need adjustment
   - Too low: noisy suggestions
   - Too high: miss relevant tickets
   - Should be configurable per deployment

3. **Category Dependency**: Works best when users select categories
   - Cross-category matching is limited
   - Some issues span multiple categories

4. **No Learning**: Algorithm doesn't improve over time
   - No feedback loop from user actions
   - No A/B testing of different weights

5. **Performance at Scale**: Shortlist mechanism helps but:
   - Very large databases (100k+ tickets) may need optimization
   - Consider adding full-text search indexes
   - May need caching for frequently searched terms

6. **Registration Security**: ⚠️ **Important Note**
   - Current registration form allows selecting "agent" role
   - This is convenient for local testing/demo
   - **Production deployment should restrict agent creation to admins**
   - Consider removing role selector from public registration

---

## 🧪 Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Run Specific Test File
```bash
php artisan test tests/Feature/AgentTicketFlowTest.php
```

### Test Coverage

The application includes tests for:
- ✅ Agent ticket workflow (claim, assign, resolve)
- ✅ Suggestion visibility and authorization
- ✅ Role-based registration
- ✅ Authentication flows
- ✅ Profile management

---

## 🎨 Code Quality Tools

### Laravel Pint (Code Formatting)

Pint is included for automatic code style fixing:

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

### PHPStan (Static Analysis)

**Note:** PHPStan is not currently installed but can be added:

```bash
# Install PHPStan
composer require --dev phpstan/phpstan larastan/larastan

# Create phpstan.neon configuration
# (See documentation for setup)

# Run static analysis
./vendor/bin/phpstan analyse
```

---

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── TicketController.php      # Main ticket CRUD operations
│   │   └── SuggestionController.php  # Suggestion API endpoint
│   └── Requests/
│       └── ProfileUpdateRequest.php
├── Models/
│   ├── Ticket.php                    # Ticket model with relationships
│   ├── TicketAttachment.php          # Attachment model
│   └── User.php                      # User model with roles
├── Policies/
│   └── TicketPolicy.php              # Authorization rules
├── Repositories/
│   ├── TicketRepositoryInterface.php
│   └── EloquentTicketRepository.php  # Data access with shortlisting
└── Services/
    ├── SuggestionService.php         # Suggestion algorithm
    └── TextNormalizer.php            # Text preprocessing

database/
├── migrations/
│   ├── *_create_users_table.php
│   ├── *_add_role_to_users_table.php
│   ├── *_create_ticket_table.php
│   └── *_create_ticket_attachments_*.php
└── seeders/
    └── DatabaseSeeder.php            # Demo data seeder

resources/
└── views/
    ├── tickets/
    │   ├── index.blade.php           # Ticket queue
    │   ├── create.blade.php          # Create ticket (with suggestions)
    │   ├── show.blade.php            # Ticket details
    │   └── edit.blade.php            # Edit ticket
    └── layouts/
        └── app.blade.php             # Main layout

routes/
└── web.php                           # Application routes

tests/
└── Feature/
    ├── AgentTicketFlowTest.php       # Agent workflow tests
    └── SuggestionVisibilityTest.php  # Authorization tests
```

---

## 🔒 Security Features

1. **Authorization**: Laravel Policies enforce all permissions
2. **Validation**: Comprehensive input validation on all forms
3. **File Upload Security**: 
   - MIME type validation
   - File size limits (5MB)
   - Secure storage with unique filenames
4. **SQL Injection Prevention**: Eloquent ORM with parameter binding
5. **CSRF Protection**: Laravel's built-in CSRF tokens
6. **Password Hashing**: Bcrypt hashing for all passwords

---

## 🚦 Ticket Workflow

```
┌─────────┐
│  OPEN   │ ← Ticket created by employee
└────┬────┘
     │
     │ Agent claims or assigns
     ↓
┌──────────────┐
│ IN_PROGRESS  │ ← Agent working on ticket
└──────┬───────┘
       │
       │ Agent marks as resolved
       ↓
┌──────────┐
│ RESOLVED │ ← Waiting for confirmation
└────┬─────┘
     │
     │ Agent closes ticket
     ↓
┌────────┐
│ CLOSED │ ← Final state
└────────┘
```

**State Transitions:**
- **Open → In Progress**: Agent claims or is assigned
- **In Progress → Resolved**: Agent marks as resolved
- **Resolved → Closed**: Agent confirms closure
- **Closed → Open**: Agent can reopen if needed (special permission)

---

## 🛠️ Development

### Running Development Server

```bash
# Start Laravel server
php artisan serve

# In another terminal, watch frontend assets
npm run dev
```

### Clearing Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database Management

```bash
# Reset database
php artisan migrate:fresh

# Reset with demo data
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_something_table

# Create new seeder
php artisan make:seeder SomethingSeeder
```

---
### ScreanShots 
### Agent Dashboard
<img width="1905" height="966" alt="Image" src="https://github.com/user-attachments/assets/d02af7ce-ab14-4d09-a553-707c7fe0c7e1" />

### Employee Dashboard
<img width="1903" height="969" alt="Image" src="https://github.com/user-attachments/assets/59041522-23e2-47bf-9696-fb8354521edc" />

### Agent ticket Page
<img width="1885" height="963" alt="Image" src="https://github.com/user-attachments/assets/82bbb0d9-bfd3-4e32-ac6c-1dd8ecbc3f75" />

### Employee ticket page 
<img width="1890" height="945" alt="Image" src="https://github.com/user-attachments/assets/956f9c1f-8f53-41e8-b341-d83f3ec656c1" />

### Agent Manage Ticekt Page
<img width="1912" height="974" alt="Image" src="https://github.com/user-attachments/assets/bea56953-02c3-45ff-bd62-27730690dcf9" />
---
