<?php

namespace Versary\EffectSystem;

use PHPUnit\Framework\TestCase;

use Versary\EffectSystem\Errors\UnhandledEffect;

class ChoiceTest extends TestCase
{
    function xor() {
        $p = yield new Choice();
        $q = yield new Choice();

        return (bool)($p ^ $q);
    }

    public function test_random() {
        $gen = handle($this->xor(), new ChoiceRandomHandler);
        $result = run($gen);

        $this->assertIsBool($result);
    }

    public function test_all() {
        $gen = handle($this->xor(), new ChoiceAllHandler);
        $result = run($gen);

        $this->assertEquals($result, [
            true, // true ^ false
            false,
            false,
            true
        ]);
    }
}

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
        $this->options[] = $resume(true);
        $this->options[] = $resume(false);
   }

    public function return(mixed $value) {
        return $this->options;
    }
}
