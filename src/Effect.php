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
        $result = yield from self::inner_handle($generator, $handler);
        return $handler->return($result);
    }

    final private static function inner_handle(\Generator $generator, Handler $handler) {
        if (!$generator->valid()) {
            return $generator->getReturn();
        }

        $effect = $generator->current();

        if ($effect instanceof $handler::$effect) {
            $resume = function ($output) use ($generator, $handler) {
                $generator->send($output instanceof \Generator ? yield from $output : $output);

                return yield from self::inner_handle($generator, $handler);
            };

            $o = yield from $handler->handle($effect, $resume);

            if ($o !== null) {
                return $o;
            }
        }

        $generator->send(yield $effect);
        return yield from self::inner_handle($generator, $handler);
    }
}
