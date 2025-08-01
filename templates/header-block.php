?>
<div class="sch-header-block">
    <div class="description-content"><?php echo $html; ?></div>
    <div class="filter-widgets"><?php do_action( 'sch_render_widgets', get_queried_object() ); ?></div>
</div>
<style>
.sch-header-block { padding:20px; background:#f9f0f5; border-radius:10px; margin-bottom:30px; }
.description-content { font-size:1.1em; line-height:1.5; }
.filter-widgets { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; }
</style>