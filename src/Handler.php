<?php

namespace Versary\EffectSystem;

abstract class Handler {
    public static $effect;

    public abstract function handle(mixed $effect);
}
