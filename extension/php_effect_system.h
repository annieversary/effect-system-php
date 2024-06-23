// we define Module constants
#define PHP_EFFECT_SYSTEM_EXTNAME "php_effect_system"
#define PHP_EFFECT_SYSTEM_VERSION "0.0.1"

PHP_FUNCTION(clone_generator);

ZEND_BEGIN_ARG_INFO_EX(arginfo_clone_generator, 0, 0, 1)
    ZEND_ARG_OBJ_INFO(0, generator, "Generator", 0)
    ZEND_ARG_OBJ_INFO(1, clone, "Generator", 0)
ZEND_END_ARG_INFO()
