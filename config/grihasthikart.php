<?php

return [
    'admin_emails' => array_filter(array_map(
        'trim',
        explode(',', env('GRIHASTHIKART_ADMIN_EMAILS', ''))
    )),
];
