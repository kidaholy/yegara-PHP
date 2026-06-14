/**
 * Admin Users (Staff) AJAX Controller
 */
const state = {
    users: [],
    floors: [],
    categories: [],
    showForm: false,
    editingUser: null,
    formLoading: false,
    revealedPasswords: {},
    formData: {
        name: '',
        email: '',
        password: '',
        role: 'cashier',
        floorId: '',
        assignedCategories: [],
        permissions: []
    }
};

const PERMISSION_GROUPS = {
    "Overview": ["overview:view"],
    "Orders": ["orders:view", "orders:create", "orders:update", "orders:delete"],
    "Users": ["users:view", "users:create", "users:update", "users:delete"],
    "Store": ["store:view", "store:create", "store:update", "store:delete", "store:transfer"],
    "Stock": ["stock:view", "stock:create", "stock:update", "stock:delete"],
    "Reports": ["reports:financial_summary", "reports:order_history", "reports:inventory_investment", "reports:store_investment", "reports:menu_item_sales", "reports:cashier_insights"],
    "Services": ["services:view", "services:create", "services:update", "services:delete"],
    "Settings": ["settings:view", "settings:update"],
    "Interfaces": ["cashier:access", "chef:access", "bar:access", "reception:access", "display:access"]
};

const ROLE_ICONS = {
    admin: 'shield-check',
    chef: 'chef-hat',
    bar: 'beer',
    display: 'monitor',
    store_keeper: 'package',
    reception: 'concierge-bell',
    custom: 'pencil',
    cashier: 'coffee'
};

const ROLE_COLORS = {
    admin: 'text-[#c5a059] bg-[#c5a059]/10 border-[#c5a059]/20',
    chef: 'text-orange-400 bg-orange-500/10 border-orange-500/20',
    bar: 'text-blue-400 bg-blue-500/10 border-blue-500/20',
    display: 'text-purple-400 bg-purple-500/10 border-purple-500/20',
    store_keeper: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
    reception: 'text-blue-400 bg-blue-500/10 border-blue-500/20',
    custom: 'text-pink-400 bg-pink-500/10 border-pink-500/20',
    cashier: 'text-[#c5a059] bg-[#c5a059]/10 border-[#c5a059]/20'
};

/**
 * INITIALIZATION
 */
async function fetchAll() {
    const loader = document.getElementById('grid-loader');
    if (loader) loader.classList.remove('hidden');
    
    try {
        const [users, floors, categories] = await Promise.all([
            fetch('api/users.php?full=1').then(r => r.json()),
            fetch('api/floors.php').then(r => r.json()),
            fetch('api/categories.php').then(r => r.json())
        ]);
        
        state.users = Array.isArray(users) ? users : [];
        state.floors = Array.isArray(floors) ? floors : [];
        state.categories = Array.isArray(categories) ? categories : [];
        
        renderUserGrid();
        renderSidebarInfo();
    } catch (e) {
        console.error('Fetch error:', e);
    } finally {
        if (loader) loader.classList.add('hidden');
    }
}

/**
 * RENDERING
 */
