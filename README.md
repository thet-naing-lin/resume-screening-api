# Resume Screening Tool — Backend

Laravel REST API backend for the Automated Resume Screening Tool. Handles authentication, resume storage, job management, candidate ranking, AI insights, and audit logging.

## Tech Stack

- **Laravel 11**
- **MySQL** database
- **Laravel Sanctum** for API token authentication
- **Spatie Laravel Permission** for role-based access control
- **Symfony Process** for calling the Python NLP service
- **Smalot PDF Parser** for PDF text extraction
- **PHPOffice/PHPWord** for DOCX text extraction
- **Google Gemini API** for AI summary and interview question generation

## Features

- Token-based authentication (register, login, logout)
- Role management: `admin`, `hr_recruiter`
- Job description CRUD
- Resume upload with file validation (PDF/DOCX, max 5MB)
- Resume text extraction and candidate parsing
- NLP scoring via Python microservice (TF-IDF + Semantic)
- Candidate ranking with filters and pagination
- Candidate status management (shortlist / reject / under review)
- AI-generated candidate summaries and interview questions (Gemini)
- CSV export of ranked candidates
- Full audit logging of all user actions
- Admin: user management, role assignment

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8+
- Python 3.9+ (for NLP service)
- Queue worker (database or Redis driver)

### Installation

```bash
# Clone the repository
git clone https://github.com/your-username/resume-screening-backend.git
cd resume-screening-backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Environment Variables

Update `.env` with your values:

```env
APP_NAME="Resume Screening Tool"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=resume_screening
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent

PYTHON_SERVICE_URL=http://127.0.0.1:5001
```

### Database Setup

```bash
# Run migrations
php artisan migrate

# Seed initial data (admin user + sample jobs)
php artisan db:seed
```

### Running the Server

```bash
# Start Laravel development server
php artisan serve

# Start queue worker (required for resume processing)
 php artisan queue:work --timeout=120 --tries=3 --verbose
```

API runs at `http://localhost:8000`.

## API Endpoints

### Authentication

| Method | Endpoint             | Description       |
| ------ | -------------------- | ----------------- |
| POST   | `/api/auth/register` | Register new user |
| POST   | `/api/auth/login`    | Login             |
| POST   | `/api/auth/logout`   | Logout            |

### Jobs

| Method | Endpoint         | Description               |
| ------ | ---------------- | ------------------------- |
| GET    | `/api/jobs`      | List all job descriptions |
| POST   | `/api/jobs`      | Create job description    |
| PUT    | `/api/jobs/{id}` | Update job description    |
| DELETE | `/api/jobs/{id}` | Delete job description    |

### Resumes

| Method | Endpoint                        | Description              |
| ------ | ------------------------------- | ------------------------ |
| GET    | `/api/resumes`                  | List uploaded resumes    |
| POST   | `/api/resumes`                  | Upload resume(s)         |
| DELETE | `/api/resumes/{id}`             | Delete a resume          |
| POST   | `/api/resumes/{id}/ai-insights` | Generate AI insights     |
| GET    | `/api/resumes/{id}/ai-insights` | Get existing AI insights |

### Rankings

| Method | Endpoint                              | Description             |
| ------ | ------------------------------------- | ----------------------- |
| GET    | `/api/candidate-rankings/export`      | Export rankings as CSV  |
| GET    | `/api/candidate-rankings`             | Get ranked candidates   |
| PATCH  | `/api/candidate-rankings/{id}/status` | Update candidate status |

### Admin

| Method | Endpoint                     | Description     |
| ------ | ---------------------------- | --------------- |
| GET    | `/api/admin/users`           | List all users  |
| PATCH  | `/api/admin/users/{id}/role` | Assign role     |
| DELETE | `/api/admin/users/{id}`      | Delete user     |
| GET    | `/api/admin/audit-logs`      | View audit logs |

## Default Credentials (After Seeding)

- #### Admin: admin@example.com / Admin@12345

- #### HR Recruiter: hr@example.com / asdfasdf

## Queue Jobs

Resume processing runs as a background job:

Upload → ProcessResumeJob dispatched
→ Text extraction (PDF/DOCX)
→ Candidate parsing (name, email, phone)
→ Python NLP scoring (TF-IDF + Semantic)
→ Score saved to database
→ Status updated to "scored"
