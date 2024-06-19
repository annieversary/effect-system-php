<?php declare(strict_types=1);

namespace Versary\EffectSystem;

use PHPUnit\Framework\TestCase;

use Versary\EffectSystem\Errors\UnhandledEffect;
use Versary\EffectSystem\Tests\ReturnOnlyTest\ReturnOnlyHandler;

class ReturnOnlyTest extends TestCase
{
    function program() {
        // force this function to be a generator
        false && yield false;

        return 3;
    }

    public function test_return_only() {
        $gen = $this->program();

        $gen = Effect::handle($gen, new ReturnOnlyHandler);

        $result = Effect::run($gen);

        $this->assertEquals(6, $result);
    }
}

namespace Versary\EffectSystem\Tests\ReturnOnlyTest;

use Versary\EffectSystem\{Effect, Handler};

class ReturnOnlyHandler extends Handler {
    public static $effect = stdClass::class;

    public function return(mixed $value) {
        return $value * 2;
    }
}
