# GrihasthiKart

# IMPLEMENTATION_WORKFLOW.md

Version: 1.0

Status: Approved

---

# Purpose

This document defines the mandatory workflow that every developer and every AI coding assistant must follow when working on the GrihasthiKart repository.

The objective is to ensure:

• Consistency

• Predictability

• High Code Quality

• Maintainability

• Production Readiness

AI shall never begin implementation without following this workflow.

---

# Phase 1
Project Understanding

Before writing code, AI shall read the following documents in order:

1. PROJECT_GUIDE.md

2. PROJECT_CONTEXT.md

3. AI_RULES.md

4. DATABASE_STANDARDS.md

5. CODING_STANDARDS.md

6. UI_GUIDELINES.md

7. ROADMAP.md

Only after reading all required documents shall implementation begin.

---

# Phase 2
Understand Current Repository

AI shall inspect:

Project Structure

Existing Models

Repositories

Services

Controllers

Routes

Views

Migrations

Policies

Seeders

Factories

Existing Coding Style

Never assume a module does not exist.

Always inspect first.

---

# Phase 3
Identify Target Milestone

Determine:

Current Milestone

Dependencies

Required Deliverables

Acceptance Criteria

Never begin work outside the approved milestone.

---

# Phase 4
Planning

Before implementation AI shall prepare an internal implementation plan.

Example:

Database

↓

Models

↓

Repositories

↓

Services

↓

Controllers

↓

Validation

↓

Views

↓

Routes

↓

Testing

↓

Documentation

↓

Commit

Never implement randomly.

---

# Phase 5
Database

If database changes are required:

Create new migration.

Never modify previous production migrations.

Create indexes.

Create foreign keys.

Follow DATABASE_STANDARDS.md.

Run migration mentally before generating.

---

# Phase 6
Models

Create Models.

Relationships.

Scopes.

Accessors.

Mutators.

Attribute Casting.

Factories.

Policies if required.

Models shall remain lightweight.

---

# Phase 7
Repositories

Create:

Repository Interface

Repository Implementation

Repositories handle only:

CRUD

Queries

Pagination

Filtering

Searching

Repositories shall not contain business logic.

---

# Phase 8
Services

Services implement:

Business Rules

Validation

Transactions

Cross-module coordination

Every workflow belongs here.

---

# Phase 9
Controllers

Controllers:

Receive Request

Call Service

Return Response

No business logic.

No SQL.

No calculations.

No inventory logic.

No coupon logic.

---

# Phase 10
Validation

Create Form Requests.

Validate:

Required fields

Data Types

Uniqueness

Business Rules

Relationships

Never rely on client-side validation.

# Phase 11
Views

Create:

Blade Templates

Bootstrap Components

Responsive Layout

Desktop

Tablet

Mobile

Views shall follow UI_GUIDELINES.md.

---

# Phase 12
Routes

Use Resource Routes whenever practical.

Group routes logically.

Protect routes with Middleware.

Avoid duplicate routes.

---

# Phase 13
Testing

Verify:

CRUD

Validation

Relationships

Calculations

Navigation

Permissions

Edge Cases

Do not consider implementation complete without verification.

---

# Phase 14
Documentation

If implementation changes:

Architecture

Business Rules

Database

UI

Workflow

Update documentation.

Documentation is part of development.

---

# Phase 15
Review

Before considering work complete:

Review Naming

Review Formatting

Review Architecture

Review Security

Review Performance

Review Maintainability

Refactor if required.

---

# Phase 16
Git

Prepare meaningful commits.

Example:

feat(category): complete category management

fix(cart): resolve quantity validation

refactor(order): move business logic to service

Avoid generic commit messages.

---

# Phase 17
Completion Checklist

Every feature shall include:

Migration

Model

Repository Interface

Repository

Service

Controller

Validation

Routes

Views

Policies

Factories

Seeders

Documentation

No TODO comments.

No placeholder implementations.

No unfinished methods.

---

# Phase 18
Definition of Done

A feature is complete only if:

Business Rules Implemented

UI Matches Approved Design

Repository Pattern Followed

Service Layer Followed

Validation Complete

Documentation Updated

Code Reviewed

Ready for Production

---

# AI Behaviour

AI shall:

Read first.

Think second.

Plan third.

Implement fourth.

Review fifth.

Never reverse this order.

---

# AI Prohibitions

AI shall never:

Invent Architecture

Redesign UI

Rename Modules

Change Folder Structure

Ignore Documentation

Skip Validation

Duplicate Logic

Hardcode Configuration

Generate Placeholder Code

Leave Incomplete Features

Modify Completed Milestones without approval

---

# Repository Philosophy

The repository is the single source of truth.

Documentation and source code must always remain synchronized.

Every change must improve the project.

Never decrease code quality.

---

# End of IMPLEMENTATION_WORKFLOW.md

Version: 1.0

Status: Approved
