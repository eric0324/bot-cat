# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bot Cat is a WordPress plugin that sends automated chatbot notifications via LINE, Telegram, and Slack when specific WordPress events occur.

## Architecture

The plugin uses PSR-4 autoloading with the `BotCat` namespace:

- **Entry Point**: `bot-cat.php` - Main plugin file
- **Core Classes**:
  - `BotCatInitializer`: Central initialization class in includes/BotCatInitializer.php
  - `BotCatConstants`: Plugin constants in includes/BotCatConstants.php
- **Directory Structure**:
  - `/includes/Api/`: REST API endpoints for OAuth authentication
  - `/includes/Service/`: Business logic for each platform and core services
  - `/includes/View/`: Admin interface and frontend components

## Development Commands

Currently, there are no build, test, or lint commands configured. Development tasks:

```bash
# Install dependencies (if any are added)
composer install

# Update autoloader after adding new classes
composer dump-autoload
```

## Key Integration Points

1. **WordPress Hooks**: Posts, comments, and user registration events
2. **REST API Routes**: OAuth callbacks for each platform
3. **Shortcode**: `[bot-cat-oauth]` for OAuth authentication buttons

## Technical Requirements

- PHP 8.2+
- WordPress 6.4.2+
- Requires API key from bot-cat.com console

## Important Files

- **Service Layer**: Platform-specific implementations in `/includes/Service/`:
  - `BotCatLineService.php`: LINE messaging
  - `BotCatTelegramService.php`: Telegram messaging
  - `BotCatSlackService.php`: Slack messaging
  - `BotCatNotificationService.php`: Core notification dispatch

- **API Layer**: OAuth endpoints in `/includes/Api/`:
  - Each platform has its own authentication API class
  - `BotCatMessageApi.php`: Handles incoming messages

- **Views**: Admin panels in `/includes/View/`:
  - Platform-specific admin views for configuration
  - Partial views for reusable components