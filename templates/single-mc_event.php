<?php
/**
 * Classic-theme fallback for a dated event occurrence.
 */

get_header();
?>
<main id="primary" class="site-main modern-catholic-events-occurrence-main">
    <?php echo modern_catholic_events_render_occurrence(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php
get_footer();

