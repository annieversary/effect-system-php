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
    clone->flags = generator->flags;
    clone->largest_used_integer_key = generator->largest_used_integer_key;

    clone->value = generator->value;
    clone->key = generator->key;
    clone->retval = generator->retval;

    clone->execute_data = generator->execute_data;
    // TODO Create new execution context
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
}

// https://github.com/php/php-src/blob/php-5.5.0beta2/Zend/zend_generators.c#L199
/* static zend_object zend_generator_clone(zval *object) /\* {{{ *\/ */
/* { */
/* 	zend_generator *orig = (zend_generator *) Z_OBJ_P(object); */
/* 	zend_object clone_val = zend_generator_create(Z_OBJCE_P(object)); */
/* 	zend_generator *clone = zend_object_store_get_object_by_handle(clone_val.handle); */

/* 	zend_objects_clone_members(&clone->std, &orig->std); */

/* 	clone->execute_data = orig->execute_data; */
/* 	clone->largest_used_integer_key = orig->largest_used_integer_key; */
/* 	clone->flags = orig->flags; */

/* 	if (orig->execute_data) { */
/* 		/\* Create a few shorter aliases to the old execution data *\/ */
/* 		zend_execute_data *execute_data = orig->execute_data; */
/* 		zend_op_array *op_array = execute_data->op_array; */
/* 		HashTable *symbol_table = execute_data->symbol_table; */
/* 		zend_execute_data *current_execute_data; */
/* 		zend_op **opline_ptr; */
/* 		HashTable *current_symbol_table; */
/* 		zend_vm_stack current_stack; */
/* 		zval *current_this; */
/* 		void **stack_frame, **orig_stack_frame; */

/* 		/\* Create new execution context. We have to back up and restore */
/* 		 * EG(current_execute_data), EG(opline_ptr), EG(active_symbol_table) */
/* 		 * and EG(This) here because the function modifies or uses them  *\/ */
/* 		current_execute_data = EG(current_execute_data); */
/* 		EG(current_execute_data) = execute_data->prev_execute_data; */
/* 		opline_ptr = EG(opline_ptr); */
/* 		current_symbol_table = EG(active_symbol_table); */
/* 		EG(active_symbol_table) = execute_data->symbol_table; */
/* 		current_this = EG(This); */
/* 		EG(This) = NULL; */
/* 		current_stack = EG(argument_stack); */
/* 		clone->execute_data = zend_create_execute_data_from_op_array(op_array, 0 TSRMLS_CC); */
/* 		clone->stack = EG(argument_stack); */
/* 		EG(argument_stack) = current_stack; */
/* 		EG(This) = current_this; */
/* 		EG(active_symbol_table) = current_symbol_table; */
/* 		EG(current_execute_data) = current_execute_data; */
/* 		EG(opline_ptr) = opline_ptr; */

/* 		/\* copy *\/ */
/* 		clone->execute_data->opline = execute_data->opline; */
/* 		clone->execute_data->function_state = execute_data->function_state; */
/* 		clone->execute_data->object = execute_data->object; */
/* 		clone->execute_data->current_scope = execute_data->current_scope; */
/* 		clone->execute_data->current_called_scope = execute_data->current_called_scope; */
/* 		clone->execute_data->fast_ret = execute_data->fast_ret; */

/* 		if (!symbol_table) { */
/* 			int i; */

/* 			/\* Copy compiled variables *\/ */
/* 			for (i = 0; i < op_array->last_var; i++) { */
/* 				if (*EX_CV_NUM(execute_data, i)) { */
/* 					*EX_CV_NUM(clone->execute_data, i) = (zval **) EX_CV_NUM(clone->execute_data, op_array->last_var + i); */
/* 					**EX_CV_NUM(clone->execute_data, i) = *(zval **) EX_CV_NUM(execute_data, op_array->last_var + i); */
/* 					Z_ADDREF_PP(*EX_CV_NUM(clone->execute_data, i)); */
/* 				} */
/* 			} */
/* 		} else { */
/* 			/\* Copy symbol table *\/ */
/* 			ALLOC_HASHTABLE(clone->execute_data->symbol_table); */
/* 			zend_hash_init(clone->execute_data->symbol_table, zend_hash_num_elements(symbol_table), NULL, ZVAL_PTR_DTOR, 0); */
/* 			zend_hash_copy(clone->execute_data->symbol_table, symbol_table, (copy_ctor_func_t) zval_add_ref, NULL, sizeof(zval *)); */

