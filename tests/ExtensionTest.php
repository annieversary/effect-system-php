<?php declare(strict_types=1);

namespace Versary\EffectSystem;

use PHPUnit\Framework\TestCase;

class ExtensionTest extends TestCase {
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

        $new->next();
        $this->assertEquals(3, $gen->current());
        $this->assertEquals(3, $new->current());
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
