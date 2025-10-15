# Changelog

All notable changes to WP Blog Agent will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-10-14

### Added
- Initial release of WP Blog Agent
- OpenAI GPT-3.5 integration for content generation
- Google Gemini AI integration as alternative provider
- Topic management system with keywords and hashtags
- SEO-optimized content generation
- Hashtag support for social media visibility
- Automated scheduling using WordPress Cron
- Flexible scheduling options (hourly, twice daily, daily, weekly)
- Auto-publish functionality with draft option
- Custom database table for topic management
- Admin settings page for configuration
- Topics management page
- Generated posts listing page
- Manual post generation from any topic
- Post metadata tracking (provider, keywords, hashtags)
- Admin UI with custom CSS styling
- JavaScript for enhanced admin experience
- Comprehensive documentation (README, ARCHITECTURE, QUICKSTART)
- Uninstall script for clean removal
- Security features (nonce verification, capability checks, input sanitization)

### Security
- All user inputs are sanitized and validated
- Nonce verification for all form submissions
- Capability checks require 'manage_options' permission
- Direct file access prevention
- Secure API key storage in WordPress options

## [Unreleased]

### Added
- Custom OpenAI base URL configuration for OpenAI-compatible API services
- Quick manual topic generation without saving to database
- Enhanced Topics page with manual generation form
- Support for one-off content generation
- Optional keywords and hashtags - can now generate posts from title only
- Ollama (local AI) support for self-hosted models
- **Image Generation feature using Gemini Imagen API**
- **New admin page for AI image generation**
- **Upload generated images to WordPress media library**
- **Attach images to blog posts with featured image support**
- **Configurable image parameters (aspect ratio, size, resolution)**
- **Image generation metadata tracking**
- **Recently generated images gallery**
- **Task Queue System for asynchronous post generation**
- **Queue management page with statistics and monitoring**
- **Automatic retry logic for failed generation tasks (up to 3 attempts)**
- **Background processing via WordPress Cron**
- **Queue cleanup functionality for old completed/failed tasks**

### Improved
- Enhanced error handling for OpenAI API with detailed error messages
- Enhanced error handling for Gemini API with better response validation
- Enhanced error handling for Ollama API for consistency
- API request and response logging moved from debug to info level for better visibility
- Better validation of API responses with comprehensive checks
- Support for different OpenAI-compatible API response formats
- More descriptive error messages for troubleshooting API issues
- **Updated Gemini Imagen model to version 4.0 (imagen-4.0-generate-001) for improved image quality**
- **Post generation now uses asynchronous queue system for better reliability**
- **Non-blocking generation requests improve UI responsiveness**

### Fixed
- Invalid response from OpenAI API now properly handled with clear error messages
- Better handling of empty or malformed API responses
- Improved HTTP status code validation for all AI providers
- **Generation tasks won't block WordPress admin interface**

### Planned Features
- Multi-language support (i18n/l10n)
- Custom post type support
- Automatic category assignment
- AI-generated featured images
- Customizable content templates
- Analytics dashboard
- Bulk post generation
- Content calendar view
- A/B testing for prompts
- Topic configuration export/import
- Rate limiting for API calls
- Content preview before publishing
- Custom field support
- Integration with popular SEO plugins
- REST API endpoints for external integrations
