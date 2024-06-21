<?php declare(strict_types=1);

namespace Versary\EffectSystem;

abstract class Effect {
    final public static function run(\Generator $generator) {
        if ($generator->valid()) {
            $effect = $generator->current();
            throw new Errors\UnhandledEffect($effect::class);
        }

        return $generator->getReturn();
    }

    final public static function handle(\Generator $generator, Handler $handler) {
        if (!$generator->valid()) {
            return $handler->return($generator->getReturn());
        }

        $effect = $generator->current();

        if ($effect instanceof $handler::$effect) {
            $resumed = false;
            $resume = function ($output) use ($generator, $handler, &$resumed) {
                // NOTE: ideally, we would clone the generator and not limit to only one `resume` call per `handle`
                // but php doesn't like fun and doesn't let me clone generators :(
                if ($resumed) throw new Errors\ResumedTwice($handler);
                $resumed = true;

                $generator->send($output instanceof \Generator ? yield from $output : $output);

                return yield from self::handle($generator, $handler);
            };

            $handled = $handler->handle($effect, $resume);
            $handled = $handled instanceof \Generator ? yield from $handled : $handled;

            // handle allows us to abort execution
            if ($handled !== null) {
                return $handler->return($handled);
            }

            return yield from self::handle($generator, $handler);
        }

        $generator->send(yield $effect);
        return yield from self::handle($generator, $handler);
    }
}
