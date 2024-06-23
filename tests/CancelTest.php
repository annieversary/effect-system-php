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
