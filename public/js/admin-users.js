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
    admin: 'text-[#d4af37] bg-[#1a1712] border-[#d4af37]/20',
    chef: 'text-orange-400 bg-orange-400/10 border-orange-400/20',
    bar: 'text-blue-400 bg-blue-400/10 border-blue-400/20',
    display: 'text-purple-400 bg-purple-400/10 border-purple-400/20',
    store_keeper: 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20',
    reception: 'text-blue-400 bg-blue-400/10 border-blue-400/20',
    custom: 'text-pink-400 bg-pink-400/10 border-pink-400/20',
    cashier: 'text-[#d4af37] bg-[#1a1712] border-[#d4af37]/20'
};

/**
 * INITIALIZATION
 */
async function fetchAll() {
    const loader = document.getElementById('grid-loader');
    if (loader) loader.classList.remove('hidden');
    
    try {
        const [users, floors, categories] = await Promise.all([
            fetch('api/users.php').then(r => r.json()),
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
        <div class="glass p-6 md:p-8 rounded-[2.5rem] border border-white/5 transition-all duration-500 hover:border-[#d4af37]/30 group relative ${isDeactivated ? 'opacity-50 grayscale dashed border-white/10' : ''}">
            <!-- Badges -->
            <div class="absolute top-6 right-6 flex items-center gap-2">
                ${isMe ? '<span class="px-2.5 py-1 rounded-full bg-[#1a1712] text-[#d4af37] border border-[#d4af37]/20 text-[8px] font-black uppercase tracking-widest">You</span>' : ''}
                <span class="px-2.5 py-1 rounded-full ${isActive ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500'} border border-current/20 text-[8px] font-black uppercase tracking-widest">
                    ${isActive ? 'Active' : 'Deactivated'}
                </span>
            </div>

            <!-- Header -->
            <div class="flex items-center gap-5 mb-8">
                <div class="w-14 h-14 rounded-2xl ${roleColor} flex items-center justify-center border shadow-lg group-hover:scale-110 transition-transform">
                    <i data-lucide="${roleIcon}" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black font-playfair italic text-[#f3cf7a] ${isDeactivated ? 'line-through opacity-40' : ''}">${u.name}</h3>
                    <p class="text-[9px] uppercase font-black tracking-widest text-gray-500 truncate max-w-[120px]">${u.email}</p>
                </div>
            </div>

            <!-- Role Tags / Specific Info -->
            <div class="space-y-3 mb-8">
                <div class="flex flex-wrap gap-2">
                    ${u.floorId ? `
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/5 text-[9px] font-bold text-gray-400">
                            <i data-lucide="map-pin" class="w-3 h-3 text-red-400"></i>
                            Floor #${state.floors.find(f => f.id == u.floorId)?.number || u.floorId}
                        </div>
                    ` : ''}
                    ${(u.assignedCategories || []).map(cat => `
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-400/5 border border-orange-400/10 text-[9px] font-bold text-orange-400">
                            <i data-lucide="utensils-crossed" class="w-3 h-3"></i>
                            ${cat}
                        </div>
                    `).join('')}
                </div>

                <!-- Last Activity -->
                <div class="grid grid-cols-2 gap-2 mt-2">
                    ${u.lastLoginAt ? `
                        <div class="p-2 rounded-xl bg-emerald-500/[0.03] border border-emerald-500/5 flex items-center gap-2">
                            <i data-lucide="log-in" class="w-3 h-3 text-emerald-500 opacity-50"></i>
                            <span class="text-[9px] font-bold text-gray-500">${new Date(u.lastLoginAt).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                        </div>
                    ` : ''}
                    ${u.lastLogoutAt ? `
                        <div class="p-2 rounded-xl bg-red-500/[0.03] border border-red-500/5 flex items-center gap-2">
                            <i data-lucide="log-out" class="w-3 h-3 text-red-500 opacity-50"></i>
                            <span class="text-[9px] font-bold text-gray-500">${new Date(u.lastLogoutAt).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                        </div>
                    ` : ''}
                </div>
            </div>

            <!-- Password Reveal Section -->
            <div class="p-2 rounded-2xl bg-[#0f1110] border border-white/5 flex items-center justify-between mb-8">
                <div class="pl-4">
                    <p class="text-[8px] uppercase font-black tracking-widest text-gray-600 mb-0.5">Password</p>
                    <p class="text-xs font-mono font-bold text-white/40 tracking-widest overflow-hidden">
                        ${isRevealed ? (u.plainPassword || '••••••••') : '••••••••'}
                    </p>
                </div>
                <button onclick="togglePassword('${u.id}')" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                    <i data-lucide="${isRevealed ? 'eye-off' : 'eye'}" class="w-4 h-4 text-[#d4af37]"></i>
                </button>
            </div>

            <!-- Actions Footer -->
            <div class="flex items-center justify-between pt-6 border-t border-white/5">
                <span class="px-3 py-1 rounded-full ${roleColor} text-[8px] font-black uppercase tracking-widest border">
                    ${u.role}
                </span>
                <div class="flex items-center gap-2">
                    ${!isMe ? `
                        <button onclick="toggleActive('${u.id}', ${!u.isActive})" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all ${isDeactivated ? 'text-emerald-500' : 'text-red-500'}">
                            <i data-lucide="${isDeactivated ? 'eye' : 'eye-off'}" class="w-4 h-4"></i>
                        </button>
                    ` : ''}
                    <button onclick="editUser('${u.id}')" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all text-blue-400">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    ${!isMe ? `
                        <button onclick="deleteUser('${u.id}')" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-red-500/20 flex items-center justify-center transition-all text-red-500">
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
    const user = state.users.find(u => u.id == id);
    if (!user) return;

    state.editingUser = user;
    state.formData = {
        name: user.name,
        email: user.email,
        password: '', // blank on edit
        role: user.role,
        floorId: user.floorId || '',
        assignedCategories: user.assignedCategories || [],
        permissions: user.permissions || []
    };
    
    renderForm();
    document.getElementById('user-modal').classList.remove('hidden');
};

function renderForm() {
    const role = state.formData.role;
    const form = document.getElementById('user-form');
    if (!form) return;

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
        <button type="button" onclick="setRole('${r}')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all ${role === r ? 'bg-[#d4af37] text-black border-[#d4af37] shadow-[0_0_20px_rgba(212,175,55,0.3)]' : 'bg-white/5 text-gray-500 border-white/5 hover:border-white/20'}">
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
        state.floors.map(f => `<option value="${f.id}" ${state.formData.floorId == f.id ? 'selected' : ''}>Floor #${f.number} - ${f.name}</option>`).join('');

    // Categories
    const catList = document.getElementById('category-list');
    catList.innerHTML = state.categories.map(c => `
        <button type="button" onclick="toggleCategory('${c.name}')" class="p-3 rounded-xl border text-[10px] font-bold text-left transition-all ${state.formData.assignedCategories.includes(c.name) ? 'bg-orange-400/10 border-orange-400 text-orange-400' : 'bg-white/5 border-white/5 text-gray-500'}">
            ${c.name}
        </button>
    `).join('');

    // Permissions Matrix
    const permGrid = document.getElementById('permission-grid');
    permGrid.innerHTML = Object.entries(PERMISSION_GROUPS).map(([cat, ps]) => {
        const allSelected = ps.every(p => state.formData.permissions.includes(p));
        return `
            <div class="col-span-full border-b border-white/5 pb-2 mt-4 mb-2 flex justify-between items-center">
                <span class="text-[9px] font-black uppercase tracking-[2px] text-gray-500">${cat}</span>
                <button type="button" onclick="togglePermGroup('${cat}')" class="text-[8px] font-black uppercase text-[#d4af37]/60 hover:text-[#d4af37]">${allSelected ? 'None' : 'All'}</button>
            </div>
            ${ps.map(p => `
                <button type="button" onclick="togglePerm('${p}')" class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5 group transition-all ${state.formData.permissions.includes(p) ? 'border-[#d4af37]/30 bg-[#1a1712]' : ''}">
                    <span class="text-[10px] font-bold ${state.formData.permissions.includes(p) ? 'text-[#f3cf7a]' : 'text-gray-500'}">${p.replace(':',' ')}</span>
                    ${state.formData.permissions.includes(p) ? '<i data-lucide="check" class="w-3 h-3 text-[#d4af37]"></i>' : ''}
                </button>
            `).join('')}
        `;
    }).join('');

    lucide.createIcons();
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
