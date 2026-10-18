<?php
/**
 * Theme-facing Bolt Edit helpers.
 *
 * @package Bolt_Edit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return edit-mode attributes for a registered field, or an empty string for visitors.
 *
 * @internal
 *
 * @param array $field Registered field from Bolt_Edit_Fields::register().
 * @return string Escaped attribute string with a leading space.
 */
function bolt_edit_internal_edit_attributes( $field ) {
	if ( '' === $field['id'] || ! Bolt_Edit_Plugin::can_edit() ) {
		return '';
	}

	$attrs = array(
		'data-bolt-edit-field' => $field['id'],
		'data-bolt-edit-label' => $field['schema']['label'],
		'data-bolt-edit-type'  => $field['schema']['type'],
		'tabindex'       => '-1',
	);

	if ( ! empty( $field['schema']['max_length'] ) ) {
		$attrs['data-bolt-edit-max-length'] = (string) absint( $field['schema']['max_length'] );
	}

	$html = '';

	foreach ( $attrs as $name => $value ) {
		$html .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
	}

	return $html;
}

/**
 * Return sanitized theme classes from a helper's `class` argument.
 *
 * @internal
 *
 * @param array    $args          Helper arguments.
 * @param string[] $extra_classes Classes to add.
 * @return string
 */
function bolt_edit_internal_class_list( $args, $extra_classes = array() ) {
	$classes = isset( $args['class'] ) ? preg_split( '/\s+/', (string) $args['class'] ) : array();
	$classes = array_merge( $extra_classes, array_map( 'bolt_edit_internal_sanitize_class', $classes ) );

	return implode( ' ', array_unique( array_filter( $classes ) ) );
}

/**
 * Sanitize one theme class name while keeping utility-class syntax intact.
 *
 * Unlike sanitize_html_class(), this keeps the characters used by utility
 * frameworks such as Tailwind (`md:text-xl`, `leading-[1.7]`, `w-1/2`). Quotes,
 * angle brackets, and backslashes are removed; the result is escaped on output.
 *
 * @internal
 *
 * @param string $class Raw class name.
 * @return string
 */
function bolt_edit_internal_sanitize_class( $class ) {
	return (string) preg_replace( '/[^A-Za-z0-9_\-:\[\]\/\.%#()!,@&=+*~>]/', '', (string) $class );
}

/**
 * Return the inline image-slot style declared by a theme.
 *
 * @internal
 *
 * @param array $args Image settings.
 * @return string
 */
function bolt_edit_internal_image_style( $args ) {
	$rules = array();

	if (
		! empty( $args['aspect_ratio'] )
		&& preg_match( '#^\s*(\d+(?:\.\d+)?)\s*(?:[/:]\s*(\d+(?:\.\d+)?))?\s*$#', (string) $args['aspect_ratio'], $matches )
	) {
		$rules[] = 'aspect-ratio:' . $matches[1] . ( ! empty( $matches[2] ) ? ' / ' . $matches[2] : '' );
	}

	$fits = array( 'cover', 'contain', 'fill', 'none', 'scale-down' );

	if ( isset( $args['object_fit'] ) && in_array( $args['object_fit'], $fits, true ) ) {
		$rules[] = 'object-fit:' . $args['object_fit'];
	} elseif ( $rules ) {
		$rules[] = 'object-fit:cover';
	}

	if ( ! empty( $args['object_position'] ) && preg_match( '/^[a-z0-9.%\s-]+$/i', (string) $args['object_position'] ) ) {
		$rules[] = 'object-position:' . trim( $args['object_position'] );
	}

	return implode( ';', $rules );
}

/**
 * Render the editor-only placeholder for an image field without an image.
 *
 * It is an <img>, so the editor handles it like any image field: click it,
 * choose an image, save.
 *
 * @internal
 *
 * @param array $field Registered field from Bolt_Edit_Fields::register().
 * @param array $args  Image settings.
 * @return void
 */
