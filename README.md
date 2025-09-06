# Smart Ticket Triage & Dashboard

A production-ready single-page application (SPA) for help-desk teams to manage support tickets with AI-powered classification. Built with Laravel 11 (PHP 8.2) backend and Vue 3 (Options API) frontend.

## Features

### Core Functionality
- **Ticket Management**: Create, view, edit, and filter support tickets
- **AI Classification**: Automated ticket categorization using OpenAI GPT-3.5-turbo
- **Queue System**: Background processing for AI classification jobs
- **Analytics Dashboard**: Real-time statistics and charts
- **Search & Filter**: Advanced filtering by status, category, and text search
- **Export**: CSV export functionality for ticket data

### Technical Highlights
- **Laravel 11**: Modern PHP framework with strict typing
- **Vue 3 SPA**: Single-page application with Vue Router
- **ULID Primary Keys**: Universally unique lexicographically sortable identifiers
- **Rate Limiting**: AI API call rate limiting and error handling
- **Responsive Design**: Mobile-friendly interface with dark/light theme support
- **BEM CSS**: Clean, maintainable styling without frameworks

## Setup Instructions

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (default) or MySQL/PostgreSQL

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd smart-ticket-triage
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

5. **Configure environment variables**
   Edit `.env` file and set:
   ```env
   # Database (SQLite is default, no additional config needed)
   DB_CONNECTION=sqlite

   # OpenAI Configuration (optional for demo)
   OPENAI_API_KEY=your_openai_api_key_here
   OPENAI_CLASSIFY_ENABLED=true
   ```

6. **Create database and run migrations**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

8. **Start the application**
   ```bash
   php artisan serve
   ```

9. **Process queue jobs (in separate terminal)**
   ```bash
   php artisan queue:work
   ```

10. **Access the application**
    Open http://localhost:8000 in your browser

## API Endpoints

### Tickets
- `GET /api/tickets` - List tickets with filtering and pagination
- `POST /api/tickets` - Create new ticket
- `GET /api/tickets/{id}` - Get ticket details
- `PATCH /api/tickets/{id}` - Update ticket
- `POST /api/tickets/{id}/classify` - Queue AI classification
- `GET /api/tickets-export` - Export tickets to CSV

### Statistics
- `GET /api/stats` - Get dashboard analytics

## Artisan Commands

### Bulk Classification
```bash
# Classify all unclassified tickets
php artisan tickets:bulk-classify --unclassified

# Classify tickets by status
php artisan tickets:bulk-classify --status=open

# Limit number of tickets to process
php artisan tickets:bulk-classify --limit=10

# Preserve manually set categories
php artisan tickets:bulk-classify --preserve-manual
```

## Architecture

### Backend (Laravel 11)
- **Models**: `Ticket` with ULID, status enum, and classification fields
- **Controllers**: RESTful API controllers with validation
- **Services**: `TicketClassifier` for OpenAI integration
- **Jobs**: `ClassifyTicket` for queued background processing
- **Migrations**: Strict typing with proper indexes

### Frontend (Vue 3)
- **SPA Router**: Client-side routing with Vue Router
- **Components**: Modular Vue components using Options API
- **State Management**: Built-in Vue reactivity
- **Charts**: Chart.js integration for analytics
- **Styling**: BEM methodology with CSS custom properties

### Key Design Decisions
- **ULID over UUID**: Better performance and natural sorting
- **Queue-based AI**: Prevents blocking UI during API calls
- **Rate Limiting**: Protects against API quota exhaustion
- **Graceful Degradation**: Dummy classification when OpenAI unavailable
- **Responsive Design**: Mobile-first approach

## Assumptions & Trade-offs

### Assumptions
1. **Single Tenant**: Application designed for single organization use
2. **English Language**: AI prompts and categories optimized for English
3. **Moderate Scale**: Designed for thousands of tickets, not millions
4. **Internal Tool**: No user authentication required (team tool)
5. **Modern Browsers**: ES6+ JavaScript features used

### Trade-offs
1. **SQLite Default**: Chosen for easy setup vs. production PostgreSQL/MySQL
2. **No Real-time Updates**: Polling-based refresh vs. WebSocket complexity
3. **Simple Categories**: Fixed category list vs. dynamic taxonomy
4. **Client-side Pagination**: Simpler implementation vs. server-side efficiency
5. **Inline Editing**: Category dropdown vs. full modal editing

### Security Considerations
- CSRF protection enabled
- Input validation on all endpoints
- SQL injection prevention via Eloquent ORM
- XSS protection through Vue.js templating
- Rate limiting on AI classification

## What I'd Do With More Time

### Enhanced Features
1. **Real-time Updates**: WebSocket integration for live ticket updates
2. **Advanced Analytics**: Time-series charts, SLA tracking, performance metrics
3. **User Management**: Authentication, roles, and permissions system
4. **Email Integration**: Automatic ticket creation from email
5. **File Attachments**: Support for ticket attachments and screenshots

### Technical Improvements
1. **Caching Layer**: Redis caching for frequently accessed data
2. **Search Engine**: Elasticsearch for advanced full-text search
3. **API Versioning**: Proper API versioning strategy
4. **Testing Suite**: Comprehensive unit and integration tests
5. **Docker Setup**: Containerized development environment

### AI Enhancements
1. **Multiple Models**: Support for different AI providers and models
2. **Custom Training**: Fine-tuned models for specific domains
3. **Sentiment Analysis**: Automatic priority assignment based on sentiment
4. **Auto-responses**: AI-generated response suggestions
5. **Escalation Rules**: Intelligent routing based on content analysis

### Performance Optimizations
1. **Database Indexing**: Advanced indexing strategies for large datasets
2. **CDN Integration**: Asset delivery optimization
3. **Background Processing**: More sophisticated queue management
4. **Monitoring**: Application performance monitoring and alerting
5. **Load Balancing**: Horizontal scaling preparation

### User Experience
1. **Keyboard Shortcuts**: Power user navigation shortcuts
2. **Bulk Operations**: Multi-select and bulk actions
3. **Custom Views**: Saved filters and personalized dashboards
4. **Mobile App**: Native mobile application
5. **Accessibility**: WCAG 2.1 AA compliance

## Technology Stack

### Backend
- **Laravel 11**: PHP framework with modern features
- **PHP 8.2**: Latest PHP with strict typing
- **SQLite/MySQL**: Database layer
- **OpenAI PHP**: Official OpenAI API client
- **Queue System**: Background job processing

### Frontend
- **Vue 3**: Progressive JavaScript framework
- **Vue Router**: Client-side routing
- **Chart.js**: Data visualization
- **Axios**: HTTP client
- **Vite**: Build tool and dev server

### Development
- **Composer**: PHP dependency management
- **NPM**: JavaScript package management
- **Laravel Pint**: Code formatting
- **Vite**: Asset bundling and HMR

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For questions or issues, please create an issue in the repository or contact the development team.
