<?php
/**
 * Popup Template: Right Side Panel
 *
 * Full-height side panel sliding in from the right.
 *
 * @package HashBar
 * @since 2.0.0
 *
 * Available variables:
 * @var int    $popup_id      The popup campaign ID
 * @var array  $settings      All popup settings
 * @var string $data_attrs    Data attributes string for JavaScript
 * @var string $popup_classes CSS classes for the popup container
 * @var string $inline_styles Inline styles for the popup container
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="hashbar-popup-<?php echo esc_attr( $popup_id ); ?>"
     class="hashbar-popup-campaign hashbar-popup-side_right <?php echo esc_attr( $popup_classes ); ?>"
     <?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
     role="dialog"
     aria-modal="true"
     aria-labelledby="hashbar-popup-heading-<?php echo esc_attr( $popup_id ); ?>">

	<div class="hashbar-popup-container" style="<?php echo esc_attr( $inline_styles ); ?>">
		<?php
		/**
		 * Hook: hashbar_popup_before_content
		 *
		 * @param int   $popup_id The popup ID
		 * @param array $settings The popup settings
		 */
		do_action( 'hashbar_popup_before_content', $popup_id, $settings );
		?>

		<div class="hashbar-popup-content">
			<?php
			/**
			 * Hook: hashbar_popup_content
			 *
			 * @param int   $popup_id The popup ID
			 * @param array $settings The popup settings
			 */
			do_action( 'hashbar_popup_content', $popup_id, $settings );
			?>
		</div>

		<?php
		/**
		 * Hook: hashbar_popup_after_content
		 *
		 * @param int   $popup_id The popup ID
		 * @param array $settings The popup settings
		 */
		do_action( 'hashbar_popup_after_content', $popup_id, $settings );
		?>
	</div>
</div>
