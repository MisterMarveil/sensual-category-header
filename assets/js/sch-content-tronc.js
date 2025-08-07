document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner toutes les descriptions sensuelles
    const descriptions = document.querySelectorAll('.sensual-description');
    
    descriptions.forEach(description => {
        // Paramètres configurables
        const wordLimit = 60; // Nombre de mots avant troncature
        const fullText = description.innerHTML;
        var truncatedHtml = "";
        let isTruncated = true;
        
        // Fonction pour compter les mots dans un texte HTML
        function countWords(html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            return tempDiv.textContent.trim().split(/\s+/).length;
        }
        
        // Fonction pour tronquer le HTML en conservant les balises
        function truncateHtml(html, words) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            let wordCount = 0;
            let truncatedContent = '';
            
            function traverseNodes(node) {
                if (wordCount >= words) return;
                
                if (node.nodeType === Node.TEXT_NODE) {
                    const textWords = node.textContent.trim().split(/\s+/);
                    const remaining = words - wordCount;
                    
                    if (textWords.length > remaining) {
                        node.textContent = textWords.slice(0, remaining).join(' ') + '...';
                        wordCount = words;
                    } else {
                        wordCount += textWords.length;
                    }
                    truncatedContent += node.textContent;
                } 
                else if (node.nodeType === Node.ELEMENT_NODE) {
                    truncatedContent += `<${node.tagName.toLowerCase()}`;
                    
                    // Copier les attributs
                    for (const attr of node.attributes) {
                        truncatedContent += ` ${attr.name}="${attr.value}"`;
                    }
                    truncatedContent += '>';
                    
                    // Traiter les enfants
                    for (const child of node.childNodes) {
                        if (wordCount >= words) break;
                        traverseNodes(child);
                    }
                    
                    truncatedContent += `</${node.tagName.toLowerCase()}>`;
                }
            }
            
            traverseNodes(tempDiv);
            return truncatedContent;
        }
        
        // Créer le bouton "Lire plus"
        function createToggleButton() {
            const button = document.createElement('a');
            button.href = '#';
            button.className = 'read-more-toggle align-left';
            button.textContent = 'Lire plus >>';
            button.style.marginTop = '0px';
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (isTruncated) {
                    description.innerHTML = fullText;
                    button.textContent = '<< Lire moins';
                    isTruncated = false;
                    
                    // Faire défiler légèrement vers le haut pour une meilleure UX
                    description.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    description.classList.remove('truncated');                    
                } else {
                    description.innerHTML = truncatedHtml;
                    button.textContent = 'Lire plus >>';
                    isTruncated = true;
                    description.classList.add('truncated');
                }
                
                // Réinsérer le bouton après la mise à jour
                description.appendChild(button);
            });
            
            return button;
        }
        
        // Vérifier si la troncature est nécessaire
        const totalWords = countWords(fullText);
        if (totalWords > wordLimit) {
            truncatedHtml = truncateHtml(fullText, wordLimit);
            description.innerHTML = truncatedHtml;
            
            // Ajouter le bouton
            const toggleButton = createToggleButton();
            description.appendChild(toggleButton);
        }
    });
});