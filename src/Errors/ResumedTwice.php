<?php

namespace Versary\EffectSystem\Errors;

class ResumedTwice extends \Exception {
    public function __construct(public $class) {
        parent::__construct('Resumed twice: ' . get_class($class));
    }
}
