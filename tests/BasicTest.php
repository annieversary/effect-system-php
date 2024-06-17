<?php

namespace Annieversary\EffectSystem;

use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    function program() {
        $v = yield new AddNumbers(3, 7);
        $v = yield new SubNumbers($v, 2);

        return $v;
    }

    public function test_works() {
        $gen = $this->program();

        $result = run($gen, [new AddNumberHandler, new SubNumberHandler]);

        $this->assertEquals(8, $result);
    }

    public function test_program() {
        $gen = $this->program();

        $this->assertInstanceOf(AddNumbers::class, $gen->current());
        $gen->send(10);
        $this->assertInstanceOf(SubNumbers::class, $gen->current());
        $gen->send(10);
        $this->assertFalse($gen->valid());
        $this->assertEquals(10, $gen->getReturn());
    }

    public function test_half_handle() {
        $gen = $this->program();

        $gen = handle($gen, new AddNumberHandler);

        $this->assertInstanceOf(SubNumbers::class, $gen->current());
        $gen->send(10);
        $this->assertFalse($gen->valid());
        $this->assertEquals(10, $gen->getReturn());
    }

    public function test_handle_and_run() {
        $gen = $this->program();

        $adds_handled = handle($gen, new AddNumberHandler);
        $result = run($adds_handled, new SubNumberHandler);

        $this->assertEquals(8, $result);
    }

    public function test_both_handled() {
        $gen = $this->program();

        $adds_handled = handle($gen, new AddNumberHandler);
        $subs_handled = handle($adds_handled, new SubNumberHandler);
        $result = run($subs_handled);

        $this->assertEquals(8, $result);
    }

    public function test_handlers_in_inverse_order_works() {
        $gen = $this->program();

        $result = run($gen, [new SubNumberHandler, new AddNumberHandler]);

        $this->assertEquals(8, $result);
    }

    public function test_missing_handler() {
        $gen = $this->program();

        $this->expectException(\Exception::class);

        run($gen, [new AddNumberHandler]);
    }

    public function test_handler_that_yields() {
        $gen = $this->program();

        $result = run($gen, [new SubNumberByAddHandler, new AddNumberHandler]);

        $this->assertEquals(8, $result);
    }

    public function test_handle_handler_that_yields() {
        $gen = $this->program();

        $adds_handled = handle($gen, new AddNumberHandler);
        $subs_handled = handle($adds_handled, new SubNumberHandler);
        $result = run($subs_handled);

        $this->assertEquals(8, $result);
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

class SubNumberByAddHandler extends Handler {
    public static $effect = SubNumbers::class;

    public function handle(mixed $effect) {
        return yield new AddNumbers($effect->a, -$effect->b);
    }
}
