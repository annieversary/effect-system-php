<?php declare(strict_types=1);

namespace Versary\EffectSystem;

use PHPUnit\Framework\TestCase;

use Versary\EffectSystem\Errors\UnhandledEffect;
use Versary\EffectSystem\Tests\AdditionTest\{AddNumbers, AddNumberHandler, SubNumbers, SubNumberHandler, SubNumberByAddHandler};

class AdditionTest extends TestCase
{
    function program() {
        $v = yield new AddNumbers(3, 7);
        $v = yield new SubNumbers($v, 2);

        return $v;
    }

    public function test_half_handle() {
        $gen = $this->program();

        $gen = Effect::handle($gen, new AddNumberHandler);

        $this->assertInstanceOf(SubNumbers::class, $gen->current());
        $gen->send(10);
        $this->assertFalse($gen->valid());
        $this->assertEquals(10, $gen->getReturn());
    }

    public function test_works() {
        $gen = $this->program();

        $gen = Effect::handle($gen, new AddNumberHandler);
        $gen = Effect::handle($gen, new SubNumberHandler);

        $result = Effect::run($gen);

        $this->assertEquals(8, $result);
    }

    public function test_handlers_in_inverse_order_works() {
        $gen = $this->program();

        $gen = Effect::handle($gen, new SubNumberHandler);
        $gen = Effect::handle($gen, new AddNumberHandler);
        $result = Effect::run($gen);

        $this->assertEquals(8, $result);
    }

    public function test_missing_handler() {
        $gen = $this->program();

        $this->expectException(UnhandledEffect::class);

        Effect::run($gen);
    }

    public function test_handler_that_yields() {
        $gen = $this->program();

        $gen = Effect::handle($gen, new SubNumberByAddHandler);
        $gen = Effect::handle($gen, new AddNumberHandler);
        $result = Effect::run($gen);

        $this->assertEquals(8, $result);
    }

    function my_gen() {
        yield 1;
        yield 2;
        yield 3;
    }

    public function test_cloning() {
        $gen = $this->my_gen();

        $gen->next();
        $this->assertEquals(2, $gen->current());

        // call the function from my PHP extension
        $new = clone_gen($gen);

        $this->assertEquals(2, $new->current());

        // advance the original generator
        $gen->next();

        // the original generator has advanced
        $this->assertEquals(3, $gen->current());
        // the new one hasn't
        $this->assertEquals(2, $new->current());
    }
}

function empty_gen() {
    if (false) yield 1;
}

function clone_gen($gen) {
    $new = empty_gen();
    clone_generator($gen, $new);
    return $new;
}

namespace Versary\EffectSystem\Tests\AdditionTest;

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

class SubNumbers extends Effect {
    public function __construct(public int $a, public int $b) {}
}

class SubNumberHandler extends Handler {
    public static $effect = SubNumbers::class;

    public function resume(mixed $effect) {
        return $effect->a - $effect->b;
    }
}

class SubNumberByAddHandler extends Handler {
    public static $effect = SubNumbers::class;

    public function resume(mixed $effect) {
        return yield new AddNumbers($effect->a, -$effect->b);
    }
}
