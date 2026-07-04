# GrihasthiKart

# PROJECT GUIDE

Version : 1.0

Status : Approved

Last Updated : July 2026

---

# 1. Project Overview

GrihasthiKart is a premium grocery and FMCG eCommerce platform designed for the Indian market.

The project consists of:

• Customer Website

• Customer Mobile Experience (Responsive Web)

• Admin Panel

• Seller Management (Future)

• Delivery Management (Future)

The platform is intended to provide a modern shopping experience similar to Blinkit, Zepto and BigBasket while maintaining its own unique branding and business workflow.

---

# 2. Project Vision

Build one of India's most organized grocery platforms with emphasis on:

• Premium UI

• Fast Performance

• Simple Navigation

• Highly Scalable Architecture

• SEO Friendly

• Mobile First

• Clean Codebase

• Enterprise Ready

The platform should remain maintainable for many years.

---

# 3. Business Objectives

Primary objectives are:

• Grocery selling

• FMCG products

• Fresh Fruits

• Vegetables

• Dairy

• Personal Care

• Home Care

• Kitchen Essentials

• Snacks

• Beverages

• Baby Care

• Pet Supplies

Future expansion should support:

• Pharmacy

• Stationery

• Electronics

• Fashion

without changing architecture.

---

# 4. Project Goals

The software must provide:

Fast browsing

Fast searching

Easy checkout

Powerful admin

Coupon engine

Cashback engine

Inventory management

Supplier management

Order management

Customer management

Analytics

Reports

SEO

Scalability

Maintainability

---

# 5. Target Audience

Primary Audience

Indian households

Working professionals

Families

Students

Senior citizens

Small offices

Restaurants

Secondary Audience

Wholesale buyers

Corporate buyers

Business customers

Future marketplace sellers

---

# 6. Business Model

Current

B2C Grocery

Future

B2B

Marketplace

Franchise

Hyperlocal Delivery

Subscription Model

---

# 7. Technology Stack

Backend

Laravel 12

PHP 8.4+

Repository Pattern

Service Layer

REST principles

MySQL

Frontend

Blade Templates

Bootstrap 5

Vanilla JavaScript

HTML5

CSS3

Icons

Bootstrap Icons

Font Awesome

Image Storage

Laravel Storage

Public Storage

Admin Theme

Custom Bootstrap Admin

No external admin template dependency.

---

# 8. Design Philosophy

The website should look

Premium

Modern

Minimal

Professional

Fast

Trustworthy

Bright

Fresh

Simple

Never cluttered.

---

# 9. UI Inspiration

Design inspiration comes from

Blinkit

BigBasket

Zepto

Instamart

Amazon Grocery

but GrihasthiKart must maintain its own unique identity.

Never copy another website.

---

# 10. Branding

Brand Name

GrihasthiKart

Primary Color

Green

Secondary Color

Orange

Background

White

Theme

Fresh

Natural

Clean

Organic

Friendly

---

# 11. Official UI Reference

The official source of truth for UI is:

docs/02_UI_Design/Desktop_Homepage.png

docs/02_UI_Design/Mobile_Homepage.png

If generated code conflicts with these designs,

THE DESIGNS ALWAYS WIN.

---

# 12. UI Design Principles

The GrihasthiKart user interface must provide a premium shopping experience while maintaining simplicity, consistency and excellent usability.

The UI must follow these principles throughout the project.

## Premium Appearance

The interface should immediately convey trust, freshness and professionalism.

Avoid unnecessary decorative elements.

Use clean spacing and modern card layouts.

The interface should feel comparable to premium grocery applications while maintaining its own branding.

---

## Simplicity

Users should never require instructions to use the website.

Every action should be intuitive.

Important actions must always be visible.

Avoid hidden functionality.

---

## Consistency

Every page must follow the same design language.

Cards

Buttons

Typography

Spacing

Icons

Borders

Colors

Animations

must remain consistent throughout the application.

---

## Mobile First

Every page must work perfectly on

