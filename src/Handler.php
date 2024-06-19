<?php

namespace Versary\EffectSystem;

abstract class Handler {
    public static $effect;

    public function return(mixed $value) {
        return $value;
    }
    public function resume(mixed $effect) {}
    public function handle(mixed $effect, \Closure $resume) {
        $resume($this->resume($effect));
    }
}
