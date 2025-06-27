document.addEventListener('DOMContentLoaded', () => {

    // Base URL for relative navigation
    window.baseUrl = window.baseUrl || '<?php echo BASE_URL; ?>';

    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    for (const button of togglePasswordButtons) {
        button.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('aria-label', 'Show password');
            }
        });
    }
    
    // Focus first input on auth pages
    const firstInput = document.querySelector('input[autofocus]');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Handle auth form submission
    const authForms = document.querySelectorAll('.auth-form');
    
    for (const form of authForms) {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const icon = submitBtn.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-spinner fa-spin mr-2';
                } else {
                    submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>${submitBtn.textContent}`;
                }
            }
        });
    }
    
    // Add input focus styles for auth inputs
    const authInputs = document.querySelectorAll('.auth-input');
    
    for (const input of authInputs) {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('input-focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('input-focused');
            }
        });
        
        if (input.value) {
            input.parentElement.classList.add('input-focused');
        }
    }
    
    // Back to top button
    const backToTop = document.getElementById('back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            backToTop.style.display = window.scrollY > 300 ? 'block' : 'none';
        });
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    } else {
        console.warn('Back to top button not found.');
    }

    // Hero Carousel functionality
    const carousel = document.getElementById('heroCarousel');
    const dots = document.querySelectorAll('.hero-pagination .dot');
    if (carousel && dots.length > 0) {
        let currentSlide = 0;
        const totalSlides = document.querySelectorAll('.hero-slide').length;

        function updateCarousel() {
            carousel.style.transform = `translateX(-${currentSlide * 100}%)`;
            for (const dot of dots) {
                dot.classList.remove('active');
            }
            dots[currentSlide].classList.add('active');
        }

        let autoSlide = setInterval(() => {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateCarousel();
        }, 5000);

        for (const dot of dots) {
            dot.addEventListener('click', () => {
                clearInterval(autoSlide);
                currentSlide = Number.parseInt(dot.getAttribute('data-slide'));
                updateCarousel();
                autoSlide = setInterval(() => {
                    currentSlide = (currentSlide + 1) % totalSlides;
                    updateCarousel();
                }, 5000);
            });
        }
    } else {
        console.warn('Hero carousel or pagination dots not found.');
    }

    // Initialize base URL
    if (!window.baseUrl) {
        console.error('window.baseUrl not defined. Please ensure header.php sets window.baseUrl.');
        window.baseUrl = ''; // Default to empty string; URLs will be relative
    }

    // Function to handle adding to cart
    const addToCartHandler = (button, redirectAfter = false) => {
        const productId = button.dataset.productId;
        if (!productId) {
            showNotification('Invalid product.', false);
            console.warn('Missing product ID.');
            return;
        }

        const originalText = button.textContent;
        button.textContent = redirectAfter ? 'Processing...' : 'Adding...';
        button.disabled = true;

        fetch(`${window.baseUrl}cart.php?add=${encodeURIComponent(productId)}`, {
            method: 'GET',
            headers: { 'ajax-request': 'true' }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            if (text.includes('Product added to cart')) {
                showNotification('Product added to cart successfully!', true);
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = Number.parseInt(cartCount.textContent || 0) + 1;
                }
                if (redirectAfter) {
                    setTimeout(() => {
                        window.location.href = `${window.baseUrl}cart.php`;
                    }, 500);
                }
            } else {
                showNotification(text || 'Failed to add product to cart.', false);
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            showNotification('Error adding to cart. Try again.', false);
        })
        .finally(() => {
            button.textContent = originalText;
            button.disabled = false;
        });
    };

    // Function to attach event listeners to buttons
    const attachButtonListeners = (selector, redirectAfter = false) => {
        const buttons = document.querySelectorAll(selector);
        for (const button of buttons) {
            button.removeEventListener('click', button._clickHandler);
            button._clickHandler = (e) => {
                e.stopPropagation();
                addToCartHandler(button, redirectAfter);
            };
            button.addEventListener('click', button._clickHandler);
        }
    };

    // Attach listeners for Add to Cart and Buy Now buttons
    attachButtonListeners('.add-to-cart', false);
    attachButtonListeners('.buy-now', true);

    // Product card click navigation
    const productCards = document.querySelectorAll('.our-product-item, .products-main-product');
    for (const card of productCards) {
        card.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart, .buy-now')) return;
            const productId = card.dataset.productId;
            if (productId) {
                window.location.href = `${window.baseUrl}products_description.php?product_id=${encodeURIComponent(productId)}`;
            }
        });
    }

    // Notification function
    function showNotification(message, isSuccess) {
        const notification = document.createElement('div');
        notification.className = `notification ${isSuccess ? 'success' : 'error'}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Cart controls
    const initializeCartControls = () => {
        const removeButtons = document.querySelectorAll('.cart-item-remove');
        for (const button of removeButtons) {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const href = button.getAttribute('href');
                const url = new URLSearchParams(href.split('?')[1]);
                const productId = url.get('remove');
                if (!productId) {
                    showNotification('Invalid product.', false);
                    return;
                }

                fetch(`${window.baseUrl}cart.php?remove=${encodeURIComponent(productId)}`, {
                    method: 'GET',
                    headers: { 'ajax-request': 'true' }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    if (text.includes('Product removed from cart')) {
                        showNotification('Product removed from cart.', true);
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        showNotification(text || 'Failed to remove product from cart.', false);
                    }
                })
                .catch(error => {
                    console.error('Error removing from cart:', error);
                    showNotification('Error removing from cart. Try again.', false);
                });
            });
        }

        const quantityInputs = document.querySelectorAll('.cart-item-quantity');
        for (const input of quantityInputs) {
            input.addEventListener('change', (e) => {
                const productId = input.dataset.productId;
                const newQty = Number.parseInt(e.target.value);
                if (!productId) {
                    showNotification('Invalid product.', false);
                    e.target.value = 1;
                    return;
                }
                if (newQty < 1) {
                    e.target.value = 1;
                    updateQuantity(productId, 1);
                } else {
                    updateQuantity(productId, newQty);
                }
            });
        }
    };

    const updateQuantity = (productId, quantity) => {
        fetch(`${window.baseUrl}cart.php?update=true&product_id=${encodeURIComponent(productId)}&quantity=${encodeURIComponent(quantity)}`, {
            method: 'GET',
            headers: { 'ajax-request': 'true' }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            if (text.includes('Quantity updated')) {
                showNotification('Quantity updated successfully.', true);
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                showNotification(text || 'Failed to update quantity.', false);
            }
        })
        .catch(error => {
            console.error('Error updating quantity:', error);
            showNotification('Error updating quantity. Try again.', false);
        });
    };

    if (document.querySelector('.cart-content-container')) {
        initializeCartControls();
    }

    // Compare feature
    const initializeCompareFeature = () => {
        const compareCheckboxes = document.querySelectorAll('.compare-product');
        const compareButton = document.querySelector('.compare-now-btn');
        const maxCompareItems = 4;
    
        // Load selected products from localStorage
        let selectedProducts = JSON.parse(localStorage.getItem('compareProducts')) || [];
    
        // Update checkboxes on products_description.php
        compareCheckboxes.forEach(checkbox => {
            const productId = checkbox.dataset.productId;
            checkbox.checked = selectedProducts.includes(productId);
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    if (selectedProducts.length >= maxCompareItems) {
                        checkbox.checked = false;
                        showNotification(`You can compare up to ${maxCompareItems} products.`, false);
                        return;
                    }
                    if (!selectedProducts.includes(productId)) {
                        selectedProducts.push(productId);
                        showNotification('Product added to comparison.', true);
                    }
                } else {
                    selectedProducts = selectedProducts.filter(id => id !== productId);
                    showNotification('Product removed from comparison.', true);
                }
                localStorage.setItem('compareProducts', JSON.stringify(selectedProducts));
            });
        });
    
        // Handle Compare Selected button on products.php
        if (compareButton) {
            compareButton.addEventListener('click', (e) => {
                e.preventDefault();
                if (selectedProducts.length < 2) {
                    showNotification('Please select at least 2 products to compare.', false);
                    return;
                }
                window.location.href = `${window.baseUrl}compare.php?products=${encodeURIComponent(selectedProducts.join(','))}`;
            });
        }
    
        // Handle Remove and Clear All buttons on compare.php
        const removeButtons = document.querySelectorAll('.compare-remove-btn');
        removeButtons.forEach(button => {
            button.addEventListener('click', () => {
                const productId = button.dataset.productId;
                selectedProducts = selectedProducts.filter(id => id !== productId);
                localStorage.setItem('compareProducts', JSON.stringify(selectedProducts));
                window.location.href = `${window.baseUrl}compare.php?products=${encodeURIComponent(selectedProducts.join(','))}`;
            });
        });
    
        const clearButton = document.querySelector('.compare-clear-btn');
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                selectedProducts = [];
                localStorage.setItem('compareProducts', JSON.stringify(selectedProducts));
                window.location.href = `${window.baseUrl}compare.php`;
            });
        }
    };
    // Initialize comparison feature
    initializeCompareFeature();
});