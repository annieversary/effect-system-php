PHP_ARG_ENABLE(php_effect_system, Whether to enable the EffectSystem extension, [ --enable-effect-system-php Enable Effect System])

if test "$PHP_EFFECT_SYSTEM" != "no"; then
    PHP_NEW_EXTENSION(php_effect_system, php_effect_system.c, $ext_shared)
fi
