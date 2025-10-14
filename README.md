# WP Blog Agent

A powerful WordPress plugin that automates blog post generation using OpenAI GPT or Google Gemini AI. Create SEO-optimized content with hashtags, keywords, scheduling, and auto-publishing capabilities.

## Features

- **AI-Powered Content Generation**: Choose between OpenAI (GPT) or Google Gemini for content creation
- **SEO Optimization**: Automatically include keywords with proper density for search engine optimization
- **Hashtag Support**: Add relevant hashtags to boost social media visibility
- **Topic Management**: Create and manage multiple topics for varied content
- **Scheduled Publishing**: Automate post generation with flexible scheduling options (hourly, twice daily, daily, weekly)
- **Auto-Publish**: Automatically publish generated posts or save as drafts for review
- **Activity Logging**: Track all plugin activities with detailed logs
- **Input Validation**: Comprehensive validation for all user inputs
- **Security First**: Nonce verification, capability checks, and sanitized inputs
- **User-Friendly Admin Interface**: Easy-to-use settings and management pages
- **Clean Uninstall**: Remove all plugin data when uninstalled

## Installation

1. Download or clone this repository to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/np2023v2/wp-blog-agent.git
   ```

2. Activate the plugin through the WordPress admin panel:
   - Go to **Plugins** → **Installed Plugins**
   - Find "WP Blog Agent" and click **Activate**

3. Configure the plugin:
   - Go to **Blog Agent** → **Settings**
   - Enter your OpenAI or Gemini API key
   - Configure your preferences

## Configuration

### 1. Get API Keys

#### OpenAI API Key
- Visit [OpenAI Platform](https://platform.openai.com/api-keys)
- Sign up or log in to your account
- Create a new API key
- Copy the key for use in the plugin

#### Google Gemini API Key
- Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
- Sign up or log in with your Google account
- Create a new API key
- Copy the key for use in the plugin

### 2. Plugin Settings

Navigate to **Blog Agent** → **Settings** to configure:

- **AI Provider**: Choose between OpenAI (GPT) or Google Gemini
- **API Keys**: Enter your API key for the selected provider
- **Enable Scheduling**: Turn on/off automated post generation
- **Schedule Frequency**: Choose how often to generate posts (hourly, twice daily, daily, weekly)
- **Auto Publish**: Decide whether to publish posts automatically or save as drafts

### 3. Manage Topics

Navigate to **Blog Agent** → **Topics** to:

- **Add Topics**: Create new topics with keywords and hashtags
- **View Topics**: See all configured topics
- **Generate**: Manually generate a post for any specific topic
- **Delete**: Remove topics you no longer need

**Example Topic Configuration:**
- **Topic**: "WordPress SEO Best Practices"
- **Keywords**: wordpress, SEO, search engine optimization, ranking, plugins
- **Hashtags**: #WordPress, #SEO, #WebDev, #BlogTips

### 4. View Generated Posts

Navigate to **Blog Agent** → **Generated Posts** to:

- View all posts created by the plugin
- Check post status (published, draft, etc.)
- See which AI provider was used
- Edit or view posts directly

### 5. Monitor Activity

Navigate to **Blog Agent** → **Logs** to:

- View recent plugin activity
- Check for errors or warnings
- Monitor content generation success
- Debug issues
- Clear old logs

## Usage

### Manual Generation

1. Go to **Blog Agent** → **Settings**
2. Click **Generate Post Now** button
3. The plugin will randomly select an active topic and generate a post

### Scheduled Generation

1. Go to **Blog Agent** → **Settings**
2. Set **Enable Scheduling** to "Yes"
3. Choose your preferred **Schedule Frequency**
4. Save settings
5. Posts will be automatically generated based on your schedule

### Topic-Specific Generation

1. Go to **Blog Agent** → **Topics**
2. Find the topic you want to generate content for
3. Click the **Generate** button next to that topic

## How It Works

1. **Topic Selection**: The plugin selects a topic from your configured list
2. **Prompt Building**: Creates an optimized prompt with the topic, keywords, and hashtags
3. **AI Generation**: Sends the prompt to your selected AI provider (OpenAI or Gemini)
4. **Content Processing**: Parses the response to extract title and content
5. **Post Creation**: Creates a WordPress post with the generated content
6. **Publishing**: Publishes the post or saves as draft based on your settings

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active internet connection
- Valid API key for OpenAI or Google Gemini

## Database Tables

The plugin creates the following custom table:

- `wp_blog_agent_topics`: Stores topics, keywords, and hashtags

## File Storage

The plugin creates log files in:

- `wp-content/uploads/wp-blog-agent-logs/`: Activity logs (protected with .htaccess)

## Cron Jobs

The plugin uses WordPress Cron to schedule automated generation:

- Hook: `wp_blog_agent_generate_post`
- Frequencies: hourly, twicedaily, daily, weekly

## Security

- API keys are stored securely in WordPress options
- All user inputs are sanitized and validated
- Nonce verification for all form submissions
- Capability checks for admin actions
- Log files protected from direct access
- Prepared statements for database queries
- Output escaping to prevent XSS

## Troubleshooting

### Posts Not Being Generated

1. Check that you have entered a valid API key
2. Verify that scheduling is enabled
3. Ensure you have at least one active topic
4. Check WordPress error logs for any issues

### API Errors

- **OpenAI**: Verify your API key has sufficient credits
- **Gemini**: Ensure your API key is valid and has proper permissions
- Check your internet connection

### Scheduling Issues

- Verify that WordPress Cron is working on your server
- Some hosting providers disable WP-Cron; you may need to set up a system cron job
- Check the Logs page for scheduling errors

### Check Logs

- Go to **Blog Agent** → **Logs**
- Look for ERROR or WARNING entries
- Check timestamps to see when issues occurred
- Use logs to debug API or generation issues

## Development

### File Structure

```
wp-blog-agent/
├── admin/
│   ├── logs-page.php
│   ├── posts-page.php
│   ├── settings-page.php
│   └── topics-page.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-wp-blog-agent-activator.php
│   ├── class-wp-blog-agent-admin.php
│   ├── class-wp-blog-agent-deactivator.php
│   ├── class-wp-blog-agent-gemini.php
│   ├── class-wp-blog-agent-generator.php
│   ├── class-wp-blog-agent-logger.php
│   ├── class-wp-blog-agent-openai.php
│   ├── class-wp-blog-agent-scheduler.php
│   └── class-wp-blog-agent-validator.php
├── .gitignore
├── ARCHITECTURE.md
├── CHANGELOG.md
├── CONTRIBUTING.md
├── LICENSE
├── QUICKSTART.md
├── README.md
├── uninstall.php
└── wp-blog-agent.php
```

## Contributing

Contributions are welcome! Please read our [Contributing Guidelines](CONTRIBUTING.md) for details on:

- Code of conduct
- Development setup
- Coding standards
- Pull request process
- Testing requirements

## License

This plugin is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/np2023v2/wp-blog-agent).

## Credits

Developed by NP2023

## Changelog

### Version 1.0.0
- Initial release
- OpenAI GPT integration
- Google Gemini integration
- Topic management system
- Automated scheduling
- SEO optimization with keywords
- Hashtag support
- Auto-publish functionality
- Activity logging system
- Input validation and security
- Logs viewer interface
- Clean uninstall process