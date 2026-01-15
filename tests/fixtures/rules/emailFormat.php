<?php

return fn() => \Gravity\DataVerify::registerRules('emailFormat')
    ->required
    ->email
    ->disposableEmail;