<?php

return [
    'alert_email' => env('SECURITY_ALERT_EMAIL') ?: 'alex@exemplo.test',
    'service' => env('SECURITY_ALERT_SERVICE', 'banda'),
];
