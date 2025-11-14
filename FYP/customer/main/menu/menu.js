// Load header and footer
document.addEventListener('DOMContentLoaded', () => {
    fetch('../../includes/header.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById('header').innerHTML = data;
            // Initialize menu after header is loaded
            loadMenuItems();
            setupEventListeners();
        })
        .catch(error => {
            console.error('Error loading header:', error);
        });

    fetch('../../includes/footer.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById('footer').innerHTML = data;
        })
        .catch(error => {
            console.error('Error loading footer:', error);
        });
});

// Menu items data
const menuItems = [
    {
        id: 1,
        name: "Nasi Campur",
        category: "indonesia",
        description: "Mixed rice with various Indonesian side dishes and sambal",
        longDescription: "A traditional Indonesian dish featuring a variety of flavors and textures. Includes steamed rice served with a selection of small portions of meat, vegetables, peanuts, eggs, and fried-shrimp krupuk. Accompanied by our special sambal for an authentic taste experience.",
        ingredients: ["Steamed rice", "Chicken satay", "Beef rendang", "Vegetables", "Fried tempeh", "Sambal", "Peanuts", "Shrimp crackers"],
        spicyLevel: "Medium",
        price: 14.99,
        image: "../../../food_images/nasi_campur.png"
    },
    {
        id: 2,
        name: "Rendang Beef",
        category: "indonesia",
        description: "Slow-cooked beef in rich coconut and spice gravy",
        longDescription: "Our signature Rendang Beef is slow-cooked to perfection in coconut milk and a blend of traditional Indonesian spices. The meat is tender and infused with rich, complex flavors. This iconic dish represents the pinnacle of Indonesian cuisine.",
        ingredients: ["Beef", "Coconut milk", "Lemongrass", "Galangal", "Turmeric", "Ginger", "Chili", "Mixed spices"],
        spicyLevel: "Medium-Hot",
        price: 18.99,
        image: "../../../food_images/rendang_beef.png"
    },
    {
        id: 3,
        name: "Nasi Ayam Penyet",
        category: "indonesia",
        description: "Traditional Indonesian smashed chicken with rice and special sambal",
        price: 15.99,
        image: "../../../food_images/nasi_ayam_penyet.png"
    },
    {
        id: 4,
        name: "Mie Goreng",
        category: "indonesia",
        description: "Indonesian stir-fried noodles with vegetables and chicken",
        price: 13.99,
        image: "../../../food_images/mie_goreng.png"
    },
    {
        id: 5,
        name: "Char Kuey Teow",
        category: "chinese",
        description: "Stir-fried flat rice noodles with fresh seafood",
        price: 13.99,
        image: "../../../food_images/char_kuey_teow.png"
    },
    {
        id: 6,
        name: "Kung Pao Chicken",
        category: "chinese",
        description: "Spicy diced chicken with peanuts and vegetables",
        price: 16.99,
        image: "../../../food_images/kung_pau_chciken.png"
    },
    {
        id: 7,
        name: "Mapo Tofu",
        category: "chinese",
        description: "Spicy tofu dish with minced meat in Sichuan style",
        price: 14.99,
        image: "../../../food_images/mapo_toufu.png"
    },
    {
        id: 8,
        name: "Tea",
        category: "drinks",
        description: "Freshly brewed Chinese or Indonesian tea",
        price: 3.99,
        image: "../../../food_images/tea.png"
    },
    {
        id: 9,
        name: "Cendol",
        category: "desserts",
        description: "Traditional Southeast Asian dessert with green rice flour jelly",
        price: 5.99,
        image: "../../../food_images/cendol.png"
    }
];

let currentFilter = 'all';
let currentSort = 'name-asc';
let searchQuery = '';

// Initialize menu
document.addEventListener('DOMContentLoaded', () => {
    loadMenuItems();
    setupEventListeners();
});

function setupEventListeners() {
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentFilter = button.dataset.category;
            filterAndDisplayItems();
        });
    });

    // Sort select
    document.getElementById('sortSelect').addEventListener('change', (e) => {
        currentSort = e.target.value;
        filterAndDisplayItems();
    });

    // Search
    document.getElementById('searchInput').addEventListener('input', (e) => {
        searchQuery = e.target.value.toLowerCase();
        filterAndDisplayItems();
    });
}

function loadMenuItems() {
    filterAndDisplayItems();
}

function filterAndDisplayItems() {
    let filteredItems = menuItems.filter(item => {
        const matchesCategory = currentFilter === 'all' || item.category === currentFilter;
        const matchesSearch = item.name.toLowerCase().includes(searchQuery) ||
                            item.description.toLowerCase().includes(searchQuery);
        return matchesCategory && matchesSearch;
    });

    // Sort items
    filteredItems.sort((a, b) => {
        switch(currentSort) {
            case 'name-asc':
                return a.name.localeCompare(b.name);
            case 'name-desc':
                return b.name.localeCompare(a.name);
            case 'price-asc':
                return a.price - b.price;
            case 'price-desc':
                return b.price - a.price;
            default:
                return 0;
        }
    });

    displayMenuItems(filteredItems);
}

