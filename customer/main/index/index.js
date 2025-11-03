document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Initializing page...');
        initializeMenu();
        initializeCarousel();
    initializeAddToCartButtons();
});

function initializeMenu() {
    const categoryButtons = document.querySelectorAll('.category-btn');
    const menuItems = document.querySelectorAll('.menu-item');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            const selectedCategory = button.dataset.category;
            
            menuItems.forEach(item => {
                if (selectedCategory === 'all' || item.dataset.category === selectedCategory) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

function initializeAddToCartButtons() {
    console.log('🛒 Initializing add to cart buttons...');
    
    const addToCartButtons = document.querySelectorAll('.add-to-cart, .order-now');
    console.log('Found', addToCartButtons.length, 'add to cart buttons');
    
    addToCartButtons.forEach(button => {
        console.log('Button:', button.textContent, 'Product ID:', button.dataset.productId);
        
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = button.dataset.productId;
            console.log('Button clicked, product ID:', productId);
            
            if (productId) {
                addToCart(productId, button);
            } else {
                console.error('No product ID found on button');
                showNotification('❌ Error: Product ID not found', true);
            }
        });
    });
}

function addToCart(productId, button) {
    console.log('🔄 Adding to cart, product ID:', productId);
    
    // Show quantity selector instead of directly adding
    showQuantitySelector(productId, button);
}

function showQuantitySelector(productId, button) {
    // Create quantity selector modal
    const modal = document.createElement('div');
    modal.className = 'quantity-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    `;

    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: #2a2a2a;
        padding: 2rem;
        border-radius: 12px;
        text-align: center;
        color: white;
        min-width: 300px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    `;

    // Get product name
    const productElement = button.closest('.menu-item, .recommendation-slide');
    const productName = productElement ? productElement.querySelector('h3').textContent : 'Product';

    modalContent.innerHTML = `
        <h3 style="margin-bottom: 1rem; color: #FF4B2B;">Add to Cart</h3>
        <p style="margin-bottom: 1.5rem; color: #a0a0a0;">${productName}</p>
        <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 2rem;">
            <button id="decreaseQty" style="
                background: #FF4B2B;
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                font-size: 1.2rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            ">-</button>
            <span id="quantityDisplay" style="
                font-size: 1.5rem;
                font-weight: bold;
                min-width: 50px;
                display: inline-block;
            ">1</span>
            <button id="increaseQty" style="
                background: #FF4B2B;
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                font-size: 1.2rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            ">+</button>
        </div>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button id="cancelBtn" style="
                background: #666;
                color: white;
                border: none;
                padding: 0.8rem 1.5rem;
                border-radius: 25px;
                cursor: pointer;
                font-weight: 500;
            ">Cancel</button>
            <button id="addBtn" style="
                background: linear-gradient(45deg, #FF4B2B, #FF416C);
                color: white;
                border: none;
                padding: 0.8rem 1.5rem;
                border-radius: 25px;
                cursor: pointer;
                font-weight: 500;
            ">Add to Cart</button>
        </div>
    `;

    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Quantity controls
    let quantity = 1;
    const quantityDisplay = document.getElementById('quantityDisplay');
    const decreaseBtn = document.getElementById('decreaseQty');
    const increaseBtn = document.getElementById('increaseQty');
    const cancelBtn = document.getElementById('cancelBtn');
    const addBtn = document.getElementById('addBtn');

    function updateQuantity() {
        quantityDisplay.textContent = quantity;
        decreaseBtn.disabled = quantity <= 1;
        decreaseBtn.style.opacity = quantity <= 1 ? '0.5' : '1';
    }

    decreaseBtn.addEventListener('click', () => {
        if (quantity > 1) {
            quantity--;
            updateQuantity();
        }
    });

    increaseBtn.addEventListener('click', () => {
        quantity++;
        updateQuantity();
    });

    cancelBtn.addEventListener('click', () => {
        document.body.removeChild(modal);
    });

    addBtn.addEventListener('click', () => {
        addToCartWithQuantity(productId, quantity, button);
        document.body.removeChild(modal);
    });

    // Close modal when clicking outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });

    updateQuantity();
}

function addToCartWithQuantity(productId, quantity, button) {
    console.log('🔄 Adding to cart, product ID:', productId, 'quantity:', quantity);
    
    // Show loading state
    const originalText = button.textContent;
    button.textContent = 'Adding...';
    button.disabled = true;
    
    // Add to cart via API
    fetch('../../api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: parseInt(productId),
            quantity: quantity
        })
    })
    .then(response => {
        console.log('📡 Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('📋 Response data:', data);
        if (data.success) {
            // Update cart count display
            updateCartCount(data.cart_count);
            showNotification(`✅ ${quantity} item(s) added to cart successfully!`);
        } else {
            if (data.message === 'User not logged in') {
                showNotification('⚠️ Please login to add items to cart', true);
                setTimeout(() => {
                    window.location.href = '../login/login.php';
                }, 2000);
    } else {
                showNotification('❌ Error: ' + data.message, true);
            }
        }
    })
    .catch(error => {
        console.error('💥 Error:', error);
        showNotification('💥 Error adding item to cart: ' + error.message, true);
    })
    .finally(() => {
        // Restore button state
        button.textContent = originalText;
        button.disabled = false;
    });
}

function updateCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        // Add a small animation to highlight the update
        cartCountElement.style.transform = 'scale(1.2)';
        setTimeout(() => {
            cartCountElement.style.transform = 'scale(1)';
        }, 200);
    }
}

function initializeCarousel() {
    const track = document.querySelector('.recommendations-track');
    if (!track) return;

    const slides = document.querySelectorAll('.recommendation-slide');
    const prevButton = document.querySelector('.prev-btn');
    const nextButton = document.querySelector('.next-btn');
    const indicators = document.querySelectorAll('.indicator');
    
    if (slides.length <= 1) {
        if(prevButton) prevButton.style.display = 'none';
        if(nextButton) nextButton.style.display = 'none';
        return;
    };
    
    let currentIndex = 0;
    const slideWidth = 100;

    const updateCarousel = (index) => {
        if (track) {
        track.style.transform = `translateX(-${index * slideWidth}%)`;
        }
        if (indicators.length > 0) {
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === index);
        });
        }
        if (prevButton && nextButton) {
        prevButton.style.opacity = index === 0 ? '0.5' : '1';
        nextButton.style.opacity = index === slides.length - 1 ? '0.5' : '1';
        }
        currentIndex = index;
    };
    
    if (prevButton) {
    prevButton.addEventListener('click', () => {
        if (currentIndex > 0) {
            updateCarousel(currentIndex - 1);
        }
    });
    }

    if (nextButton) {
    nextButton.addEventListener('click', () => {
        if (currentIndex < slides.length - 1) {
            updateCarousel(currentIndex + 1);
        }
    });
    }

    if (indicators.length > 0) {
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            updateCarousel(index);
        });
    });
    }
    
    let autoAdvanceInterval = setInterval(() => {
        const nextIndex = (currentIndex + 1) % slides.length;
        updateCarousel(nextIndex);
    }, 5000);
    
    const carouselContainer = document.querySelector('.recommendations-carousel');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', () => {
        clearInterval(autoAdvanceInterval);
    });
    
        carouselContainer.addEventListener('mouseleave', () => {
        autoAdvanceInterval = setInterval(() => {
            const nextIndex = (currentIndex + 1) % slides.length;
            updateCarousel(nextIndex);
        }, 5000);
    });
}
}

function showNotification(message, isError = false) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${isError ? '#dc3545' : '#28a745'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        font-weight: 500;
        max-width: 300px;
        animation: slideIn 0.3s ease-out;
        font-family: Arial, sans-serif;
    `;
    notification.textContent = message;
    
    // Add CSS animation
    if (!document.querySelector('#notification-style')) {
        const style = document.createElement('style');
        style.id = 'notification-style';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Debug function to test cart API
function testCartAPI() {
    console.log('🧪 Testing cart API...');
    fetch('../../api/cart.php?action=get')
        .then(response => response.json())
        .then(data => {
            console.log('Cart API response:', data);
        })
        .catch(error => {
            console.error('Cart API error:', error);
        });
}

// Auto-test on page load
window.addEventListener('load', function() {
    console.log('🚀 Page fully loaded');
    setTimeout(() => {
        testCartAPI();
    }, 2000);
    });
