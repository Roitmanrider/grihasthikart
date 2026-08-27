<?php

return [
    'admin_emails' => array_filter(array_map(
        'trim',
        explode(',', env('GRIHASTHIKART_ADMIN_EMAILS', ''))
    )),

    'portals' => [
        'customer_host' => env('GK_CUSTOMER_HOST', 'grihasthikart.in'),
        'admin_host' => env('GK_ADMIN_HOST', 'admin.grihasthikart.in'),
        'staff_host' => env('GK_STAFF_HOST', 'staff.grihasthikart.in'),
        'enforce_hosts' => env('GK_PORTAL_ENFORCE_HOSTS', false),
    ],
];