function bolt_edit_internal_image_placeholder( $field, $args ) {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice">'
		. '<rect width="400" height="300" fill="#e9eaee"/>'
		. '<g fill="none" stroke="#8a8f98" stroke-width="6" stroke-linejoin="round">'
		. '<rect x="160" y="115" width="80" height="62" rx="6"/>'
		. '<path d="M166 170l22-24 16 16 12-12 18 20"/>'
		. '</g><circle cx="216" cy="133" r="7" fill="#8a8f98"/></svg>';
	$label = __( 'Choose image', 'bolt-edit' );
	$style = bolt_edit_internal_image_style( $args );

	printf(
		'<img src="%1$s" alt="%2$s" title="%2$s" class="%3$s" width="400" height="300"%4$s data-bolt-edit-field="%5$s" data-bolt-edit-label="%6$s" data-bolt-edit-type="image" data-bolt-edit-attachment-id="0" tabindex="-1">',
		esc_attr( 'data:image/svg+xml;charset=utf-8,' . rawurlencode( $svg ) ),
		esc_attr( $label ),
		esc_attr( trim( bolt_edit_internal_class_list( $args ) . ' bolt-edit-field bolt-edit-field--image bolt-edit-image-placeholder' ) ),
		$style ? ' style="' . esc_attr( $style ) . '"' : '',
		esc_attr( $field['id'] ),
		esc_attr( $field['schema']['label'] )
	);
}

