<?php

namespace Versary\EffectSystem\Errors;

class UnhandledEffect extends \Exception {
    public function __construct(public $class) {
        parent::__construct('Unhandled Effect: ' . $class);
    }
}