Desktop

Tablet

Mobile

Responsive behaviour is mandatory.

No desktop-only components should exist.

---

## Accessibility

Buttons must be easy to click.

Font sizes must remain readable.

Contrast should be sufficient.

Icons should always have labels where necessary.

---

## Performance

UI components should load quickly.

Images should be optimized.

Animations should be subtle.

Avoid heavy JavaScript frameworks.

---

# 13. Customer Website Structure

The customer website is divided into logical sections.

The primary sections include:

Home

Categories

Products

Product Details

Wishlist

Cart

Checkout

Orders

Account

Offers

Search

Support

Policy Pages

Static Pages

---

## Static Pages

About Us

Privacy Policy

Terms & Conditions

Shipping Policy

Return Policy

Cancellation Policy

Contact Us

FAQ

Disclaimer

---

# 14. Homepage Specification

The homepage is the most important page of the entire platform.

It serves as:

Storefront

Marketing page

Category discovery

Offer discovery

Search entry point

Brand promotion

Customer acquisition page

The homepage must remain lightweight despite containing multiple sections.

---

## Homepage Layout Order

The approved homepage layout shall follow this sequence.

1.
Top Header

2.
Main Navigation

3.
Search Bar

4.
Hero Banner Slider

5.
Primary Categories

6.
Homepage Category Sections

7.
Daily Offers

8.
Service Highlights

9.
Associated Partners

10.
Footer

This order should remain unchanged unless officially approved.

---

## Homepage Header

The header must contain:

Logo

Search

Account

Wishlist

Cart

WhatsApp

Call

The desktop and mobile layouts may differ, but the available functionality must remain the same.

---

## Search

Search is one of the highest priority features.

Search should support:

Products

Categories

Subcategories

Brands

Future:

SKU

Barcode

Voice Search

Search suggestions

Trending searches

Recent searches

---

## Hero Banner

The homepage contains a large promotional banner.

Banner supports:

Multiple slides

Desktop image

Mobile image

Start date

End date

Display priority

Clickable URL

Active/Inactive status

Banner scheduling

---

## Homepage Categories

Homepage category blocks are dynamic.

Admin controls:

Visibility

Order

Title

Products

Images

View All button

Sorting

Active status

Each section should be reusable.

---

# 15. Navigation System

Navigation should minimize user effort.

Maximum two clicks to discover any product.

The navigation system consists of:

Main Header

Search

Category Navigation

Breadcrumb

Footer Navigation

Internal Links

---

## Main Header

Desktop:

Logo

Search

Account

Wishlist

Cart

WhatsApp

Call

Mobile:

Logo

Cart

WhatsApp

Call

Search below header

Bottom Navigation

---

## Footer

Footer contains:

Company Information

Quick Links

Policies

Newsletter

Contact Form

Payment Methods

Copyright

Social Media

Trust Badges

Footer remains visible on every page.

---

# 16. Customer Journey

The intended customer journey is:

Homepage

↓

Search or Browse

↓

Category

↓

Product Listing

↓

Product Details

↓

Add to Cart

↓

Cart

↓

Checkout

↓

Payment

↓

Order Success

↓

Order Tracking

↓

Order History

This flow should require minimum user effort.

---

## Guest Users

Guest users may browse products.

Guest users may search.

Guest users may view offers.

Guest users must login before placing an order.

---

## Registered Customers

Registered customers can:

Manage Profile

Wishlist

Cart

Orders

Addresses

Coupons

Cashback

Notifications

Support

Account Settings

---

# 17. Functional Modules

The system consists of the following major modules.

Catalog Management

Inventory Management

Supplier Management

Customer Management

Authentication

Product Management

Category Management

Brand Management

Offer Management

Coupon Management

Cashback Management

Cart Management

Checkout Management

Order Management

Delivery Management

Reports

CMS

Settings

Each module must remain independent and scalable.

---

# 18. User Roles

The application supports multiple user roles.

Primary roles:

