/**
 * Satay Ordering System - Client & Kitchen Staff Portal (KDS & POS)
 */

if (typeof SatayApp !== 'undefined' && !SatayApp.escapeHtml) {
  SatayApp.escapeHtml = function(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };
}

const ClientApp = {
  activeTab: 'kds', // 'kds', 'pos', 'stock'
  activeFilter: 'all', // 'all', 'dine_in', 'takeaway', 'delivery'
  activeStation: 'all', // 'all', 'grill', 'drinks'
  activeOrders: [],
  knownOrderIds: new Set(),
  knownDrinkOrderIds: new Set(),
  isFirstLoad: true,
  pollInterval: null,
  soundEnabled: true,
  posCart: [],
  menuItems: [],
  tables: [],
  lastGrillSummary: null,
  lastDrinksSummary: null,

  isDrinkStaff() {
    if (typeof window.currentUser !== 'undefined' && window.currentUser) {
      return (
        window.currentUser.role === 'staff_drinks' ||
        String(window.currentUser.username || '').toLowerCase().includes('drink')
      );
    }
    return false;
  },

  init() {
    // If logged-in user is drink staff, default to Drink Station view
    if (this.isDrinkStaff()) {
      this.activeStation = 'drinks';
    }

    this.updateStationPillsUI();
    this.fetchKDSOrders();
    this.fetchMenuItems();
    this.fetchTables();
    this.bindEvents();

    // Auto-refresh KDS every 4 seconds for real-time kitchen & drink notifications
    this.pollInterval = setInterval(() => this.fetchKDSOrders(), 4000);

    // Prompt for notification permission on first user interaction anywhere on screen
    const unlockNotifications = () => {
      if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
      }
      document.removeEventListener('click', unlockNotifications);
    };
    document.addEventListener('click', unlockNotifications, { once: true });
  },

  bindEvents() {
    // Tab Switcher (KDS vs POS vs Stock)
    document.querySelectorAll('.client-tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        this.switchTab(tab);
      });
    });

    // Station Pills in KDS (All vs Grill vs Drinks)
    document.querySelectorAll('.kds-station-pill').forEach(pill => {
      pill.addEventListener('click', () => {
        document.querySelectorAll('.kds-station-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        this.activeStation = pill.dataset.station || 'all';
        this.renderGrillAggregator(this.lastGrillSummary, this.lastDrinksSummary);
        this.renderKDSBoard();
      });
    });

    // Dining Mode Filter Pills in KDS
    document.querySelectorAll('.kds-filter-pill').forEach(pill => {
      pill.addEventListener('click', () => {
        document.querySelectorAll('.kds-filter-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        this.activeFilter = pill.dataset.filter;
        this.renderKDSBoard();
      });
    });

    // Sound alert toggle
    const soundToggle = document.getElementById('sound-alert-toggle');
    if (soundToggle) {
      soundToggle.addEventListener('change', (e) => {
        this.soundEnabled = e.target.checked;
      });
    }

    // POS Dining Mode switcher (Takeaway vs Dine-In)
    const posDiningSelect = document.getElementById('pos-dining-type');
    if (posDiningSelect) {
      posDiningSelect.addEventListener('change', () => this.handlePOSDiningModeChange());
      // Initial state sync
      this.handlePOSDiningModeChange();
    }
  },

  handlePOSDiningModeChange() {
    const diningMode = document.getElementById('pos-dining-type')?.value || 'takeaway';
    const tableSelect = document.getElementById('pos-table-number');
    const customerInput = document.getElementById('pos-customer-name');

    const isTakeaway = (diningMode === 'takeaway');

    if (tableSelect) {
      tableSelect.disabled = isTakeaway;
      if (isTakeaway) {
        tableSelect.value = '';
        tableSelect.style.opacity = '0.55';
        tableSelect.style.cursor = 'not-allowed';
        tableSelect.style.backgroundColor = 'var(--bg-main)';
      } else {
        tableSelect.style.opacity = '1';
        tableSelect.style.cursor = 'default';
        tableSelect.style.backgroundColor = '';
        // If not selected, pick first available table
        if (!tableSelect.value && tableSelect.options.length > 1) {
          tableSelect.selectedIndex = 1;
        }
      }
    }

    if (customerInput) {
      customerInput.disabled = isTakeaway;
      if (isTakeaway) {
        customerInput.value = 'Takeaway Customer';
        customerInput.style.opacity = '0.55';
        customerInput.style.cursor = 'not-allowed';
        customerInput.style.backgroundColor = 'var(--bg-main)';
      } else {
        if (customerInput.value === 'Takeaway Customer') {
          customerInput.value = '';
        }
        customerInput.placeholder = 'e.g. Encik Farhan / Guest';
        customerInput.style.opacity = '1';
        customerInput.style.cursor = 'text';
        customerInput.style.backgroundColor = '';
      }
    }
  },

  updateStationPillsUI() {
    document.querySelectorAll('.kds-station-pill').forEach(pill => {
      pill.classList.toggle('active', pill.dataset.station === this.activeStation);
    });
  },

  switchTab(tab) {
    this.activeTab = tab;
    document.querySelectorAll('.client-tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    const sections = ['kds', 'pos', 'stock'];
    sections.forEach(s => {
      const el = document.getElementById(`view-${s}`);
      if (el) el.style.display = (s === tab) ? 'block' : 'none';
    });

    const titleMap = {
      'kds': this.isDrinkStaff() ? 'Drink Station Queue' : 'Kitchen Orders Display (KDS)',
      'pos': 'Walk-In Cashier POS',
      'stock': 'Live Menu Stock Manager'
    };
    const titleBadge = document.getElementById('topbar-kitchen-title');
    if (titleBadge && titleMap[tab]) {
      titleBadge.textContent = titleMap[tab];
    }
  },

  // Test Handphone Ringtone, Vibration & Push Notification
  async enableAndTestNotification() {
    if ('Notification' in window && Notification.permission !== 'granted') {
      try {
        await Notification.requestPermission();
      } catch (e) {}
    }

    // 1. Play ringing chime
    SatayApp.playChime('drink_order');

    // 2. Vibrate phone
    SatayApp.vibratePhone([400, 150, 400, 150, 600]);

    // 3. Trigger system push notification
    SatayApp.showPushNotification('🥤 Drink Alert Active!', {
      body: 'Your phone will ring and vibrate whenever a new drink order is received.',
      tag: 'satay-test-alert'
    });

    // 4. Show on-screen toast
    SatayApp.showToast('🔔 Phone Ringtone & Vibration Activated!', 'success', 4000);
  },

  async fetchTables() {
    try {
      const res = await fetch('api/tables.php');
      const data = await res.json();
      if (data.success) {
        this.tables = data.tables || [];
        this.populatePOSTables();
      }
    } catch (e) {
      console.error('Error loading tables in client:', e);
    }
  },

  populatePOSTables() {
    const select = document.getElementById('pos-table-number');
    if (!select) return;

    if (!this.tables || this.tables.length === 0) {
      select.innerHTML = `<option value="">-- No Tables (Takeaway/Counter) --</option>`;
      return;
    }

    let html = `<option value="">-- No Table (Takeaway/Counter) --</option>`;
    this.tables.forEach(tbl => {
      html += `<option value="${SatayApp.escapeHtml(tbl.table_number)}">${SatayApp.escapeHtml(tbl.table_number)} (${tbl.capacity} Pax Seats)</option>`;
    });
    select.innerHTML = html;
  },

  switchTab(tab) {
    this.activeTab = tab;
    document.querySelectorAll('.client-tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    document.getElementById('view-kds').style.display = (tab === 'kds') ? 'block' : 'none';
    document.getElementById('view-pos').style.display = (tab === 'pos') ? 'block' : 'none';
    document.getElementById('view-stock').style.display = (tab === 'stock') ? 'block' : 'none';

    if (tab === 'pos') {
      this.populatePOSTables();
      this.handlePOSDiningModeChange();
    } else if (tab === 'stock') {
      this.renderStockManager();
    }
  },

  async fetchKDSOrders() {
    try {
      const res = await fetch('api/orders.php?live=1');
      const data = await res.json();

      if (data.success) {
        const orders = data.orders || [];
        this.lastGrillSummary = data.grill_summary;
        this.lastDrinksSummary = data.drinks_summary;

        // Detect new incoming orders for real-time ring & handphone vibration
        let hasNewDrinkOrder = false;
        let newDrinkOrderDetails = [];
        let hasNewGeneralOrder = false;

        orders.forEach(ord => {
          const ordId = ord.id;
          
          // Check for new drink order
          if (ord.has_drinks) {
            if (!this.knownDrinkOrderIds.has(ordId)) {
              if (!this.isFirstLoad) {
                hasNewDrinkOrder = true;
                newDrinkOrderDetails.push(ord);
              }
              this.knownDrinkOrderIds.add(ordId);
            }
          }

          // Check for new general order
          if (!this.knownOrderIds.has(ordId)) {
            if (!this.isFirstLoad) {
              hasNewGeneralOrder = true;
            }
            this.knownOrderIds.add(ordId);
          }
        });

        // 🔔 TRIGGER DRINK SECTION NOTIFICATION (RINGTONE + PHONE VIBRATION + PUSH)
        if (hasNewDrinkOrder && newDrinkOrderDetails.length > 0) {
          const latestDrinkOrder = newDrinkOrderDetails[0];
          const tableInfo = latestDrinkOrder.table_number ? `Table ${latestDrinkOrder.table_number}` : latestDrinkOrder.dining_type.toUpperCase();
          const drinkText = latestDrinkOrder.drink_summary_text || 'New beverage order';

          // 1. Play Drink Melody Chime
          if (this.soundEnabled) {
            SatayApp.playChime('drink_order');
          }

          // 2. Vibrate Handphone (Mobile Web API)
          SatayApp.vibratePhone([500, 150, 500, 150, 800]);

          // 3. System Push / Handphone Notification
          SatayApp.showPushNotification(`🥤 NEW DRINK ORDER #${latestDrinkOrder.order_number}`, {
            body: `${tableInfo} • ${drinkText} (${latestDrinkOrder.customer_name || 'Guest'})`,
            vibrate: [500, 150, 500, 150, 800]
          });

          // 4. On-Screen High-Visibility Flash Toast
          SatayApp.showToast(`🥤 NEW DRINK ORDER #${latestDrinkOrder.order_number}: ${drinkText}`, 'info', 6000);
        } else if (hasNewGeneralOrder && (!this.isDrinkStaff() || this.activeStation !== 'drinks')) {
          // Standard kitchen grill chime
          if (this.soundEnabled) {
            SatayApp.playChime('new_order');
            SatayApp.vibratePhone([300, 100, 300]);
          }
          SatayApp.showToast('🔥 New order received on kitchen queue!', 'warning', 4000);
        }

        this.isFirstLoad = false;
        this.activeOrders = orders;

        this.renderGrillAggregator(data.grill_summary, data.drinks_summary);
        this.renderKDSBoard();

        const countBadge = document.getElementById('active-orders-count');
        if (countBadge) countBadge.innerText = data.active_orders_count;
      }
    } catch (err) {
      console.error('KDS poll error:', err);
    }
  },

  renderGrillAggregator(grillSummary, drinksSummary) {
    const bar = document.getElementById('grill-aggregator-content');
    if (!bar) return;

    const gSummary = grillSummary || { total_sticks: 0, tulang_madu: 0, ayam: 0, daging: 0, other: 0 };
    const dSummary = drinksSummary || { total_drinks: 0, breakdown: {} };

    if (this.activeStation === 'drinks') {
      // 🥤 Dedicated Drink Section Queue Bar
      const breakdownChips = Object.entries(dSummary.breakdown || {}).map(([name, qty]) => `
        <div class="meat-chip" style="background:rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.4); color:#93c5fd;">
          🥤 ${SatayApp.escapeHtml(name)}: <strong style="color:#60a5fa;">${qty}</strong>
        </div>
      `).join('');

      bar.innerHTML = `
        <div style="font-weight:800; font-size:1.05rem; display:flex; align-items:center; gap:6px; color:#60a5fa;">
          <span>🥤 Drink Bar Queue:</span> <strong>${dSummary.total_drinks || 0} Cups/Glasses</strong>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
          ${breakdownChips}
          ${dSummary.total_drinks === 0 ? `<span style="font-size:0.85rem; color:var(--text-muted);">Drink counter is clear. Ready for orders!</span>` : ''}
        </div>
      `;
    } else if (this.activeStation === 'grill') {
      // 🔥 Dedicated Grill Station Queue Bar
      bar.innerHTML = `
        <div style="font-weight:800; font-size:1.05rem; display:flex; align-items:center; gap:6px; color:var(--primary);">
          <span>🔥 Grill Queue:</span> <strong>${gSummary.total_sticks || 0} Sticks</strong>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
          ${gSummary.tulang_madu > 0 ? `<div class="meat-chip">🍯 Tulang Madu: <strong style="color:var(--gold);">${gSummary.tulang_madu}</strong></div>` : ''}
          ${gSummary.ayam > 0 ? `<div class="meat-chip">🍗 Ayam: <strong style="color:var(--gold);">${gSummary.ayam}</strong></div>` : ''}
          ${gSummary.daging > 0 ? `<div class="meat-chip">🥩 Daging: <strong style="color:var(--gold);">${gSummary.daging}</strong></div>` : ''}
          ${gSummary.other > 0 ? `<div class="meat-chip">Other: <strong style="color:var(--primary);">${gSummary.other}</strong></div>` : ''}
          ${gSummary.total_sticks === 0 ? `<span style="font-size:0.85rem; color:var(--text-muted);">Grill is clear. Ready for orders!</span>` : ''}
        </div>
      `;
    } else {
      // 🍢 Combined All Stations Queue Bar
      bar.innerHTML = `
        <div style="display:flex; gap:1.2rem; flex-wrap:wrap; align-items:center;">
          <div style="font-weight:800; font-size:0.98rem; display:flex; align-items:center; gap:6px; color:var(--primary);">
            <span>🔥 Grill:</span> <strong>${gSummary.total_sticks || 0} Sticks</strong>
          </div>
          <div style="font-weight:800; font-size:0.98rem; display:flex; align-items:center; gap:6px; color:#60a5fa;">
            <span>🥤 Drinks:</span> <strong>${dSummary.total_drinks || 0} Drinks</strong>
          </div>
          <div style="display:flex; gap:0.4rem; flex-wrap:wrap; align-items:center;">
            ${gSummary.tulang_madu > 0 ? `<div class="meat-chip" style="font-size:0.75rem;">🍯 ${gSummary.tulang_madu}</div>` : ''}
            ${gSummary.ayam > 0 ? `<div class="meat-chip" style="font-size:0.75rem;">🍗 ${gSummary.ayam}</div>` : ''}
            ${gSummary.daging > 0 ? `<div class="meat-chip" style="font-size:0.75rem;">🥩 ${gSummary.daging}</div>` : ''}
            ${dSummary.total_drinks > 0 ? `<div class="meat-chip" style="font-size:0.75rem; background:rgba(59,130,246,0.15); border-color:#60a5fa; color:#93c5fd;">🥤 ${dSummary.total_drinks} Cups</div>` : ''}
          </div>
        </div>
      `;
    }
  },

  renderKDSBoard() {
    const grid = document.getElementById('kds-grid');
    if (!grid) return;

    let list = this.activeOrders;

    // Filter by station (Grill vs Drinks vs All)
    if (this.activeStation === 'drinks') {
      list = list.filter(o => o.has_drinks === true);
    } else if (this.activeStation === 'grill') {
      list = list.filter(o => o.has_grill === true);
    }

    // Filter by dining type
    if (this.activeFilter !== 'all') {
      list = list.filter(o => o.dining_type === this.activeFilter);
    }

    if (list.length === 0) {
      grid.innerHTML = `
        <div style="grid-column:1/-1; text-align:center; padding:4rem 1rem; color:var(--text-muted);">
          <div style="font-size:3.5rem; margin-bottom:0.75rem;">${this.activeStation === 'drinks' ? '🥤' : '🍢'}</div>
          <h3>No active orders ${this.activeStation === 'drinks' ? 'for Drink Station' : 'in kitchen queue'}</h3>
          <p>${this.activeStation === 'drinks' ? 'Incoming drink orders will appear here and trigger handphone ring & vibration.' : 'All incoming orders will appear here with live queue counters and timers.'}</p>
        </div>
      `;
      return;
    }

    grid.innerHTML = list.map(order => {
      // Calculate real-time elapsed minutes and display string
      const elapsedMins = SatayApp.getElapsedMinutes(order.created_at);
      const timeAgoStr = SatayApp.formatTimeAgo(order.created_at);
      const clockTimeStr = SatayApp.formatClockTime(order.created_at);
      const isLate = elapsedMins >= 20;

      const itemsHtml = (order.items || []).map(it => {
        const isDrink = Boolean(it.is_drink);

        return `
          <div class="ticket-item-row" style="${isDrink ? 'background:rgba(59,130,246,0.08); border-left:3px solid var(--info); padding:4px 8px; border-radius:4px; margin-bottom:4px;' : ''}">
            <div style="display:flex; align-items:baseline; gap:8px;">
              <span class="ticket-item-qty" style="${isDrink ? 'color:var(--info); font-weight:800;' : ''}">${it.quantity}x</span>
              <div>
                <strong style="${isDrink ? 'color:#bfdbfe;' : ''}">${isDrink ? '🥤 ' : ''}${SatayApp.escapeHtml(it.item_name)}</strong>
                ${it.spicy_level && it.spicy_level !== 'normal' ? `<span style="font-size:0.75rem; color:var(--primary); font-weight:700;">[${SatayApp.escapeHtml(it.spicy_level)}]</span>` : ''}
                ${it.special_notes ? `<div style="font-size:0.75rem; color:var(--gold);">* ${SatayApp.escapeHtml(it.special_notes)}</div>` : ''}
              </div>
            </div>
          </div>
        `;
      }).join('');

      return `
        <div class="ticket-card status-${order.order_status}">
          <div class="ticket-header">
            <div>
              <div class="ticket-number">#${SatayApp.escapeHtml(order.order_number)}</div>
              <div style="font-size:0.8rem; font-weight:600; color:var(--text-muted); margin-top:2px;">
                ${SatayApp.escapeHtml(order.customer_name || 'Walk-in')} ${order.table_number ? `• Table ${SatayApp.escapeHtml(order.table_number)}` : ''}
              </div>
            </div>
            <div style="text-align:right;">
              <span class="badge ${this.getStatusBadgeClass(order.order_status)}">${SatayApp.escapeHtml(order.order_status)}</span>
              <div class="ticket-time" style="color: ${isLate ? 'var(--danger)' : 'var(--text-muted)'}; font-weight:${isLate ? '800' : '500'};">
                ${clockTimeStr ? `🕒 ${clockTimeStr} (${timeAgoStr})` : timeAgoStr}
              </div>
            </div>
          </div>

          <div class="ticket-body">
            <div style="margin-bottom:0.6rem; display:flex; justify-content:space-between; font-size:0.8rem; color:var(--text-muted);">
              <span>Mode: <strong style="color:#fff; text-transform:capitalize;">${SatayApp.escapeHtml(order.dining_type.replace('_', ' '))}</strong></span>
              <span>Pay: <strong style="color:${order.payment_status === 'paid' ? 'var(--success)' : 'var(--gold)'};">${SatayApp.escapeHtml(order.payment_status)}</strong></span>
            </div>

            ${order.notes ? `
              <div style="background:rgba(255,94,20,0.1); border-left:3px solid var(--primary); padding:6px 10px; border-radius:4px; font-size:0.8rem; color:#ffedd5; margin-bottom:0.75rem;">
                <strong>Note:</strong> ${SatayApp.escapeHtml(order.notes)}
              </div>
            ` : ''}

            <div style="margin-top:0.5rem;">
              ${itemsHtml}
            </div>
          </div>

          <div class="ticket-footer">
            ${this.renderTicketActionButtons(order)}
          </div>
        </div>
      `;
    }).join('');
  },

  getStatusBadgeClass(status) {
    switch (status) {
      case 'pending': return 'badge-info';
      case 'confirmed': return 'badge-gold';
      case 'grilling': return 'badge-primary';
      case 'ready': return 'badge-success';
      default: return 'badge-neutral';
    }
  },

  renderTicketActionButtons(order) {
    if (order.order_status === 'pending') {
      return `
        <button class="btn btn-secondary btn-sm" style="flex:1;" onclick="ClientApp.updateStatus(${order.id}, 'cancelled')">Reject</button>
        <button class="btn btn-primary btn-sm" style="flex:2;" onclick="ClientApp.updateStatus(${order.id}, 'confirmed')">Accept Order</button>
      `;
    } else if (order.order_status === 'confirmed' || order.order_status === 'grilling') {
      if (this.activeStation === 'drinks') {
        return `
          <button class="btn btn-primary btn-sm" style="width:100%; background:var(--info); border-color:var(--info);" onclick="ClientApp.updateStatus(${order.id}, 'ready')">
            🥤 Mark Drinks Ready
          </button>
        `;
      }
      return `
        <button class="btn btn-success btn-sm" style="width:100%;" onclick="ClientApp.updateStatus(${order.id}, 'ready')">
          🛎️ Mark Ready for Customer
        </button>
      `;
    } else if (order.order_status === 'ready') {
      return `
        <button class="btn btn-secondary btn-sm" style="flex:1;" onclick="SatayApp.printThermalReceipt(${JSON.stringify(order).replace(/"/g, '&quot;')})">🖨️ Receipt</button>
        <button class="btn btn-success btn-sm" style="flex:2;" onclick="ClientApp.updateStatus(${order.id}, 'completed')">
          Completed
        </button>
      `;
    }
    return '';
  },

  async updateStatus(orderId, nextStatus) {
    try {
      const res = await fetch('api/orders.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: orderId,
          order_status: nextStatus,
          payment_status: (nextStatus === 'completed') ? 'paid' : undefined
        })
      });

      const data = await res.json();
      if (data.success) {
        if (nextStatus === 'ready') {
          SatayApp.playChime('ready');
        }
        SatayApp.showToast(`Order status updated to ${nextStatus.toUpperCase()}`, 'success');
        this.fetchKDSOrders();
      } else {
        SatayApp.showToast(data.message || 'Update failed', 'danger');
      }
    } catch (err) {
      console.error(err);
      SatayApp.showToast('Network error updating status', 'danger');
    }
  },

  // POS Mode Functions
  async fetchMenuItems() {
    try {
      const res = await fetch('api/menu.php?all=1');
      const data = await res.json();
      if (data.success) {
        this.menuItems = data.items || [];
        if (data.tax_rate_percent !== undefined) {
          this.taxRatePercent = parseFloat(data.tax_rate_percent);
        }
        this.renderPOSGrid();
        this.renderPOSCart();
      }
    } catch (e) {
      console.error(e);
    }
  },

  renderPOSGrid() {
    const grid = document.getElementById('pos-menu-grid');
    if (!grid) return;

    grid.innerHTML = this.menuItems.map(item => {
      const cartItem = this.posCart.find(i => i.menu_item_id == item.id);
      const qty = cartItem ? cartItem.quantity : 0;
      const inCart = qty > 0;

      return `
        <div class="pos-dish-card ${inCart ? 'in-cart' : ''}" id="pos-card-${item.id}">
          <div>
            <div class="pos-dish-title">
              <span>${SatayApp.escapeHtml(item.name)}</span>
              <span class="pos-dish-qty-badge" id="pos-qty-${item.id}" style="${inCart ? '' : 'display:none;'}">
                <span class="pos-qty-prefix">Total: </span><span class="pos-qty-val">${qty}</span>
              </span>
            </div>
            <div class="pos-dish-price">
              ${SatayApp.formatPrice(item.price_per_unit)} <span>/ ${SatayApp.escapeHtml(item.unit_name)}</span>
            </div>
          </div>

          <div class="pos-qty-actions">
            <button type="button" class="btn btn-secondary btn-sm pos-qty-btn" onclick="ClientApp.addToPOSCart(${item.id}, 1)">+1</button>
            <button type="button" class="btn btn-secondary btn-sm pos-qty-btn" onclick="ClientApp.addToPOSCart(${item.id}, 5)">+5</button>
            <button type="button" class="btn btn-secondary btn-sm pos-qty-btn" onclick="ClientApp.addToPOSCart(${item.id}, 10)">+10</button>
          </div>
        </div>
      `;
    }).join('');
  },

  updatePOSGridBadges() {
    if (!this.menuItems || !this.menuItems.length) return;
    this.menuItems.forEach(item => {
      const badge = document.getElementById(`pos-qty-${item.id}`);
      const card = document.getElementById(`pos-card-${item.id}`);
      if (!badge) return;

      const cartItem = this.posCart.find(i => i.menu_item_id == item.id);
      const qty = cartItem ? cartItem.quantity : 0;

      if (qty > 0) {
        const valEl = badge.querySelector('.pos-qty-val');
        if (valEl) valEl.textContent = qty;
        badge.style.display = 'inline-flex';
        badge.classList.remove('pos-badge-pulse');
        void badge.offsetWidth; // Trigger reflow for animation
        badge.classList.add('pos-badge-pulse');
        if (card) card.classList.add('in-cart');
      } else {
        badge.style.display = 'none';
        if (card) card.classList.remove('in-cart');
      }
    });
  },

  addToPOSCart(itemId, qty = 1) {
    const item = this.menuItems.find(i => i.id == itemId);
    if (!item) return;

    const existing = this.posCart.find(i => i.menu_item_id == itemId);
    if (existing) {
      existing.quantity += qty;
    } else {
      this.posCart.push({
        menu_item_id: item.id,
        name: item.name,
        unit_price: parseFloat(item.price_per_unit),
        quantity: qty,
        spicy_level: 'normal'
      });
    }

    this.renderPOSCart();
  },

  adjustPOSCartQty(idx, delta) {
    this.posCart[idx].quantity += delta;
    if (this.posCart[idx].quantity <= 0) {
      this.posCart.splice(idx, 1);
    }
    this.renderPOSCart();
  },

  clearPOSCart() {
    this.posCart = [];
    this.renderPOSCart();
  },

  renderPOSCart() {
    const list = document.getElementById('pos-cart-items');
    if (!list) return;

    let subtotal = 0;
    list.innerHTML = this.posCart.map((it, idx) => {
      const line = it.unit_price * it.quantity;
      subtotal += line;
      return `
        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0; border-bottom:1px solid var(--border-color);">
          <div style="font-size:0.9rem;">
            <strong>${SatayApp.escapeHtml(it.name)}</strong>
            <div style="color:var(--text-muted); font-size:0.75rem;">${it.quantity} x ${SatayApp.formatPrice(it.unit_price)}</div>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-weight:700; color:var(--gold);">${SatayApp.formatPrice(line)}</span>
            <div class="qty-stepper">
              <button type="button" class="qty-btn" onclick="ClientApp.adjustPOSCartQty(${idx}, -1)">-</button>
              <button type="button" class="qty-btn" onclick="ClientApp.adjustPOSCartQty(${idx}, 1)">+</button>
            </div>
          </div>
        </div>
      `;
    }).join('');

    const taxPercent = (this.taxRatePercent !== undefined && !isNaN(this.taxRatePercent)) ? this.taxRatePercent : 6.0;
    const tax = subtotal * (taxPercent / 100);
    const total = subtotal + tax;

    const subtotalEl = document.getElementById('pos-subtotal');
    const taxEl = document.getElementById('pos-tax');
    const taxLabelEl = document.getElementById('pos-tax-label');
    const totalEl = document.getElementById('pos-total');

    if (subtotalEl) subtotalEl.innerText = SatayApp.formatPrice(subtotal);
    if (taxLabelEl) taxLabelEl.innerText = `SST (${taxPercent}%):`;
    if (taxEl) taxEl.innerText = SatayApp.formatPrice(tax);
    if (totalEl) totalEl.innerText = SatayApp.formatPrice(total);

    this.calculatePOSChange();
    this.updatePOSGridBadges();
  },

  calculatePOSChange() {
    const totalStr = document.getElementById('pos-total')?.innerText.replace('RM', '').trim() || '0';
    const total = parseFloat(totalStr);
    const received = parseFloat(document.getElementById('pos-cash-received')?.value || 0);

    const changeEl = document.getElementById('pos-change-amount');
    if (changeEl) {
      const change = Math.max(0, received - total);
      changeEl.innerText = SatayApp.formatPrice(change);
      changeEl.style.color = (received >= total && total > 0) ? 'var(--success)' : 'var(--text-muted)';
    }
  },

  async submitPOSOrder() {
    if (this.posCart.length === 0) {
      SatayApp.showToast('POS Cart is empty!', 'warning');
      return;
    }

    const dining = document.getElementById('pos-dining-type')?.value || 'takeaway';
    const isTakeaway = (dining === 'takeaway');
    const table = isTakeaway ? '' : (document.getElementById('pos-table-number')?.value.trim() || 'Counter');
    const customer = isTakeaway ? 'Takeaway Customer' : (document.getElementById('pos-customer-name')?.value.trim() || 'Walk-in Guest');
    const payment = document.getElementById('pos-payment-method')?.value || 'cash';

    const payload = {
      customer_name: customer,
      customer_phone: '',
      dining_type: dining,
      table_number: table,
      delivery_address: '',
      notes: 'POS Counter Order',
      payment_method: payment,
      payment_status: 'paid', // Walk-in is paid immediately
      items: this.posCart.map(it => ({
        menu_item_id: it.menu_item_id,
        item_name: it.name,
        unit_price: it.unit_price,
        quantity: it.quantity,
        spicy_level: 'normal'
      }))
    };

    try {
      const res = await fetch('api/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data.success) {
        SatayApp.showToast(`Order #${data.order_number} created successfully!`, 'success');
        SatayApp.playChime('new_order');

        // Print receipt option
        const printPayload = {
          order_number: data.order_number,
          created_at: new Date().toLocaleString(),
          dining_type: payload.dining_type,
          table_number: payload.table_number,
          customer_name: payload.customer_name,
          customer_phone: payload.customer_phone,
          payment_method: payload.payment_method,
          payment_status: 'paid',
          items: payload.items.map(it => ({
            item_name: it.item_name || 'Satay Dish',
            quantity: it.quantity,
            unit_price: it.unit_price,
            total_price: it.total_price || (it.unit_price * it.quantity),
            spicy_level: it.spicy_level,
            special_notes: it.special_notes
          })),
          subtotal: payload.subtotal || data.total_amount,
          tax_amount: 0,
          service_fee: 0,
          total_amount: data.total_amount
        };
        SatayApp.printThermalReceipt(printPayload);

        this.clearPOSCart();
        this.fetchKDSOrders();
      } else {
        SatayApp.showToast(data.message || 'Order failed', 'danger');
      }
    } catch (e) {
      console.error(e);
      SatayApp.showToast('Error submitting POS order', 'danger');
    }
  },

  // Stock & Quantity Left Manager
  async renderStockManager() {
    const container = document.getElementById('stock-manager-table-body');
    if (!container) return;

    await this.fetchMenuItems();

    container.innerHTML = this.menuItems.map(item => {
      const stock = parseInt(item.stock_quantity !== null && item.stock_quantity !== undefined ? item.stock_quantity : 100);
      const isAvailable = (item.is_available == 1 && stock > 0);

      return `
        <tr>
          <td>
            <div style="display:flex; align-items:center; gap:10px;">
              <img src="${item.image_url || 'assets/images/sate_ayam.png'}" style="width:40px; height:40px; border-radius:var(--radius-sm); object-fit:cover; border:1px solid var(--border-color);" onerror="this.src='assets/images/sate_ayam.png'">
              <div>
                <strong>${SatayApp.escapeHtml(item.name)}</strong>
                <div style="font-size:0.75rem; color:var(--text-muted);">${item.unit_name ? `per ${SatayApp.escapeHtml(item.unit_name)}` : ''} ${item.min_quantity > 1 ? `(Min ${item.min_quantity})` : ''}</div>
              </div>
            </div>
          </td>
          <td><span class="badge badge-neutral">${SatayApp.escapeHtml(item.category_name || '')}</span></td>
          <td style="font-weight:700; color:var(--gold);">${SatayApp.formatPrice(item.price_per_unit)}</td>
          <td>
            <div style="display:flex; align-items:center; gap:6px;">
              <input type="number" class="form-input" min="0" value="${stock}" id="kitchen-stock-${item.id}" style="width:85px; font-weight:800; font-size:1rem; text-align:center; padding:0.35rem 0.5rem; ${stock <= 10 ? 'border-color:var(--danger); color:var(--danger);' : ''}" onchange="ClientApp.setStockQuantity(${item.id}, this.value)">
              <button class="btn btn-sm btn-primary" onclick="ClientApp.setStockQuantity(${item.id}, document.getElementById('kitchen-stock-${item.id}').value)" title="Save Quantity">
                Save
              </button>
            </div>
          </td>
          <td>
            <button class="btn btn-sm ${isAvailable ? 'btn-danger' : 'btn-success'}" style="width:120px;" onclick="ClientApp.toggleStock(${item.id}, ${isAvailable ? 0 : 1})">
              ${isAvailable ? '🔴 Mark Sold Out' : '🟢 Set Available'}
            </button>
          </td>
        </tr>
      `;
    }).join('');
  },

  async setStockQuantity(itemId, qty) {
    const quantity = Math.max(0, parseInt(qty) || 0);
    try {
      const res = await fetch('api/menu.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: itemId, stock_quantity: quantity })
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast(`Stock updated to ${quantity} left`, 'success');
        await this.fetchMenuItems();
        this.renderStockManager();
      } else {
        SatayApp.showToast(data.message || 'Update failed', 'danger');
      }
    } catch (e) {
      console.error(e);
      SatayApp.showToast('Network error updating stock', 'danger');
    }
  },

  async adjustStockDelta(itemId, delta) {
    try {
      const res = await fetch('api/menu.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: itemId, stock_delta: delta })
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast(`Stock adjusted (${delta > 0 ? '+' : ''}${delta}) → ${data.stock_quantity} left`, 'success');
        await this.fetchMenuItems();
        this.renderStockManager();
      } else {
        SatayApp.showToast(data.message || 'Adjust failed', 'danger');
      }
    } catch (e) {
      console.error(e);
    }
  },

  async toggleStock(itemId, nextState) {
    try {
      const res = await fetch('api/menu.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          id: itemId, 
          is_available: nextState,
          stock_quantity: nextState === 1 ? 50 : 0
        })
      });
      const data = await res.json();
      if (data.success) {
        SatayApp.showToast(nextState === 1 ? 'Item set to Available (50 stock)' : 'Item marked as Sold Out', 'success');
        await this.fetchMenuItems();
        this.renderStockManager();
      } else {
        SatayApp.showToast(data.message || 'Toggle failed', 'danger');
      }
    } catch (e) {
      console.error(e);
    }
  }
};

document.addEventListener('DOMContentLoaded', () => {
  ClientApp.init();
});
