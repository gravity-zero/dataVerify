<?php

return fn() => \Gravity\DataVerify::registerRules('strongPassword')
    ->minLength(12)
    ->containsUpper
    ->containsLower
    ->containsNumber;