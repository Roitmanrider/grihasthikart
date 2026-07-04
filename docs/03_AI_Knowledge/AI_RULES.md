# GrihasthiKart

# AI_RULES.md

Version: 1.0

Status: Approved

---

# 1. Purpose

This document defines the mandatory implementation rules for GrihasthiKart.

Every AI coding assistant working on this repository must read this document before modifying any source code.

PROJECT_GUIDE.md explains the project.

AI_RULES.md explains how the project must be implemented.

---

# 2. Highest Priority Rule

Never assume.

If the documentation defines behaviour,

the documentation wins.

If documentation conflicts with generated code,

generated code must be corrected.

---

# 3. Approved Technology Stack

The project shall use:

Laravel 12

PHP 8.4+

MySQL 8+

Blade Templates

Bootstrap 5

Vanilla JavaScript

CSS3

HTML5

Laravel Storage

Bootstrap Icons

Font Awesome

---

The following technologies shall NOT be introduced without explicit approval:

Livewire

Inertia.js

Vue.js

React

Angular

Tailwind CSS

Alpine.js

Next.js

Nuxt.js

External Admin Templates

Heavy JavaScript frameworks

---

# 4. Architecture Rules

Mandatory architecture:

Presentation Layer

↓

Controller

↓

Service

↓

Repository

↓

Model

↓

Database

Every layer has a single responsibility.

No shortcuts.

---

# 5. Repository Pattern

Repository Pattern is mandatory.

Repositories handle only data access.

Repositories shall never contain:

Business Logic

Validation

UI Logic

Authorization

Workflow Logic

---

# 6. Service Layer

Business logic belongs only inside Services.

Examples:

Checkout

Coupon Validation

Cashback

Order Placement

Inventory Updates

Pricing

Delivery Charge

Order Cancellation

Refund Processing

Repositories must never implement these rules.

---

# 7. Controllers

Controllers must remain thin.

Controllers shall:

Receive Request

Call Service

Return Response

Controllers shall NOT:

Calculate prices

Update inventory

Validate coupons

Calculate cashback

Write business workflows

# 8. Models

Models shall contain:

Relationships

Scopes

Accessors

Mutators

Attribute Casting

Small helper methods

Models shall NOT:

Perform checkout

Generate invoices

Validate coupons

Reserve inventory

Perform business workflows

---

# 9. Blade Templates

Blade is mandatory.

Views shall remain clean.

Views must never contain business logic.

Avoid complex PHP inside Blade.

---

# 10. Bootstrap

Bootstrap 5 is mandatory.

Custom CSS should extend Bootstrap.

Do not replace Bootstrap with another CSS framework.

---

# 11. JavaScript

Use Vanilla JavaScript.

Avoid unnecessary libraries.

Do not introduce jQuery plugins unless approved.

Every script should have a clear purpose.

---

# 12. Database

Use MySQL.

Use Laravel migrations.

Use foreign keys.

Use indexes where appropriate.

Soft Deletes should be used where business data should remain recoverable.

Never delete transactional history.

---

# 13. Naming Standards

Use singular Model names.

Examples:

Product

Category

Brand

Order

Customer

Use plural table names.

products

categories

brands

orders

customers

Use meaningful variable names.

Avoid abbreviations.

---

# 14. Validation

Always validate server-side.

Never rely solely on JavaScript validation.

Use Laravel Form Requests whenever practical.

# 15. Authentication

Laravel Authentication is mandatory.

Passwords must always be hashed.

Sessions must remain secure.

Authorization shall use RBAC.

---

# 16. UI Rules

The approved Desktop Homepage and Mobile Homepage are the official UI references.

AI shall not redesign them.

AI shall not:

Move sections

Change layout

Replace colors

Introduce different navigation

Remove approved components

UI improvements require explicit approval.

---

# 17. Homepage Rules

Homepage section order shall remain:

Header

Hero Banner

Category Ribbon

Category Sections

Daily Offers

Service Highlights

Associated Partners

Footer

The approved order shall not change without approval.

---

# 18. Business Rules

Wallet module has been removed.

Cashback replaces Wallet.

Coupons and Cashback are independent systems.

Only one coupon per order in Version 1.

Cashback is earned only after eligible delivered orders.

Cashback cannot be withdrawn as cash.

# 19. Performance Rules

Avoid N+1 queries.

Use eager loading where appropriate.

Use pagination.

Optimize images.

Cache configuration in production.

Never sacrifice maintainability for premature optimization.

---

# 20. Security Rules

Protect against:

CSRF

XSS

SQL Injection

Session Fixation

Validate uploaded files.

Never trust user input.

---

# 21. Git Rules

Every milestone shall be committed independently.

Commit messages shall be meaningful.

Avoid unrelated changes in a single commit.

---

# 22. Documentation Rules

If architecture changes,

update documentation.

If business rules change,

update documentation.

Documentation is part of the project.

---

# 23. AI Behaviour Rules

Before coding,

AI shall read:

PROJECT_GUIDE.md

AI_RULES.md

DATABASE_STANDARDS.md

CODING_STANDARDS.md

UI_GUIDELINES.md

ROADMAP.md

AI shall never begin implementation without reading project knowledge.

---

# 24. Never Do List

Never redesign approved UI.

Never rename modules without approval.

Never replace Bootstrap.

Never replace Blade.

Never introduce Livewire.

Never introduce Inertia.

Never introduce React.

Never remove Repository Pattern.

Never remove Service Layer.

Never hardcode configuration.

Never hardcode business rules.

Never skip validation.

Never delete transactional history.

---

# 25. Definition of Done

A feature is complete only when it includes:

Migration

Model

Repository Interface

Repository

Service

Validation

Controller

Routes

Blade Views

Admin UI

Customer UI (where applicable)

Tests (where applicable)

Documentation update

No TODO comments

No placeholder code

Production-ready quality

---

# End of AI_RULES.md

Status: Approved

Version: 1.0
