<?php
/**
 * The Template for displaying single product pages
 * Styles loaded from assetslp/css/woocommerce.css
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<div class="woocommerce-wrapper">
    <div class="single-product-wrapper">
        <div class="container-fluid" style="max-width: 90%;">
            <?php
                while ( have_posts() ) :
                    the_post();
                    wc_get_template_part( 'content', 'single-product' );
                endwhile;
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>