# Pak Travel Buddy - Explore, Connect, Travel

Pak-travel-final is a travel-group coordination application.

![PHP](https://img.shields.io/badge/PHP-2563EB?style=flat-square) ![MySQL](https://img.shields.io/badge/MySQL-0F172A?style=flat-square)

## Overview

The data model covers `groups`, `group_chats`, `group_members`, `join_requests`, `users`.

## Application areas

| Area | Pages in the repository |
| --- | --- |
| Root Pages | [`chat.php`](chat.php), [`create_group.php`](create_group.php), [`edit_group.php`](edit_group.php), [`home.php`](home.php), [`login.php`](login.php) |

## Getting started

### 1. Get the project

```bash
git clone https://github.com/nasirabbas827/Pak-travel-final.git
cd Pak-travel-final
```

### 2. Configure PHP and the database

1. Use a local PHP server with MySQL or MariaDB, such as XAMPP.
2. Import [`Database/travel_db.sql`](Database/travel_db.sql) into your local database server.
3. Set your local database connection details in [`config.php`](config.php).
4. Keep the existing asset and upload directory paths intact.

### 3. Start the application

With the database running, start a local development server from the repository root:

```bash
php -S 127.0.0.1:8000
```

Open http://127.0.0.1:8000/index.php. If using Apache/XAMPP instead, place the project in its document root and open the matching localhost folder.

## Repository guide

| Path | Purpose |
| --- | --- |
| [`Database`](Database) | Database scripts |
| [`config.php`](config.php) | Application/database configuration |
| [`css`](css) | Stylesheets |
| [`images`](images) | Image assets |
| [`index.php`](index.php) | Web entry page |

## Development notes

These setup instructions are based on the repository source. The application has not been executed as part of this documentation update; verify dependencies and configuration in your local environment.
