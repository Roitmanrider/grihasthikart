# GrihasthiKart

# PROJECT_CONTEXT.md

Version: 1.0

Status: Approved

---

# Purpose

This document explains the historical decisions, architectural choices, business priorities, and design philosophy behind GrihasthiKart.

Unlike PROJECT_GUIDE.md, which defines the project, this document explains *why* important decisions were made.

AI coding assistants should read this document before proposing architectural changes.

---

# Project Background

GrihasthiKart is intended to become a scalable grocery and FMCG eCommerce platform for the Indian market.

The project is designed for long-term growth rather than rapid prototyping.

The objective is to create an enterprise-grade application that can evolve over many years without major architectural rewrites.

---

# Long-Term Vision

The initial release focuses on B2C grocery commerce.

However, the architecture must support future expansion into:

- B2B
- Marketplace
- Hyperlocal Delivery
- Multiple Warehouses
- Multiple Cities
- Franchise Operations
- Native Mobile Applications
- Progressive Web App (PWA)
- AI-assisted recommendations

These future capabilities influenced the current architecture even if they are not part of Version 1.

---

# Technology Decisions

## Laravel

Laravel was selected because it provides:

- Long-term maintainability
- Strong ecosystem
- Excellent community support
- Mature ORM
- Scalable architecture
- Clean routing
- Strong security features

---

## Blade Templates

Blade was chosen because:

- Server-side rendering improves SEO.
- It reduces frontend complexity.
- It integrates naturally with Laravel.
- It keeps deployment simple.
- It minimizes JavaScript dependencies.

---

## Bootstrap 5

Bootstrap was selected because:

- Stable ecosystem
- Responsive grid
- Fast development
- Wide browser compatibility
- Large component library
- Long-term maintainability

The project intentionally avoids replacing Bootstrap with another CSS framework.

---

## Repository Pattern

Repository Pattern was selected because it:

- Separates data access from business logic.
- Improves maintainability.
- Simplifies testing.
- Makes future data source changes easier.

Repositories should remain focused on persistence.

---

## Service Layer

Business logic belongs in Services.

This keeps Controllers lightweight and Models focused on domain representation.

Complex workflows such as checkout, coupon validation, cashback calculation, and inventory updates should always be implemented in Services.

---

# Business Decisions

## Wallet Removed

The Wallet module was intentionally removed.

Reasons:

- Reduce implementation complexity.
- Reduce accounting complexity.
- Simplify compliance.
- Focus on faster delivery.

---

## Cashback Introduced

Cashback replaces Wallet for customer rewards.

Cashback:

- Cannot be withdrawn.
- Can only be redeemed during checkout.
- Is fully auditable.
- Is campaign-driven.

---

## Coupons

Coupons remain independent of Cashback.

Version 1 supports one coupon per order.

Future versions may introduce advanced coupon stacking if business requirements change.

---

# UI Philosophy

The approved Desktop Homepage and Mobile Homepage are considered the visual foundation of the platform.

Consistency is preferred over experimentation.

Future pages should extend the same design language rather than introducing new visual styles.

---

# Project Philosophy

The project values:

- Maintainability over shortcuts.
- Simplicity over unnecessary complexity.
- Predictability over clever implementations.
- Business correctness over technical novelty.
- Long-term scalability over rapid hacks.

---

# AI Expectations

AI coding assistants are expected to:

- Respect documented business decisions.
- Preserve architectural consistency.
- Follow the approved UI.
- Produce production-ready implementations.
- Avoid introducing technologies outside the approved stack.

When documentation conflicts with assumptions, documentation always takes precedence.

---

# Future Evolution

As the project grows, this document should record the rationale behind major architectural and business decisions.

Its purpose is to preserve project knowledge for both developers and AI systems.

---

# End of PROJECT_CONTEXT.md

Version: 1.0

Status: Approved
