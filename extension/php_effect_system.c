#include <php.h>
#include "zend_generators.h"
#include "zend_closures.h"
#include "php_effect_system.h"

/* forward declaration so it can be referenced in the module entry below */
PHP_MINIT_FUNCTION(effect_system_php);

zend_function_entry effect_system_php_functions[] = {
    PHP_FE(clone_generator, arginfo_clone_generator)
    PHP_FE_END
};

zend_module_entry effect_system_php_module_entry = {
    STANDARD_MODULE_HEADER,
    PHP_EFFECT_SYSTEM_EXTNAME,
    effect_system_php_functions,
    PHP_MINIT(effect_system_php),
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_EFFECT_SYSTEM_VERSION,
    STANDARD_MODULE_PROPERTIES
};

ZEND_GET_MODULE(effect_system_php)

/* ---- core clone logic -------------------------------------------------- */

static void copy(zend_generator *generator, zend_generator *clone)
{
    clone->flags = generator->flags & ~ZEND_GENERATOR_CURRENTLY_RUNNING;
    clone->largest_used_integer_key = generator->largest_used_integer_key;

    zval_ptr_dtor(&clone->value);
    ZVAL_COPY(&clone->value, &generator->value);

    zval_ptr_dtor(&clone->key);
    ZVAL_COPY(&clone->key, &generator->key);

    zval_ptr_dtor(&clone->retval);
    ZVAL_COPY(&clone->retval, &generator->retval);

    if (clone->execute_data) {
        zend_execute_data *old_ex = clone->execute_data;
        clone->execute_data = NULL;
        zend_free_compiled_variables(old_ex);
        efree(old_ex);
    }

    if (!generator->execute_data) {
        clone->func = NULL;
        clone->send_target = NULL;
        clone->frozen_call_stack = NULL;
        return;
    }

    zend_execute_data *orig_ex = generator->execute_data;
    zend_op_array    *op_array = &orig_ex->func->op_array;
    uint32_t          num_args = ZEND_CALL_NUM_ARGS(orig_ex);
    size_t            frame_size;

    if (num_args <= op_array->num_args) {
        frame_size = (size_t)(ZEND_CALL_FRAME_SLOT
                              + op_array->last_var
                              + op_array->T) * sizeof(zval);
    } else {
        frame_size = (size_t)(ZEND_CALL_FRAME_SLOT
                              + num_args
                              + op_array->last_var
                              + op_array->T
                              - op_array->num_args) * sizeof(zval);
    }

    zend_execute_data *new_ex = (zend_execute_data *) emalloc(frame_size);
    memcpy(new_ex, orig_ex, frame_size);

    new_ex->return_value = (zval *) clone;
    new_ex->prev_execute_data = &clone->execute_fake;

    uint32_t call_info = ZEND_CALL_INFO(new_ex);
    if (call_info & ZEND_CALL_RELEASE_THIS) {
        Z_ADDREF(new_ex->This);
    }
    if (call_info & ZEND_CALL_CLOSURE) {
        GC_ADDREF(ZEND_CLOSURE_OBJECT(new_ex->func));
    }

    for (uint32_t i = 0; i < (uint32_t) op_array->last_var; i++) {
        zval *var = ZEND_CALL_VAR_NUM(new_ex, i);
        if (Z_TYPE_P(var) != IS_UNDEF && Z_REFCOUNTED_P(var)) {
            Z_ADDREF_P(var);
        }
    }

    /* Only addref temporaries whose live_range spans the current yield. */
    uint32_t op_num = (uint32_t)(orig_ex->opline - op_array->opcodes);
    for (int i = 0; i < op_array->last_live_range; i++) {
        const zend_live_range *range = &op_array->live_range[i];
        if ((range->var & ZEND_LIVE_MASK) == ZEND_LIVE_SILENCE) continue;
        if (range->start < op_num && op_num <= range->end) {
            uint32_t var_offset = range->var & ~ZEND_LIVE_MASK;
            zval *var = (zval *)((char *) new_ex + var_offset);
            if (Z_REFCOUNTED_P(var)) {
                Z_ADDREF_P(var);
            }
        }
    }

    /* Addref extra variadic args stored beyond last_var + T. */
    if (num_args > op_array->num_args) {
        uint32_t base = (uint32_t)(op_array->last_var + op_array->T);
        uint32_t extra = num_args - op_array->num_args;
        for (uint32_t i = 0; i < extra; i++) {
            zval *var = ZEND_CALL_VAR_NUM(new_ex, base + i);
            if (Z_REFCOUNTED_P(var)) {
                Z_ADDREF_P(var);
            }
        }
    }

    if (generator->send_target) {
        ptrdiff_t offset = (char *) generator->send_target - (char *) orig_ex;
        clone->send_target = (zval *)((char *) new_ex + offset);
    } else {
        clone->send_target = NULL;
    }

    clone->execute_data = new_ex;
    clone->func = new_ex->func;
    clone->frozen_call_stack = NULL;
    memset(&clone->node, 0, sizeof(clone->node));
}

/* ---- clone_obj handler — makes `clone $gen` work ----------------------- */

static zend_object *generator_clone_obj(zend_object *old_obj)
{
    zend_generator *generator = (zend_generator *) old_obj;

    if (generator->frozen_call_stack) {
        zend_throw_error(NULL,
            "Cannot clone a generator that is suspended inside a nested call stack");
        GC_ADDREF(old_obj);
        return old_obj;
    }

    if (generator->node.parent != NULL) {
        zend_throw_error(NULL,
            "Cannot clone a generator that is currently delegating via yield from");
        GC_ADDREF(old_obj);
        return old_obj;
    }

    zval clone_zval;
    object_init_ex(&clone_zval, zend_ce_generator);
    zend_generator *clone = (zend_generator *) Z_OBJ(clone_zval);

    ZVAL_OBJ(&clone->execute_fake.This, Z_OBJ(clone_zval));
    copy(generator, clone);

    return Z_OBJ(clone_zval);
}

/* ---- module init: install clone handler -------------------------------- */

/* Writable copy of Generator's object handlers stored in our data segment. */
static zend_object_handlers generator_handlers;

PHP_MINIT_FUNCTION(effect_system_php)
{
    memcpy(&generator_handlers, zend_ce_generator->default_object_handlers,
           sizeof(zend_object_handlers));
    generator_handlers.clone_obj = generator_clone_obj;

    /* Redirect the class entry to our copy. The class entry is heap-allocated
       so writing through the const* pointer here is safe. */
    zend_object_handlers **p =
        (zend_object_handlers **) &zend_ce_generator->default_object_handlers;
    *p = &generator_handlers;

    return SUCCESS;
}

/* ---- PHP-callable wrapper ---------------------------------------------- */

PHP_FUNCTION(clone_generator) {
    zval *gen_val;

    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_OBJECT_OF_CLASS(gen_val, zend_ce_generator);
    ZEND_PARSE_PARAMETERS_END();

    RETURN_OBJ(generator_clone_obj(Z_OBJ_P(gen_val)));
}
