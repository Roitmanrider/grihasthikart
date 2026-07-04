# GrihasthiKart

# CODING_STANDARDS.md

Version: 1.0

Status: Approved

---

# Purpose

This document defines the official coding standards for GrihasthiKart.

All contributors, including AI coding assistants, shall follow these standards.

The objective is to maintain:

- Readability
- Consistency
- Maintainability
- Scalability
- Production-quality code

---

# PHP Standards

The project shall follow:

- PSR-12 Coding Style
- Laravel 12 Best Practices

Use:

- Typed properties
- Return type declarations
- Constructor Property Promotion where appropriate

Avoid deprecated PHP features.

---

# General Principles

Write code that is:

Simple

Readable

Reusable

Testable

Maintainable

Avoid clever code.

Prefer understandable code over shorter code.

---

# SOLID Principles

All code should follow SOLID.

Single Responsibility Principle

Open/Closed Principle

Liskov Substitution Principle

Interface Segregation Principle

Dependency Inversion Principle

---

# DRY Principle

Never duplicate business logic.

Shared logic should be extracted into:

Services

Traits

Helpers

Reusable Components

---

# KISS Principle

Keep implementation simple.

Avoid unnecessary abstraction.

Avoid premature optimization.

---

# Folder Structure

Application code shall remain inside:

app/

Repositories/

Services/

Models/

Http/

Policies/

Providers/

Traits/

Helpers/

Observers/

Resources/

Future modules shall follow the same structure.

---

# Controllers

Controllers should:

Receive Requests

Call Services

Return Responses

Maximum responsibility:

HTTP handling.

Controllers shall never:

Query database directly

Calculate prices

Validate coupons

Reserve inventory

Perform checkout

Write reports

---

# Services

Every business workflow belongs inside Services.

Examples:

CheckoutService

OrderService

InventoryService

CouponService

CashbackService

HomepageService

SettingsService

NotificationService

Services may call multiple repositories.

---

# Repositories

Repositories are responsible for:

Database Queries

Filtering

Searching

Sorting

Pagination

CRUD

Repositories shall never:

Calculate totals

Validate coupons

Reserve inventory

Implement business workflows

# Interfaces

Every repository shall have an interface.

Example:

ProductRepositoryInterface

CategoryRepositoryInterface

OrderRepositoryInterface

Interfaces improve:

Maintainability

Testing

Dependency Injection

---

# Models

Models contain:

Relationships

Scopes

Attribute Casts

Accessors

Mutators

Small helper methods

Models should not contain business workflows.

---

# Form Requests

Validation belongs in Form Requests.

Examples:

StoreProductRequest

UpdateProductRequest

StoreCategoryRequest

UpdateCategoryRequest

Avoid validation directly inside controllers.

---

# Routes

Use Resource Routes whenever practical.

Group routes logically.

Protect routes using middleware.

Avoid duplicate routes.

---

# Blade Templates

Blade is mandatory.

Views should remain clean.

Avoid business logic inside Blade.

Extract reusable components.

---

# Bootstrap

Bootstrap 5 is the official UI framework.

Do not replace Bootstrap.

Custom CSS should extend Bootstrap rather than override extensively.

---

# JavaScript

Use Vanilla JavaScript.

Prefer modular scripts.

Avoid unnecessary dependencies.

Future modules should remain framework-independent.

# Naming Standards

Classes

PascalCase

Examples:

ProductService

OrderController

InventoryRepository

Methods

camelCase

Examples:

createProduct()

updateInventory()

calculateCashback()

Variables

camelCase

Examples:

$product

$orderTotal

$availableStock

Constants

UPPER_SNAKE_CASE

Examples:

MAX_CART_ITEMS

DEFAULT_TAX_RATE

---

# Database Access

Always use Eloquent Models.

Use eager loading where appropriate.

Avoid N+1 queries.

Use transactions for multi-table updates.

---

# Exception Handling

Throw meaningful exceptions.

Do not suppress exceptions silently.

Log unexpected failures.

Return user-friendly messages.

---

# Logging

Log:

System errors

Critical failures

Order events

Inventory adjustments

Authentication events

Do not log passwords or sensitive information.

---

# Configuration

Configuration values belong in:

config/

.env

Never hardcode:

API Keys

Credentials

Business constants

URLs

Email addresses

# Security

Always validate input.

Escape output.

Hash passwords.

Use CSRF protection.

Authorize administrative actions.

Validate uploaded files.

Never trust client-side data.

---

# Performance

Use pagination.

Use eager loading.

Optimize queries.

Optimize images.

Cache configuration in production.

Do not optimize prematurely.

---

# Documentation

Every major module shall include:

Purpose

Responsibilities

Dependencies

Business Rules

Documentation shall evolve with the project.

---

# Code Reviews

Every implementation should be reviewed for:

Architecture

Naming

Security

Performance

Readability

Maintainability

Business Rule Compliance

---

# Definition of Production Ready

Code is production ready only when:

- Compiles successfully
- Passes validation
- Matches approved UI
- Follows Repository Pattern
- Uses Service Layer
- Has meaningful naming
- Contains no placeholder code
- Contains no TODO comments
- Documentation is updated

---

# End of CODING_STANDARDS.md

Version: 1.0

Status: Approved
