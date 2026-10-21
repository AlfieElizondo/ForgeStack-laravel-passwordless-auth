<?php

return [

    /*
    | Minutos que dura un enlace de acceso desde que se solicita. Un solo uso.
    */
    'login_link_minutes' => (int) env('PASSWORDLESS_LOGIN_LINK_MINUTES', 15),

    /*
    | Días que un navegador recordado sigue válido desde su último uso.
    | Cada uso extiende la vigencia.
    */
    'trusted_device_days' => (int) env('PASSWORDLESS_TRUSTED_DEVICE_DAYS', 60),

];
