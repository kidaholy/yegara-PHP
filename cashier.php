<?php
/**
 * Refined Cashier POS Interface
 */
require_once 'includes/layout.php';

requireAuth(['cashier', 'admin']);

$title = "Sales Point";
renderHeader($title);
?>

<div class="h-[calc(100vh-140px)] flex gap-8 max-w-[1700px] mx-auto animate-in">
    <!-- Left: Menu Selection -->
    <div class="flex-1 flex flex-col gap-6 overflow-hidden">
        <div class="flex items-center justify-between shrink-0">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold font-playfair tracking-tight text-white">Service Menu</h1>
                <p class="text-xs text-muted-foreground font-medium opacity-50">Select items to add to current order</p>
            </div>
            <div class="relative w-64 group">
                <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-muted-foreground group-focus-within:text-blue-500 transition-colors"></i>
                <input type="text" id="item-search" placeholder="Search menu..." 
                       class="w-full bg-white/5 border border-white/10 rounded-xl py-2 pl-10 pr-4 text-sm focus:outline-none focus:border-blue-500/50 transition-all text-white">
            </div>
        </div>

        <!-- Categories pill bar -->
        <div class="flex gap-2 p-1 bg-white/5 border border-white/5 rounded-2xl overflow-x-auto shrink-0 no-scrollbar" id="category-bar">
            <!-- Categories will be injected here -->
            <div class="px-6 py-2 animate-pulse text-xs text-muted-foreground">Loading categories...</div>
        </div>

        <!-- Items Grid -->
        <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar" id="items-grid">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4" id="items-container">
                <!-- Items will be injected here -->
                 <div class="col-span-full py-20 text-center text-muted-foreground opacity-50">
                    <div class="animate-spin mb-4 inline-block"><i data-lucide="loader-2" class="w-8 h-8 text-blue-500"></i></div>
                    <p>Loading items...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Order Detail / Cart -->
    <div class="w-[400px] glass rounded-[2.5rem] border border-white/10 flex flex-col overflow-hidden shadow-2xl relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-purple-500 opacity-50"></div>
        
        <div class="p-6 border-b border-white/5 flex items-center justify-between shrink-0">
            <h3 class="font-bold text-white flex items-center gap-3">
                <i data-lucide="shopping-cart" class="w-5 h-5 text-blue-500"></i>
                Current Order
            </h3>
            <button onclick="clearCart()" class="text-[10px] font-bold uppercase tracking-widest text-red-400 hover:text-red-300 transition-colors">Clear</button>
        </div>

        <!-- Order Options -->
        <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] shrink-0 space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground opacity-50 ml-1">Table / Space</label>
                    <select id="table-select" class="w-full bg-white/5 border border-white/10 rounded-xl py-2 px-3 text-xs focus:outline-none focus:border-blue-500/50 text-white">
                        <option value="Buy&Go">Buy & Go</option>
                        <!-- Tables will be loaded here -->
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground opacity-50 ml-1">Payment</label>
                    <select id="payment-method" class="w-full bg-white/5 border border-white/10 rounded-xl py-2 px-3 text-xs focus:outline-none focus:border-blue-500/50 text-white">
                        <option value="cash">Cash</option>
                        <option value="card">Card / CBE Birr</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4 custom-scrollbar" id="cart-container">
            <!-- Empty Cart Initial View -->
            <div class="h-full flex flex-col items-center justify-center text-center opacity-40">
                <i data-lucide="package-open" class="w-12 h-12 mb-4"></i>
                <p class="text-sm font-medium">No items yet</p>
                <p class="text-[10px] uppercase tracking-widest font-bold">Start selecting from the menu</p>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="p-6 border-t border-white/10 bg-white/[0.03] space-y-4 shrink-0">
            <div class="space-y-2">
                <div class="flex justify-between items-center text-xs text-muted-foreground">
                    <span>Subtotal</span>
                    <span id="cart-subtotal">0.00 Br</span>
                </div>
                <div class="flex justify-between items-center text-xs text-muted-foreground">
                    <span>Tax (0%)</span>
                    <span>0.00 Br</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-white/5">
                    <span class="text-sm font-bold text-white">Total Amount</span>
                    <span class="text-lg font-bold text-blue-500" id="cart-total">0.00 Br</span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <button id="place-order-btn" onclick="placeOrder()" disabled
                        class="w-full bg-white text-slate-950 font-black py-4 rounded-2xl transition-all flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed enabled:hover:scale-[1.02] enabled:active:scale-[0.98] shadow-2xl">
                    <span>Order</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
                <button id="print-order-btn" onclick="printReceipt()" disabled
                        class="w-full bg-blue-500/10 text-blue-500 border border-blue-500/20 font-black py-4 rounded-2xl transition-all flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed enabled:hover:bg-blue-500/20 enabled:active:scale-[0.98]">
                    <span>Receipt</span>
                    <i data-lucide="printer" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let allItems = [];
    let categories = [];
    let cart = [];
    let selectedCategoryId = null;

    async function loadData() {
        try {
            const resp = await fetch('api/menu.php');
            const data = await resp.json();
            allItems = data.items;
            categories = data.categories;
            
            // Also load tables
            const tableResp = await fetch('api/orders.php?tables=true'); // We'll need to update orders.php for this or just hardcode some
            
            renderCategories();
            renderItems();
        } catch (err) {
            console.error('Data load error:', err);
        }
    }

    function renderCategories() {
        const bar = document.getElementById('category-bar');
        bar.innerHTML = `
            <button onclick="filterCategory(null)" 
                    class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all ${selectedCategoryId === null ? 'bg-white text-slate-950 shadow-lg' : 'text-muted-foreground hover:bg-white/5 hover:text-white'}">
                All Items
            </button>
        ` + categories.map(cat => `
            <button onclick="filterCategory('${cat.id}')" 
                    class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all ${selectedCategoryId === cat.id ? 'bg-white text-slate-950 shadow-lg' : 'text-muted-foreground hover:bg-white/5 hover:text-white'}">
                ${cat.name}
            </button>
        `).join('');
    }

    function filterCategory(id) {
        selectedCategoryId = id;
        renderCategories();
        renderItems();
    }

    function renderItems() {
        const grid = document.getElementById('items-container');
        const search = document.getElementById('item-search').value.toLowerCase();
        
        const filtered = allItems.filter(item => {
            const matchCategory = !selectedCategoryId || item.categoryId === selectedCategoryId;
            const matchSearch = item.name.toLowerCase().includes(search);
            return matchCategory && matchSearch;
        });

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-20 text-center opacity-40 text-sm">No items matching your selection</div>';
            return;
        }

        grid.innerHTML = filtered.map(item => `
            <button onclick="addToCart('${item.id}')" 
                    class="glass p-3.5 rounded-2xl border border-white/5 hover:border-blue-500/50 hover:bg-blue-500/5 transition-all text-left flex flex-col group active:scale-[0.96]">
                <div class="flex-1 space-y-1.5">
                    <p class="text-xs font-bold text-white group-hover:text-blue-400 transition-colors leading-tight line-clamp-2">${item.name}</p>
                    <p class="text-[10px] font-medium text-muted-foreground opacity-60">${item.category || ''}</p>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-xs font-black text-white">${parseFloat(item.price).toFixed(2)} Br</span>
                    <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-500 opacity-0 group-hover:opacity-100 transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </div>
                </div>
            </button>
        `).join('');
        lucide.createIcons();
    }

    function addToCart(itemId) {
        const item = allItems.find(i => i.id === itemId);
        const existing = cart.find(c => c.id === itemId);
        
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({ ...item, quantity: 1, notes: '' });
        }
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cart-container');
        if (cart.length === 0) {
            container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-center opacity-30">
                    <i data-lucide="package-open" class="w-12 h-12 mb-4"></i>
                    <p class="text-xs font-bold uppercase tracking-widest leading-loose">The order is empty<br>Select some items</p>
                </div>
            `;
            document.getElementById('place-order-btn').disabled = true;
            updateTotals();
            lucide.createIcons();
            return;
        }

        container.innerHTML = cart.map((item, index) => `
            <div class="flex flex-col p-4 rounded-2xl bg-white/[0.03] border border-white/5 space-y-3 group hover:border-white/10 transition-all">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">${item.name}</p>
                        <p class="text-[10px] text-muted-foreground font-medium">${parseFloat(item.price).toFixed(2)} Br</p>
                    </div>
                    <div class="flex items-center bg-white/5 rounded-lg border border-white/10">
                        <button onclick="updateQty(${index}, -1)" class="w-7 h-7 flex items-center justify-center text-muted-foreground hover:text-white transition-colors"><i data-lucide="minus" class="w-3 h-3"></i></button>
                        <span class="w-8 text-center text-xs font-bold text-white">${item.quantity}</span>
                        <button onclick="updateQty(${index}, 1)" class="w-7 h-7 flex items-center justify-center text-muted-foreground hover:text-white transition-colors"><i data-lucide="plus" class="w-3 h-3"></i></button>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 group/input">
                        <i data-lucide="sticky-note" class="absolute left-2.5 top-2 w-3 h-3 text-muted-foreground opacity-40 group-focus-within/input:text-blue-500"></i>
                        <input type="text" placeholder="Special notes..." value="${item.notes}"
                               onchange="updateNotes(${index}, this.value)"
                               class="w-full bg-white/5 border border-white/5 rounded-lg py-1.5 pl-8 pr-3 text-[10px] focus:outline-none focus:border-blue-500/30 text-slate-300 placeholder-slate-600">
                    </div>
                    <button onclick="removeFromCart(${index})" class="w-8 h-8 rounded-lg flex items-center justify-center text-red-500/40 hover:text-red-400 hover:bg-red-500/10 transition-all">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        `).join('');
        
        document.getElementById('place-order-btn').disabled = false;
        updateTotals();
        lucide.createIcons();
    }

    function updateQty(index, delta) {
        cart[index].quantity += delta;
        if (cart[index].quantity < 1) {
            cart.splice(index, 1);
        }
        renderCart();
    }

    function updateNotes(index, notes) {
        cart[index].notes = notes;
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        if (confirm('Clear entire order?')) {
            cart = [];
            renderCart();
        }
    }

    function updateTotals() {
        const subtotal = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
        document.getElementById('cart-subtotal').innerText = subtotal.toFixed(2) + ' Br';
        document.getElementById('cart-total').innerText = subtotal.toFixed(2) + ' Br';
    }

    async function placeOrder() {
        const btn = document.getElementById('place-order-btn');
        const oldContent = btn.innerHTML;
        
        try {
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>Processing...</span>';
            lucide.createIcons();

            const orderData = {
                tableNumber: document.getElementById('table-select').value,
                paymentMethod: document.getElementById('payment-method').value,
                totalAmount: cart.reduce((acc, item) => acc + (item.price * item.quantity), 0),
                items: cart.map(i => ({
                    menuItemId: i.id,
                    name: i.name,
                    quantity: i.quantity,
                    price: i.price,
                    notes: i.notes
                }))
            };

            const resp = await fetch('api/orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });

            const result = await resp.json();
            if (resp.ok) {
                alert('Order #' + result.orderNumber + ' confirmed successfully!');
                cart = [];
                renderCart();
            } else {
                alert('Error: ' + (result.message || 'Failed to place order'));
            }
        } catch (err) {
            alert('Critial server error. Check connection.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldContent;
            lucide.createIcons();
        }
    }

    function printReceipt() {
        if (cart.length === 0) return;
        
        const printWindow = window.open('', '_blank', 'width=300,height=600');
        const subtotal = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
        
        let html = `
            <html>
            <head>
                <style>
                    body { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; padding: 10px; margin: 0; }
                    .header { text-align: center; border-bottom: 1px dashed black; padding-bottom: 10px; margin-bottom: 10px; }
                    .item { display: flex; justify-content: space-between; margin-bottom: 5px; }
                    .total { border-top: 1px dashed black; margin-top: 10px; padding-top: 10px; font-weight: bold; }
                    .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2 style="margin:0;">PRIME ADDIS</h2>
                    <p style="margin:5px 0;">Luxury Hotel & Spa</p>
                    <p style="font-size:10px;">Date: ${new Date().toLocaleString()}</p>
                </div>
                \${cart.map(item => `
                    <div class="item">
                        <span>\${item.quantity}x \${item.name}</span>
                        <span>\${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `).join('')}
                <div class="total">
                    <div class="item">
                        <span>TOTAL</span>
                        <span>\${subtotal.toFixed(2)} Br</span>
                    </div>
                </div>
                <div class="footer">
                    <p>Thank you for choosing Prime Addis!</p>
                    <p>*** LUXURY EXPERIENCE ***</p>
                </div>
                <script>window.print(); window.close();<\/script>
            </body>
            </html>
        `;
        
        printWindow.document.write(html);
        printWindow.document.close();
    }

    document.getElementById('item-search').addEventListener('input', renderItems);
    loadData();
</script>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.1); }
</style>

<?php renderFooter(); ?>
