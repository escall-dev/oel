---
trigger: always_on
description: Explains the unified Document Logbook architecture and prevents reverting to the old split Incoming/Outgoing system.
---

# Unified Document Logbook Architecture

This project was recently refactored from a multi-direction tracking system (Incoming/Outgoing) to a unified **Document Logbook**. When making changes or additions to the codebase, adhere strictly to these architectural guidelines:

## Core Principles
1. **No Directionality**: The system no longer tracks whether a document is "Incoming" or "Outgoing". Do not add, reference, or expect a `direction` column in the database or the UI.
2. **Unified Interface**: The primary log interface is `document_log.php`. The old files `incoming.php`, `outgoing.php`, and `documents.php` have been deprecated and removed. Never reference them in navigation or code.
3. **Origin/Source Only**: Use the `origin_source` column to record where a document came from or where it is going. Do not use or reintroduce `recipient_office`.

## Forbidden Terminology
- Avoid terms like "Incoming Log", "Outgoing Log", "Log Incoming", or "Log Outgoing" in the UI. 
- Use "Document Log", "Log Document", or "Log Entry" instead.

## Database Schema
The `documents` table MUST NOT contain the following columns:
- `direction`
- `recipient_office`
All queries to the database must omit these fields.