Super Administrator

Administrator

Store Manager

Inventory Manager

Customer Support

Delivery Staff (Future)

Seller (Future)

Customer

Every role must use Role Based Access Control (RBAC).

Permissions must never be hardcoded.

Future roles should be easily added without changing the existing architecture.

---

# 19. Admin Panel Philosophy

The Admin Panel is the operational backbone of GrihasthiKart.

It is designed for speed, simplicity, and scalability.

Every feature available to customers must be manageable through the Admin Panel.

The Admin Panel should not require technical knowledge to operate.

---

## Core Objectives

The Admin Panel shall provide complete control over:

- Catalog
- Products
- Categories
- Brands
- Inventory
- Suppliers
- Customers
- Orders
- Coupons
- Cashback
- Homepage
- Banners
- Offers
- Reports
- CMS Pages
- Settings
- Notifications

---

## Dashboard

The dashboard should present important business information immediately.

Examples include:

Today's Orders

Today's Revenue

Pending Orders

Delivered Orders

Cancelled Orders

Total Customers

Active Products

Low Stock Products

Out of Stock Products

Recent Orders

Recent Customer Registrations

Top Selling Products

Top Categories

Sales Graph

Revenue Graph

Inventory Alerts

Coupon Usage

Cashback Statistics

System Notifications

---

## Admin Design Principles

The Admin UI should be:

Simple

Professional

Fast

Minimal

Responsive

Consistent

Forms should avoid unnecessary fields.

Frequently used actions should require the fewest possible clicks.

---

# 20. Module Definitions

The application consists of independent modules.

Every module should be loosely coupled and easily maintainable.

Modules communicate only through Services and Repositories.

Modules must never directly manipulate another module's internal logic.

---

## Module List

Catalog

Products

Categories

Brands

Inventory

Suppliers

Customers

Authentication

Cart

Checkout

Orders

Coupons

Cashback

CMS

Reports

Settings

Notifications

Future Marketplace

Future Delivery Management

Future Franchise

Future Analytics

---

# 21. Product Management

Product Management is the most important business module.

Products represent sellable inventory.

Products must support future business expansion without structural changes.

---

## Product Information

Each product should support:

Product Name

Short Name

Slug

SKU

Barcode (Future)

Brand

Category

Subcategory

Description

Short Description

Product Images

Thumbnail

Gallery Images

Tags

Keywords

Unit

Weight

Dimensions

MRP

Selling Price

Cost Price

GST

HSN Code

Country of Origin

Manufacturer

Expiry Information

Shelf Life

Status

Visibility

Featured Product

New Arrival

Best Seller

Trending Product

Seasonal Product

Recommended Product

Meta Title

Meta Description

SEO Keywords

Search Keywords

---

## Product Images

Each product should support:

Primary Image

Multiple Gallery Images

Optimized Images

Future WebP Support

Automatic Thumbnail Generation

---

## Product Status

Draft

Active

Inactive

Out of Stock

Discontinued

Archived

---

## Product Visibility

Visible

Hidden

Homepage Featured

Category Featured

Offer Featured

Search Only

---

# 22. Category Management

Categories define the navigation hierarchy.

Categories should remain dynamic.

The system must support unlimited categories.

---

## Category Information

Category Name

Slug

Parent Category

Description

Image

Icon

Banner

Display Order

SEO Information

Visibility

Status

Homepage Display

---

## Homepage Categories

Homepage category sections are managed completely from the Admin Panel.

Administrator should control:

Section Title

Display Order

Products

Categories

Banner

View All Button

Visibility

Background Theme

---

## Category Hierarchy

Example:

Fruits & Vegetables

→ Vegetables

→ Fruits

→ Herbs & Seasoning

→ Fruit Baskets

→ Seasonal

→ Cut Fruits

→ Salads & Sprouts

The hierarchy must support future nesting.

---

# 23. Brand Management

Brands are independent entities.

Products belong to Brands.

Brands should support:

Brand Name

Logo

