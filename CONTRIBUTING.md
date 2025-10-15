# Contributing to WP Blog Agent

Thank you for considering contributing to WP Blog Agent! This document provides guidelines for contributing to the project.

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers and help them learn
- Focus on constructive feedback
- Maintain professionalism in all interactions

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported in [Issues](https://github.com/np2023v2/wp-blog-agent/issues)
2. Create a new issue with a clear title and description
3. Include:
   - WordPress version
   - PHP version
   - Plugin version
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Screenshots if applicable
   - Error messages from logs

### Suggesting Features

1. Check [Issues](https://github.com/np2023v2/wp-blog-agent/issues) for existing feature requests
2. Create a new issue with the `enhancement` label
3. Describe:
   - The problem the feature would solve
   - Proposed solution
   - Alternative solutions considered
   - Additional context

### Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes following our coding standards
4. Test your changes thoroughly
5. Commit with clear messages (`git commit -m 'Add amazing feature'`)
6. Push to your branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## Development Setup

### Prerequisites

- WordPress 5.0+
- PHP 7.4+
- Composer (optional, for development dependencies)
- Git

### Local Setup

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/wp-blog-agent.git

# Navigate to plugin directory
cd wp-blog-agent

# Install in WordPress plugins directory
cp -r . /path/to/wordpress/wp-content/plugins/wp-blog-agent/

# Activate the plugin in WordPress admin
```

## Coding Standards

### PHP Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use PSR-4 autoloading structure
- Add PHPDoc comments to all functions and classes
- Use meaningful variable and function names
- Keep functions focused and small

### File Organization

```
wp-blog-agent/
├── admin/           # Admin page templates
├── assets/          # CSS, JS, images
├── includes/        # PHP classes
├── languages/       # Translation files (future)
└── tests/           # Unit tests (future)
```

### Naming Conventions

- Classes: `WP_Blog_Agent_ClassName`
- Functions: `wp_blog_agent_function_name()`
- Variables: `$snake_case`
- Constants: `WP_BLOG_AGENT_CONSTANT`

### Security

- Always sanitize user input
- Escape output
- Use nonces for forms
- Check capabilities
- Validate and sanitize data
- Use prepared statements for database queries

Example:
```php
// Good
$topic = sanitize_text_field($_POST['topic']);
check_admin_referer('wp_blog_agent_add_topic');
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

// Bad
$topic = $_POST['topic'];
```

### Database Operations

- Use `$wpdb` for queries
- Always use prepared statements
- Handle errors gracefully

Example:
```php
global $wpdb;
$result = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}blog_agent_topics WHERE id = %d",
    $topic_id
));
```

### Error Handling

- Use `WP_Error` for errors
- Log errors with `WP_Blog_Agent_Logger`
- Provide user-friendly error messages

Example:
```php
if (!$valid) {
    WP_Blog_Agent_Logger::error('Validation failed', array('field' => $field));
    return new WP_Error('validation_error', 'Invalid input');
}
```

## Testing

### Manual Testing

1. Test with fresh WordPress installation
2. Test activation/deactivation
3. Test all admin pages
4. Test content generation with both APIs
5. Test scheduling functionality
6. Test error conditions
7. Test uninstall process

### Test Checklist

- [ ] Plugin activates without errors
- [ ] Settings page loads and saves correctly
- [ ] Topics can be added, viewed, and deleted
- [ ] OpenAI integration works
- [ ] Gemini integration works
- [ ] Manual generation works
- [ ] Scheduled generation works
- [ ] Auto-publish works correctly
- [ ] Draft mode works correctly
- [ ] Logs page shows entries
- [ ] Generated posts display correctly
- [ ] Uninstall cleans up properly
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors

## Documentation

- Update README.md for user-facing changes
- Update ARCHITECTURE.md for structural changes
- Update CHANGELOG.md with all changes
- Add inline comments for complex logic
- Update QUICKSTART.md if setup process changes

## Commit Messages

Use clear, descriptive commit messages:

```
Add feature: Brief description

Longer explanation of the change if needed.
Include why the change was made and any important details.

Fixes #issue_number
```

## Code Review Process

1. All PRs require review before merging
2. Address all review comments
3. Keep PRs focused and small
4. Include tests where applicable
5. Update documentation as needed

## Adding New Features

### AI Provider Integration

To add a new AI provider:

1. Create `includes/class-wp-blog-agent-newprovider.php`
2. Implement `generate_content()` method
3. Add provider option to settings page
4. Update validator to include new provider
5. Test thoroughly
6. Document API key requirements

### Admin Page

To add a new admin page:

1. Create template in `admin/new-page.php`
2. Add submenu in `includes/class-wp-blog-agent-admin.php`
3. Add render method in admin class
4. Include necessary assets
5. Add translations

## Release Process

### Using the Bump Version Script (Recommended)

Use the `bump-version.sh` script to automate version updates:

```bash
./bump-version.sh 1.0.2
```

This will:
1. Update version in `wp-blog-agent.php` (header and constant)
2. Add new version section to `CHANGELOG.md`
3. Provide instructions for next steps

Then:
1. Review changes: `git diff wp-blog-agent.php CHANGELOG.md`
2. Update `CHANGELOG.md` with actual changes for this version
3. Commit: `git add wp-blog-agent.php CHANGELOG.md && git commit -m "Bump version to 1.0.2"`
4. Tag: `git tag -a v1.0.2 -m "Version 1.0.2"`
5. Push: `git push && git push origin v1.0.2`
6. Create GitHub release with notes

### Manual Process

If you prefer to update manually:

1. Update version in `wp-blog-agent.php` (both header comment and constant)
2. Update CHANGELOG.md with new version section
3. Tag release: `git tag -a v1.0.1 -m "Version 1.0.1"`
4. Push tag: `git push origin v1.0.1`
5. Create GitHub release with notes

## Questions?

- Open an issue for questions
- Check existing documentation
- Review code examples

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT License).

Thank you for contributing to WP Blog Agent!
