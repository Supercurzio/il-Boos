<?php
//SUPERCURZIO: Script esempio per aggiungere notitifca personalizzata. Nell'esempio su action. A me non serve, funzioni chiamate al momento.
// this is to add a fake component to BuddyPress. A registered component is needed to add notifications
function custom_filter_notifications_get_registered_components( $component_names = array() ) {

	// Force $component_names to be an array
	if ( ! is_array( $component_names ) ) {
		$component_names = array();
	}

	// Add 'custom' component to registered components array
	array_push( $component_names, 'custom' );

	// Return component's with 'custom' appended
	return $component_names;
}
add_filter( 'bp_notifications_get_registered_components', 'custom_filter_notifications_get_registered_components' );


// this gets the saved item id, compiles some data and then displays the notification
function custom_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	// New custom notifications
	if ( 'custom_action' === $action ) {
	
		$comment = get_comment( $item_id );
	
		$custom_title = $comment->comment_author . ' commented on the post ' . get_the_title( $comment->comment_post_ID );
		$custom_link  = get_comment_link( $comment );
		$custom_text = $comment->comment_author . ' commented on your post ' . get_the_title( $comment->comment_post_ID );

		// WordPress Toolbar
		if ( 'string' === $format ) {
			$return = apply_filters( 'custom_filter', '<a href="' . esc_url( $custom_link ) . '" title="' . esc_attr( $custom_title ) . '">' . esc_html( $custom_text ) . '</a>', $custom_text, $custom_link );

		// Deprecated BuddyBar
		} else {
			$return = apply_filters( 'custom_filter', array(
				'text' => $custom_text,
				'link' => $custom_link
			), $custom_link, (int) $total_items, $custom_text, $custom_title );
		}
		
		return $return;
		
	}
	
}
add_filter( 'bp_notifications_get_notifications_for_user', 'custom_format_buddypress_notifications', 10, 5 );


// this hooks to comment creation and saves the comment id
function bp_custom_add_notification( $comment_id, $comment_object ) {

	$post = get_post( $comment_object->comment_post_ID );
	$author_id = $post->post_author;

	bp_notifications_add_notification( array(
		'user_id'           => $author_id,
		'item_id'           => $comment_id,
		'component_name'    => 'custom',
		'component_action'  => 'custom_action',
		'date_notified'     => bp_core_current_time(),
		'is_new'            => 1,
	) );
	
}
add_action( 'wp_insert_comment', 'bp_custom_add_notification', 99, 2 );
