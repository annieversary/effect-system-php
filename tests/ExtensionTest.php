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

        // clone the generator using PHP's native clone keyword
        $new = clone $gen;

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

    // ---- local variable independence ----

    function counter_gen() {
        $n = 0;
        while (true) {
            $n++;
            yield $n;
        }
    }

    public function test_local_variable_independence() {
        $gen = $this->counter_gen();
        $this->assertEquals(1, $gen->current());

        $clone = clone $gen;

        // advance original twice
        $gen->next();
        $gen->next();

        $this->assertEquals(3, $gen->current());
        // clone has its own $n and hasn't advanced
        $this->assertEquals(1, $clone->current());

        $clone->next();
        $this->assertEquals(3, $gen->current()); // original unchanged
        $this->assertEquals(2, $clone->current());
    }

    // ---- multiple independent clones from one source ----

    public function test_multiple_clones_are_independent() {
        $gen = $this->my_gen();
        $this->assertEquals(1, $gen->current());

        $a = clone $gen;
        $b = clone $gen;
        $c = clone $gen;

        // advance each a different amount
        $gen->next(); // at 2
        $a->next(); $a->next(); // at 3
        // b and c untouched

        $this->assertEquals(2, $gen->current());
        $this->assertEquals(3, $a->current());
        $this->assertEquals(1, $b->current());
        $this->assertEquals(1, $c->current());
    }

    // ---- clone of a clone ----

    public function test_clone_of_clone() {
        $gen = $this->my_gen();
        $this->assertEquals(1, $gen->current());

        $clone1 = clone $gen;
        $clone1->next(); // at 2

        $clone2 = clone $clone1; // snapshot at 2

        $clone1->next(); // advance clone1 to 3

        $this->assertEquals(1, $gen->current());    // original at 1
        $this->assertEquals(3, $clone1->current()); // clone1 at 3
        $this->assertEquals(2, $clone2->current()); // clone2 still at 2
    }

    // ---- string local variables (heap allocation / refcount safety) ----

    function string_gen() {
        $s = 'hello';
        yield $s;
        $s .= ' world';
        yield $s;
    }

    public function test_string_local_independence() {
        $gen = $this->string_gen();
        $this->assertEquals('hello', $gen->current());

        $clone = clone $gen;

        $gen->next(); // original now yields 'hello world'

        $this->assertEquals('hello world', $gen->current());
        $this->assertEquals('hello', $clone->current()); // clone has its own $s

        $clone->next();
        $this->assertEquals('hello world', $clone->current());
    }

    // ---- array local variables (copy-on-write) ----

    function array_gen() {
        $arr = [1, 2, 3];
        yield $arr;
        $arr[] = 4;
        yield $arr;
    }

    public function test_array_local_independence() {
        $gen = $this->array_gen();
        $this->assertEquals([1, 2, 3], $gen->current());

        $clone = clone $gen;

        $gen->next(); // original now yields [1,2,3,4]

        $this->assertEquals([1, 2, 3, 4], $gen->current());
        $this->assertEquals([1, 2, 3], $clone->current()); // clone has its own copy

        $clone->next();
        $this->assertEquals([1, 2, 3, 4], $clone->current());
    }

    // ---- object local variables are shared (shallow copy) ----

    function object_gen() {
        $obj = new \stdClass();
        $obj->n = 0;
        yield $obj;
        yield $obj;
    }

    public function test_object_locals_are_shared() {
        $gen = $this->object_gen();
        $gen->current(); // start

        $clone = clone $gen;

        // Mutate the object through one generator's yielded value
        $gen->current()->n = 99;

        // The clone holds a reference to the same object, so it sees the mutation
        $this->assertEquals(99, $clone->current()->n);
    }

    // ---- clone works as a resumable checkpoint ----

    function two_choice_gen() {
        $a = yield;
        $b = yield;
        return [$a, $b];
    }

    public function test_clone_as_checkpoint() {
        // Save a checkpoint before the first send, then branch twice from it
        $root = $this->two_choice_gen();
        $root->current(); // start

        $checkpoint = clone $root;

        // Branch 1: send true first
        $root->send(true);
        $root->send(true);
        $this->assertEquals([true, true], $root->getReturn());

        // Branch 2: start fresh from the checkpoint
        $checkpoint->send(false);
        $checkpoint->send(false);
        $this->assertEquals([false, false], $checkpoint->getReturn());
    }

    // ---- all paths via nested cloning (the primary extension use-case) ----

    public function test_all_paths_via_cloning() {
        // Demonstrates exploring every combination of two binary choices without
        // re-executing the shared prefix — impossible without generator cloning.
        $results = [];

        $root = $this->two_choice_gen();
        $root->current();

        foreach ([true, false] as $first) {
            $l1 = clone $root;
            $l1->send($first); // at second yield

            foreach ([true, false] as $second) {
                $l2 = clone $l1;
                $l2->send($second);
                $results[] = $l2->getReturn();
            }
        }

        $this->assertEqualsCanonicalizing(
            [[true, true], [true, false], [false, true], [false, false]],
            $results
        );
    }

    // ---- cloning enables double-resume across multiple sends ----

    function nondeterministic_sum_gen() {
        $a = yield;
        $b = yield;
        return $a + $b;
    }

    public function test_double_resume_collects_all_sums() {
        $choices = [1, 2, 3];
        $results = [];

        $gen = $this->nondeterministic_sum_gen();
        $gen->current(); // at first yield

        foreach ($choices as $first) {
            $branch = clone $gen;
            $branch->send($first); // at second yield

            foreach ($choices as $second) {
                $leaf = clone $branch;
                $leaf->send($second);
                $results[] = $leaf->getReturn();
            }
        }

        $expected = [];
        foreach ($choices as $a) {
            foreach ($choices as $b) {
                $expected[] = $a + $b;
            }
        }

        $this->assertEqualsCanonicalizing($expected, $results);
    }

    // ---- cloning inside a handler enables the ChoiceAll pattern ----

    public function test_choice_all_with_cloning() {
        // This is the pattern that previously threw ResumedTwice: a handler
        // that resumes the same continuation with multiple values to collect
        // all possible outcomes.
        $program = function() {
            $p = yield;
            $q = yield;
            return (int)$p ^ (int)$q; // xor
        };

        $collect = function(\Generator $gen, array $options) use (&$collect): array {
            if (!$gen->valid()) {
                return [$gen->getReturn()];
            }
            $results = [];
            foreach ($options as $value) {
                $branch = clone $gen;
                $branch->send($value);
                $results = array_merge($results, $collect($branch, $options));
            }
            return $results;
        };

        $gen = $program();
        $gen->current();

        $results = $collect($gen, [true, false]);

        sort($results);
        // true^true=0, true^false=1, false^true=1, false^false=0
        $this->assertEquals([0, 0, 1, 1], $results);
    }

    // ---- extra arguments beyond declared params are preserved in clone ----

    function gen_with_declared($a, $b) {
        yield $a;
        yield $b;
    }

    public function test_clone_with_extra_args() {
        // Pass extra args beyond the declared parameter count so that
        // num_args > op_array->num_args, exercising the extra-arg addref path.
        $gen = call_user_func_array([$this, 'gen_with_declared'], ['hello', 'world', 'extra1', 'extra2']);
        $this->assertEquals('hello', $gen->current());

        $clone = clone $gen;

        $gen->next();
        $clone->next();

        $this->assertEquals('world', $gen->current());
        $this->assertEquals('world', $clone->current());
    }

    // ---- unsupported: cloning a generator suspended in yield from ----

    function inner_gen() { yield 1; yield 2; }
    function delegating_gen() { yield from $this->inner_gen(); yield 3; }

    public function test_clone_of_delegating_generator_throws() {
        $gen = $this->delegating_gen();
        $gen->current(); // starts outer, delegates to inner, inner yields 1

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot clone a generator that is currently delegating via yield from');
        clone $gen;
    }
}
