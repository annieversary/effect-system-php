// include the PHP API itself
#include <php.h>
#include "zend_generators.h"
// then include the header of your extension
#include "php_effect_system.h"

// register our function to the PHP API
// so that PHP knows, which functions are in this module
zend_function_entry effect_system_php_functions[] = {
    PHP_FE(clone_generator, arginfo_clone_generator)
    PHP_FE_END
};

// some pieces of information about our module
zend_module_entry effect_system_php_module_entry = {
    STANDARD_MODULE_HEADER,
    PHP_EFFECT_SYSTEM_EXTNAME,
    effect_system_php_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_EFFECT_SYSTEM_VERSION,
    STANDARD_MODULE_PROPERTIES
};

// use a macro to output additional C code, to make ext dynamically loadable
ZEND_GET_MODULE(effect_system_php)

void copy(zend_generator *generator, zend_generator *clone) {
    clone->std = generator->std;
    clone->execute_data = generator->execute_data;
    clone->frozen_call_stack = generator->frozen_call_stack;

    clone->value = generator->value;
    clone->key = generator->key;
    clone->retval = generator->retval;
    // this is a pointer, so its probably wrong now lol
    clone->send_target = generator->send_target;
    clone->largest_used_integer_key = generator->largest_used_integer_key;

    clone->values = generator->values;
    clone->node = generator->node;
    clone->execute_fake = generator->execute_fake;

    // func is only in master
    /* clone->func = generator->func; */
    clone->flags = generator->flags;
}

PHP_FUNCTION(clone_generator) {
    zval *gen_val, *clone_val;

    ZEND_PARSE_PARAMETERS_START(2, 2)
        Z_PARAM_OBJECT_OF_CLASS(gen_val, zend_ce_generator);
        Z_PARAM_ZVAL(clone_val);
    ZEND_PARSE_PARAMETERS_END();

    zend_object *gen_obj = Z_OBJ_P(gen_val);
    zend_generator *generator = (zend_generator*) gen_obj;

    ZVAL_DEREF(clone_val);
    zend_object *clone_obj = Z_OBJ_P(clone_val);
    zend_generator *clone = (zend_generator*) clone_obj;

    copy(generator, clone);

    php_printf("Hello World! %d\n", generator->value);
    php_printf("Hello World! %d\n", clone->value);
}
