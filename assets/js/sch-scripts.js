jQuery(document).ready(function($) {
    // Animation des cartes marketing
    $('.marketing-card').hover(function() {
        $(this).find('.card-hover-content').fadeTo(200, 1);
    }, function() {
        $(this).find('.card-hover-content').fadeTo(200, 0);
    });
    
    // Filtres rapides
    $('.filter-options input').change(function() {
        let filters = {};
        
        $('.filter-group').each(function() {
            const filterName = $(this).find('h4').text().toLowerCase();
            filters[filterName] = [];
            
            $(this).find('input:checked').each(function() {
                filters[filterName].push($(this).val());
            });
        });
        
        // Ici: logique pour filtrer les produits
        console.log('Filtres appliqués:', filters);
        // Normalement: requête AJAX pour mettre à jour les produits
    });
    
    // Mode d'aperçu pour les options de couleur
    $('.color-option').hover(function() {
        const color = $(this).css('background-color');
        $(this).closest('.filter-group').find('h4').css('border-bottom-color', color);
    }, function() {
        $(this).closest('.filter-group').find('h4').css('border-bottom-color', 'rgba(255,255,255,0.2)');
    });
});