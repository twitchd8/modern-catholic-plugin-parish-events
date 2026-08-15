<?php
/**
 * Classic-theme fallback for Events and Event Category archives.
 */

get_header();
?>
<main id="primary" class="site-main modern-catholic-events-archive-main">
    <?php echo modern_catholic_events_render_archive(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php
get_footer();