/* 			/\* Update zval** pointers for compiled variables *\/ */
/* 			{ */
/* 				int i; */
/* 				for (i = 0; i < op_array->last_var; i++) { */
/* 					if (zend_hash_quick_find(clone->execute_data->symbol_table, op_array->vars[i].name, op_array->vars[i].name_len + 1, op_array->vars[i].hash_value, (void **) EX_CV_NUM(clone->execute_data, i)) == FAILURE) { */
/* 						*EX_CV_NUM(clone->execute_data, i) = NULL; */
/* 					} */
/* 				} */
/* 			} */
/* 		} */

/* 		/\* Copy nested-calls stack *\/ */
/* 		if (execute_data->call) { */
/* 			clone->execute_data->call = clone->execute_data->call_slots + */
/* 				(execute_data->call - execute_data->call_slots); */
/* 		} else { */
/* 			clone->execute_data->call = NULL; */
/* 		} */
/* 		memcpy(clone->execute_data->call_slots, execute_data->call_slots, ZEND_MM_ALIGNED_SIZE(sizeof(call_slot)) * op_array->nested_calls); */
/* 		if (clone->execute_data->call >= clone->execute_data->call_slots) { */
/* 			call_slot *call = clone->execute_data->call; */

/* 			while (call >= clone->execute_data->call_slots) { */
/* 				if (call->object) { */
/* 					Z_ADDREF_P(call->object); */
/* 				} */
/* 				call--; */
/* 			} */
/* 		} */

/* 		/\* Copy the temporary variables *\/ */
/* 		memcpy(EX_TMP_VAR_NUM(clone->execute_data, op_array->T-1), EX_TMP_VAR_NUM(execute_data, op_array->T-1), ZEND_MM_ALIGNED_SIZE(sizeof(temp_variable)) * op_array->T); */

/* 		/\* Copy arguments passed on stack *\/ */
/* 		stack_frame = zend_vm_stack_frame_base(clone->execute_data); */
/* 		orig_stack_frame = zend_vm_stack_frame_base(execute_data); */
/* 		clone->stack->top = stack_frame + (orig->stack->top - orig_stack_frame); */
/* 		if (clone->stack->top != stack_frame) { */
/* 			memcpy(stack_frame, orig_stack_frame, ZEND_MM_ALIGNED_SIZE(sizeof(zval*)) * (orig->stack->top - orig_stack_frame)); */
/* 			while (clone->stack->top != stack_frame) { */
/* 				Z_ADDREF_PP((zval**)stack_frame); */
/* 				stack_frame++; */
/* 			} */
/* 		} */

/* 		/\* Add references to loop variables *\/ */
/* 		{ */
/* 			zend_uint op_num = execute_data->opline - op_array->opcodes; */

/* 			int i; */
/* 			for (i = 0; i < op_array->last_brk_cont; ++i) { */
/* 				zend_brk_cont_element *brk_cont = op_array->brk_cont_array + i; */

/* 				if (brk_cont->start < 0) { */
/* 					continue; */
/* 				} else if (brk_cont->start > op_num) { */
/* 					break; */
/* 				} else if (brk_cont->brk > op_num) { */
/* 					zend_op *brk_opline = op_array->opcodes + brk_cont->brk; */

/* 					if (brk_opline->opcode == ZEND_SWITCH_FREE) { */
/* 						temp_variable *var = EX_TMP_VAR(execute_data, brk_opline->op1.var); */

/* 						Z_ADDREF_P(var->var.ptr); */
/* 					} */
/* 				} */
/* 			} */
/* 		} */

/* 		/\* Update the send_target to use the temporary variable with the same */
/* 		 * offset as the original generator, but in our temporary variable */
/* 		 * memory segment. *\/ */
/* 		if (orig->send_target) { */
/* 			size_t offset = (char *) orig->send_target - (char *)execute_data; */
/* 			clone->send_target = EX_TMP_VAR(clone->execute_data, offset); */
/* 			zval_copy_ctor(&clone->send_target->tmp_var); */
/* 		} */

/* 		if (execute_data->current_this) { */
/* 			clone->execute_data->current_this = execute_data->current_this; */
/* 			Z_ADDREF_P(execute_data->current_this); */
/* 		} */
/* 	} */

/* 	/\* The value and key are known not to be references, so simply add refs *\/ */
/* 	if (orig->value) { */
/* 		clone->value = orig->value; */
/* 		Z_ADDREF_P(orig->value); */
/* 	} */

/* 	if (orig->key) { */
/* 		clone->key = orig->key; */
/* 		Z_ADDREF_P(orig->key); */
/* 	} */

/* 	return clone_val; */
/* } */
