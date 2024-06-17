<?php

namespace Annieversary\EffectSystem;

function run(\Generator $gen, array|Handler|null $handlers = null) {
    if ($handlers instanceof Handler) {
        $handlers = [$handlers];
    }

    $handle = function (Effect $effect) use ($handlers) {
        foreach ($handlers as $handler) {
            if ($effect instanceof $handler::$effect) {
                $o = $handler->handle($effect);

                if ($o instanceof \Generator) {
                    return run($o, $handlers);
                }

                return $o;
            }
        }

        // TODO change to new class
        throw new \Exception('Unhandled effect ' . $effect::class);
    };

    while (true) {
        if (!$gen->valid()) {
            return $gen->getReturn();
        }

        $effect = $gen->current();
        $output = $handle($effect);
        $gen->send($output);
    }
}

function handle(\Generator $gen, array|Handler $handlers) {
    if ($handlers instanceof Handler) {
        $handlers = [$handlers];
    }

    while (true) {
        if (!$gen->valid()) {
            return $gen->getReturn();
        }

        $effect = $gen->current();

        $output = [];
        foreach ($handlers as $handler) {
            if ($effect instanceof $handler::$effect) {
                $o = $handler->handle($effect);
                if ($o instanceof \Generator) {
                    $output[] = yield from $o;
                } else {
                    $output[] = $o;
                }
            }
        }
        if (count($output) == 0) {
            $output[] = yield $effect;
        }

        $gen->send($output[0]);
    }
}

abstract class Effect {
    //
}
