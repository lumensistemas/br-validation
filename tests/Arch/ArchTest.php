<?php

arch()->preset()->php();

arch()->preset()->security();

arch()->expect('LumenSistemas\BrValidation')->toUseStrictTypes();
