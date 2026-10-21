<?php

return [

    /*
    | Minutos que dura un enlace de acceso desde que se solicita. Un solo uso.
    */
    'login_link_minutes' => (int) env('PASSWORDLESS_LOGIN_LINK_MINUTES', 15),

];