if ( ! function_exists( 'bolt_edit_text' ) ) {
	/**
	 * Render a Bolt Edit-owned single-line text field.
	 *
	 * @param string $id   Stable field ID.
	 * @param array  $args Field settings: label, default, max_length, scope.
	 * @return void
	 */
	function bolt_edit_text( $id, $args = array() ) {
		$field = Bolt_Edit_Fields::register( 'text', $id, $args );
		$attrs = bolt_edit_internal_edit_attributes( $field );

		if ( '' === $attrs ) {
			echo esc_html( $field['value'] );
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped by bolt_edit_internal_edit_attributes().
		printf( '<span class="bolt-edit-field"%s>%s</span>', $attrs, esc_html( $field['value'] ) );
	}
}

if ( ! function_exists( 'bolt_edit_richtext' ) ) {
	/**
	 * Render a Bolt Edit-owned rich text field with bold, italic, links, and lists.
	 *
	 * Always rendered inside `<div class="bolt-edit-richtext">` so layout is the same
	 * for visitors and editors.
	 *
	 * @param string $id   Stable field ID.
	 * @param array  $args Field settings: label, default, max_length, class, scope.
	 * @return void
	 */
	function bolt_edit_richtext( $id, $args = array() ) {
		$field   = Bolt_Edit_Fields::register( 'richtext', $id, $args );
		$classes = bolt_edit_internal_class_list( $args, array( 'bolt-edit-richtext' ) );
		$attrs   = bolt_edit_internal_edit_attributes( $field );

		if ( '' !== $attrs ) {
			$classes .= ' bolt-edit-field';
		}

		printf(
			'<div class="%s"%s>%s</div>',
			esc_attr( $classes ),
			$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by bolt_edit_internal_edit_attributes().
			wp_kses( $field['value'], Bolt_Edit_Fields::richtext_tags() )
		);
	}
}

if ( ! function_exists( 'bolt_edit_button' ) ) {
	/**
	 * Render a Bolt Edit-owned link button with editable text and URL.
	 *
	 * `before` and `after` hold theme-controlled markup inside the link, such as
	 * a decorative icon. The editable text is then wrapped in
	 * `<span class="bolt-edit-button__text">`.
	 *
	 * @param string $id   Stable field ID.
	 * @param array  $args Field settings: label, default (array with text and url),
	 *                     max_length, class, target, before, after, scope.
	 * @return void
	 */
	function bolt_edit_button( $id, $args = array() ) {
		$field = Bolt_Edit_Fields::register( 'button', $id, $args );
		$value = $field['value'];
		$attrs = bolt_edit_internal_edit_attributes( $field );

		if ( '' === $value['text'] && '' === $attrs ) {
			return;
		}

		$classes = bolt_edit_internal_class_list( $args, '' !== $attrs ? array( 'bolt-edit-field' ) : array() );
		$target  = isset( $args['target'] ) && '_blank' === $args['target'] ? ' target="_blank" rel="noopener"' : '';
		$before  = isset( $args['before'] ) ? wp_kses_post( $args['before'] ) : '';
		$after   = isset( $args['after'] ) ? wp_kses_post( $args['after'] ) : '';
		$text    = esc_html( $value['text'] );

		if ( '' !== $before || '' !== $after ) {
			$text = '<span class="bolt-edit-button__text">' . $text . '</span>';
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Every part is escaped above or by bolt_edit_internal_edit_attributes().
		printf(
			'<a%s href="%s"%s%s>%s%s%s</a>',
			$classes ? ' class="' . esc_attr( $classes ) . '"' : '',
			esc_url( '' !== $value['url'] ? $value['url'] : '#' ),
			$target,
			$attrs,
			$before,
			$text,
			$after
		);
		// phpcs:enable
	}
}

if ( ! function_exists( 'bolt_edit_image' ) ) {
	/**
	 * Render a Media Library-backed image field.
	 *
	 * @param string $id   Stable field ID.
	 * @param array  $args Image settings: label, default (attachment ID), size, class,
	 *                     loading, fetchpriority, aspect_ratio, object_fit,
	 *                     object_position, scope.
	 * @return void
	 */
	function bolt_edit_image( $id, $args = array() ) {
		$field         = Bolt_Edit_Fields::register( 'image', $id, $args );
		$attachment_id = $field['value'];

		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
			// Visitors see nothing; editors get a clickable placeholder to choose an image.
			if ( '' !== $field['id'] && Bolt_Edit_Plugin::can_edit() ) {
				bolt_edit_internal_image_placeholder( $field, $args );
			}
			return;
		}

		$attrs = array(
			'class' => bolt_edit_internal_class_list( $args ),
		);
		$style = bolt_edit_internal_image_style( $args );

		if ( $style ) {
			$attrs['style'] = $style;
		}

		if ( isset( $args['loading'] ) ) {
			$attrs['loading'] = sanitize_key( $args['loading'] );
		}

		if ( isset( $args['fetchpriority'] ) ) {
			$attrs['fetchpriority'] = sanitize_key( $args['fetchpriority'] );
		}

		if ( '' !== $field['id'] && Bolt_Edit_Plugin::can_edit() ) {
			$attrs['class']                  = trim( $attrs['class'] . ' bolt-edit-field bolt-edit-field--image' );
			$attrs['data-bolt-edit-field']         = $field['id'];
			$attrs['data-bolt-edit-label']         = $field['schema']['label'];
			$attrs['data-bolt-edit-type']          = 'image';
			$attrs['data-bolt-edit-attachment-id'] = (string) $attachment_id;
			$attrs['tabindex']               = '-1';
		}

		echo wp_get_attachment_image( $attachment_id, $field['schema']['size'], false, $attrs );
	}
}

if ( ! function_exists( 'bolt_edit_managed_region_open' ) ) {
	/**
	 * Open a read-only region owned by ACF or another content provider.
	 *
	 * @param array $args Managed-region settings.
	 * @return void
	 */
	function bolt_edit_managed_region_open( $args = array() ) {
		$provider = isset( $args['provider'] ) ? sanitize_key( $args['provider'] ) : 'provider';
		$names    = array(
			'acf'         => 'ACF',
			'jetengine'   => 'JetEngine',
			'woocommerce' => 'WooCommerce',
			'wordpress'   => 'WordPress',
		);
		$name     = isset( $names[ $provider ] ) ? $names[ $provider ] : ucfirst( $provider );
		$label    = isset( $args['label'] ) && $args['label']
			? sanitize_text_field( $args['label'] )
			: sprintf(
				/* translators: %s: content provider name. */
				__( 'Editable in %s', 'bolt-edit' ),
				$name
			);
		$action_label = isset( $args['action_label'] ) && $args['action_label']
			? sanitize_text_field( $args['action_label'] )
			: __( 'Edit', 'bolt-edit' );
		$classes  = array( 'bolt-edit-managed-region' );

		if ( ! empty( $args['class'] ) ) {
			$extra_classes = preg_split( '/\s+/', (string) $args['class'] );
			$classes       = array_merge( $classes, array_map( 'bolt_edit_internal_sanitize_class', $extra_classes ) );
		}

		$classes = array_filter( array_unique( $classes ) );
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			<?php if ( Bolt_Edit_Plugin::can_edit() ) : ?>
				data-bolt-edit-managed="<?php echo esc_attr( $provider ); ?>"
				data-bolt-edit-managed-label="<?php echo esc_attr( $label ); ?>"
				data-bolt-edit-managed-action-label="<?php echo esc_attr( $action_label ); ?>"
				<?php if ( ! empty( $args['edit_url'] ) ) : ?>
					data-bolt-edit-managed-edit-url="<?php echo esc_url( $args['edit_url'] ); ?>"
				<?php endif; ?>
			<?php endif; ?>
		>
		<?php
	}
}

if ( ! function_exists( 'bolt_edit_managed_region_close' ) ) {
	/**
	 * Close a read-only provider region.
	 *
	 * @return void
	 */
	function bolt_edit_managed_region_close() {
		echo '</div>';
	}
}