Description

Website

SEO

Featured Brand

Display Order

Status

Visibility

Brand Banner

Brand Slug

Future Brand Pages

---

# 24. Inventory Philosophy

Inventory must always represent actual stock.

Inventory management should remain independent from Product Management.

This separation allows future warehouse expansion.

---

## Inventory Features

Opening Stock

Current Stock

Reserved Stock

Available Stock

Minimum Stock

Maximum Stock

Reorder Level

Warehouse

Supplier

Purchase Price

Last Purchase Date

Stock History

Inventory Logs

Inventory Adjustment

Future Batch Tracking

Future Lot Tracking

Future Expiry Tracking

---

## Stock Status

In Stock

Low Stock

Out of Stock

Discontinued

---

## Inventory Rules

Inventory should never become negative.

Every stock movement must be logged.

Manual stock adjustments require remarks.

Inventory history should never be deleted.

---

# 25. Customer Management

Customers are valuable business assets.

The system should maintain complete customer profiles.

---

## Customer Information

Customer ID

Name

Email

Mobile Number

Password

Date of Birth

Gender

Addresses

Default Address

Referral Code (Future)

Wallet Removed

Cashback Balance

Order History

Wishlist

Coupons

Notifications

Support Requests

Status

Registration Date

Last Login

---

## Customer Status

Active

Inactive

Blocked

Pending Verification

Deleted (Soft Delete)

---

## Address Management

Each customer may have multiple addresses.

Each address contains:

House Number

Apartment

Street

Area

Landmark

City

State

Country

PIN Code

Mobile Number

Default Address

Address Type

---

# 26. Coupon & Cashback System

The project uses Coupons and Cashback.

Wallet functionality has been intentionally removed.

No Wallet module should be implemented unless officially approved in future.

---

## Coupon Types

Percentage Discount

Flat Discount

Free Delivery

Category Discount

Brand Discount

Product Discount

First Order Coupon

Festival Coupon

Referral Coupon (Future)

---

## Coupon Features

Coupon Code

Description

Validity

Usage Limit

Minimum Order Value

Maximum Discount

Applicable Categories

Applicable Brands

Applicable Products

Customer Restrictions

Active Status

Priority

---

## Cashback System

Cashback is independent from Coupons.

Cashback may be earned through:

Orders

Campaigns

Promotions

Festivals

Special Events

Future Referral Program

---

## Cashback Rules

Cashback expires according to campaign rules.

Cashback is non-transferable.

Cashback cannot be withdrawn as cash.

Cashback can only be redeemed during checkout.

Every cashback transaction must be logged.

The Admin Panel must provide a complete cashback ledger.

---

# 27. Order Management

Order Management is the central transaction module of GrihasthiKart.

Every order must be completely traceable from placement to completion.

Orders must never be permanently deleted.

---

## Order Lifecycle

Draft

↓

Pending Payment

↓

Payment Successful

↓

Order Confirmed

↓

Processing

↓

Packed

↓

Ready for Dispatch

↓

Out for Delivery

↓

Delivered

↓

Completed

Possible alternative paths:

Cancelled

Refund Initiated

Refund Completed

Returned

Rejected

Failed

---

## Order Information

Each order should store:

Order Number

Customer

Billing Address

Shipping Address

Order Date

Delivery Slot

Payment Method

Payment Status

Order Status

Coupon Applied

Cashback Used

Cashback Earned

Tax Details

Delivery Charges

Discount Amount

Grand Total

Invoice Number

Notes

Internal Remarks

---

## Order Rules

Every order receives a unique Order ID.

Order history must never be editable.

Price at purchase time must remain unchanged even if product prices change later.

Every order activity must be logged.

---

# 28. Cart Management

Cart is temporary storage before checkout.

Guest users may maintain a temporary cart.

Registered users should have synchronized carts.

---

## Cart Features

Add Product

Update Quantity

Remove Product

Move to Wishlist

Apply Coupon

Estimate Delivery Charges

