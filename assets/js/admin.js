/**
 * Satay Ordering System - Admin Management Portal Logic
 */

if (typeof SatayApp !== 'undefined' && !SatayApp.escapeHtml) {
  SatayApp.escapeHtml = function (str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  };
}

const AdminApp = {
  activeTab: 'dashboard', // 'dashboard', 'menu', 'orders', 'tables', 'users', 'settings'
  menuItems: [],
  categories: [],
  orders: [],
  tables: [],
  users: [],
  userRoleFilter: 'all',
  overviewDateFilter: 'today',
  overviewStartDate: '',
  overviewEndDate: '',

  init() {
    this.fetchDashboardStats();
    this.fetchMenu();
    this.fetchOrders();
    this.fetchTables();
    this.fetchUsers();
    this.fetchSettings();
    this.bindEvents();
  },

  bindEvents() {
    // Tab switching
    document.querySelectorAll('.admin-nav-item').forEach(btn => {
      btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        this.switchTab(tab);
      });
    });

    // Menu item form submit
    const menuForm = document.getElementById('menu-item-form');
    if (menuForm) {
      menuForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.saveMenuItem();
      });
    }

    // User form submit
    const userForm = document.getElementById('user-form');
    if (userForm) {
      userForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.saveUser();
      });
    }

    // Settings form submit
    const settingsForm = document.getElementById('settings-form');
    if (settingsForm) {
      settingsForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.saveSettings();
      });
    }

    // Order filters
    const orderStatusFilter = document.getElementById('filter-order-status');
    const orderDiningFilter = document.getElementById('filter-order-dining');
    const orderDateFilter = document.getElementById('filter-order-date');
    const orderSearch = document.getElementById('filter-order-search');

    if (orderStatusFilter) orderStatusFilter.addEventListener('change', () => this.fetchOrders());
    if (orderDiningFilter) orderDiningFilter.addEventListener('change', () => this.fetchOrders());
    if (orderDateFilter) orderDateFilter.addEventListener('change', () => this.fetchOrders());
    if (orderSearch) orderSearch.addEventListener('input', () => this.fetchOrders());
  },

  switchTab(tab) {
    this.activeTab = tab;
    document.querySelectorAll('.admin-nav-item').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    const titleMap = {
      'dashboard': 'Executive Overview',
      'menu': 'Menu Catalog',
      'orders': 'Live Orders',
      'tables': 'Table QRs & Stands',
      'users': 'Staff & Customer Accounts',
      'settings': 'Store & System Settings'
    };
    const titleBadge = document.getElementById('topbar-admin-title');
    if (titleBadge && titleMap[tab]) {
      titleBadge.textContent = titleMap[tab];
    }

    const sections = ['dashboard', 'menu', 'orders', 'tables', 'users', 'settings'];
    sections.forEach(s => {
      const el = document.getElementById(`tab-${s}`);
      if (el) el.style.display = (s === tab) ? 'block' : 'none';
    });

    if (tab === 'dashboard') this.fetchDashboardStats();
    if (tab === 'orders') this.fetchOrders();
    if (tab === 'tables') this.fetchTables();
    if (tab === 'users') this.fetchUsers();
  },

  // 1. Dashboard Analytics
  setDateFilter(range) {
    this.overviewDateFilter = range;
    document.querySelectorAll('.overview-date-preset').forEach(btn => {
      const match = btn.dataset.range === range;
      btn.classList.toggle('active', match);
      btn.classList.toggle('btn-primary', match);
      btn.classList.toggle('btn-ghost', !match);
    });

    const customContainer = document.getElementById('overview-custom-date-container');
    if (customContainer) {
      customContainer.style.display = (range === 'custom') ? 'inline-flex' : 'none';
    }

    if (range !== 'custom') {
      this.fetchDashboardStats();
    }
  },

  toggleCustomDateInputs() {
    this.setDateFilter('custom');
  },

  applyCustomDateFilter() {
    const start = document.getElementById('overview-start-date')?.value;
    const end = document.getElementById('overview-end-date')?.value;
    if (!start && !end) {
      if (typeof SatayApp !== 'undefined') {
        SatayApp.showToast('Please select at least a start date', 'warning');
      }
      return;
    }
    this.overviewStartDate = start || '';
    this.overviewEndDate = end || '';
    this.fetchDashboardStats();
  },

  async fetchDashboardStats() {
    try {
      const params = new URLSearchParams();
      params.append('filter_type', this.overviewDateFilter);
      if (this.overviewDateFilter === 'custom') {
        if (this.overviewStartDate) params.append('start_date', this.overviewStartDate);
        if (this.overviewEndDate) params.append('end_date', this.overviewEndDate);
      }

      const res = await fetch(`api/stats.php?${params.toString()}`);
      const data = await res.json();
      if (data.success) {
        this.renderDashboardKPIs(data);
        this.renderDailyBreakdownTable(data.daily_breakdown || []);
        this.renderStickBreakdown(data.stick_breakdown);
        this.renderTopItems(data.top_items);
        this.renderRecentOrders(data.recent_orders);
      }
    } catch (e) {
      console.error('Stats fetch error:', e);
    }
  },

  renderDashboardKPIs(data) {
    const stats = data.period_stats || data.today || {};
    const dateLabel = data.date_filter?.label || 'Today';

    const activeDateEl = document.getElementById('overview-active-date-label');
    if (activeDateEl) activeDateEl.textContent = dateLabel;

    const revLabel = document.getElementById('kpi-revenue-label');
    const sticksLabel = document.getElementById('kpi-sticks-label');
    const ordersLabel = document.getElementById('kpi-orders-label');

    if (revLabel) revLabel.innerText = `${dateLabel.toUpperCase()} REVENUE`;
    if (sticksLabel) sticksLabel.innerText = `${dateLabel.toUpperCase()} STICKS`;
    if (ordersLabel) ordersLabel.innerText = `${dateLabel.toUpperCase()} ORDERS`;

    const todayRev = document.getElementById('kpi-today-revenue');
    const todaySticks = document.getElementById('kpi-today-sticks');
    const todayOrders = document.getElementById('kpi-today-orders');
    const activeOrders = document.getElementById('kpi-active-orders');
    const aov = document.getElementById('kpi-aov');

    if (todayRev) todayRev.innerText = SatayApp.formatPrice(stats.total_revenue || 0);
    if (todaySticks) todaySticks.innerText = `${stats.total_sticks_sold || 0} Sticks`;
    if (todayOrders) todayOrders.innerText = `${stats.total_orders || 0} Orders`;
    if (activeOrders) activeOrders.innerText = `${stats.active_orders || 0} In Kitchen`;
    if (aov) aov.innerText = SatayApp.formatPrice(stats.avg_order_value || 0);
  },

  renderDailyBreakdownTable(dailyList) {
    const tbody = document.getElementById('daily-breakdown-tbody');
    if (!tbody) return;

    if (!dailyList || dailyList.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No sales data recorded for the selected date timeframe.</td></tr>`;
      return;
    }

    tbody.innerHTML = dailyList.map(row => {
      const ordersCount = parseInt(row.orders_count, 10) || 0;
      const revenue = parseFloat(row.daily_revenue) || 0;
      const completed = parseInt(row.completed_count, 10) || 0;
      const cancelled = parseInt(row.cancelled_count, 10) || 0;
      const nonCancelledOrders = Math.max(1, ordersCount - cancelled);
      const avgValue = revenue > 0 ? (revenue / nonCancelledOrders) : 0;

      // Format date for readable label
      const d = new Date(row.order_date + 'T00:00:00');
      const formattedDate = !isNaN(d)
        ? d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
        : row.order_date;

      return `
        <tr>
          <td>
            <div style="font-weight:700; color:var(--text-main);">${formattedDate}</div>
            <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">${row.order_date}</div>
          </td>
          <td><strong style="color:var(--text-main);">${ordersCount}</strong></td>
          <td><span class="badge badge-success" style="font-size:0.75rem;">${completed} completed</span></td>
          <td>${cancelled > 0 ? `<span class="badge badge-danger" style="font-size:0.75rem;">${cancelled} cancelled</span>` : `<span style="color:var(--text-muted); font-size:0.85rem;">0</span>`}</td>
          <td><strong style="color:var(--gold); font-size:0.95rem;">${SatayApp.formatPrice(revenue)}</strong></td>
          <td><span style="color:var(--text-muted);">${SatayApp.formatPrice(avgValue)}</span></td>
          <td>
            <button class="btn btn-secondary btn-sm" onclick="AdminApp.filterOrdersBySpecificDate('${row.order_date}')" title="View all orders placed on this date">
              View Orders →
            </button>
          </td>
        </tr>
      `;
    }).join('');
  },

  filterOrdersBySpecificDate(dateStr) {
    this.switchTab('orders');
    const dateInput = document.getElementById('filter-order-date');
    if (dateInput) {
      dateInput.value = dateStr;
      this.fetchOrders();
    }
  },

  renderStickBreakdown(sticks) {
    const container = document.getElementById('stick-breakdown-container');
    if (!container || !sticks) return;

    let total = 0;
    Object.values(sticks).forEach(v => total += v);

    container.innerHTML = Object.entries(sticks).map(([meat, qty]) => {
      const pct = total > 0 ? Math.round((qty / total) * 100) : 0;
      return `
 <div style="margin-bottom:1rem;">
 <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:4px;">
 <span><strong>${meat}</strong></span>
 <span><strong>${qty} sticks</strong> (${pct}%)</span>
 </div>
 <div style="background:rgba(255,255,255,0.08); border-radius:var(--radius-full); height:8px; overflow:hidden;">
 <div style="width:${pct}%; height:100%; background:linear-gradient(90deg, var(--primary), var(--gold)); border-radius:var(--radius-full);"></div>
 </div>
 </div>
 `;
    }).join('');
  },

  renderTopItems(items) {
    const container = document.getElementById('top-items-list');
    if (!container || !items) return;

    container.innerHTML = items.map((it, idx) => `
 <div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0; border-bottom:1px solid var(--border-color);">
 <div style="display:flex; align-items:center; gap:8px;">
 <span class="badge badge-gold" style="width:24px; height:24px; padding:0; justify-content:center;">${idx + 1}</span>
 <strong>${it.item_name}</strong>
 </div>
 <div style="text-align:right;">
 <strong style="color:var(--gold);">${SatayApp.formatPrice(it.total_revenue)}</strong>
 <div style="font-size:0.75rem; color:var(--text-muted);">${it.total_qty} units sold</div>
 </div>
 </div>
 `).join('');
  },

  renderRecentOrders(orders) {
    const tbody = document.getElementById('recent-orders-tbody');
    if (!tbody || !orders) return;

    tbody.innerHTML = orders.map(ord => `
 <tr>
 <td><strong>#${ord.order_number}</strong></td>
 <td>${ord.customer_name}</td>
 <td><span class="badge badge-neutral">${ord.dining_type.toUpperCase()}</span></td>
 <td><span class="badge ${this.getOrderBadgeClass(ord.order_status)}">${ord.order_status}</span></td>
 <td style="font-weight:700; color:var(--gold);">${SatayApp.formatPrice(ord.total_amount)}</td>
 <td>
 <button class="btn btn-sm btn-secondary" onclick="AdminApp.viewOrderDetails(${ord.id})">Details</button>
 </td>
 </tr>
 `).join('');
  },

  // 2. Menu Management (CRUD)
  async fetchMenu() {
    try {
      const res = await fetch('api/menu.php?all=1');
      const data = await res.json();
      if (data.success) {
        this.menuItems = data.items;
        this.categories = data.categories;
        this.renderMenuTable();
        this.populateCategorySelect();
      }
    } catch (e) {
      console.error(e);
    }
  },

  populateCategorySelect() {
    const sel = document.getElementById('menu-item-category');
    if (!sel) return;

    sel.innerHTML = this.categories.map(c => `
 <option value="${c.id}">${c.name}</option>
 `).join('');
  },

  renderMenuTable() {
    const tbody = document.getElementById('admin-menu-tbody');
    if (!tbody) return;

    if (this.menuItems.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" style="text-align:center; padding: 3rem 1rem; color:var(--text-muted);">
            <div style="font-size:2.5rem; margin-bottom:0.5rem;">🍢</div>
            <h3>Menu Catalog is Empty</h3>
            <p style="font-size:0.9rem; margin-bottom:1.25rem;">Start building your menu by adding your first satay skewers or platters.</p>
            <button type="button" class="btn btn-primary" onclick="AdminApp.openAddMenuModal()">
              + Add Your First Item 
            </button>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = this.menuItems.map(it => {
      const stock = parseInt(it.stock_quantity !== null && it.stock_quantity !== undefined ? it.stock_quantity : 100);
      const isAvailable = (it.is_available == 1 && stock > 0);

      return `
        <tr>
          <td>
            <div style="display:flex; align-items:center; gap:10px;">
              <img src="${it.image_url}" style="width:42px; height:42px; border-radius:var(--radius-sm); object-fit:cover; border:1px solid var(--border-color);" onerror="this.src='assets/images/sate_ayam.png'">
              <div>
                <strong>${SatayApp.escapeHtml(it.name)}</strong>
                <div style="font-size:0.75rem; color:var(--text-muted);">${it.description ? SatayApp.escapeHtml(it.description.substring(0, 35)) + '...' : ''}</div>
              </div>
            </div>
          </td>
          <td><span class="badge badge-neutral">${SatayApp.escapeHtml(it.category_name)}</span></td>
          <td style="font-weight:700; color:var(--gold);">${SatayApp.formatPrice(it.price_per_unit)} / ${SatayApp.escapeHtml(it.unit_name)}</td>
          <td>${it.min_quantity > 1 ? `<span class="badge badge-primary">Min ${it.min_quantity}</span>` : '1'}</td>
          <td>
            <div style="display:flex; align-items:center; gap:6px;">
              <input type="number" class="form-input" min="0" value="${stock}" id="admin-stock-${it.id}" style="width:75px; text-align:center; font-weight:800; padding:0.3rem 0.4rem; ${stock <= 10 ? 'border-color:var(--danger); color:var(--danger);' : ''}" onchange="AdminApp.quickSetStock(${it.id}, this.value)">
              <span class="badge ${isAvailable ? 'badge-success' : 'badge-danger'}">
                ${isAvailable ? 'In Stock' : 'Sold Out'}
              </span>
            </div>
          </td>
          <td>
            <div style="display:flex; gap:6px;">
              <button class="btn btn-sm btn-secondary" onclick="AdminApp.openEditMenuModal(${it.id})">Edit</button>
              <button class="btn btn-sm btn-danger" onclick="AdminApp.deleteMenuItem(${it.id})">Delete</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  },

  async quickSetStock(id, qty) {
    const quantity = Math.max(0, parseInt(qty) || 0);
    try {
      const res = await fetch('api/menu.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, stock_quantity: quantity })
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast(`Stock updated to ${quantity}`, 'success');
        this.fetchMenu();
      } else {
        SatayApp.showToast(data.message || 'Update failed', 'danger');
      }
    } catch (e) {
      console.error(e);
    }
  },

  openAddMenuModal() {
    document.getElementById('menu-item-id').value = '';
    document.getElementById('menu-item-name').value = '';
    document.getElementById('menu-item-desc').value = '';
    document.getElementById('menu-item-price').value = '';
    document.getElementById('menu-item-unit').value = 'cucuk';
    document.getElementById('menu-item-stock').value = '100';
    document.getElementById('menu-item-min-qty').value = '5';
    document.getElementById('menu-item-meat-type').value = 'ayam';
    document.getElementById('menu-item-img').value = 'assets/images/sate_ayam.png';
    const preview = document.getElementById('menu-item-img-preview');
    if (preview) preview.src = 'assets/images/sate_ayam.png';
    const fileInput = document.getElementById('menu-item-file-input');
    if (fileInput) fileInput.value = '';
    document.getElementById('menu-item-popular').checked = false;
    document.getElementById('menu-item-available').checked = true;
    document.getElementById('menu-modal-title').innerText = 'Add New Menu Item';

    SatayApp.openModal('menu-modal');
  },

  openEditMenuModal(id) {
    const item = this.menuItems.find(i => i.id == id);
    if (!item) return;

    const imgUrl = item.image_url || 'assets/images/sate_ayam.png';
    document.getElementById('menu-item-id').value = item.id;
    document.getElementById('menu-item-name').value = item.name;
    document.getElementById('menu-item-category').value = item.category_id;
    document.getElementById('menu-item-desc').value = item.description || '';
    document.getElementById('menu-item-price').value = item.price_per_unit;
    document.getElementById('menu-item-unit').value = item.unit_name || 'cucuk';
    document.getElementById('menu-item-stock').value = (item.stock_quantity !== null && item.stock_quantity !== undefined) ? item.stock_quantity : 100;
    document.getElementById('menu-item-min-qty').value = item.min_quantity || 1;
    document.getElementById('menu-item-meat-type').value = item.stick_meat_type || '';
    document.getElementById('menu-item-img').value = imgUrl;
    const preview = document.getElementById('menu-item-img-preview');
    if (preview) preview.src = imgUrl;
    const fileInput = document.getElementById('menu-item-file-input');
    if (fileInput) fileInput.value = '';
    document.getElementById('menu-item-popular').checked = (item.is_popular == 1);
    document.getElementById('menu-item-available').checked = (item.is_available == 1);
    document.getElementById('menu-modal-title').innerText = `Edit Menu Item: ${item.name}`;

    SatayApp.openModal('menu-modal');
  },

  updateImagePreview(url) {
    const preview = document.getElementById('menu-item-img-preview');
    if (preview) {
      preview.src = url || 'assets/images/sate_ayam.png';
    }
  },

  async handleImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);

    const imgInput = document.getElementById('menu-item-img');
    const preview = document.getElementById('menu-item-img-preview');

    try {
      SatayApp.showToast('Uploading image...', 'info');
      const res = await fetch('api/upload.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        if (imgInput) imgInput.value = data.image_url;
        if (preview) preview.src = data.image_url;
        SatayApp.showToast('Image uploaded successfully!', 'success');
      } else {
        SatayApp.showToast(data.message || 'Image upload failed', 'danger');
      }
    } catch (err) {
      console.error(err);
      SatayApp.showToast('Network error uploading image', 'danger');
    }
  },

  async saveMenuItem() {
    const id = document.getElementById('menu-item-id').value;
    const payload = {
      id: id ? parseInt(id) : null,
      name: document.getElementById('menu-item-name').value.trim(),
      category_id: parseInt(document.getElementById('menu-item-category').value),
      description: document.getElementById('menu-item-desc').value.trim(),
      price_per_unit: parseFloat(document.getElementById('menu-item-price').value),
      unit_name: document.getElementById('menu-item-unit').value.trim(),
      stock_quantity: parseInt(document.getElementById('menu-item-stock').value) || 0,
      min_quantity: parseInt(document.getElementById('menu-item-min-qty').value) || 1,
      stick_meat_type: document.getElementById('menu-item-meat-type').value.trim() || null,
      image_url: document.getElementById('menu-item-img').value.trim() || 'assets/images/sate_ayam.png',
      is_popular: document.getElementById('menu-item-popular').checked ? 1 : 0,
      is_available: document.getElementById('menu-item-available').checked ? 1 : 0
    };

    try {
      const res = await fetch('api/menu.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast(data.message, 'success');
        SatayApp.closeModal('menu-modal');
        this.fetchMenu();
      } else {
        SatayApp.showToast(data.message || 'Save failed', 'danger');
      }
    } catch (e) {
      console.error(e);
      SatayApp.showToast('Error saving item', 'danger');
    }
  },

  async deleteMenuItem(id) {
    if (!confirm('Are you sure you want to delete this menu item?')) return;

    try {
      const res = await fetch(`api/menu.php?id=${id}`, { method: 'DELETE' });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast('Item deleted', 'success');
        this.fetchMenu();
      }
    } catch (e) {
      console.error(e);
    }
  },

  // 3. Orders Master Management
  async fetchOrders() {
    const status = document.getElementById('filter-order-status')?.value || 'all';
    const dining = document.getElementById('filter-order-dining')?.value || 'all';
    const date = document.getElementById('filter-order-date')?.value || '';
    const search = document.getElementById('filter-order-search')?.value.trim() || '';

    try {
      const res = await fetch(`api/orders.php?status=${status}&dining_type=${dining}&date=${encodeURIComponent(date)}&search=${encodeURIComponent(search)}`);
      const data = await res.json();
      if (data.success) {
        this.orders = data.orders;
        this.renderOrdersTable();
      }
    } catch (e) {
      console.error(e);
    }
  },

  renderOrdersTable() {
    const tbody = document.getElementById('admin-orders-tbody');
    if (!tbody) return;

    if (this.orders.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">No orders found matching criteria.</td></tr>`;
      return;
    }

    tbody.innerHTML = this.orders.map(ord => `
 <tr>
 <td><strong>#${ord.order_number}</strong></td>
 <td>
 <strong>${ord.customer_name}</strong>
 <div style="font-size:0.75rem; color:var(--text-muted);">${ord.customer_phone || ''}</div>
 </td>
 <td>
 <span class="badge badge-neutral">${ord.dining_type.toUpperCase()}</span>
 ${ord.table_number ? `<div style="font-size:0.75rem; color:var(--gold); margin-top:2px;">Table ${ord.table_number}</div>` : ''}
 </td>
 <td>
 <span class="badge ${this.getOrderBadgeClass(ord.order_status)}">${ord.order_status}</span>
 </td>
 <td>
 <span class="badge ${ord.payment_status === 'paid' ? 'badge-success' : 'badge-gold'}">${ord.payment_status}</span>
 </td>
 <td style="font-weight:700; color:var(--gold);">${SatayApp.formatPrice(ord.total_amount)}</td>
 <td>
 <button class="btn btn-sm btn-secondary" onclick="AdminApp.viewOrderDetails(${ord.id})">Details / Edit</button>
 </td>
 </tr>
 `).join('');
  },

  getOrderBadgeClass(status) {
    switch (status) {
      case 'pending': return 'badge-info';
      case 'confirmed': return 'badge-gold';
      case 'grilling': return 'badge-primary';
      case 'ready': return 'badge-success';
      case 'completed': return 'badge-neutral';
      case 'cancelled': return 'badge-danger';
      default: return 'badge-neutral';
    }
  },

  async viewOrderDetails(orderId) {
    try {
      const res = await fetch(`api/orders.php?id=${orderId}`);
      const data = await res.json();
      if (data.success && data.order) {
        const ord = data.order;
        const modal = document.getElementById('order-detail-content');
        if (!modal) return;

        const itemsHtml = (ord.items || []).map(it => `
 <div style="display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid var(--border-color);">
 <div>
 <strong>${it.quantity}x</strong> ${it.item_name}
 ${it.special_notes ? `<div style="font-size:0.75rem; color:var(--gold);">* ${it.special_notes}</div>` : ''}
 </div>
 <div style="font-weight:700;">${SatayApp.formatPrice(it.total_price)}</div>
 </div>
 `).join('');

        modal.innerHTML = `
 <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
 <div>
 <h3 style="font-size:1.25rem;">Order #${ord.order_number}</h3>
 <p style="font-size:0.85rem; color:var(--text-muted);">${ord.created_at}</p>
 </div>
 <div style="text-align:right;">
 <span class="badge ${this.getOrderBadgeClass(ord.order_status)}" style="font-size:0.85rem;">${ord.order_status}</span>
 </div>
 </div>

 <div style="background:var(--bg-card); padding:1rem; border-radius:var(--radius-md); border:1px solid var(--border-color); margin-bottom:1rem;">
 <div style="font-size:0.88rem; margin-bottom:4px;"><strong>Customer:</strong> ${ord.customer_name} (${ord.customer_phone || 'No phone'})</div>
 <div style="font-size:0.88rem; margin-bottom:4px;"><strong>Mode:</strong> ${ord.dining_type.toUpperCase()} ${ord.table_number ? `(Table ${ord.table_number})` : ''}</div>
 ${ord.delivery_address ? `<div style="font-size:0.88rem; margin-bottom:4px;"><strong>Address:</strong> ${ord.delivery_address}</div>` : ''}
 ${ord.notes ? `<div style="font-size:0.88rem; color:var(--gold);"><strong>Notes:</strong> ${ord.notes}</div>` : ''}
 </div>

 <div style="margin-bottom:1rem;">
 <h4 style="font-size:0.95rem; margin-bottom:0.5rem;">Order Items:</h4>
 ${itemsHtml}
 </div>

 <div style="border-top:1px solid var(--border-color); padding-top:0.75rem; margin-bottom:1.5rem;">
 <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
 <span>Subtotal:</span>
 <span>${SatayApp.formatPrice(ord.subtotal)}</span>
 </div>
 <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
 <span>SST (6%):</span>
 <span>${SatayApp.formatPrice(ord.tax_amount)}</span>
 </div>
 ${parseFloat(ord.service_fee) > 0 ? `
 <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
 <span>Delivery Fee:</span>
 <span>${SatayApp.formatPrice(ord.service_fee)}</span>
 </div>` : ''}
 <div style="display:flex; justify-content:space-between; font-size:1.15rem; font-weight:800; color:var(--gold); margin-top:6px;">
 <span>Total Amount:</span>
 <span>${SatayApp.formatPrice(ord.total_amount)}</span>
 </div>
 </div>

 <div style="display:flex; gap:0.5rem; flex-wrap:wrap; justify-content:flex-end;">
 <button class="btn btn-secondary" onclick="SatayApp.printThermalReceipt(${JSON.stringify(ord).replace(/"/g, '&quot;')})"> Print Bill</button>
 <select class="form-select" id="update-order-status-val" style="width:auto; padding:0.4rem 0.8rem;">
 <option value="pending" ${ord.order_status === 'pending' ? 'selected' : ''}>Pending</option>
 <option value="confirmed" ${ord.order_status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
 <option value="grilling" ${ord.order_status === 'grilling' ? 'selected' : ''}>Grilling</option>
 <option value="ready" ${ord.order_status === 'ready' ? 'selected' : ''}>Ready</option>
 <option value="completed" ${ord.order_status === 'completed' ? 'selected' : ''}>Completed</option>
 <option value="cancelled" ${ord.order_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
 </select>
 <button class="btn btn-primary" onclick="AdminApp.updateOrderStatusFromModal(${ord.id})">Update Status</button>
 </div>
 `;

        SatayApp.openModal('order-detail-modal');
      }
    } catch (e) {
      console.error(e);
    }
  },

  async updateOrderStatusFromModal(orderId) {
    const status = document.getElementById('update-order-status-val').value;
    try {
      const res = await fetch('api/orders.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: orderId, order_status: status })
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast('Order status updated', 'success');
        SatayApp.closeModal('order-detail-modal');
        this.fetchOrders();
        this.fetchDashboardStats();
      }
    } catch (e) {
      console.error(e);
    }
  },

  exportCSV() {
    window.location.href = 'api/stats.php?export=orders_csv';
  },

  // 4. Table & QR Manager
  async fetchTables() {
    try {
      const res = await fetch('api/tables.php');
      const data = await res.json();
      if (data.success) {
        this.tables = data.tables || [];
        this.baseUrl = data.base_url || window.location.href.split('admin.php')[0];
        this.renderTablesGrid();
      }
    } catch (e) {
      console.error('Error fetching tables:', e);
    }
  },

  renderTablesGrid() {
    const grid = document.getElementById('tables-grid');
    if (!grid) return;

    if (!this.tables || this.tables.length === 0) {
      grid.innerHTML = `
        <div style="grid-column:1 / -1; text-align:center; padding:3rem 1.5rem; background:var(--bg-surface); border:1px dashed var(--border-color); border-radius:var(--radius-lg);">
          <div style="font-size:2.5rem; margin-bottom:0.5rem;">🍽️</div>
          <h3 style="margin-bottom:0.25rem;">No Dining Tables Registered</h3>
          <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:1.25rem; max-width:440px; margin-left:auto; margin-right:auto;">
            Add your dining tables to generate printable QR code stands and enable table selection in dine-in ordering and POS.
          </p>
          <button class="btn btn-primary" onclick="AdminApp.openAddTableModal()">
            + Add First Table
          </button>
        </div>
      `;
      return;
    }

    grid.innerHTML = this.tables.map(tbl => {
      const isOccupied = tbl.is_occupied;
      const baseUrl = this.baseUrl || window.location.href.split('admin.php')[0];
      const tableOrderUrl = tbl.order_url || `${baseUrl}index.php?table=${encodeURIComponent(tbl.table_number)}&token=${encodeURIComponent(tbl.qr_token)}`;

      return `
        <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.25rem; display:flex; flex-direction:column; justify-content:space-between;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem;">
            <div>
              <h3 style="font-size:1.3rem; font-weight:800;">${SatayApp.escapeHtml(tbl.table_number)}</h3>
              <p style="font-size:0.8rem; color:var(--text-muted);">Capacity: ${tbl.capacity} Pax Seats</p>
            </div>
            <span class="badge ${isOccupied ? 'badge-primary' : 'badge-success'}" style="font-size:0.72rem;">
              ${isOccupied ? `● ${tbl.active_orders} Active Order(s)` : 'Available'}
            </span>
          </div>

          <div style="background:var(--bg-card); padding:0.75rem; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:1rem; text-align:center;">
            <div style="font-size:0.75rem; color:var(--text-muted); word-break:break-all;">
              QR Token: <strong>${SatayApp.escapeHtml(tbl.qr_token)}</strong>
            </div>
          </div>

          <div style="display:flex; gap:6px;">
            <button class="btn btn-primary btn-sm" style="flex:1;" onclick="AdminApp.printTableCard('${SatayApp.escapeHtml(tbl.table_number)}', '${SatayApp.escapeHtml(tbl.qr_token)}', '${tableOrderUrl}')">
              🖨️ Print QR Stand
            </button>
            <button class="btn btn-secondary btn-sm" style="color:var(--danger); border-color:rgba(181,61,46,0.3);" title="Delete Table" onclick="AdminApp.deleteTable(${tbl.id}, '${SatayApp.escapeHtml(tbl.table_number)}')">
              🗑️
            </button>
          </div>
        </div>
      `;
    }).join('');
  },

  openAddTableModal() {
    const numInput = document.getElementById('table-num-input');
    const capInput = document.getElementById('table-cap-input');
    if (numInput) numInput.value = '';
    if (capInput) capInput.value = '4';
    SatayApp.openModal('table-modal');
  },

  async saveTable(e) {
    e.preventDefault();
    const tableNumber = document.getElementById('table-num-input').value.trim();
    const capacity = parseInt(document.getElementById('table-cap-input').value.trim() || 4);
    const submitBtn = document.getElementById('btn-save-table');

    if (!tableNumber) {
      SatayApp.showToast('Please enter table number or label', 'danger');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.innerText = 'Creating Table...';

    try {
      const res = await fetch('api/tables.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ table_number: tableNumber, capacity: capacity })
      });
      const data = await res.json();

      if (data.success) {
        SatayApp.showToast(`Table "${tableNumber}" added successfully!`, 'success');
        SatayApp.closeModal('table-modal');
        this.fetchTables();
      } else {
        SatayApp.showToast(data.message || 'Failed to add table', 'danger');
      }
    } catch (err) {
      SatayApp.showToast('Connection error. Please try again.', 'danger');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerText = 'Create Table';
    }
  },

  async deleteTable(id, tableNumber) {
    if (!confirm(`Are you sure you want to delete table "${tableNumber}"?`)) return;

    try {
      const res = await fetch(`api/tables.php?id=${id}`, {
        method: 'DELETE'
      });
      const data = await res.json();

      if (data.success) {
        SatayApp.showToast(`Table "${tableNumber}" deleted`, 'info');
        this.fetchTables();
      } else {
        SatayApp.showToast(data.message || 'Failed to delete table', 'danger');
      }
    } catch (err) {
      SatayApp.showToast('Connection error', 'danger');
    }
  },

  async deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user account?')) return;

    try {
      const res = await fetch(`api/stats.php?users=1&id=${userId}`, {
        method: 'DELETE'
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast('User deleted successfully', 'success');
        this.fetchUsers();
      } else {
        SatayApp.showToast(data.message || 'Failed to delete user', 'danger');
      }
    } catch (e) {
      console.error(e);
      SatayApp.showToast('Network error deleting user', 'danger');
    }
  },

  // 6. Settings Management
  async fetchSettings() {
    try {
      const res = await fetch('api/stats.php?settings=1');
      const data = await res.json();
      if (data.success && data.settings) {
        const s = data.settings;
        if (document.getElementById('setting-store-name')) {
          document.getElementById('setting-store-name').value = s.store_name || '';
        }
        if (document.getElementById('setting-public-base-url')) {
          document.getElementById('setting-public-base-url').value = s.public_base_url || '';
        }
        if (document.getElementById('setting-tax-rate')) {
          document.getElementById('setting-tax-rate').value = s.tax_rate_percent || '6';
        }
        if (document.getElementById('setting-currency')) {
          document.getElementById('setting-currency').value = s.currency_symbol || 'RM';
        }
        if (document.getElementById('setting-phone')) {
          document.getElementById('setting-phone').value = s.whatsapp_number || '';
        }
        if (document.getElementById('setting-auto-refresh')) {
          document.getElementById('setting-auto-refresh').value = s.auto_refresh_seconds || '6';
        }
        if (document.getElementById('setting-address')) {
          document.getElementById('setting-address').value = s.store_address || '';
        }
        if (document.getElementById('setting-kitchen-audio')) {
          document.getElementById('setting-kitchen-audio').checked = s.kitchen_sound_alert !== '0';
        }
      }
    } catch (e) {
      console.error('Error fetching settings:', e);
    }
  },

  autoDetectBaseUrl() {
    const origin = window.location.origin;
    let path = window.location.pathname;
    if (path.includes('admin.php')) {
      path = path.substring(0, path.indexOf('admin.php'));
    }
    const detectedUrl = (origin + path).replace(/\/?$/, '/');
    const input = document.getElementById('setting-public-base-url');
    if (input) {
      input.value = detectedUrl;
      SatayApp.showToast(`Auto-detected URL: ${detectedUrl}`, 'info');
    }
  },

  async saveSettings() {
    const btn = document.getElementById('btn-save-settings');
    if (btn) {
      btn.disabled = true;
      btn.innerText = 'Saving...';
    }

    const payload = {
      store_name: document.getElementById('setting-store-name')?.value.trim() || 'Sate Tulang Madu',
      public_base_url: document.getElementById('setting-public-base-url')?.value.trim() || '',
      tax_rate_percent: document.getElementById('setting-tax-rate')?.value.trim() || '6',
      currency_symbol: document.getElementById('setting-currency')?.value.trim() || 'RM',
      whatsapp_number: document.getElementById('setting-phone')?.value.trim() || '',
      auto_refresh_seconds: document.getElementById('setting-auto-refresh')?.value.trim() || '6',
      store_address: document.getElementById('setting-address')?.value.trim() || '',
      kitchen_sound_alert: document.getElementById('setting-kitchen-audio')?.checked ? '1' : '0'
    };

    try {
      const res = await fetch('api/stats.php?settings=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast('Settings saved successfully! Table QRs updated.', 'success');
        this.fetchTables(); // Refresh table links with new base URL
      } else {
        SatayApp.showToast(data.message || 'Failed to save settings', 'danger');
      }
    } catch (e) {
      console.error(e);
      SatayApp.showToast('Network error saving settings', 'danger');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerText = '💾 Save System Settings';
      }
    }
  },

  printTableCard(tableNumber, token, orderUrl) {
    const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(orderUrl)}`;

    const printHtml = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Table QR Stand - ${SatayApp.escapeHtml(tableNumber)}</title>
        <style>
          @page { size: A5 portrait; margin: 8mm; }
          * { box-sizing: border-box; }
          body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
          }
          .no-print-bar {
            width: 100%;
            max-width: 380px;
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
          }
          .btn-print {
            flex: 1;
            padding: 10px;
            background: #8B4513;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
          }
          .btn-close {
            padding: 10px 16px;
            background: #e2e8f0;
            color: #333;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
          }
          .stand-card {
            background: #fff;
            border: 4px solid #8B4513;
            border-radius: 24px;
            padding: 36px 24px;
            width: 100%;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
          }
          .brand-badge {
            width: 48px;
            height: 48px;
            background: #8B4513;
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
          }
          h1 {
            font-size: 22px;
            color: #8B4513;
            margin: 0 0 4px;
            font-weight: 800;
          }
          .tagline {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 16px;
          }
          .table-badge {
            background: #8B4513;
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            padding: 8px 30px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 18px;
            box-shadow: 0 4px 10px rgba(139,69,19,0.3);
          }
          .qr-wrapper {
            background: #faf8f5;
            border: 2px dashed #8B4513;
            border-radius: 16px;
            padding: 16px;
            display: inline-block;
            margin-bottom: 16px;
          }
          .qr-wrapper img {
            width: 220px;
            height: 220px;
            display: block;
          }
          .instruction-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
          }
          .steps {
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 14px;
          }
          .footer-note {
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
          }
          @media print {
            body { background: transparent; padding: 0; }
            .no-print-bar { display: none !important; }
            .stand-card { box-shadow: none; border-color: #8B4513; margin: 0 auto; }
          }
        </style>
      </head>
      <body>
        <div class="no-print-bar">
          <button class="btn-print" onclick="window.print()">🖨️ Print Stand Card</button>
          <button class="btn-close" onclick="window.close()">✕ Close</button>
        </div>
        <div class="stand-card">
          <div class="brand-badge">S</div>
          <h1>SATE TULANG MADU</h1>
          <div class="tagline">Authentic Charcoal Grilled Skewers</div>
          <div class="table-badge">TABLE ${SatayApp.escapeHtml(tableNumber)}</div>
          <div class="qr-wrapper">
            <img src="${qrApiUrl}" alt="Table QR" id="qr-img">
          </div>
          <div class="instruction-title">📱 Scan to Browse Menu & Place Order</div>
          <div class="steps">
            1. Open Camera / QR Scanner on your phone<br>
            2. Choose your skewers, sides & drinks<br>
            3. Order will be freshly prepared and served to your table!
          </div>
          <div class="footer-note">
            Sate Tulang Madu • Contactless Table QR Ordering
          </div>
        </div>
        <script>
          const img = document.getElementById('qr-img');
          function triggerPrint() {
            setTimeout(function() {
              window.print();
            }, 300);
          }
          if (img && img.complete) {
            triggerPrint();
          } else if (img) {
            img.onload = triggerPrint;
            img.onerror = triggerPrint;
          }
        </script>
      </body>
      </html>
    `;

    const printWin = window.open('', '_blank', 'width=520,height=720');
    if (printWin) {
      printWin.document.write(printHtml);
      printWin.document.close();
    } else {
      let iframe = document.getElementById('print-table-iframe');
      if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'print-table-iframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
      }
      iframe.contentWindow.document.open();
      iframe.contentWindow.document.write(printHtml);
      iframe.contentWindow.document.close();
      setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
      }, 500);
    }
  },

  // 5. Settings
  async fetchSettings() {
    try {
      const res = await fetch('api/stats.php?settings=1');
      const data = await res.json();
      if (data.success && data.settings) {
        const s = data.settings;
        if (document.getElementById('setting-store-name')) document.getElementById('setting-store-name').value = s.store_name || '';
        if (document.getElementById('setting-tax-rate')) document.getElementById('setting-tax-rate').value = s.tax_rate_percent || '6';
        if (document.getElementById('setting-currency')) document.getElementById('setting-currency').value = s.currency_symbol || 'RM';
        if (document.getElementById('setting-phone')) document.getElementById('setting-phone').value = s.whatsapp_number || '';
        if (document.getElementById('setting-address')) document.getElementById('setting-address').value = s.store_address || '';
      }
    } catch (e) {
      console.error(e);
    }
  },

  async saveSettings() {
    const payload = {
      store_name: document.getElementById('setting-store-name').value.trim(),
      tax_rate_percent: document.getElementById('setting-tax-rate').value.trim(),
      currency_symbol: document.getElementById('setting-currency').value.trim(),
      whatsapp_number: document.getElementById('setting-phone').value.trim(),
      store_address: document.getElementById('setting-address').value.trim()
    };

    try {
      const res = await fetch('api/stats.php?settings=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast('Settings saved successfully', 'success');
      }
    } catch (e) {
      console.error(e);
    }
  },

  // 6. User & Customer Management
  async fetchUsers() {
    try {
      const res = await fetch('api/stats.php?users=1');
      const data = await res.json();
      if (data.success) {
        this.users = data.users || [];
        this.renderUsersTable();
      }
    } catch (e) {
      console.error('Error fetching users:', e);
    }
  },

  filterUsersByRole(role) {
    this.userRoleFilter = role;
    document.querySelectorAll('.user-filter-pill').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.role === role);
    });
    this.renderUsersTable();
  },

  renderUsersTable() {
    const tbody = document.getElementById('admin-users-tbody');
    if (!tbody) return;

    let filtered = this.users;
    if (this.userRoleFilter !== 'all') {
      filtered = filtered.filter(u => u.role === this.userRoleFilter);
    }

    if (filtered.length === 0) {
      tbody.innerHTML = `
 <tr>
 <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">
 No user accounts found matching filter.
 </td>
 </tr>
 `;
      return;
    }

    const roleBadges = {
      admin: '<span class="badge role-tag-admin" style="font-size:0.75rem;">👑 ADMIN</span>',
      staff_drinks: '<span class="badge" style="font-size:0.75rem; background:rgba(59,130,246,0.2); color:#60a5fa; border:1px solid rgba(59,130,246,0.3);">🥤 DRINK STAFF</span>',
      staff: '<span class="badge role-tag-staff" style="font-size:0.75rem;">🍢 GRILL/STAFF</span>',
      customer: '<span class="badge role-tag-customer" style="font-size:0.75rem;">🛒 CUSTOMER</span>'
    };

    tbody.innerHTML = filtered.map(u => `
 <tr>
 <td>
 <div style="font-weight:700; color:var(--text-main);">${SatayApp.escapeHtml(u.full_name)}</div>
 ${u.address ? `<div style="font-size:0.75rem; color:var(--text-muted); max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"> ${SatayApp.escapeHtml(u.address)}</div>` : ''}
 </td>
 <td>
 <code>${SatayApp.escapeHtml(u.username)}</code>
 </td>
 <td>
 <div style="font-size:0.85rem;"> ${SatayApp.escapeHtml(u.phone || 'None')}</div>
 ${u.email ? `<div style="font-size:0.75rem; color:var(--text-muted);"> ${SatayApp.escapeHtml(u.email)}</div>` : ''}
 </td>
 <td>
 ${roleBadges[u.role] || u.role}
 </td>
 <td>
 <strong style="color:var(--gold);">${u.order_count || 0} Orders</strong>
 ${u.total_spent > 0 ? `<div style="font-size:0.75rem; color:var(--text-muted);">${SatayApp.formatPrice(u.total_spent)}</div>` : ''}
 </td>
 <td style="font-size:0.8rem; color:var(--text-muted);">
 ${u.created_at ? u.created_at.split(' ')[0] : '-'}
 </td>
 <td>
 <div style="display:flex; gap:6px;">
 <button class="btn btn-secondary btn-sm" style="padding:3px 8px; font-size:0.78rem;" onclick="AdminApp.openEditUserModal(${u.id})">
 Edit
 </button>
 <button class="btn btn-secondary btn-sm" style="padding:3px 8px; font-size:0.78rem; color:var(--danger); border-color:rgba(239,68,68,0.3);" onclick="AdminApp.deleteUser(${u.id})">
 Delete
 </button>
 </div>
 </td>
 </tr>
 `).join('');
  },

  openAddUserModal() {
    document.getElementById('user-id').value = '';
    document.getElementById('user-fullname').value = '';
    document.getElementById('user-username').value = '';
    document.getElementById('user-phone').value = '';
    document.getElementById('user-email').value = '';
    document.getElementById('user-address').value = '';
    document.getElementById('user-role').value = 'staff';
    document.getElementById('user-password').value = '';
    document.getElementById('user-modal-title').innerText = '+ Add User Account';

    SatayApp.openModal('user-modal');
  },

  openEditUserModal(userId) {
    const u = this.users.find(x => x.id == userId);
    if (!u) return;

    document.getElementById('user-id').value = u.id;
    document.getElementById('user-fullname').value = u.full_name || '';
    document.getElementById('user-username').value = u.username || '';
    document.getElementById('user-phone').value = u.phone || '';
    document.getElementById('user-email').value = u.email || '';
    document.getElementById('user-address').value = u.address || '';
    document.getElementById('user-role').value = u.role || 'customer';
    document.getElementById('user-password').value = '';
    document.getElementById('user-modal-title').innerText = `Edit User: ${u.full_name}`;

    SatayApp.openModal('user-modal');
  },

  async saveUser() {
    const id = document.getElementById('user-id').value;
    const payload = {
      id: id ? parseInt(id) : null,
      full_name: document.getElementById('user-fullname').value.trim(),
      username: document.getElementById('user-username').value.trim(),
      role: document.getElementById('user-role').value,
      phone: document.getElementById('user-phone').value.trim(),
      email: document.getElementById('user-email').value.trim(),
      address: document.getElementById('user-address').value.trim(),
      password: document.getElementById('user-password').value.trim()
    };

    try {
      const res = await fetch('api/stats.php?users=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.closeModal('user-modal');
        SatayApp.showToast(data.message || 'User saved successfully', 'success');
        this.fetchUsers();
      } else {
        SatayApp.showToast(data.message || 'Failed to save user', 'danger');
      }
    } catch (e) {
      console.error(e);
      SatayApp.showToast('Network error saving user', 'danger');
    }
  },

  async deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user account?')) return;

    try {
      const res = await fetch(`api/stats.php?users=1&id=${userId}`, {
        method: 'DELETE'
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast('User deleted successfully', 'success');
        this.fetchUsers();
      } else {
        SatayApp.showToast(data.message || 'Failed to delete user', 'danger');
      }
    } catch (e) {
      console.error(e);
      SatayApp.showToast('Network error deleting user', 'danger');
    }
  }
};

document.addEventListener('DOMContentLoaded', () => {
  AdminApp.init();
});
