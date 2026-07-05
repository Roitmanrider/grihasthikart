# 🛒 GrihasthiKart

Enterprise Grocery & FMCG eCommerce Platform

---

## Project Status

Current Version

Version 1.0 (Development)

Current Milestone

Milestone 2 — Complete Catalog

Project Status

Active Development

---

# Technology Stack

Backend

- Laravel 12
- PHP 8.4+
- MySQL 8+

Frontend

- Blade
- Bootstrap 5
- Vanilla JavaScript
- HTML5
- CSS3

Architecture

- Repository Pattern
- Service Layer
- RBAC
- REST Principles

---

# Repository Structure

```
app/
bootstrap/
config/
database/
public/
resources/
routes/

docs/
```

---

# Documentation

## Business Documentation

```
docs/01_Project_Documentation/
```

Contains the official business specification.

---

## Deployment

```
docs/08_Deployment/
```

Contains Hostinger deployment preparation, production environment checklist, File Manager upload workflow, rollback plan, and post-deployment tests.

Release packaging helpers:

```
scripts/create-release-package.ps1
```

Use the script only after tests, Pint, and Vite build pass. It creates a local release ZIP under `releases/` and does not include real `.env` files.

---

## UI

```
docs/02_UI_Design/
```

Contains approved Desktop and Mobile UI.

---

## AI Knowledge Base

```
docs/03_AI_Knowledge/
```

Contains

PROJECT_GUIDE

PROJECT_CONTEXT

AI_RULES

ROADMAP

DATABASE_STANDARDS

CODING_STANDARDS

UI_GUIDELINES

IMPLEMENTATION_WORKFLOW

---

## AI Context

```
docs/04_AI_Context/
```

Contains

Business Decisions

Change History

Deferred Features

---

# Current Development Roadmap

✅ Foundation

⬜ Complete Catalog

⬜ Inventory & Suppliers

⬜ Customers

⬜ Cart & Checkout

⬜ Orders

⬜ Coupons & Cashback

⬜ Reports & Settings

⬜ Production Deployment

---

# Development Principles

Every implementation must follow

PROJECT_GUIDE.md

AI_RULES.md

DATABASE_STANDARDS.md

CODING_STANDARDS.md

UI_GUIDELINES.md

IMPLEMENTATION_WORKFLOW.md

ROADMAP.md

---

# Project Philosophy

Maintainability First

Business First

Security First

Scalability First

Performance First

Clean Architecture

Production Ready

---

# License

Private Project

All Rights Reserved.