Estimate Cashback

Save for Later (Future)

---

## Cart Rules

Cart should automatically validate:

Stock Availability

Minimum Quantity

Maximum Quantity

Product Status

Coupon Validity

Price Changes

---

# 29. Checkout Management

Checkout should require minimum user effort.

The checkout process should ideally be completed in a single page.

---

## Checkout Flow

Address

↓

Delivery Slot

↓

Order Summary

↓

Coupon

↓

Cashback

↓

Payment Method

↓

Review

↓

Place Order

---

## Payment Methods

Cash on Delivery

UPI

Credit Card

Debit Card

Net Banking

Wallets (Third-party payment wallets only)

Future BNPL

---

## Checkout Rules

Customer must be authenticated.

Products must be in stock.

Coupon must be valid.

Cashback must be validated.

Prices must be revalidated before order confirmation.

---

# 30. Wishlist Management

Wishlist allows customers to save products for future purchase.

---

## Wishlist Features

Add Product

Remove Product

Move to Cart

Availability Notification (Future)

Price Drop Notification (Future)

---

## Wishlist Rules

Wishlist belongs to customer account.

Guest wishlist is optional.

Wishlist should survive logout/login.

---

# 31. Search System

Search is one of the highest priority modules.

---

## Search Scope

Products

Categories

Subcategories

Brands

Offers

Future Voice Search

Future Barcode Search

Future Image Search

---

## Search Features

Autocomplete

Suggestions

Popular Searches

Recent Searches

Search History

Filters

Sorting

Pagination

---

## Search Filters

Category

Brand

Price Range

Discount

Availability

Newest

Best Selling

Rating (Future)

---

# 32. Offer Management

Offers are marketing campaigns managed by administrators.

Offers are independent of Coupons.

---

## Offer Types

Homepage Offers

Category Offers

Brand Offers

Festival Offers

Flash Sale

Daily Offers

Weekend Offers

Seasonal Offers

Clearance Offers

---

## Offer Configuration

Title

Description

Banner

Start Date

End Date

Priority

Visibility

Applicable Products

Applicable Categories

Applicable Brands

Status

---

# 33. Homepage Management

The homepage is fully dynamic.

Administrators should be able to modify homepage content without code changes.

---

## Homepage Components

Hero Slider

Category Sections

Offer Sections

Partner Section

Service Highlights

Footer

Homepage Banners

Featured Products

Trending Products

Best Sellers

New Arrivals

---

## Homepage Rules

Display order must be configurable.

Visibility must be configurable.

Every section should support activation/deactivation.

Sections should support scheduling where applicable.

---

# 34. Content Management System (CMS)

CMS manages all informational pages.

---

## CMS Pages

About Us

Contact Us

Privacy Policy

Terms & Conditions

Shipping Policy

Return Policy

Cancellation Policy

Disclaimer

FAQ

---

## CMS Features

Rich Text Editor

SEO Metadata

Banner Image

Slug

Status

Preview

Version History (Future)

---

# 35. Notifications

The system should support multiple notification channels.

---

## Notification Channels

Email

SMS

WhatsApp

In-App Notifications

Push Notifications (Future)

---

## Notification Events

Registration

OTP

Order Confirmation

Order Status

Payment Success

Payment Failure

Delivery Updates

Coupon Campaigns

Cashback Earned

Support Updates

---

# 36. Reports & Analytics

Reports provide business insights.

Reports should support export to Excel and PDF.

---

## Standard Reports

Sales Report

Order Report

Customer Report

Inventory Report

Supplier Report

Coupon Report

Cashback Report

Tax Report

Payment Report

Delivery Report

Low Stock Report

Out of Stock Report

Top Selling Products

Top Categories

Revenue Analysis

Profit Analysis (Future)

---

# 37. Settings

Settings centralize platform configuration.

---

## Setting Categories

General Settings

Store Information

Business Hours

Delivery Settings

Tax Settings

Payment Settings

Email Settings

SMS Settings

