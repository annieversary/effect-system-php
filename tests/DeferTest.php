<?php declare(strict_types=1);

namespace Versary\EffectSystem;

use PHPUnit\Framework\TestCase;

use Versary\EffectSystem\Errors\UnhandledEffect;
use Versary\EffectSystem\Tests\DeferTest\{Defer, DeferHandler};

class DeferTest extends TestCase
{
    function program() {
        $this->v = 1;

        yield new Defer(fn () => $this->v = 3);

        $this->assertEquals(1, $this->v);
    }

    public function test_basic() {
        Effect::run(Effect::handle($this->program(), new DeferHandler));

        $this->assertEquals(3, $this->v);
    }
}

namespace Versary\EffectSystem\Tests\DeferTest;

use Versary\EffectSystem\{Effect, Handler};

class Defer extends Effect {
    public function __construct(public \Closure $closure) {}
}

class DeferHandler extends Handler {
    public static $effect = Defer::class;

    public function handle(mixed $effect, \Closure $resume) {
        yield from $resume(null);

        ($effect->closure)();
    }
}
