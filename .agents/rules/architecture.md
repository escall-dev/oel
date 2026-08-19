---
trigger: always_on
description: Explains the unified Document Logbook architecture and prevents reverting to the old split Incoming/Outgoing system.
---

# Unified Document Logbook Architecture

This project was recently refactored from a multi-direction tracking system (Incoming/Outgoing) to a unified **Document Logbook**. When making changes or additions to the codebase, adhere strictly to these architectural guidelines:

## Core Principles
1. **Directionality**: The system tracks document direction via "Incoming" and "Outgoing" Categories.
2. **Unified Interface**: The primary log interface is `document_log.php`. The old files `incoming.php`, `outgoing.php`, and `documents.php` have been deprecated and removed. Never reference them in navigation or code.
3. **Origin and Destination**: Use the `origin_source` column for the origin, and reintroduce/use the `recipient_office` (Office Destination) column for where it is going.

## Database Schema
The `documents` table MUST contain the `recipient_office` column. The `direction` column logic is superseded by using "Incoming" and "Outgoing" as Category values.