WhatsApp Settings

SEO Settings

Homepage Settings

Media Settings

Backup Settings

Security Settings

---

## Settings Rules

All settings must be editable from Admin Panel.

No configuration values should be hardcoded unless they are framework-level settings.

---

# 38. Logging & Audit Trail

Every important business operation must be recorded.

---

## Audit Log Examples

Product Created

Product Updated

Product Deleted

Category Updated

Coupon Created

Cashback Issued

Order Status Changed

Customer Updated

Settings Changed

Login

Logout

Failed Login

---

## Audit Rules

Audit logs are immutable.

Audit logs should include:

User

Timestamp

IP Address

Action

Affected Record

Previous Value

New Value

Reason (where applicable)

---
# 39. Security Architecture

Security is a first-class requirement throughout the GrihasthiKart platform.

Every module shall be designed following the principle of "Secure by Default."

---

## Security Principles

The application shall implement:

• Authentication

• Authorization

• CSRF Protection

• XSS Protection

• SQL Injection Protection

• Password Hashing

• Secure Session Management

• HTTPS Only

• Server-side Validation

• Audit Logging

Security must never depend solely on client-side validation.

---

## Authentication

The system shall use Laravel Authentication.

Passwords shall always be hashed.

Passwords shall never be stored in plain text.

Remember Me functionality may be enabled.

Future OTP login should be supported without redesigning the architecture.

---

## Authorization

Role Based Access Control (RBAC) shall be implemented.

Every administrative action must be permission-based.

Permissions shall never be hardcoded.

Future roles should be supported without changing existing modules.

---

## File Upload Security

Every uploaded file must be validated.

Allowed file types:

jpg

jpeg

png

webp

pdf (future)

Maximum upload size shall be configurable.

Uploaded files shall receive unique filenames.

Original filenames shall not be trusted.

---

## Database Security

Use Eloquent ORM whenever possible.

Parameterized queries shall be used.

Raw SQL should be avoided unless performance requires it.

Database credentials shall never be committed to Git.

---

# 40. Performance Requirements

Performance is one of the primary goals of GrihasthiKart.

Users should experience a fast and responsive website even under increasing load.

---

## Performance Objectives

Fast page loading

Optimized database queries

Optimized images

Efficient pagination

Minimal JavaScript

Server-side rendering

Caching where appropriate

---

## Target Performance

Homepage

≤ 2 seconds

Category Pages

≤ 2 seconds

Product Page

≤ 2 seconds

Checkout

≤ 3 seconds

Admin Dashboard

≤ 3 seconds

These are target values under normal production conditions.

---

## Optimization Strategy

Configuration caching

Route caching

View caching

Optimized Composer autoload

Image optimization

Lazy loading

Database indexing

Pagination

Future Redis caching

Future CDN

---

# 41. SEO Standards

Search Engine Optimization is mandatory.

The platform shall be SEO-friendly from the beginning.

---

## SEO Requirements

Human-readable URLs

Unique slugs

Meta Titles

Meta Descriptions

Canonical URLs

Open Graph metadata

Twitter Cards

Structured Data (future)

XML Sitemap

Robots.txt

Breadcrumb Schema (future)

---

## Product SEO

Every product should support:

SEO Title

SEO Description

SEO Keywords

Slug

Image ALT text

Schema support (future)

---

## Category SEO

Categories should support:

Slug

Meta Title

Meta Description

Keywords

Banner ALT text

---

# 42. Media & File Management

Media shall be centrally managed.

---

## Supported Media

Product Images

Category Images

Brand Logos

Homepage Banners

Offer Banners

Partner Logos

CMS Images

Company Logo

Favicons

Future Videos

---

## Image Standards

Images should be:

Optimized

Responsive

High Quality

Fast Loading

WebP-ready

Unique filenames

Stored using Laravel Storage.

---

## Folder Organization

Images shall be organized into dedicated directories.

Examples:

products/

categories/

brands/

banners/

cms/

partners/

