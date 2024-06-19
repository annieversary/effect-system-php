<?php declare(strict_types=1);

namespace Versary\EffectSystem;

function run(\Generator $generator) {
    if ($generator->valid()) {
        $effect = $generator->current();
        throw new Errors\UnhandledEffect($effect::class);
    }

    return $generator->getReturn();
}

function handle(\Generator $generator, Handler $handler) {
    while (true) {
        if (!$generator->valid()) {
            return $handler->return($generator->getReturn());
        }

        $effect = $generator->current();

        if ($effect instanceof $handler::$effect) {
            // TODO implement resume and call handle
            // im not sure if handle should return a value or not
            // personally i dont think so
            $resume = fn ($p) => null;

            $o = $handler->resume($effect);
            if ($o instanceof \Generator) {
                $output = yield from $o;
            } else {
                $output = $o;
            }
        } else {
            $output = yield $effect;
        }

        $generator->send($output);
    }
}

abstract class Effect {
    //
}
