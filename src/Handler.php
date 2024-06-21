<?php

namespace Versary\EffectSystem;

abstract class Handler {
    public static $effect;

    public function return(mixed $value) {
        return $value;
    }
    public function resume(mixed $effect) {}
    public function handle(mixed $effect, \Closure $resume) {
        $value = $this->resume($effect);
        yield from $resume($value instanceof \Generator ? yield from $value : $value);
    }
}
