// Load header and footer
document.addEventListener('DOMContentLoaded', () => {
    fetch('../../includes/header.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById('header').innerHTML = data;
            // Initialize product after header is loaded
            const urlParams = new URLSearchParams(window.location.search);
            const productId = parseInt(urlParams.get('id'));
            if (productId) {
                loadProductDetails(productId);
            } else {
                window.location.href = '../menu/menu.html';
            }
            setupEventListeners();
        });

    fetch('../../includes/footer.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById('footer').innerHTML = data;
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
    // ... other menu items ...
];

function loadProductDetails(productId) {
    const product = menuItems.find(item => item.id === productId);
    if (!product) {
        window.location.href = '../menu/menu.html';
        return;
    }

    // Update breadcrumb
    document.getElementById('categoryBreadcrumb').textContent = 
        product.category.charAt(0).toUpperCase() + product.category.slice(1);
    document.getElementById('productName').textContent = product.name;

    // Update product details
    document.getElementById('productImage').src = product.image;
    document.getElementById('productImage').alt = product.name;
    document.getElementById('productTitle').textContent = product.name;
    document.getElementById('productDescription').textContent = product.longDescription || product.description;
    document.getElementById('spicyLevel').textContent = product.spicyLevel || 'N/A';
    document.getElementById('productPrice').textContent = `$${product.price.toFixed(2)}`;

    // Update ingredients list
    const ingredientsList = document.getElementById('ingredientsList');
    if (product.ingredients) {
        ingredientsList.innerHTML = product.ingredients
            .map(ingredient => `<li>${ingredient}</li>`)
            .join('');
    } else {
        ingredientsList.innerHTML = '<li>Ingredients information not available</li>';
    }

    // Setup add to cart button
    const addToCartBtn = document.getElementById('addToCartBtn');
    addToCartBtn.onclick = () => {
        const quantity = parseInt(document.getElementById('quantity').value);
        addToCartFromProduct(product, quantity);
    };
}

function setupEventListeners() {
    // Quantity input validation
    const quantityInput = document.getElementById('quantity');
    quantityInput.addEventListener('change', () => {
        let value = parseInt(quantityInput.value);
        if (isNaN(value) || value < 1) value = 1;
        if (value > 10) value = 10;
        quantityInput.value = value;
    });
}

function updateQuantity(delta) {
    const quantityInput = document.getElementById('quantity');
    let value = parseInt(quantityInput.value) + delta;
    if (value < 1) value = 1;
    if (value > 10) value = 10;
    quantityInput.value = value;
}

// Add to cart from product page
function addToCartFromProduct(product, quantity) {
    if (product && quantity > 0) {
        window.addToCart(product, quantity);
    }
}

// Add styles for notification and cart items
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