# Staff Subdomain Deployment

Do not deploy from this milestone note. This is the future Hostinger setup plan.

## Target Domains

- Customer: `grihasthikart.in`
- Super Admin: `admin.grihasthikart.in`
- Staff: `staff.grihasthikart.in`

All domains point to the same Laravel application and the same `public` document root. Do not duplicate Laravel code, `vendor`, storage, or database.

## Environment

Add when ready:

```env
GK_CUSTOMER_HOST=grihasthikart.in
GK_ADMIN_HOST=admin.grihasthikart.in
GK_STAFF_HOST=staff.grihasthikart.in
GK_PORTAL_ENFORCE_HOSTS=false
```

Keep `GK_PORTAL_ENFORCE_HOSTS=false` during transition so existing `/admin` production access is not broken before DNS/SSL is confirmed.

## Hostinger Setup

1. Create subdomains in Hostinger:
   - `admin.grihasthikart.in`
   - `staff.grihasthikart.in`
2. Point each subdomain document root to the same Laravel `public` directory used by the main site.
3. Ensure HTTPS is enabled for all three hosts.
4. Confirm `.htaccess`/rewrite rules are active for Laravel front controller routing.
5. Do not copy the Laravel project into separate directories.

## Session/Cookie Notes

The application currently uses one Laravel session guard and route-level authorization boundaries.

Before strict host enforcement:

- `/admin` remains the safe production fallback.
- `/staff` is available as the staff transition route.
- Staff access is blocked by active staff status, operational role/permission checks, and store-scope checks, not by hidden navigation or host name alone.
- Future subdomain cookie scoping should be handled in a separate controlled milestone after DNS/SSL verification.

After subdomains are stable, host-enforcement middleware can be added in a separate scoped milestone if required.

## Rollback

If subdomain DNS or SSL is misconfigured:

- Keep `/admin` and `/staff` path routes active.
- Set `GK_PORTAL_ENFORCE_HOSTS=false`.
- Repoint subdomains without touching database state.
