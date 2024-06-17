<?php

namespace Annieversary\EffectSystem;

class BasicTest extends \PHPUnit\Framework\TestCase
{
    function program() {
        $v = yield new AddNumbers(3, 7);
        $v = yield new SubNumbers($v, 2);

        return $v;
    }

    public function test_works() {
        $gen = $this->program();

        $result = handle($gen, [new AddNumberHandler, new SubNumberHandler]);

        $this->assertEquals(8, $result);
    }

    public function test_handlers_in_inverse_order_works() {
        $gen = $this->program();

        $result = handle($gen, [new SubNumberHandler, new AddNumberHandler]);

        $this->assertEquals(8, $result);
    }

    public function test_missing_handler() {
        $gen = $this->program();

        $this->expectException(\Exception::class);

        handle($gen, [new AddNumberHandler]);
    }
}

class AddNumbers extends Effect {
    public function __construct(public int $a, public int $b) {}
}

class AddNumberHandler extends Handler {
    public static $effect = AddNumbers::class;

    public function handle(mixed $effect) {
        return $effect->a + $effect->b;
    }
}

class SubNumbers extends Effect {
    public function __construct(public int $a, public int $b) {}
}

class SubNumberHandler extends Handler {
    public static $effect = SubNumbers::class;

    public function handle(mixed $effect) {
        return $effect->a - $effect->b;
    }
}