function renderUserGrid() {
    const grid = document.getElementById('user-grid');
    if (!grid) return;

    if (state.users.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full py-20 text-center">
                <p class="text-4xl mb-4">🌙</p>
                <p class="text-xs uppercase font-black tracking-widest text-white/20">No team members found</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = state.users.map(u => renderUserCard(u)).join('');
    lucide.createIcons();
}

function renderUserCard(u) {
    const isMe = u.id === window.currentUserId;
    const isActive = u.isActive;
    const isDeactivated = !isActive;
    const roleIcon = ROLE_ICONS[u.role] || 'user';
    const roleColor = ROLE_COLORS[u.role] || '';
    const isRevealed = state.revealedPasswords[u.id];

    return `
        <div class="bg-gray-800/60 p-6 rounded-2xl border border-gray-700/50 hover:bg-gray-800 hover:border-[#c5a059]/30 transition-colors group relative ${isDeactivated ? 'opacity-50 grayscale border-dashed border-gray-600' : ''}">
            <!-- Badges -->
            <div class="flex items-center justify-between gap-4 mb-6">
                <!-- Header -->
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl ${roleColor} flex items-center justify-center border shrink-0">
                        <i data-lucide="${roleIcon}" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-200 truncate ${isDeactivated ? 'line-through opacity-40' : ''}">${u.name}</h3>
                        <p class="text-[10px] font-semibold text-gray-500 truncate">${u.email}</p>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-1.5 shrink-0">
                    ${isMe ? '<span class="px-2 py-0.5 rounded bg-[#1a1712] text-[#c5a059] border border-[#c5a059]/20 text-[9px] font-bold uppercase tracking-wider">You</span>' : ''}
                    <span class="px-2 py-0.5 rounded ${isActive ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'} border text-[9px] font-bold uppercase tracking-wider">
                        ${isActive ? 'Active' : 'Deactivated'}
                    </span>
                </div>
            </div>

            <!-- Role Tags / Specific Info -->
            <div class="space-y-3 mb-6">
                <div class="flex flex-wrap gap-2">
                    ${u.floorId ? `
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-md bg-gray-900 border border-gray-700 text-xs font-bold text-gray-400">
                            <i data-lucide="map-pin" class="w-3 h-3 text-red-400"></i>
                            Floor #${state.floors.find(f => f.id == u.floorId)?.floorNumber || u.floorId}
                        </div>
                    ` : ''}
                    ${(u.assignedCategories || []).map(cat => `
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-md bg-orange-500/10 border border-orange-500/20 text-xs font-bold text-orange-400">
                            <i data-lucide="utensils-crossed" class="w-3 h-3"></i>
                            ${cat}
                        </div>
                    `).join('')}
                </div>

                <!-- Last Activity -->
                <div class="grid grid-cols-2 gap-2 mt-2">
                    ${u.lastLoginAt ? `
                        <div class="p-2 rounded-lg bg-emerald-500/5 border border-emerald-500/10 flex items-center gap-2">
                            <i data-lucide="log-in" class="w-3 h-3 text-emerald-500"></i>
                            <span class="text-xs font-semibold text-gray-400">${new Date(u.lastLoginAt).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                        </div>
                    ` : ''}
                    ${u.lastLogoutAt ? `
                        <div class="p-2 rounded-lg bg-red-500/5 border border-red-500/10 flex items-center gap-2">
                            <i data-lucide="log-out" class="w-3 h-3 text-red-500"></i>
                            <span class="text-xs font-semibold text-gray-400">${new Date(u.lastLogoutAt).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                        </div>
                    ` : ''}
                </div>
            </div>

            <!-- Password Reveal Section -->
            <div class="p-2.5 rounded-xl bg-gray-900 border border-gray-700 flex items-center justify-between mb-6">
                <div class="pl-2">
                    <p class="text-[10px] uppercase font-bold tracking-wider text-gray-500 mb-0.5">Password</p>
                    <p class="text-sm font-mono font-bold text-gray-300 tracking-widest overflow-hidden">
                        ${isRevealed ? (u.plainPassword || '••••••••') : '••••••••'}
                    </p>
                </div>
                <button onclick="togglePassword('${u.id}')" class="w-9 h-9 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700 flex items-center justify-center transition-colors">
                    <i data-lucide="${isRevealed ? 'eye-off' : 'eye'}" class="w-4 h-4 text-[#c5a059]"></i>
                </button>
            </div>

            <!-- Actions Footer -->
            <div class="flex items-center justify-between pt-5 border-t border-gray-700/50">
                <span class="px-2.5 py-1 rounded-md ${roleColor} text-[10px] font-bold uppercase tracking-wider border">
                    ${u.role}
                </span>
                <div class="flex items-center gap-2">
                    ${!isMe ? `
                        <button data-id="${u.id}" data-status="${!u.isActive}" class="toggle-btn w-9 h-9 rounded-lg bg-gray-800 border border-gray-700 hover:bg-gray-700 flex items-center justify-center transition-colors ${isDeactivated ? 'text-emerald-500' : 'text-red-500'}">
                            <i data-lucide="${isDeactivated ? 'eye' : 'eye-off'}" class="w-4 h-4"></i>
                        </button>
                    ` : ''}
                    <button type="button" data-id="${u.id}" onclick="event.stopPropagation(); window.editUser('${u.id}')" class="edit-btn w-10 h-10 rounded-full bg-blue-500/10 border border-blue-500/30 hover:bg-blue-500 hover:text-white flex items-center justify-center transition-all text-blue-400 relative z-[50] shadow-lg">
                        <i data-lucide="pencil" class="w-5 h-5 pointer-events-none"></i>
                    </button>
                    ${!isMe ? `
                        <button onclick="deleteUser('${u.id}')" class="w-9 h-9 rounded-lg bg-gray-800 border border-gray-700 hover:bg-red-600 hover:text-white hover:border-red-500 flex items-center justify-center transition-colors text-gray-400">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function renderSidebarInfo() {
    const countEl = document.getElementById('staff-count');
    if (countEl) countEl.textContent = `Total Active Staff: ${state.users.filter(u => u.isActive).length}`;
}

/**
 * ACTIONS
 */
window.togglePassword = (id) => {
    state.revealedPasswords[id] = !state.revealedPasswords[id];
    renderUserGrid();
};

window.resetForm = () => {
    state.editingUser = null;
    state.formData = {
        name: '', email: '', password: '', role: 'cashier',
        floorId: '', assignedCategories: [], permissions: []
    };
    renderForm();
};

window.openCreateModal = () => {
    window.resetForm();
    document.getElementById('user-modal').classList.remove('hidden');
};

window.closeModal = () => {
    document.getElementById('user-modal').classList.add('hidden');
};

window.editUser = (id) => {
    try {
        console.log('[Staff] editUser call:', id);
        const searchId = String(id || '').trim();

        if (!searchId && window.currentUserId) {
            console.warn('[Staff] editUser called without ID, falling back to currentUserId');
            return window.editUser(window.currentUserId);
        }

        // Strategy 1: Direct Match
        let user = state.users.find(u => String(u.id).trim() === searchId);
        
        // Strategy 2: Fallback for Super Admin (Match by currentUserId)
        if (!user && window.currentUserId) {
            if (searchId === String(window.currentUserId).trim()) {
                user = state.users.find(u => String(u.id).trim() === String(window.currentUserId).trim());
            }
        }

        // Strategy 3: Global Fallback for Super Admin (Match by SUPER_ADMIN_ID)
        if (!user && window.SUPER_ADMIN_ID) {
            if (searchId === String(window.SUPER_ADMIN_ID).trim()) {
                 user = state.users.find(u => String(u.id).trim() === String(window.SUPER_ADMIN_ID).trim());
            }
        }

        // Strategy 4: Soft Match (Role + Admin status) if it's the current user's card
        if (!user && (searchId === String(window.currentUserId).trim() || searchId === String(window.SUPER_ADMIN_ID).trim())) {
            user = state.users.find(u => u.role === 'admin' && (u.email.includes('kidayos') || u.name.toLowerCase().includes('super')));
        }

        // Strategy 5: Last ditch
        if (!user && searchId === String(window.currentUserId).trim()) {
            user = state.users.find(u => u.role === 'admin');
        }
        
        if (!user) {
            console.error('[Staff] Edit Failed: User not found in state:', searchId);
            alert('Error: Could not find user data. Please refresh.');
            return;
        }

        state.editingUser = user;
        state.formData = {
            name: user.name,
            email: user.email,
            password: '', 
            role: user.role,
            floorId: user.floorId || '',
            assignedCategories: user.assignedCategories || [],
            permissions: user.permissions || []
        };
        
        renderForm();
        
        const modal = document.getElementById('user-modal');
        if (modal) {
            modal.classList.remove('hidden');
        } else {
            console.error('[Staff] Modal element not found!');
            alert('UI Error: Modal element not found.');
        }
    } catch (err) {
        console.error('[Staff] Fatal error in editUser:', err);
        alert('Critical Error: ' + err.message);
    }
};

function renderForm() {
    try {
        const role = state.formData.role;
        const form = document.getElementById('user-form');
        if (!form) {
            console.error('[Staff] Form element not found in renderForm');
            return;
        }

    // Title
    document.getElementById('form-title').textContent = state.editingUser ? 'Edit Profile' : 'New Member';
    
    // Inputs
    form.querySelector('[name="name"]').value = state.formData.name;
    form.querySelector('[name="email"]').value = state.formData.email;
    form.querySelector('[name="password"]').value = state.formData.password;

    // Roles selection
    const roleContainer = document.getElementById('role-selector');
    roleContainer.innerHTML = [
        'cashier', 'chef', 'bar', 'admin', 'display', 'store_keeper', 'reception', 'custom'
    ].map(r => `
        <button type="button" onclick="setRole('${r}')" class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border transition-colors ${role === r ? 'bg-[#c5a059] text-gray-900 border-[#c5a059]' : 'bg-gray-800 text-gray-400 border-gray-700 hover:bg-gray-700'}">
            ${r}
        </button>
    `).join('');

    // Conditional Fields
    document.getElementById('floor-section').classList.toggle('hidden', !['cashier', 'display'].includes(role));
    document.getElementById('category-section').classList.toggle('hidden', !['chef', 'bar'].includes(role));
    document.getElementById('permission-section').classList.toggle('hidden', role !== 'custom');

    // Floors dropdown
    const floorSelect = document.getElementById('floor-select');
    floorSelect.innerHTML = '<option value="">All Floors (Global)</option>' + 
        state.floors.map(f => `<option value="${f.id}" ${state.formData.floorId == f.id ? 'selected' : ''}>Floor ${f.floorNumber}</option>`).join('');

    // Categories
    const catList = document.getElementById('category-list');
    catList.innerHTML = state.categories.map(c => `
        <button type="button" onclick="toggleCategory('${c.name}')" class="p-2.5 rounded-lg border text-xs font-bold text-left transition-colors ${state.formData.assignedCategories.includes(c.name) ? 'bg-orange-500/10 border-orange-500/30 text-orange-400' : 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-700'}">
            ${c.name}
        </button>
    `).join('');

    // Permissions Matrix
    const permGrid = document.getElementById('permission-grid');
    permGrid.innerHTML = Object.entries(PERMISSION_GROUPS).map(([cat, ps]) => {
        const allSelected = ps.every(p => state.formData.permissions.includes(p));
        return `
            <div class="col-span-full border-b border-gray-700/50 pb-2 mt-3 mb-1 flex justify-between items-center">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">${cat}</span>
                <button type="button" onclick="togglePermGroup('${cat}')" class="text-[10px] font-bold uppercase text-[#c5a059] hover:underline">${allSelected ? 'None' : 'All'}</button>
            </div>
            ${ps.map(p => `
                <button type="button" onclick="togglePerm('${p}')" class="flex items-center justify-between p-2.5 rounded-lg border transition-colors ${state.formData.permissions.includes(p) ? 'border-[#c5a059]/30 bg-[#c5a059]/10' : 'bg-gray-800 border-gray-700 hover:bg-gray-700'}">
                    <span class="text-xs font-semibold ${state.formData.permissions.includes(p) ? 'text-[#c5a059]' : 'text-gray-400'}">${p.replace(':',' ')}</span>
                    ${state.formData.permissions.includes(p) ? '<i data-lucide="check" class="w-4 h-4 text-[#c5a059]"></i>' : ''}
                </button>
            `).join('')}
        `;
    }).join('');

    lucide.createIcons();
    } catch (err) {
        console.error('[Staff] Error in renderForm:', err);
    }
}

/**
 * FORM HELPERS
 */
window.setRole = (r) => { 
    state.formData.role = r; 
    renderForm(); 
};

window.generatePassword = () => {
    state.formData.password = Math.random().toString(36).slice(-8);
    renderForm();
};

window.toggleCategory = (name) => {
    const list = state.formData.assignedCategories;
    const idx = list.indexOf(name);
    if (idx > -1) list.splice(idx, 1);
    else list.push(name);
    renderForm();
};

window.togglePerm = (p) => {
    const list = state.formData.permissions;
    const idx = list.indexOf(p);
    if (idx > -1) list.splice(idx, 1);
    else list.push(p);
    renderForm();
};

window.togglePermGroup = (cat) => {
    const ps = PERMISSION_GROUPS[cat];
    const all = ps.every(p => state.formData.permissions.includes(p));
    if (all) {
        state.formData.permissions = state.formData.permissions.filter(p => !ps.includes(p));
    } else {
        ps.forEach(p => { if(!state.formData.permissions.includes(p)) state.formData.permissions.push(p); });
    }
    renderForm();
};

/**
 * API SUBMISSION
 */
window.handleFormSubmit = async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    
    const url = state.editingUser ? `api/users.php?id=${state.editingUser.id}` : 'api/users.php';
    const method = state.editingUser ? 'PUT' : 'POST';

    // Collect data
    const data = {
        name: e.target.name.value,
        email: e.target.email.value,
        password: e.target.password.value,
        role: state.formData.role,
        floorId: e.target['floor-select']?.value,
        assignedCategories: state.formData.assignedCategories,
        permissions: state.formData.permissions
    };

    try {
        const res = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if (res.ok) {
            closeModal();
            fetchAll();
            if (result.credentials) {
                showNotification(`User: ${result.credentials.email}<br>Pass: ${result.credentials.password}`, 'Creation Successful');
            } else {
                showToast('Success', result.message);
            }
        } else {
            alert(result.message);
        }
    } catch (err) {
        alert('Server error');
    } finally {
        btn.disabled = false;
    }
};

window.toggleActive = async (id, status) => {
    try {
        const res = await fetch(`api/users.php?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ isActive: status })
        });
        if (res.ok) fetchAll();
        else alert((await res.json()).message);
    } catch (e) { alert('Action failed'); }
};

window.deleteUser = async (id) => {
    if (!confirm('Are you sure you want to delete this member? This action is permanent.')) return;
    try {
        const res = await fetch(`api/users.php?id=${id}`, { method: 'DELETE' });
        if (res.ok) fetchAll();
        else alert((await res.json()).message);
    } catch (e) { alert('Delete failed'); }
};

function showNotification(msg, title) {
    const notify = document.getElementById('notification-card');
    if (!notify) { alert(msg); return; }
    notify.querySelector('.notify-title').textContent = title;
    notify.querySelector('.notify-content').innerHTML = msg;
    notify.classList.remove('hidden');
}

window.closeNotification = () => {
    document.getElementById('notification-card').classList.add('hidden');
};

function showToast(title, msg) {
    // Simple toast fallback
    console.log(`${title}: ${msg}`);
}

// Global listeners
document.addEventListener('DOMContentLoaded', fetchAll);

// DELEGATED EVENT LISTENER FOR ROBUST ACTION HANDLING
document.addEventListener('click', (e) => {
    const editBtn = e.target.closest('.edit-btn');
    if (editBtn) {
        const id = editBtn.getAttribute('data-id');
        console.log('[Staff] Delegated Edit Click:', id);
        if (id) window.editUser(id);
        return;
    }

    const toggleBtn = e.target.closest('.toggle-btn');
    if (toggleBtn) {
        const id = toggleBtn.getAttribute('data-id');
        const status = toggleBtn.getAttribute('data-status') === 'true';
        if (id) window.toggleActive(id, status);
        return;
    }
});
