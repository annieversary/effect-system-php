<?php declare(strict_types=1);

namespace Versary\EffectSystem;

use PHPUnit\Framework\TestCase;

use Versary\EffectSystem\Errors\UnhandledEffect;
use Versary\EffectSystem\Tests\CancelTest\{Cancel, CancelHandler};

class CancelTest extends TestCase
{
    // shitty way to test this
    public bool $flag = true;

    function inner() {
        yield new Cancel;
    }

    function program() {
        $this->flag = true;

        yield from $this->inner();

        // this does not get executed
        $this->flag = false;
    }

    public function test_cancel() {
        $gen = $this->program();
        $gen = Effect::handle($gen, new CancelHandler);
        $result = Effect::run($gen);

        $this->assertEquals('cancelled', $result);
        $this->assertTrue($this->flag);
    }

    function division(int $a, int $b) {
        if ($b === 0) yield new Cancel;

        return $a / $b;
    }

    public function test_division() {
        $result = Effect::run(Effect::handle($this->division(3, 0), new CancelHandler));
        $this->assertEquals('cancelled', $result);

        $result = Effect::run(Effect::handle($this->division(6, 2), new CancelHandler));
        $this->assertEquals(3, $result);
    }

    public function test_unhandled_division() {
        $this->expectException(\DivisionByZeroError::class);

        Effect::run(Effect::handle($this->division(3, 0), new class extends Handler {
            public static $effect = Cancel::class;
        }));
    }
}

namespace Versary\EffectSystem\Tests\CancelTest;

use Versary\EffectSystem\{Effect, Handler};

class Cancel extends Effect {}

class CancelHandler extends Handler {
    public static $effect = Cancel::class;

    public function handle(mixed $effect, \Closure $resume) {
        return 'cancelled';
    }
}
