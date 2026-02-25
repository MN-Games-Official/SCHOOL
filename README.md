# SCHOOL AI — AI-Powered Learning Platform

An AI-powered learning web application built with PHP, vanilla JavaScript, TailwindCSS, and JSON file storage. Powered by Cerebras AI for lesson generation, quiz creation, exam simulation, and writing assistance.

![Landing Page](https://github.com/user-attachments/assets/76538812-61e6-4ae5-b8e4-cbd069bfe064)

## Features

### 🎓 AI Lesson Generation
- Select from 129+ topics across AP, SAT, ACT, PreACT, MCA, and general subjects
- 10 difficulty levels from Beginner to PhD Level
- 5 lesson lengths from Brief to Extensive
- Markdown + LaTeX rendering for math/science content
- Structured lessons with Overview, Key Concepts, Worked Examples, Common Mistakes, and Quick Practice

### 📝 Quiz Generation & Taking
- Generate 5-25 question quizzes on any topic
- Multiple question types: multiple choice, multi-select, short answer, numeric entry, fill-in-the-blank
- Instant grading with score breakdown
- AI-generated explanations on demand (explicit button press)
- AI hints available per question

### 📊 Standardized Exam Simulation
- **ACT**: 4 sections (English, Math, Reading, Science) with accurate timing
- **SAT**: 4 modules (R&W Module 1 & 2, Math Module 1 & 2) with adaptive format
- **PreACT**: 4 sections with appropriate timing
- **MCA**: Multiple grade-level sections for Math, Reading, and Science
- Section start pages with clear instructions
- Countdown timers matching real test conditions
- Fullscreen enforcement with pause overlay on exit
- Estimated scaled score ranges
- Domain and difficulty breakdowns

### ✍️ AI Writing Studio
- Google Docs-like writing experience
- Markdown editor with live preview
- Formatting toolbar (headings, bold, italic, lists, code, equations, tables)
- AI document scanning for diagnostics only (no rewrites)
- AI grading against rubrics (built-in AP templates or custom)
- Integrity tracking: revision history, snapshots, AI interaction logs
- Shareable integrity reports with password protection

### 🔒 Security
- Bcrypt password hashing
- CSRF token protection on all POST requests
- Session-based authentication
- Input sanitization
- Secure random ID generation

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, TailwindCSS (CDN), Vanilla JavaScript |
| Backend | PHP 8.x (no framework) |
| Storage | JSON files on filesystem |
| AI | Cerebras Chat Completions API |
| Models | gpt-oss-120b (heavy tasks), llama3.1-8b (light tasks) |
| Math | KaTeX for LaTeX rendering |
| Markdown | Marked.js + DOMPurify |

## Project Structure

```
SCHOOL/
├── index.php              # Front controller / router
├── .htaccess              # Apache URL rewriting
├── .gitignore
│
├── includes/              # PHP backend modules
│   ├── config.php         # Configuration & CSRF helpers
│   ├── auth.php           # Authentication (signup/login/logout)
│   ├── storage.php        # JSON file storage layer
│   ├── validator.php      # Input validation & AI response validation
│   ├── ai_gateway.php     # Cerebras AI API integration
│   ├── exam_service.php   # Exam configs & score estimation
│   ├── writing_service.php # Document management & integrity
│   ├── share_service.php  # Password-protected sharing
│   └── prompts/           # AI prompt templates
│       ├── lesson_generate.php
│       ├── quiz_generate.php
│       ├── exam_generate_section.php
│       ├── explanation_generate.php
│       ├── hint_generate.php
│       ├── writing_scan.php
│       └── writing_grade.php
│
├── api/                   # REST API handlers
│   ├── auth_handler.php
│   ├── topics_handler.php
│   ├── lessons_handler.php
│   ├── quizzes_handler.php
│   ├── exams_handler.php
│   ├── ai_handler.php
│   ├── writing_handler.php
│   ├── shares_handler.php
│   └── dashboard_handler.php
│
├── views/                 # PHP view templates
│   ├── layout.php         # Base layout (sidebar + topbar + global components)
│   ├── landing.php        # Landing page
│   ├── dashboard.php      # Dashboard with stats
│   ├── topics.php         # Topic browser
│   ├── share.php          # Shared report viewer
│   ├── auth/
│   │   ├── login.php
│   │   └── signup.php
│   ├── lessons/
│   │   ├── index.php      # Lesson library
│   │   └── generate.php   # Lesson generator
│   ├── quizzes/
│   │   ├── index.php      # Quiz library
│   │   ├── generate.php   # Quiz generator
│   │   ├── take.php       # Quiz taking UI
│   │   └── review.php     # Quiz review with explanations
│   ├── exams/
│   │   ├── index.php      # Exam hub
│   │   ├── builder.php    # Exam builder
│   │   ├── take.php       # Exam runner (fullscreen + timers)
│   │   └── review.php     # Exam review with scores
│   ├── writing/
│   │   ├── index.php      # Document list
│   │   ├── editor.php     # Writing studio editor
│   │   └── report.php     # Integrity report
│   └── admin/
│       └── topics.php     # Admin topics viewer
│
├── js/                    # Frontend JavaScript modules
│   ├── app.js             # Main app initialization
│   ├── ui.js              # UI component library
│   ├── api.js             # API client with CSRF
│   ├── markdown.js        # Markdown + KaTeX renderer
│   ├── topics.js          # Topics page logic
│   ├── lessons.js         # Lesson generation & library
│   ├── quizzes.js         # Quiz generation & taking
│   ├── exams.js           # Exam system
│   ├── test-runner.js     # Standardized test runner (fullscreen, timers, pause)
│   ├── writing.js         # Writing studio editor
│   ├── analytics.js       # Dashboard analytics
│   ├── integrity.js       # Integrity report viewer
│   └── command-palette.js # Ctrl+K command palette
│
├── data/                  # JSON file storage (auto-created)
│   ├── topics/
│   │   └── topics.json    # 129+ topic catalog
│   ├── users/             # User accounts
│   ├── lessons/           # Generated lessons
│   ├── quizzes/           # Generated quizzes
│   ├── exams/             # Exam attempts
│   ├── writing/           # Writing documents
│   ├── integrity/         # Integrity logs
│   ├── shares/            # Shared reports
│   └── ai_logs/           # AI request logs
│
└── scripts/
    └── seed_demo.php      # Demo data seeding script
```

## Setup Instructions

### Prerequisites
- PHP 8.0+ with curl extension
- Apache with mod_rewrite (or PHP built-in server for development)
- A Cerebras API key

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/MN-Games-Official/SCHOOL.git
   cd SCHOOL
   ```

2. Set the Cerebras API key:
   ```bash
   export CEREBRAS_API_KEY="your-api-key-here"
   ```

3. Ensure the data directory is writable:
   ```bash
   chmod -R 775 data/
   ```

4. Start the development server:
   ```bash
   php -S localhost:8080 index.php
   ```

5. Open http://localhost:8080 in your browser.

### Demo Mode

Seed a demo account with sample data:
```bash
php scripts/seed_demo.php
```

This creates:
- **Username**: `demo` / **Password**: `demo1234`
- Sample lesson, quiz, writing document, and exam data

### Production Deployment (Apache)

1. Point your document root to the project directory
2. Ensure `.htaccess` is enabled (AllowOverride All)
3. Set environment variable: `SetEnv CEREBRAS_API_KEY your-key`
4. Ensure `data/` directory is writable by the web server user

## AI Models

| Model | Used For | Recommended For |
|-------|----------|----------------|
| `gpt-oss-120b` | Heavy generation | Lessons, full exams, writing grading |
| `llama3.1-8b` | Light tasks | Hints, explanations, writing scanning |

Users can override the model selection per request.

## API Endpoints

### Authentication
- `POST /api/auth/signup` — Create account
- `POST /api/auth/login` — Log in
- `POST /api/auth/logout` — Log out

### Topics
- `GET /api/topics` — List all topics (129+)

### Lessons
- `POST /api/lessons/generate` — Generate AI lesson
- `GET /api/lessons/list` — List user's lessons
- `GET /api/lessons/{id}` — Get specific lesson

### Quizzes
- `POST /api/quizzes/generate` — Generate AI quiz
- `GET /api/quizzes/list` — List user's quizzes
- `GET /api/quizzes/{id}` — Get specific quiz
- `POST /api/quizzes/{id}/grade` — Grade quiz answers

### Exams
- `POST /api/exams/generate-section` — Generate exam section
- `GET /api/exams/list` — List user's exams
- `GET /api/exams/{id}` — Get specific exam
- `POST /api/exams/{id}/grade-section` — Grade exam section

### AI
- `POST /api/ai/request` — Generic AI request
- `POST /api/ai/explain` — Generate explanations
- `POST /api/ai/hint` — Generate hint

### Writing
- `GET /api/writing/list` — List documents
- `POST /api/writing/create` — Create document
- `GET /api/writing/{id}` — Get document
- `POST /api/writing/{id}/save` — Save document
- `POST /api/writing/{id}/scan` — AI scan for diagnostics
- `POST /api/writing/{id}/grade` — AI grade with rubric
- `POST /api/writing/{id}/snapshot` — Save snapshot
- `GET /api/writing/{id}/integrity` — Get integrity log

### Sharing
- `POST /api/shares/create` — Create share link
- `GET /api/shares/{id}` — View shared report

### Dashboard
- `GET /api/dashboard/stats` — User statistics

## Design Principles

1. **All AI calls are user-triggered** — Every AI request requires an explicit button click and confirmation
2. **AI outputs JSON only** — All AI responses are validated against operation schemas
3. **Writing assistant never rewrites** — Only provides diagnostics and guidance
4. **Standardized tests enforce fullscreen** — Section start pages, timers, and pause overlays
5. **JSON file storage** — No database required, all data in human-readable JSON files
6. **Transparency** — All AI interactions are logged and auditable

## Accessibility

- Keyboard navigation throughout
- High contrast toggle
- Dyslexia-friendly font toggle
- ARIA attributes on interactive elements
- Command palette (Ctrl+K) for quick navigation

## License

MIT
