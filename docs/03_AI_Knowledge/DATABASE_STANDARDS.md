# GrihasthiKart

# DATABASE_STANDARDS.md

Version 1.0

Status : Approved

---

# Purpose

This document defines every database standard used throughout GrihasthiKart.

Every migration

Every model

Every repository

Every service

must follow these standards.

---

# Database Engine

MySQL 8+

Storage Engine

InnoDB

Character Set

utf8mb4

Collation

utf8mb4_unicode_ci

Timezone

UTC in database

Application timezone configurable.

---

# Naming Convention

Database

snake_case

Tables

plural

Examples

products

categories

orders

customers

Columns

snake_case

Examples

created_at

updated_at

selling_price

category_id

Model

Singular

Example

Product

Category

Order

Customer

---

# Primary Keys

Every table shall contain

id

BIGINT

Unsigned

Auto Increment

Primary Key

Laravel default.

UUID support may be added in future.

---

# Foreign Keys

Every relationship shall use foreign keys.

Examples

category_id

brand_id

supplier_id

customer_id

order_id

product_id

Foreign key constraints are mandatory unless technically impossible.

---

# Timestamp Policy

Every business table shall contain

created_at

updated_at

Soft deletes where applicable.

---

# Soft Delete Policy

Soft Deletes SHALL be used for

Products

Categories

Brands

Customers

Suppliers

CMS

Coupons

Cashback Campaigns

Soft Deletes SHALL NOT be used for

Order Items

Inventory Transactions

Cashback Ledger

Audit Logs

Payment History

These are permanent records.

---

# Audit Philosophy

Historical business data shall never be deleted.

Inventory history

Order history

Cashback history

Coupon usage

Payment logs

shall remain permanently.

---

# Column Naming

Foreign Keys

category_id

brand_id

supplier_id

customer_id

order_id

Booleans

is_active

is_featured

is_visible

is_homepage

Dates

start_date

end_date

expiry_date

published_at

Prices

mrp

selling_price

cost_price

discount_price

Tax

gst_rate

gst_amount

# Status Columns

Use ENUM or controlled constants where practical.

Examples

status

visibility

payment_status

order_status

---

# Slugs

Every SEO entity shall contain

slug

Examples

Products

Categories

Brands

CMS

Offers

Coupons (optional)

Slug shall be unique.

---

# SEO Columns

Every SEO entity should support

meta_title

meta_description

meta_keywords

---

# Image Columns

Preferred

image

thumbnail

banner

gallery handled separately.

Avoid

image1

image2

image3

---

# Gallery Images

Gallery images shall use separate tables.

Example

product_images

category_images (future)

banner_images (future)

Never store multiple filenames in one column.

---

# Monetary Values

Use

DECIMAL

Never FLOAT.

Examples

DECIMAL(10,2)

DECIMAL(12,2)

---

# Quantity

Use INT.

Never VARCHAR.

---

# JSON Usage

JSON columns may be used only where appropriate.

Examples

future API response cache

future settings

future search filters

Do not replace relational design with JSON.

---

# Index Strategy

Indexes should exist on

slug

sku

barcode

email

mobile

category_id

brand_id

supplier_id

customer_id

status

created_at

Composite indexes should be introduced only when supported by query analysis.

# Relationships

Use Eloquent Relationships.

Examples

hasMany

belongsTo

belongsToMany

hasOne

morphMany (future)

Avoid manual joins unless performance requires it.

---

# Transactions

Database Transactions shall be used for

Checkout

Order Creation

Inventory Update

Cashback

Coupon Redemption

Purchase Entry

Stock Adjustment

Any operation affecting multiple tables.

---

# Migration Rules

One migration

One responsibility.

Migration names should remain descriptive.

Never edit production migrations.

Always create new migrations.

---

# Seeder Rules

Seeders shall exist for

Categories

Brands

Settings

Permissions

Roles

Demo Products

Development Data

Production seeders shall never overwrite business data.

---

# Factory Rules

Factories should exist for

Product

Category

Brand

Customer

Order

Supplier

Inventory

Factories are intended for testing and development.

---

# Reserved Tables

Future tables reserved:

warehouses

warehouse_stock

delivery_partners

delivery_routes

seller_accounts

seller_products

subscriptions

reviews

ratings

referrals

Do not use these names for other purposes.

---

# End of DATABASE_STANDARDS.md

Status : Approved

Version : 1.0
