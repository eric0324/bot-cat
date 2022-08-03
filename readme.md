# BotCat

Simply send chatbot notifications via plugins

## Description

[BotCat](https://bot-cat.com) plugin is the ultimate way to send chatbot message via *LINE*, *LINE Notify*, *Telegram*, and others.

### Installation

1. Install the "BotCat" plugin
2. Activate the plugin through the **Plugins** screen (**Plugins > Installed Plugins**)
3. After the plugin has been activated, going to **BotCat -> Basic**.
4. If you already have and key, Input your key. If you don’t already have a key, then go to [BotCat Console](https://bot-cat.com/console), and  generate key.

## Supported extensions and events

The [BotCat](https://bot-cat.com) plugin for WordPress supports core WordPress events, plus events from such popular plugins as **WooCommerce**. See the full list of supported solutions and events below:

### WordPress

* Post
    * Post Published
    * Post Sent for Review

* Comment
    * Comment Added

* User
    * User Registered

### WooCommerce

* Order
    * New Order

* Product
    * New Product
    * Low Stock
    * Out Of Stock
    * On Backorder

## Changelog

### 1.0.0
* Initial version.

### 1.0.1
* Fixed bc_function name bug.

### 1.0.2
* Fixed some bug.

### 1.0.3
* Use `action-wordpress-plugin-deploy` to release

### 1.0.4
* Set in the admin whether to show the OAuth button on the profile page (for roles)
