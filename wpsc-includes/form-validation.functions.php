<?php

function wpsc_validate_form( $form, $validated_array = null ) {
	if ( ! is_array( $validated_array ) )
		$validated_array = $_POST;

	$error = new WP_Error();
	$a =& $error;
	foreach ( $form as $field => $props ) {
		$rules = explode( '|', $props['rules'] );
		$value =& $validated_array[$field];

		foreach ( $rules as $rule ) {
			if ( function_exists( $rule ) ) {
				$value = call_user_func( $rule, $value );
				continue;
			}

			if ( preg_match( '/([^\[]+)\[([^\]]+)\]/', $rule, $matches ) ) {
				$rule = $matches[1];
				$matched_field = $matches[2];
				$matched_value = isset( $validated_array[$matched_field] ) ? $validated_array[$matched_field] : null;
				$matched_props = isset( $form[$matched_field] ) ? $form[$matched_field] : array();
				$error = apply_filters( "wpsc_validation_rule_{$rule}", $error, $value, $field, $props, $matched_field, $matched_value, $matched_props );
			} else {
				$error = apply_filters( "wpsc_validation_rule_{$rule}", $error, $value, $field, $props );
			}
		}
	}

	if ( count( $error->get_error_messages() ) )
		return $error;

	return true;
}

function wpsc_validation_rule_required( $error, $value, $field, $props ) {
	if ( $value === '' ) {
		$error_message = apply_filters( 'wpsc_validation_rule_required_message', __( 'The %s field is empty.', 'wpsc' ) );
		$title = isset( $prop['title'] ) ? $prop['title'] : $field;
		$error->add( $field, sprintf( $error_message, $props['title'] ), array( 'value' => $value, 'props' => $props ) );
	}

	return $error;
}
add_filter( 'wpsc_validation_rule_required', 'wpsc_validation_rule_required', 10, 4 );

function wpsc_validation_rule_valid_username_or_email( $error, $value, $field, $props ) {
	if ( strpos( $value, '@' ) ) {
		$user = get_user_by( 'email', $value );
		if ( empty( $user ) )
			$error->add( $field, __( 'There is no user registered with that email address.', 'wpsc' ), array( 'value' => $value, 'props' => $props) );
	} else {
		$user = get_user_by( 'login', $value );
		if ( empty( $user ) )
			$error->add( $field, __( 'There is no user registered with that username.', 'wpsc' ), array( 'value' => $value, 'props' => $props ) );
	}

	return $error;
}
add_filter( 'wpsc_validation_rule_valid_username_or_email', 'wpsc_validation_rule_valid_username_or_email', 10, 4 );

function wpsc_validation_rule_matches( $error, $value, $field, $props, $matched_field, $matched_value, $matched_props ) {
	if ( is_null( $matched_value ) || $value != $matched_value ) {
		$message = apply_filters( 'wpsc_validation_rule_fields_dont_match_message', __( 'The %s and %s fields do not match.', 'wpsc' ), $value, $field, $props, $matched_field, $matched_value, $matched_props );
		$title = isset( $props['title'] ) ? $props['title'] : $field;
		$matched_title = isset( $matched_props['title'] ) ? $matched_props['title'] : $field;
		$error->add( $field, sprintf( $message, $title, $matched_title ), array( 'value' => $value, 'props' => $props ) );
	}

	return $error;
}
add_filter( 'wpsc_validation_rule_matches', 'wpsc_validation_rule_matches', 10, 6 );add_filter( 'wpsc_validation_rule_matches', 'wpsc_validation_rule_matches', 10, 7 );