<?php
namespace Hashbar\Pro;

/**
 * A/B Test Winner Determination
 *
 * Determines the winner of an A/B test using statistical significance calculations.
 *
 * @package HashBar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Hashbar\Pro\ABTest\Statistics;

/**
 * Determine A/B test winner
 *
 * @param int $bar_id Announcement bar ID.
 * @return array Winner information with statistical significance data.
 */
function hashbar_ab_test_determine_winner( $bar_id ) {
	// Get statistics
	$stats = Statistics::get_statistics( $bar_id );

	if ( empty( $stats['variants'] ) || count( $stats['variants'] ) < 2 ) {
		return array(
			'winner' => null,
			'significant' => false,
			'message' => __( 'Not enough variants or data to determine winner', 'hashbar' ),
		);
	}

	$variants = $stats['variants'];

	// Find control variant
	$control = null;
	$test_variants = array();
	foreach ( $variants as $variant ) {
		if ( $variant['variant_id'] === 'control' || $variant['variant_id'] === '' ) {
			$control = $variant;
		} else {
			$test_variants[] = $variant;
		}
	}

	// If no control variant found but we have test variants, create a default control
	if ( ! $control && ! empty( $test_variants ) ) {
		$control = array(
			'variant_id' => 'control',
			'variant_name' => __( 'Control (Original)', 'hashbar' ),
			'unique_visitors' => 0,
			'impressions' => 0,
			'clicks' => 0,
			'conversions' => 0,
			'countdown_views' => 0,
			'coupon_copies' => 0,
			'ctr' => 0,
			'conversion_rate' => 0,
		);
	}

	if ( ! $control || empty( $test_variants ) ) {
		return array(
			'winner' => null,
			'significant' => false,
			'message' => __( 'Not enough data to determine winner. Continue testing.', 'hashbar' ),
		);
	}

	// Compare each test variant against control
	$comparisons = array();
	$best_variant = null;
	$best_improvement = 0;

	foreach ( $test_variants as $variant ) {
		$comparison = hashbar_ab_test_calculate_significance( $control, $variant );
		$comparisons[ $variant['variant_id'] ] = $comparison;

		// Calculate improvement
		if ( $control['conversion_rate'] > 0 ) {
			$improvement = ( ( $variant['conversion_rate'] - $control['conversion_rate'] ) / $control['conversion_rate'] ) * 100;
		} else {
			$improvement = $variant['conversion_rate'] > 0 ? 100 : 0;
		}

		if ( $improvement > $best_improvement ) {
			$best_improvement = $improvement;
			$best_variant = $variant;
		}
	}

	// Determine if there's a significant winner
	$winner = null;
	$significant = false;

	if ( $best_variant && isset( $comparisons[ $best_variant['variant_id'] ] ) ) {
		$best_comparison = $comparisons[ $best_variant['variant_id'] ];
		if ( $best_comparison['significant'] && $best_comparison['z_score'] > 0 ) {
			$winner = $best_variant;
			$significant = true;
		}
	}

	return array(
		'winner' => $winner,
		'significant' => $significant,
		'best_variant' => $best_variant,
		'improvement' => round( $best_improvement, 2 ),
		'comparisons' => $comparisons,
		'control' => $control,
		'message' => $significant
			? sprintf( __( '%s is the winner with %.2f%% improvement (%.1f%% confidence)', 'hashbar' ), $winner['variant_name'], $best_improvement, $best_comparison['confidence_level'] )
			: __( 'No statistically significant winner yet. Continue testing.', 'hashbar' ),
	);
}

/**
 * Calculate statistical significance between two variants
 *
 * @param array $variant_a First variant statistics.
 * @param array $variant_b Second variant statistics.
 * @return array Significance calculation results.
 */
function hashbar_ab_test_calculate_significance( $variant_a, $variant_b ) {
	$n1 = $variant_a['impressions'];
	$x1 = $variant_a['conversions'];
	$p1 = $n1 > 0 ? $x1 / $n1 : 0;

	$n2 = $variant_b['impressions'];
	$x2 = $variant_b['conversions'];
	$p2 = $n2 > 0 ? $x2 / $n2 : 0;

	// Need minimum sample size for significance testing
	if ( $n1 < 30 || $n2 < 30 ) {
		return array(
			'z_score' => 0,
			'p_value' => 1.0,
			'significant' => false,
			'confidence_level' => 0,
			'message' => __( 'Insufficient sample size (need at least 30 impressions per variant)', 'hashbar' ),
		);
	}

	// Pooled proportion
	$p_pool = ( $x1 + $x2 ) / ( $n1 + $n2 );

	// Standard error
	$se = sqrt( $p_pool * ( 1 - $p_pool ) * ( 1 / $n1 + 1 / $n2 ) );

	if ( $se === 0 ) {
		return array(
			'z_score' => 0,
			'p_value' => 1.0,
			'significant' => false,
			'confidence_level' => 0,
			'message' => __( 'Cannot calculate significance (zero standard error)', 'hashbar' ),
		);
	}

	// Z-score
	$z = ( $p1 - $p2 ) / $se;

	// P-value (two-tailed test)
	// Using normal approximation
	$p_value = 2 * ( 1 - hashbar_ab_test_normal_cdf( abs( $z ) ) );

	// Significance threshold (95% confidence = p < 0.05)
	$significant = $p_value < 0.05;
	$confidence_level = ( 1 - $p_value ) * 100;

	return array(
		'z_score' => round( $z, 4 ),
		'p_value' => round( $p_value, 4 ),
		'significant' => $significant,
		'confidence_level' => round( $confidence_level, 2 ),
		'message' => $significant
			? sprintf( __( 'Statistically significant (p=%.4f, %.1f%% confidence)', 'hashbar' ), $p_value, $confidence_level )
			: sprintf( __( 'Not significant (p=%.4f, %.1f%% confidence)', 'hashbar' ), $p_value, $confidence_level ),
	);
}

/**
 * Normal CDF approximation (cumulative distribution function)
 *
 * @param float $z Z-score.
 * @return float Probability.
 */
function hashbar_ab_test_normal_cdf( $z ) {
	// Abramowitz and Stegun approximation
	$sign = 1;
	if ( $z < 0 ) {
		$sign = -1;
		$z = -$z;
	}

	$a1 = 0.254829592;
	$a2 = -0.284496736;
	$a3 = 1.421413741;
	$a4 = -1.453152027;
	$a5 = 1.061405429;
	$p = 0.3275911;

	$t = 1.0 / ( 1.0 + $p * $z );
	$y = 1.0 - ( ( ( ( ( $a5 * $t + $a4 ) * $t ) + $a3 ) * $t + $a2 ) * $t + $a1 ) * $t * exp( -$z * $z );

	return 0.5 * ( 1.0 + $sign * $y );
}



