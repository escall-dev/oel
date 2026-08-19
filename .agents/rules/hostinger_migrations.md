---
trigger: always_on
description: Ensures that any local database changes are accompanied by a standalone SQL script for production deployment on Hostinger.
---

# Hostinger SQL Migrations

Whenever you make changes to the database schema (e.g., adding tables, adding columns) or perform data migrations (e.g., inserting seed data, updating categories) on the local machine:
1. You MUST always create a standalone `.sql` script containing the precise SQL commands needed to replicate the changes.
2. Store these scripts inside the `c:\xampp\htdocs\oel\sql schemas\` directory. Create this directory if it does not exist.
3. Name the files sequentially or descriptively (e.g., `01_add_remarks_action.sql`, `02_update_categories.sql`) so the user can easily run them one by one in their Hostinger phpMyAdmin panel.
4. Never assume that running the SQL locally is sufficient; the user relies on these scripts to deploy changes to their live server.
