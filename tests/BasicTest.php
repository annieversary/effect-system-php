<?php declare(strict_types=1);

namespace Versary\EffectSystem;

use PHPUnit\Framework\TestCase;

use Versary\EffectSystem\Errors\UnhandledEffect;
use Versary\EffectSystem\Tests\BasicTest\{AddNumbers, AddNumberHandler};

class BasicTest extends TestCase
{
    function basic() {
        $v = yield new AddNumbers(3, 7);

        return $v;
    }

    public function test_basic() {
        $gen = $this->basic();

        $gen = Effect::handle($gen, new AddNumberHandler);

        $result = Effect::run($gen);

        $this->assertEquals(10, $result);
    }

    function double() {
        $v = yield new AddNumbers(3, 1);
        $v = yield new AddNumbers($v, 7);

        return $v;
    }

    public function test_double() {
        $gen = $this->double();

        $gen = Effect::handle($gen, new AddNumberHandler);

        $result = Effect::run($gen);

        $this->assertEquals(11, $result);
    }
}

namespace Versary\EffectSystem\Tests\BasicTest;

use Versary\EffectSystem\{Effect, Handler};

class AddNumbers extends Effect {
    public function __construct(public int $a, public int $b) {}
}

class AddNumberHandler extends Handler {
    public static $effect = AddNumbers::class;

    public function resume(mixed $effect) {
        return $effect->a + $effect->b;
    }
}
