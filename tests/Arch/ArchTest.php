<?php

arch()->preset()->php();

arch()->preset()->security();

arch()->expect('LumenSistemas\BrValidation')->toUseStrictTypes();

arch('all package classes are final')
    ->expect('LumenSistemas\BrValidation')
    ->classes()
    ->toBeFinal();

arch('every public validator exposes isValid')
    ->expect('LumenSistemas\BrValidation')
    ->classes()
    ->toHaveMethod('isValid')
    ->ignoring('LumenSistemas\BrValidation\Concerns');