function displayMenuItems(items) {
    const menuGrid = document.querySelector('.menu-grid');
    menuGrid.innerHTML = items.map(item => `
        <div class="menu-item">
            <div class="menu-item-image-container">
                <img src="${item.image}" alt="${item.name}" class="menu-item-image">
            </div>
            <div class="menu-item-info">
                <span class="menu-item-category">${item.category.charAt(0).toUpperCase() + item.category.slice(1)}</span>
                <h3 class="menu-item-title">${item.name}</h3>
                <p class="menu-item-description">${item.description}</p>
                <div class="menu-item-footer">
                    <span class="menu-item-price">$${item.price.toFixed(2)}</span>
                    <a href="details.html?id=${item.id}" class="view-details-btn">View Details</a>
                </div>
            </div>
        </div>
    `).join('');
}

// Add to cart from menu
function addToCartFromMenu(itemId) {
    const item = menuItems.find(item => item.id === itemId);
    if (item) {
        window.addToCart(item, 1);
    }
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    updateCart();
}

function updateItemQuantity(itemId, delta) {
    const cartItem = cart.find(item => item.id === itemId);
    if (cartItem) {
        cartItem.quantity += delta;
        if (cartItem.quantity <= 0) {
            removeFromCart(itemId);
        } else {
            updateCart();
        }
    }
}

function updateCart() {
    const cartItems = document.querySelector('.cart-items');
    const totalAmount = document.querySelector('.total-amount');
    
    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item">
            <img src="${item.image}" alt="${item.name}" class="cart-item-image">
            <div class="cart-item-info">
                <h4 class="cart-item-title">${item.name}</h4>
                <div class="cart-item-details">
                    <span class="cart-item-price">$${(item.price * item.quantity).toFixed(2)}</span>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" onclick="updateItemQuantity(${item.id}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button class="quantity-btn" onclick="updateItemQuantity(${item.id}, 1)">+</button>
                    </div>
                </div>
                <button class="remove-item" onclick="removeFromCart(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');

    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    totalAmount.textContent = `$${total.toFixed(2)}`;

    // Store cart in localStorage
    localStorage.setItem('cart', JSON.stringify(cart));

    // Dispatch storage event for other pages
    window.dispatchEvent(new StorageEvent('storage', {
        key: 'cart',
        newValue: JSON.stringify(cart)
    }));

    // Update cart count in header
    const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.querySelector('.cart-count').textContent = cartCount;
}

function toggleCart(show = undefined) {
    const cart = document.getElementById('cartPreview');
    if (show === undefined) {
        cart.classList.toggle('active');
    } else {
        cart.classList.toggle('active', show);
    }
}

// Load cart from localStorage
function loadCart() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCart();
    }
}

// Checkout function
document.querySelector('.checkout-btn').addEventListener('click', () => {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }
    // Store cart data for checkout
    localStorage.setItem('checkoutCart', JSON.stringify(cart));
    // Redirect to checkout page
    window.location.href = '../checkout/checkout.html';
});

// Add styles for notification
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--gradient-primary);
        color: white;
        padding: 1rem 2rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-soft);
        animation: slideIn 0.3s ease-out;
        z-index: 1000;
    }

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

    .cart-item-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
    }

    .remove-item {
        background: none;
        border: none;
        color: var(--text-gray);
        cursor: pointer;
        padding: 0.5rem;
        transition: var(--transition);
    }

    .remove-item:hover {
        color: var(--primary-color);
    }
`;
document.head.appendChild(style);

// Handle item details view when coming from cart
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('item');
    
    if (itemId) {
        const item = menuItems.find(item => item.id === parseInt(itemId));
        if (item) {
            window.location.href = `details.html?id=${itemId}`;
        }
    }
});

// Update cart display
function updateCartDisplay() {
    const cartItems = document.querySelector('.cart-items');
    const totalAmount = document.querySelector('.total-amount');
    const cartCount = document.querySelector('.cart-count');
    
    if (!cartItems || !totalAmount || !cartCount) return;

    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item">
            <img src="${item.image}" alt="${item.name}" class="cart-item-image">
            <div class="cart-item-info">
                <h4 class="cart-item-title">${item.name}</h4>
                <div class="cart-item-details">
                    <span class="cart-item-price">$${(item.price * item.quantity).toFixed(2)}</span>
                    <div class="cart-item-actions">
                        <a href="details.html?id=${item.id}" class="view-details-btn">View Details</a>
                    </div>
                </div>
            </div>
            <button class="remove-item" onclick="removeFromCart(${item.id})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `).join('');

    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    totalAmount.textContent = `$${total.toFixed(2)}`;

    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
} 