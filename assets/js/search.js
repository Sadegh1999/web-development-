/**
 * Movie Search Functionality
 * This script enhances the search form to work with AJAX for searching movies
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get search form and input elements
    const searchForm = document.querySelector('form[action="search.php"]');
    const searchInput = searchForm.querySelector('input[name="q"]');
    const resultsContainer = document.createElement('div');
    
    // Add search results container
    resultsContainer.className = 'search-results-container';
    resultsContainer.style.display = 'none';
    searchForm.appendChild(resultsContainer);
    
    // Style the search results container
    resultsContainer.style.position = 'absolute';
    resultsContainer.style.top = '100%';
    resultsContainer.style.left = '0';
    resultsContainer.style.right = '0';
    resultsContainer.style.backgroundColor = '#222';
    resultsContainer.style.borderRadius = '0 0 8px 8px';
    resultsContainer.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';
    resultsContainer.style.maxHeight = '350px';
    resultsContainer.style.overflowY = 'auto';
    resultsContainer.style.zIndex = '1000';
    
    // Add event listeners
    searchInput.addEventListener('focus', handleSearchFocus);
    searchInput.addEventListener('input', debounce(handleSearchInput, 300));
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
    
    // Override form submission to handle search
    searchForm.addEventListener('submit', function(e) {
        const searchTerm = searchInput.value.trim();
        
        if (searchTerm.length < 2) {
            e.preventDefault();
            return;
        }
        
        // If we have an active selection in dropdown, navigate to it instead
        const activeResult = resultsContainer.querySelector('.search-result-item.active');
        if (activeResult && resultsContainer.style.display !== 'none') {
            e.preventDefault();
            window.location.href = activeResult.getAttribute('data-url');
        }
    });
    
    // Focus handler
    function handleSearchFocus() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm.length >= 2) {
            performSearch(searchTerm);
        }
    }
    
    // Input handler with debounce to avoid too many requests
    function handleSearchInput() {
        const searchTerm = searchInput.value.trim();
        
        if (searchTerm.length < 2) {
            resultsContainer.style.display = 'none';
            return;
        }
        
        performSearch(searchTerm);
    }
    
    // Perform AJAX search
    function performSearch(term) {
        // First try client-side search in the existing movie cards on the page
        const movies = performClientSideSearch(term);
        
        if (movies.length > 0) {
            displayResults(movies);
            return;
        }
        
        // If no client-side results or need more, perform AJAX request
        fetch(`search.php?q=${encodeURIComponent(term)}&ajax=1`)
            .then(response => response.json())
            .then(data => {
                displayResults(data.movies || []);
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }
    
    // Search in current page movie cards first (for faster results)
    function performClientSideSearch(term) {
        const results = [];
        const movieCards = document.querySelectorAll('.movie-card');
        const termLower = term.toLowerCase();
        
        movieCards.forEach(card => {
            const title = card.querySelector('.movie-title').textContent.toLowerCase();
            const link = card.querySelector('a[href^="movie.php"]');
            
            if (title.includes(termLower) && link) {
                const id = new URL(link.href).searchParams.get('id');
                const imgSrc = card.querySelector('.movie-poster img').src;
                const price = card.querySelector('.movie-price').textContent;
                
                results.push({
                    id: id,
                    title: card.querySelector('.movie-title').textContent,
                    image: imgSrc,
                    price: price
                });
            }
        });
        
        return results;
    }
    
    // Display search results
    function displayResults(movies) {
        resultsContainer.innerHTML = '';
        
        if (movies.length === 0) {
            resultsContainer.innerHTML = '<div class="search-no-results">No movies found</div>';
            resultsContainer.style.display = 'block';
            return;
        }
        
        movies.forEach(movie => {
            const resultItem = document.createElement('div');
            resultItem.className = 'search-result-item';
            resultItem.setAttribute('data-url', `movie.php?id=${movie.id}`);
            
            resultItem.innerHTML = `
                <div class="search-result-image">
                    <img src="${movie.image || movie.poster_url || 'assets/images/placeholder.jpg'}" alt="${movie.title}">
                </div>
                <div class="search-result-info">
                    <div class="search-result-title">${movie.title}</div>
                    <div class="search-result-price">${movie.price}</div>
                </div>
            `;
            
            // Style the result item
            resultItem.style.display = 'flex';
            resultItem.style.alignItems = 'center';
            resultItem.style.padding = '10px 15px';
            resultItem.style.borderBottom = '1px solid rgba(255,255,255,0.1)';
            resultItem.style.cursor = 'pointer';
            resultItem.style.transition = 'background-color 0.2s';
            
            // Style the image container
            const imageContainer = resultItem.querySelector('.search-result-image');
            imageContainer.style.width = '40px';
            imageContainer.style.height = '60px';
            imageContainer.style.marginRight = '15px';
            imageContainer.style.overflow = 'hidden';
            imageContainer.style.borderRadius = '4px';
            
            // Style the image
            const image = resultItem.querySelector('img');
            image.style.width = '100%';
            image.style.height = '100%';
            image.style.objectFit = 'cover';
            
            // Style the info container
            const infoContainer = resultItem.querySelector('.search-result-info');
            infoContainer.style.flex = '1';
            
            // Style the title
            const title = resultItem.querySelector('.search-result-title');
            title.style.fontWeight = 'bold';
            title.style.color = '#fff';
            title.style.marginBottom = '5px';
            
            // Style the price
            const price = resultItem.querySelector('.search-result-price');
            price.style.color = '#e50914';
            price.style.fontSize = '0.9rem';
            
            // Add hover effect
            resultItem.addEventListener('mouseover', function() {
                this.style.backgroundColor = 'rgba(255,255,255,0.1)';
                // Remove active class from all items and add to this one
                resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            });
            
            resultItem.addEventListener('mouseout', function() {
                this.style.backgroundColor = '';
            });
            
            // Add click handler
            resultItem.addEventListener('click', function() {
                window.location.href = this.getAttribute('data-url');
            });
            
            resultsContainer.appendChild(resultItem);
        });
        
        // Style no results message if present
        const noResults = resultsContainer.querySelector('.search-no-results');
        if (noResults) {
            noResults.style.padding = '15px';
            noResults.style.textAlign = 'center';
            noResults.style.color = '#999';
        }
        
        resultsContainer.style.display = 'block';
    }
    
    // Keyboard navigation for search results
    searchInput.addEventListener('keydown', function(e) {
        const items = resultsContainer.querySelectorAll('.search-result-item');
        
        if (items.length === 0 || resultsContainer.style.display === 'none') {
            return;
        }
        
        let activeItem = resultsContainer.querySelector('.search-result-item.active');
        let activeIndex = -1;
        
        if (activeItem) {
            items.forEach((item, index) => {
                if (item === activeItem) {
                    activeIndex = index;
                }
            });
        }
        
        // Arrow down
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            
            activeIndex = (activeIndex + 1) % items.length;
            updateActiveItem(items, activeIndex);
        }
        
        // Arrow up
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            
            activeIndex = (activeIndex - 1 + items.length) % items.length;
            updateActiveItem(items, activeIndex);
        }
        
        // Enter key
        if (e.key === 'Enter' && activeItem) {
            e.preventDefault();
            window.location.href = activeItem.getAttribute('data-url');
        }
    });
    
    function updateActiveItem(items, activeIndex) {
        items.forEach((item, index) => {
            if (index === activeIndex) {
                item.classList.add('active');
                item.style.backgroundColor = 'rgba(255,255,255,0.1)';
                
                // Scroll into view if needed
                const itemTop = item.offsetTop;
                const itemBottom = itemTop + item.offsetHeight;
                const containerTop = resultsContainer.scrollTop;
                const containerBottom = containerTop + resultsContainer.offsetHeight;
                
                if (itemTop < containerTop) {
                    resultsContainer.scrollTop = itemTop;
                } else if (itemBottom > containerBottom) {
                    resultsContainer.scrollTop = itemBottom - resultsContainer.offsetHeight;
                }
            } else {
                item.classList.remove('active');
                item.style.backgroundColor = '';
            }
        });
    }
    
    // Utility function to debounce
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }
}); 