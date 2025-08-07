<style type="text/css">
    .sch-header-block { padding:20px; background:#f9f0f5; border-radius:10px; margin-bottom:30px; }
    .description-content { font-size:1.1em; line-height:1.5; }
    .filter-widgets { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; }     
    <?php //echo wp_strip_all_tags( $css ); ?>
</style>
<?php echo $html; ?>
<?php if (!empty($subcategories)) : ?>
<div class="subcategories-card">
    <h3 class="subcategories-title">Découvrez nos collections</h3>
    <div class="subcategories-grid">
        <?php foreach ($subcategories as $subcat) : 
            $url = add_query_arg([
                'wpf_filter_cat_list_0s' => $subcat->slug,
                'wpf_fbv' => 1
            ], get_permalink( wc_get_page_id( 'shop' ) ));
        ?>
            <a href="<?php echo esc_url($url); ?>" class="subcategory-button">
                <?php echo esc_html($subcat->name); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<!-- Nouvelle section pour les filtres -->
<div class="sch-product-filters">
    <h3>Filtrer les produits</h3>
    <?php 
    if (shortcode_exists('woof')) {
        echo do_shortcode('[woof]');
    } else {
        echo '<p>Installez le plugin WOOF pour les filtres</p>';
    }
    ?>
</div>
<!--div class="sch-header-block">
    <div class="description-content"><?php //echo $html; ?></div>
    <div class="filter-widgets"><?php //do_action( 'sch_render_widgets', get_queried_object() ); ?></div>
</div-->
