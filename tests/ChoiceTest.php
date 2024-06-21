<?php

namespace Versary\EffectSystem;

use PHPUnit\Framework\TestCase;

use Versary\EffectSystem\Errors\ResumedTwice;
use Versary\EffectSystem\Tests\ChoiceTest\{Choice, ChoiceRandomHandler, ChoiceAllHandler};

class ChoiceTest extends TestCase
{
    function xor() {
        $p = yield new Choice();
        $q = yield new Choice();

        return (bool)($p ^ $q);
    }

    public function test_random() {
        $gen = Effect::handle($this->xor(), new ChoiceRandomHandler);
        $result = Effect::run($gen);

        $this->assertIsBool($result);
    }

    public function test_all() {
        // we are not allowed to resume twice cause php doesn't let me clone generators :((
        $this->expectException(ResumedTwice::class);

        $gen = Effect::handle($this->xor(), new ChoiceAllHandler);
        $result = Effect::run($gen);

        $this->assertEquals($result, [
            true, // true ^ false
            false,
            false,
            true
        ]);
    }
}



namespace Versary\EffectSystem\Tests\ChoiceTest;

use Versary\EffectSystem\{Effect, Handler};

class Choice extends Effect {
    public function __construct() {}
}

class ChoiceRandomHandler extends Handler {
    public static $effect = Choice::class;

    public function resume(mixed $effect) {
        return (bool)random_int(0, 1);
    }
}

class ChoiceAllHandler extends Handler {
    public static $effect = Choice::class;

    public function __construct(public array $options = []) {}

    public function handle(mixed $effect, \Closure $resume) {
        return [
            ...yield from $resume(true),
            ...yield from $resume(false),
        ];
   }

    public function return(mixed $value) {
        return [$value];
    }
}
