jQuery(document).ready(function($) {
    // Configuration
    const ajaxurl = sch_admin_params.ajax_url;
    const nonce = sch_admin_params.nonce;
    let searchTimer = null;
    const $searchResults = $('#search-results');
    const $editorSection = $('#editor-section');
    const $editorLoader = $('.editor-loader');
    const $descriptionEditor = $('#description-editor');
    const $editorActions = $('.editor-actions');
    const $categorySearch = $('#category-search');
    
    
    // Recherche AJAX avec délai
    $categorySearch.on('input', function() {
        // Réinitialiser l'éditeur si une recherche est relancée
        if ($editorSection.is(':visible')) {
            $editorSection.slideUp();
            $descriptionEditor.val('');
        }
        
        const searchTerm = $(this).val().trim();
        
        // Clear previous timer
        clearTimeout(searchTimer);
        
        // Hide results while typing
        $searchResults.hide();
        
        if (searchTerm.length < 2) {
            $searchResults.empty();
            return;
        }
        
        // Show loading indicator
        $searchResults.html('<div class="sch-loader"></div>').show();
        
        // Set new timer
        searchTimer = setTimeout(() => {
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
                    $searchResults.html(html).show();
                } else {
                    $searchResults.html('<div class="sch-no-results">Aucune catégorie ne correspond au terme de recherche</div>').show();
                }
            }).fail(function() {
                $searchResults.html('<div class="sch-error">Erreur de recherche. Veuillez réessayer.</div>').show();
            });
        }, 400); // 400ms delay
    });
    
    // Sélection d'un résultat
    $(document).on('click', '.sch-search-results li', function() {
        const categoryId = $(this).data('id');
        const categoryName = $(this).text();
        
        // Masquer les résultats et réinitialiser le champ
        $searchResults.hide().empty();
        $categorySearch.val('');
        
        // Afficher le loader dans l'éditeur
        $editorSection.show();
        $descriptionEditor.hide();
        $editorActions.hide();
        $editorLoader.show();
        
        // Mettre à jour les champs
        $('#selected-category-id').val(categoryId);
        $('#selected-category-name').text(categoryName);
        
        // Charger la description
        $.post(ajaxurl, {
            action: 'sch_get_description',
            category_id: categoryId,
            security: nonce
        }, function(response) {
            if (response.success) {
                $descriptionEditor.val(response.data.description);
                
                // Cacher loader, afficher contenu
                setTimeout(() => {
                    $editorLoader.hide();
                    $descriptionEditor.show();
                    $editorActions.show();
                }, 300);
            }
        }).fail(function() {
            // Afficher un message si aucune description n'est trouvée
            $editorLoader.hide();
            $descriptionEditor.show().val('Aucune description trouvée pour cette catégorie.');
            $editorActions.show();
            $('#delete-description').hide(); // Cacher le bouton supprimer
        });
    });
    
    // Sauvegarde
    $('#save-description').click(function() {
        const $button = $(this);
        const originalText = $button.text();
        const categoryId = $('#selected-category-id').val();
        const description = $('#description-editor').val();
        
        $button.text('Enregistrement...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'sch_save_description',
            category_id: categoryId,
            description: description,
            security: nonce
        }, function(response) {
            showFeedback(response.data.message, 'success');
        }).fail(function() {
            showFeedback('Erreur serveur', 'error');
        }).always(function() {
            $button.text(originalText).prop('disabled', false);
        });
    });
    
    // Suppression
    $('#delete-description').click(function() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement cette description?')) return;
        
        const $button = $(this);
        const originalText = $button.text();
        const categoryId = $('#selected-category-id').val();
        
        $button.text('Suppression...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'sch_delete_description',
            category_id: categoryId,
            security: nonce
        }, function(response) {
            if (response.success) {
                showFeedback(response.data.message, 'success');
                $descriptionEditor.val('');
                setTimeout(() => {
                    $editorSection.slideUp();
                    $button.text(originalText).prop('disabled', false);
                }, 1500);
            }
        }).fail(function() {
            showFeedback('Erreur serveur', 'error');
            $button.text(originalText).prop('disabled', false);
        });
    });
    
     // Affichage des feedbacks
    function showFeedback(message, type) {
        const $feedback = $('#action-feedback');
        $feedback.text(message).removeClass('success error').addClass(type).show();
        setTimeout(() => $feedback.fadeOut(), 3000);
    }
    
    // Réinitialisation de la recherche
    $('#reset-search').click(function() {
        $categorySearch.val('');
        $searchResults.hide().empty();
        $editorSection.slideUp();
        $descriptionEditor.val('');
    });
});