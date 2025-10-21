<?php
/**
 * The Template for displaying product archives (shop page)
 * Styles loaded from assetslp/css/woocommerce.css
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="woocommerce-wrapper">
    <?php if (is_shop() && !is_search() && !is_product_category()) : ?>
    <!-- Hero Section - Impactful design for main shop page -->
    <div class="shop-hero-section">
        <div class="hero-background" style="--hero-bg-image: url('https://opanije.com/shop/wp-content/uploads/2023/01/untitled-1-1-scaled.webp');"></div>
        <div class="hero-content-wrapper">
            <div class="hero-brand">
                <div class="hero-brand-frame">
                    <?php
                    $logo = get_field('logo', 'option');
                    if ($logo) : ?>
                        <div class="hero-logo">
                            <?php echo wp_get_attachment_image($logo, array(140, 140)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="hero-brand-name"><?php echo esc_html(get_bloginfo('name') ?: 'OPANIJE'); ?></div>
                    <div class="hero-brand-tagline"><?php echo esc_html(get_field('brand_tagline', 'option') ?: 'Authentic Drums & Percussion'); ?></div>
                    <div class="hero-brand-divider"></div>
                    <div class="hero-brand-trust">
                        <div class="trust-item">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            <span><?php echo esc_html(get_field('trust_text', 'option') ?: 'Trusted Worldwide'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-text-content">
                <h1 class="hero-title">Authentic Brazilian & African Drums</h1>
                <p class="hero-description">
                    Discover our curated collection of handcrafted percussion instruments.
                    Each drum tells a story of tradition, craftsmanship, and rhythm.
                    From the heart of Brazil and Africa to your hands.
                </p>
                <div class="hero-features">
                    <div class="hero-feature">
                        <i class="fas fa-hand-sparkles"></i>
                        <span>Handcrafted</span>
                    </div>
                    <div class="hero-feature">
                        <i class="fas fa-globe-americas"></i>
                        <span>Authentic</span>
                    </div>
                    <div class="hero-feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Quality Assured</span>
                    </div>
                </div>
            </div>

            <?php
            // Get a featured product image for the hero with caching
            $hero_image_html = get_transient('opanije_hero_product_image');

            if (false === $hero_image_html) {
                $featured_image_query = new WP_Query(array(
                    'post_type' => 'product',
                    'posts_per_page' => 1,
                    'meta_query' => array(
                        array(
                            'key' => '_featured',
                            'value' => 'yes'
                        ),
                        array(
                            'key' => '_thumbnail_id',
                            'compare' => 'EXISTS'
                        )
                    ),
                    'post_status' => 'publish'
                ));

                if ($featured_image_query->have_posts()) {
                    $featured_image_query->the_post();
                    $image_id = get_post_thumbnail_id();
                    if ($image_id) {
                        $hero_image_html = '<div class="hero-product-image">' .
                            wp_get_attachment_image($image_id, 'large') .
                            '</div>';
                    }
                    wp_reset_postdata();
                }

                // Cache for 1 hour
                set_transient('opanije_hero_product_image', $hero_image_html, HOUR_IN_SECONDS);
            }

            echo wp_kses_post($hero_image_html);
            ?>
        </div>
    </div>

    <!-- Featured Products Section -->
    <div class="featured-products-section">
        <div class="container-fluid" style="max-width: 90%;">
            <div class="section-header">
                <h2 class="section-title">Featured Instruments</h2>
                <p class="section-subtitle">Handpicked favorites from our collection</p>
            </div>

            <?php
            // Get featured products with images only and caching
            $featured_products_html = get_transient('opanije_featured_products');

            if (false === $featured_products_html) {
                $featured_query = new WP_Query(array(
                    'post_type' => 'product',
                    'posts_per_page' => 3,
                    'meta_query' => array(
                        array(
                            'key' => '_featured',
                            'value' => 'yes'
                        ),
                        array(
                            'key' => '_thumbnail_id',
                            'compare' => 'EXISTS'
                        )
                    ),
                    'post_status' => 'publish'
                ));

                ob_start();
                if ($featured_query->have_posts()) :
                    $valid_products = array();

                    // Collect valid products (with thumbnails only)
                    while ($featured_query->have_posts()) {
                        $featured_query->the_post();
                        if (has_post_thumbnail()) {
                            $valid_products[] = array(
                                'id' => get_the_ID(),
                                'permalink' => get_permalink(),
                                'title' => get_the_title(),
                                'thumbnail' => get_the_post_thumbnail(get_the_ID(), 'medium'),
                                'price' => wc_get_product(get_the_ID())->get_price_html()
                            );
                        }
                    }

                    // Display valid products
                    if (!empty($valid_products)) : ?>
                        <div class="featured-products-grid">
                            <?php foreach ($valid_products as $prod) : ?>
                                <div class="featured-product-card">
                                    <div class="featured-product-image">
                                        <a href="<?php echo esc_url($prod['permalink']); ?>">
                                            <?php echo $prod['thumbnail']; ?>
                                        </a>
                                    </div>
                                    <div class="featured-product-info">
                                        <h3>
                                            <a href="<?php echo esc_url($prod['permalink']); ?>">
                                                <?php echo esc_html($prod['title']); ?>
                                            </a>
                                        </h3>
                                        <span class="featured-price"><?php echo $prod['price']; ?></span>
                                        <a href="<?php echo esc_url($prod['permalink']); ?>" class="featured-product-button">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="no-featured-products"><?php esc_html_e('No featured products available at the moment.', 'opanije'); ?></p>
                    <?php endif;
                endif;
                wp_reset_postdata();

                $featured_products_html = ob_get_clean();

                // Cache for 1 hour
                set_transient('opanije_featured_products', $featured_products_html, HOUR_IN_SECONDS);
            }

            echo wp_kses_post($featured_products_html);
            ?>
        </div>
    </div>
    <?php else : ?>
    <!-- Simple header for category/search pages -->
    <div class="shop-header-simple">
        <div class="container-fluid" style="max-width: 90%;">
            <?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
                <h1 class="page-title"><?php woocommerce_page_title(); ?></h1>
            <?php endif; ?>

            <?php
            /**
             * Hook: woocommerce_archive_description.
             */
            do_action( 'woocommerce_archive_description' );
            ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="container-fluid" style="max-width: 90%; padding-bottom: 80px;">
        <div class="woocommerce">
            <?php
            if ( woocommerce_product_loop() ) {

                /**
                 * Hook: woocommerce_before_shop_loop.
                 *
                 * @hooked woocommerce_output_all_notices - 10
                 * @hooked woocommerce_result_count - 20
                 * @hooked woocommerce_catalog_ordering - 30
                 */
                do_action( 'woocommerce_before_shop_loop' );

                woocommerce_product_loop_start();

                if ( wc_get_loop_prop( 'total' ) ) {
                    while ( have_posts() ) {
                        the_post();

                        /**
                         * Hook: woocommerce_shop_loop.
                         */
                        do_action( 'woocommerce_shop_loop' );

                        wc_get_template_part( 'content', 'product' );
                    }
                }

                woocommerce_product_loop_end();

                /**
                 * Hook: woocommerce_after_shop_loop.
                 *
                 * @hooked woocommerce_pagination - 10
                 */
                do_action( 'woocommerce_after_shop_loop' );
            } else {
                /**
                 * Hook: woocommerce_no_products_found.
                 *
                 * @hooked wc_no_products_found - 10
                 */
                do_action( 'woocommerce_no_products_found' );
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
