<?php

namespace Annieversary\EffectSystem;

function handle(\Generator $gen, array|Handler $handlers) {
    if ($handlers instanceof Handler) {
        $handlers = [$handlers];
    }

    $handle = function (Effect $effect) use ($handlers) {
        foreach ($handlers as $handler) {
            if ($effect instanceof $handler::$effect) {
                return $handler->handle($effect);
            }
        }

        // TODO change to new class
        throw new \Exception('Missing Handler for Effect ' . $effect::class);
    };

    while (true) {
        $effect = $gen->current();
        $output = $handle($effect);
        $gen->send($output);

        if (!$gen->valid()) {
            return $gen->getReturn();
        }
    }
}

abstract class Effect {
    //
}
