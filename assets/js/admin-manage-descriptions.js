jQuery(document).ready(function($) {
    // Configuration
    const ajaxurl = sch_admin_params.ajax_url;
    const nonce = sch_admin_params.nonce;
    
    // Recherche AJAX
    $('#category-search').on('input', function() {
        const searchTerm = $(this).val().trim();
        
        if (searchTerm.length < 2) {
            $('#search-results').empty().hide();
            return;
        }
        
        $.get(ajaxurl, {
            action: 'sch_search_categories',
            term: searchTerm,
            security: nonce
        }, function(response) {
            if (response.results && response.results.length) {
                let html = '<ul class="sch-search-results">';
                response.results.forEach(item => {
                    html += `<li data-id="${item.id}">${item.text}</li>`;
                });
                html += '</ul>';
                $('#search-results').html(html).show();
            } else {
                $('#search-results').html('<p>Aucun résultat</p>').show();
            }
        });
    });
    
    // Sélection d'un résultat
    $(document).on('click', '.sch-search-results li', function() {
        const categoryId = $(this).data('id');
        const categoryName = $(this).text();
        
        $('#selected-category-id').val(categoryId);
        $('#selected-category-name').text(categoryName);
        
        // Charger la description
        $.post(ajaxurl, {
            action: 'sch_get_description',
            category_id: categoryId,
            security: nonce
        }, function(response) {
            if (response.success) {
                $('#description-editor').val(response.data.description);
                $('#editor-section').slideDown();
            }
        });
    });
    
    // Sauvegarde
    $('#save-description').click(function() {
        const categoryId = $('#selected-category-id').val();
        const description = $('#description-editor').val();
        
        $.post(ajaxurl, {
            action: 'sch_save_description',
            category_id: categoryId,
            description: description,
            security: nonce
        }, function(response) {
            showFeedback(response.data.message, 'success');
        }).fail(function() {
            showFeedback('Erreur serveur', 'error');
        });
    });
    
    // Suppression
    $('#delete-description').click(function() {
        if (!confirm('Supprimer définitivement cette description?')) return;
        
        const categoryId = $('#selected-category-id').val();
        
        $.post(ajaxurl, {
            action: 'sch_delete_description',
            category_id: categoryId,
            security: nonce
        }, function(response) {
            if (response.success) {
                showFeedback(response.data.message, 'success');
                $('#description-editor').val('');
                setTimeout(() => $('#editor-section').slideUp(), 1500);
            }
        });
    });
    
    // Affichage des feedbacks
    function showFeedback(message, type) {
        const $feedback = $('#action-feedback');
        $feedback.text(message).removeClass('success error').addClass(type).show();
        setTimeout(() => $feedback.fadeOut(), 3000);
    }
});