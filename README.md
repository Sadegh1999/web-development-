# Movie Streaming Website

A modern movie streaming platform built with PHP, CSS, HTML, MySQL,JAVASCRIPT and Bootstrap. The website allows users to browse, watch, and purchase movies, manage their watchlist, and track their orders.

## Features

- 🎬 **Movie Management**
  - Browse movies by categories
  - Search functionality
  - Movie details with trailers
  - Popular and new movie badges
  - Movie status tracking (active/inactive)

- 👤 **User Features**
  - User registration and login
  - Profile management
  - Watchlist functionality
  - Order history
  - Secure payment processing

- 👨‍💼 **Admin Panel**
  - Dashboard with statistics
  - Movie management (add, edit, delete)
  - User management
  - Order management
  - Category management

- 🎨 **Modern UI/UX**
  - Responsive design
  - Clean and intuitive interface
  - Smooth animations
  - Dark mode support
  - Mobile-friendly layout

## Tech Stack

- **Backend**
  - PHP 8.2
    - Object-oriented programming
    - PDO for database connections
    - Custom MVC architecture
  - MySQL 8.0
    - Optimized database queries
    - Stored procedures
    - Transaction management
  - Apache Web Server
    - mod_rewrite for clean URLs
    - Custom error handling

- **Frontend**
  - HTML5
    - Semantic markup
    - Accessibility features (ARIA)
    - Structured data (Schema.org)
    - Responsive meta tags
  - CSS3
    - Flexbox and Grid layouts
    - Custom variables (CSS Variables)
    - Media queries for responsiveness
    - Animations and transitions
    - BEM naming convention
  - JavaScript (ES6+)
    - Async/Await functionality
    - DOM manipulation
    - Form validation
    - AJAX requests
  - Bootstrap 5
    - Custom theme modifications
    - Responsive grid system
    - Component customization
  - Font Awesome 6
    - Custom icon integration
    - SVG with JavaScript

- **Development & Tools**
  - Docker
    - Multi-container setup
    - Custom networking
    - Volume management
  - Git
    - Feature branch workflow
    - Conventional commits
  - VS Code
    - Recommended extensions
    - Custom workspace settings

- **API Integrations**
  - Payment gateway integration
  - Movie data API
  - YouTube API for trailers

## Project Structure

```
├── admin/                 # Admin panel files
├── assets/               # Static assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   ├── images/          # Images and icons
│   └── fonts/           # Custom fonts
├── includes/             # PHP includes
│   ├── process/         # Process files
│   ├── config.php       # Database configuration
│   ├── functions.php    # Helper functions
│   ├── header.php       # Common header
│   └── footer.php       # Common footer
├── uploads/             # User uploads
├── docker/              # Docker configuration
├── Dockerfile           # Docker build instructions
├── docker-compose.yml   # Docker services configuration
└── README.md           # Project documentation
```

## Coding Standards & Best Practices

### HTML
- Semantic HTML5 elements for better structure and SEO
- Proper heading hierarchy (h1-h6)
- ARIA labels and roles for accessibility
- Valid and well-formed markup (W3C validated)
- Responsive images with srcset and sizes
- Lazy loading for media elements

### CSS
- Mobile-first approach
- BEM (Block Element Modifier) methodology
- CSS Custom Properties for theming
- Modular SCSS architecture
  - Base styles
  - Components
  - Layouts
  - Utilities
- Performance optimizations
  - Critical CSS
  - Minification
  - Efficient selectors

### JavaScript
- ES6+ features
- Module pattern
- Event delegation
- Error handling
- Performance optimization
  - Code splitting
  - Lazy loading
  - Debouncing/Throttling
- Clean code principles

### PHP
- PSR-12 coding standards
- SOLID principles
- Security best practices
  - Input validation
  - Output escaping
  - Prepared statements
- Error logging and handling
- Dependency management with Composer

## Installation

### Prerequisites

- Docker and Docker Compose
- Git

### Setup

1. Clone the repository:
   ```bash
   git clone [repository-url]
   cd movie-streaming-website
   ```

2. Start the containers:
   ```bash
   docker-compose up -d
   ```

3. Access the application:
   - Website: http://localhost:8081
   - Admin Panel: http://localhost:8081/admin
   - Database: localhost:3306

## Configuration

1. Database setup:
   - Create a new database named `newdb`
   - Import the SQL schema from `database/schema.sql`

2. Environment variables:
   - Update `includes/config.php` with your database credentials
   - Configure payment gateway settings in `includes/config.php`

## Usage

### User Features

1. **Registration & Login**
   - Create an account or log in to access features
   - Manage your profile information

2. **Browsing Movies**
   - View all available movies
   - Filter by categories
   - Search for specific movies
   - View movie details and trailers

3. **Watchlist**
   - Add movies to your watchlist
   - Remove movies from watchlist
   - View your watchlist

4. **Purchasing**
   - Select movies to purchase
   - Complete payment process
   - View order history

### Admin Features

1. **Dashboard**
   - View site statistics
   - Monitor user activity
   - Track orders and revenue

2. **Content Management**
   - Add new movies
   - Edit existing movies
   - Manage movie status
   - Handle categories

3. **User Management**
   - View user list
   - Manage user roles
   - Monitor user activity

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Security

- All user inputs are sanitized
- Password hashing using modern algorithms
- CSRF protection implemented
- XSS prevention measures
- Secure session handling

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, email [support@example.com](mailto:support@example.com) or create an issue in the repository.

## Acknowledgments

- Bootstrap for the frontend framework
- Font Awesome for icons
- All contributors who have helped with the project 