company/

No mixed storage should be allowed.

---

# 43. Coding Architecture Overview

The application follows a layered architecture.

Presentation Layer

↓

Controller Layer

↓

Service Layer

↓

Repository Layer

↓

Model Layer

↓

Database

Each layer has a clearly defined responsibility.

---

## Presentation Layer

Responsible for:

Blade Templates

Bootstrap UI

Forms

Validation Messages

Client-side interactions

---

## Controller Layer

Responsible for:

Receiving Requests

Calling Services

Returning Responses

No business logic shall exist inside controllers.

---

## Service Layer

Responsible for:

Business Logic

Validation

Workflows

Transactions

Cross-module coordination

Every business rule belongs here.

---

## Repository Layer

Responsible only for data access.

Repositories shall not contain business rules.

Repositories shall use Eloquent models.

---

## Model Layer

Models define:

Relationships

Scopes

Accessors

Mutators

Casts

No business workflows should exist inside models.

---

# 44. Folder Structure Philosophy

The project structure should remain clean and predictable.

Every developer and AI coding assistant should understand the location of every component.

---

## Application Structure

app/

Console/

Exceptions/

Helpers/

Http/

Models/

Providers/

Repositories/

Services/

Traits/

Policies/

Observers/

Resources/

Future modules should follow the same structure.

---

## Resources Structure

resources/

views/

admin/

customer/

layouts/

components/

partials/

emails/

CSS

JavaScript

Images

All UI assets should remain organized.

---

# 45. Scalability Strategy

The project must support future growth without architectural redesign.

Future expansions include:

Multiple warehouses

Multiple cities

Marketplace

Seller Portal

Delivery Management

Native Mobile Apps

REST API

PWA

AI Recommendations

Analytics

Franchise Management

Internationalization

The architecture shall support these features with minimal structural change.

---

# 46. AI Development Workflow

Artificial Intelligence is considered part of the development workflow.

Every AI coding assistant must follow the approved documentation.

---

## AI Responsibilities

Read documentation before coding.

Never redesign approved UI.

Never rename modules without approval.

Follow Repository Pattern.

Follow Service Layer.

Follow Bootstrap 5.

Use Blade Templates.

Respect coding standards.

Implement production-quality code.

Avoid placeholders and unfinished implementations.

---

## AI Code Generation Rules

Before implementing any feature, AI shall read:

PROJECT_GUIDE.md

AI_RULES.md

DATABASE_STANDARDS.md

CODING_STANDARDS.md

UI_GUIDELINES.md

ROADMAP.md

Only after reading these documents should implementation begin.

---

# 47. Project Governance

This repository represents the official implementation of GrihasthiKart.

Documentation and source code must remain synchronized.

Business decisions shall be documented before implementation whenever practical.

Major architectural changes require updating the AI Knowledge Base.

---

## Version Control

Git shall be the official version control system.

Every milestone should be committed independently.

Meaningful commit messages shall be used.

Production releases shall be tagged.

---

## Documentation Priority

When conflicts exist, priority shall be:

1. Approved Business Decision

2. PROJECT_GUIDE.md

3. AI_RULES.md

4. UI_GUIDELINES.md

5. DATABASE_STANDARDS.md

6. CODING_STANDARDS.md

7. ROADMAP.md

8. Source Code

Source code shall be updated to match approved documentation.

---

# 48. Final Development Principles

Every implementation of GrihasthiKart shall follow these principles:

Business First

User First

Performance First

Security First

Scalability First

Maintainability First

Clean Architecture

Reusable Components

Consistent Design

Predictable Behaviour

Readable Code

Documented Decisions

Production Ready

The objective is not simply to create a working application.

The objective is to create a maintainable, enterprise-grade grocery commerce platform capable of supporting future business expansion while remaining easy to understand for both developers and AI coding assistants.

---

# End of PROJECT_GUIDE.md

Status: Approved

Version: 1.0

This document is the primary source of truth for the GrihasthiKart project.
