// Global modal functions accessible from inline HTML onclicks
window.closeModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
};

window.switchShipmentModalTab = (tabName) => {
    const detailsForm = document.getElementById('shipmentForm');
    const eventsForm = document.getElementById('addEventForm');
    const btnDetails = document.getElementById('btnTabShipmentDetails');
    const btnEvents = document.getElementById('btnTabShipmentEvents');

    if (tabName === 'details') {
        detailsForm.style.display = 'block';
        eventsForm.style.display = 'none';
        btnDetails.classList.add('active');
        btnEvents.classList.remove('active');
    } else {
        detailsForm.style.display = 'none';
        eventsForm.style.display = 'block';
        btnEvents.classList.add('active');
        btnDetails.classList.remove('active');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    // 1. Authentication & Role Check
    const checkAdminAuth = async () => {
        const token = localStorage.getItem("auth_token");
        if (!token) {
            window.location.href = "signin.html";
            return;
        }

        try {
            const response = await fetch(`${CONFIG.API_URL}/user`, {
                method: "GET",
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });

            if (!response.ok) {
                throw new Error("Not authenticated");
            }

            const user = await response.json();
            
            if (user.role !== 'admin') {
                window.location.href = "dashboard.html";
                return;
            }

            document.getElementById('adminName').textContent = user.name;
            document.getElementById('adminAvatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random`;

            loadDashboardData();

        } catch (error) {
            console.error("Auth error:", error);
            localStorage.removeItem("loggedIn");
            localStorage.removeItem("userEmail");
            localStorage.removeItem("auth_token");
            window.location.href = "signin.html";
        }
    };

    // 2. Dashboard Overview Data
    const loadDashboardData = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/overview`, {
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });

            if (response.ok) {
                const data = await response.json();
                
                if(data.users_count !== undefined) document.getElementById('metric-users').textContent = data.users_count;
                if(data.procurements_count !== undefined) document.getElementById('metric-procurements').textContent = data.procurements_count;
                if(data.shipments_count !== undefined) document.getElementById('metric-shipments').textContent = data.shipments_count;
                
                const tbody = document.getElementById('activity-tbody');
                if (data.activity && data.activity.length > 0) {
                    tbody.innerHTML = data.activity.map(item => `
                        <tr>
                            <td>${item.id}</td>
                            <td>${item.type}</td>
                            <td>${item.user}</td>
                            <td><span class="status-badge ${item.status_class}">${item.status}</span></td>
                            <td>${item.date}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center;">No recent activity found.</td></tr>`;
                }
            }
        } catch (error) {
            console.error("Error fetching admin data:", error);
        }
    };

    // 3. Procurements Management
    let allProcurements = [];
    const loadProcurements = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/procurements`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            const tbody = document.getElementById('admin-procurements-tbody');
            if (response.ok) {
                allProcurements = await response.json();
                if (allProcurements.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center;">No procurements found.</td></tr>`;
                    return;
                }
                tbody.innerHTML = allProcurements.map(p => `
                    <tr>
                        <td><strong>${p.procurement_id || 'PROC-'+p.id}</strong></td>
                        <td>${p.user ? p.user.name : p.name}<br><small style="color: #6b7280;">${p.email}</small></td>
                        <td>${p.details ? (p.details.length > 40 ? p.details.substring(0, 40)+'...' : p.details) : 'N/A'}</td>
                        <td><span class="status-badge ${p.status}">${p.status ? p.status.toUpperCase() : 'PENDING'}</span></td>
                        <td>${new Date(p.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="btn-primary-outline" onclick="openProcurementModal('${p.procurement_id || p.id}')">
                                <i class="fas fa-edit"></i> Manage
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.openProcurementModal = async (id) => {
        const token = localStorage.getItem("auth_token");
        try {
            const res = await fetch(`${CONFIG.API_URL}/admin/procurements/${id}`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            if (res.ok) {
                const p = await res.json();
                document.getElementById('editProcurementId').value = p.id;
                document.getElementById('procurementModalTitle').textContent = `Manage Procurement (${p.procurement_id || 'PROC-'+p.id})`;
                document.getElementById('editProcStatus').value = p.status || 'pending';
                document.getElementById('editProcSupplier').value = p.supplier || '';
                document.getElementById('editProcCategory').value = p.category || '';
                document.getElementById('editProcQuantity').value = p.quantity || '';
                document.getElementById('editProcCost').value = p.cost || '';
                document.getElementById('editProcLocation').value = p.location || '';
                document.getElementById('editProcRecipientLocation').value = p.recipient_location || '';
                document.getElementById('editProcExpectedDate').value = p.expected_date || '';
                document.getElementById('editProcDeliveredDate').value = p.delivered_date || '';
                document.getElementById('editProcDetails').value = p.details || '';

                document.getElementById('procurementModal').style.display = 'flex';
            }
        } catch (err) {
            console.error(err);
        }
    };

    const procurementForm = document.getElementById('procurementForm');
    if (procurementForm) {
        procurementForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = procurementForm.querySelector("button[type='submit']") || procurementForm.querySelector("button");
            const token = localStorage.getItem("auth_token");
            const id = document.getElementById('editProcurementId').value;

            const payload = {
                status: document.getElementById('editProcStatus').value,
                supplier: document.getElementById('editProcSupplier').value,
                category: document.getElementById('editProcCategory').value,
                quantity: document.getElementById('editProcQuantity').value,
                cost: document.getElementById('editProcCost').value,
                location: document.getElementById('editProcLocation').value,
                recipient_location: document.getElementById('editProcRecipientLocation').value,
                expected_date: document.getElementById('editProcExpectedDate').value || null,
                delivered_date: document.getElementById('editProcDeliveredDate').value || null,
            };

            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, true, 'Saving...');
            }

            try {
                const res = await fetch(`${CONFIG.API_URL}/admin/procurements/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    showToast('Procurement details updated successfully!', 'success');
                    window.closeModal('procurementModal');
                    loadProcurements();
                } else {
                    showToast('Failed to update procurement', 'error');
                }
            } catch (error) {
                console.error(error);
            } finally {
                if (typeof setButtonLoading === 'function' && submitBtn) {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    }

    // 4. Shipments Management
    let currentShipment = null;
    const loadShipments = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/shipments`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            const tbody = document.getElementById('admin-shipments-tbody');
            if (response.ok) {
                const shipments = await response.json();
                if (shipments.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center;">No shipments found.</td></tr>`;
                    return;
                }
                tbody.innerHTML = shipments.map(s => `
                    <tr>
                        <td><strong>${s.tracking_id}</strong></td>
                        <td>${s.user ? s.user.name : 'Customer #'+s.user_id}</td>
                        <td>${s.origin} &rarr; ${s.destination}</td>
                        <td><span class="status-badge ${s.status}">${s.status.toUpperCase()}</span></td>
                        <td>
                            <button class="btn-primary-outline" onclick="openShipmentModal('${s.tracking_id}')">
                                <i class="fas fa-edit"></i> Edit / Track Event
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.openShipmentModal = async (id) => {
        const token = localStorage.getItem("auth_token");
        try {
            const res = await fetch(`${CONFIG.API_URL}/admin/shipments/${id}`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            if (res.ok) {
                currentShipment = await res.json();
                document.getElementById('editShipmentId').value = currentShipment.id;
                document.getElementById('eventShipmentId').value = currentShipment.id;
                document.getElementById('shipmentModalTitle').textContent = `Manage Shipment (${currentShipment.tracking_id})`;
                document.getElementById('editShipTrackingId').value = currentShipment.tracking_id;
                document.getElementById('editShipStatus').value = currentShipment.status || 'pending';
                document.getElementById('editShipCurrentLocation').value = currentShipment.current_location || '';
                document.getElementById('editShipService').value = currentShipment.service || '';
                document.getElementById('editShipWeight').value = currentShipment.weight || '';
                document.getElementById('editShipPackages').value = currentShipment.packages || '';
                document.getElementById('editShipCost').value = currentShipment.shipping_cost || '';
                document.getElementById('editShipRecipient').value = currentShipment.recipient || '';
                document.getElementById('editShipOrigin').value = currentShipment.origin || '';
                document.getElementById('editShipDestination').value = currentShipment.destination || '';
                document.getElementById('editShipExpectedDate').value = currentShipment.expected_delivery_date || '';
                document.getElementById('editShipDeliveredDate').value = currentShipment.delivered_date || '';

                // Populate events
                renderShipmentEventsTimeline(currentShipment.events || []);

                window.switchShipmentModalTab('details');
                document.getElementById('shipmentModal').style.display = 'flex';
            }
        } catch (err) {
            console.error(err);
        }
    };

    const renderShipmentEventsTimeline = (events) => {
        const timeline = document.getElementById('shipmentEventsTimeline');
        if (!events || events.length === 0) {
            timeline.innerHTML = `<p style="color: #6b7280;">No tracking events recorded yet.</p>`;
            return;
        }
        timeline.innerHTML = events.map(ev => `
            <div style="border-left: 2px solid var(--primary-color); padding-left: 10px; margin-bottom: 10px;">
                <strong>${ev.location || 'Location Unspecified'}</strong> &mdash; <small>${new Date(ev.created_at).toLocaleString()}</small>
                <p style="margin-top: 2px; color: #4b5563;">${ev.description}</p>
            </div>
        `).join('');
    };

    const shipmentForm = document.getElementById('shipmentForm');
    if (shipmentForm) {
        shipmentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = shipmentForm.querySelector("button[type='submit']") || shipmentForm.querySelector("button");
            const token = localStorage.getItem("auth_token");
            const id = document.getElementById('editShipmentId').value;

            const payload = {
                status: document.getElementById('editShipStatus').value,
                current_location: document.getElementById('editShipCurrentLocation').value,
                service: document.getElementById('editShipService').value,
                weight: document.getElementById('editShipWeight').value,
                packages: document.getElementById('editShipPackages').value,
                shipping_cost: document.getElementById('editShipCost').value,
                recipient: document.getElementById('editShipRecipient').value,
                origin: document.getElementById('editShipOrigin').value,
                destination: document.getElementById('editShipDestination').value,
                expected_delivery_date: document.getElementById('editShipExpectedDate').value || null,
                delivered_date: document.getElementById('editShipDeliveredDate').value || null,
            };

            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, true, 'Updating...');
            }

            try {
                const res = await fetch(`${CONFIG.API_URL}/admin/shipments/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    showToast('Shipment details updated successfully!', 'success');
                    window.closeModal('shipmentModal');
                    loadShipments();
                } else {
                    showToast('Failed to update shipment', 'error');
                }
            } catch (err) {
                console.error(err);
            } finally {
                if (typeof setButtonLoading === 'function' && submitBtn) {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    }

    const addEventForm = document.getElementById('addEventForm');
    if (addEventForm) {
        addEventForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = addEventForm.querySelector("button[type='submit']") || addEventForm.querySelector("button");
            const token = localStorage.getItem("auth_token");
            const id = document.getElementById('eventShipmentId').value;

            const payload = {
                location: document.getElementById('eventLocation').value,
                description: document.getElementById('eventDescription').value,
            };

            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, true, 'Adding Event...');
            }

            try {
                const res = await fetch(`${CONFIG.API_URL}/admin/shipments/${id}/events`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    showToast('Tracking event added!', 'success');
                    addEventForm.reset();
                    openShipmentModal(id);
                } else {
                    showToast('Failed to add tracking event', 'error');
                }
            } catch (err) {
                console.error(err);
            } finally {
                if (typeof setButtonLoading === 'function' && submitBtn) {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    }

    // 5. Quotes Management
    let allQuotes = [];
    const loadQuotes = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/quotes`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            const tbody = document.getElementById('admin-quotes-tbody');
            if (response.ok) {
                allQuotes = await response.json();
                if (allQuotes.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center;">No quote requests found.</td></tr>`;
                    return;
                }
                tbody.innerHTML = allQuotes.map(q => `
                    <tr>
                        <td><strong>Q-${q.id}</strong></td>
                        <td>${q.name}<br><small style="color: #6b7280;">${q.email}</small></td>
                        <td>${q.origin_country} &rarr; ${q.destination_country}</td>
                        <td>${q.shipping_type || 'Standard'}</td>
                        <td>${q.weight || 0} kg</td>
                        <td><strong>${q.calculated_cost ? '₦'+parseFloat(q.calculated_cost).toLocaleString('en-NG', { minimumFractionDigits: 2 }) : 'Pending Rate'}</strong></td>
                        <td><span class="status-badge ${q.status}">${q.status ? q.status.toUpperCase() : 'PENDING'}</span></td>
                        <td>
                            <button class="btn-primary-outline" onclick="openQuoteModal(${q.id})">
                                <i class="fas fa-calculator"></i> Set Rate
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.openQuoteModal = (id) => {
        const q = allQuotes.find(item => item.id == id);
        if (!q) return;

        document.getElementById('quoteId').value = q.id;
        document.getElementById('quoteCustomer').textContent = `${q.name} (${q.email}, ${q.phone})`;
        document.getElementById('quoteType').textContent = q.shipping_type || 'Standard';
        document.getElementById('quoteRoute').textContent = `${q.origin_country} -> ${q.destination_country}`;
        document.getElementById('quoteSpecs').textContent = `${q.weight}kg (${q.length||0}x${q.width||0}x${q.height||0}cm)`;
        document.getElementById('quoteDetails').textContent = q.shipping_details || 'N/A';
        document.getElementById('quoteCost').value = q.calculated_cost || '';
        document.getElementById('quoteStatus').value = q.status || 'pending';

        document.getElementById('quoteModal').style.display = 'flex';
    };

    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        quoteForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = quoteForm.querySelector("button[type='submit']") || quoteForm.querySelector("button");
            const token = localStorage.getItem("auth_token");
            const id = document.getElementById('quoteId').value;

            const payload = {
                calculated_cost: document.getElementById('quoteCost').value,
                status: document.getElementById('quoteStatus').value,
            };

            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, true, 'Saving Rate...');
            }

            try {
                const res = await fetch(`${CONFIG.API_URL}/admin/quotes/${id}/cost`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    showToast('Quote rate updated successfully!', 'success');
                    window.closeModal('quoteModal');
                    loadQuotes();
                } else {
                    showToast('Failed to update quote rate', 'error');
                }
            } catch (err) {
                console.error(err);
            } finally {
                if (typeof setButtonLoading === 'function' && submitBtn) {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    }

    // 6. Contact Messages
    const loadMessages = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/contact-messages`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            const tbody = document.getElementById('admin-messages-tbody');
            if (response.ok) {
                const messages = await response.json();
                if (messages.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center;">No contact messages received.</td></tr>`;
                    return;
                }
                tbody.innerHTML = messages.map(m => `
                    <tr>
                        <td>MSG-${m.id}</td>
                        <td>${m.name}</td>
                        <td>${m.email}</td>
                        <td><strong>${m.subject || 'Enquiry'}</strong></td>
                        <td>${m.message}</td>
                        <td>${new Date(m.created_at).toLocaleDateString()}</td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error(e);
        }
    };

    // 7. Newsletter Subscribers
    const loadSubscribers = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/newsletter-subscribers`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            const tbody = document.getElementById('admin-subscribers-tbody');
            if (response.ok) {
                const subs = await response.json();
                if (subs.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" style="text-align: center;">No newsletter subscribers yet.</td></tr>`;
                    return;
                }
                tbody.innerHTML = subs.map(s => `
                    <tr>
                        <td>SUB-${s.id}</td>
                        <td><strong>${s.email}</strong></td>
                        <td>${new Date(s.created_at).toLocaleDateString()}</td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error(e);
        }
    };

    // 8. Users List & Password Reset
    let allUsers = [];
    const populateUserDropdown = (users) => {
        const select = document.getElementById('shipUser');
        if (!select) return;
        const regularUsers = users.filter(u => (u.role || '').toLowerCase() !== 'admin');
        select.innerHTML = '<option value="" disabled selected>Select User</option>' +
            regularUsers.map(u => `<option value="${u.id}">${u.name} (${u.email})</option>`).join('');
    };

    const loadUsers = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/users`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            const tbody = document.getElementById('admin-users-tbody');
            if (response.ok) {
                allUsers = await response.json();
                populateUserDropdown(allUsers);
                if (allUsers.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center;">No users found.</td></tr>`;
                    return;
                }
                tbody.innerHTML = allUsers.map(u => `
                    <tr>
                        <td>USR-${u.id}</td>
                        <td><strong>${u.name}</strong></td>
                        <td>${u.email}</td>
                        <td><span class="status-badge ${u.role === 'admin' ? 'completed' : 'pending'}">${u.role ? u.role.toUpperCase() : 'USER'}</span></td>
                        <td>${new Date(u.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="btn-primary-outline" onclick="openResetUserPasswordModal(${u.id})">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error("Error loading users", e);
        }
    };

    window.openResetUserPasswordModal = (id) => {
        const u = allUsers.find(item => item.id == id);
        if (!u) return;

        document.getElementById('resetTargetUserId').value = u.id;
        document.getElementById('resetTargetUserText').innerHTML = `Resetting password for user: <strong>${u.name}</strong> (${u.email})`;
        document.getElementById('resetTargetNewPassword').value = '';
        document.getElementById('resetTargetConfirmPassword').value = '';

        document.getElementById('resetUserPasswordModal').style.display = 'flex';
    };

    const resetUserPasswordForm = document.getElementById('resetUserPasswordForm');
    if (resetUserPasswordForm) {
        resetUserPasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = resetUserPasswordForm.querySelector("button[type='submit']") || resetUserPasswordForm.querySelector("button");
            const token = localStorage.getItem("auth_token");
            const id = document.getElementById('resetTargetUserId').value;
            const newPassword = document.getElementById('resetTargetNewPassword').value;
            const confirmPassword = document.getElementById('resetTargetConfirmPassword').value;

            if (newPassword !== confirmPassword) {
                showToast('Passwords do not match.', 'error');
                return;
            }

            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, true, 'Resetting...');
            }

            try {
                const res = await fetch(`${CONFIG.API_URL}/admin/users/${id}/password`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        new_password: newPassword,
                        new_password_confirmation: confirmPassword
                    })
                });

                const data = await res.json();

                if (res.ok) {
                    showToast(data.message || 'User password reset successfully!', 'success');
                    window.closeModal('resetUserPasswordModal');
                    resetUserPasswordForm.reset();
                } else {
                    const err = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    showToast(err || 'Failed to reset password.', 'error');
                }
            } catch (err) {
                console.error("User password reset error:", err);
                showToast('An error occurred while resetting password.', 'error');
            } finally {
                if (typeof setButtonLoading === 'function' && submitBtn) {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    }

    // 8b. System Settings
    const loadSettings = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/settings`, {
                headers: { "Authorization": `Bearer ${token}`, "Accept": "application/json" }
            });
            if (response.ok) {
                const settings = await response.json();
                if (settings.default_shipping_rate !== undefined) {
                    document.getElementById('settingShippingRate').value = settings.default_shipping_rate;
                }
            }
        } catch (e) {
            console.error("Error loading settings:", e);
        }
    };

    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = settingsForm.querySelector("button[type='submit']") || settingsForm.querySelector("button");
            const token = localStorage.getItem("auth_token");
            const shippingRate = document.getElementById('settingShippingRate').value;

            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, true, 'Saving...');
            }

            try {
                const response = await fetch(`${CONFIG.API_URL}/admin/settings`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        settings: {
                            default_shipping_rate: shippingRate
                        }
                    })
                });

                if (response.ok) {
                    showToast('Shipping configuration saved successfully!', 'success');
                } else {
                    const err = await response.json();
                    showToast(err.message || 'Failed to save settings', 'error');
                }
            } catch (error) {
                console.error("Error saving settings:", error);
                showToast('An error occurred while saving settings.', 'error');
            } finally {
                if (typeof setButtonLoading === 'function' && submitBtn) {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    };

    // 9. Create Shipment Quick Button
    const btnCreateShipment = document.getElementById('btnCreateShipment');
    const createShipmentFormContainer = document.getElementById('createShipmentFormContainer');
    if (btnCreateShipment) {
        btnCreateShipment.addEventListener('click', async () => {
            if (allUsers.length === 0) {
                await loadUsers();
            } else {
                populateUserDropdown(allUsers);
            }
            createShipmentFormContainer.style.display = createShipmentFormContainer.style.display === 'none' ? 'block' : 'none';
        });
    }

    const createShipmentForm = document.getElementById('createShipmentForm');
    if (createShipmentForm) {
        createShipmentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = createShipmentForm.querySelector("button[type='submit']") || createShipmentForm.querySelector("button");
            const token = localStorage.getItem("auth_token");

            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, true, 'Creating...');
            }

            try {
                const response = await fetch(`${CONFIG.API_URL}/admin/shipments`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify({
                        user_id: document.getElementById('shipUser').value,
                        origin: document.getElementById('shipOrigin').value || 'Lagos, Nigeria',
                        destination: document.getElementById('shipDestination').value,
                        service: document.getElementById('shipService').value,
                        weight: document.getElementById('shipWeight').value,
                        packages: document.getElementById('shipPackages').value,
                        recipient: document.getElementById('shipRecipient').value,
                    })
                });
                if (response.ok) {
                    showToast('Shipment created successfully', 'success');
                    createShipmentForm.reset();
                    createShipmentFormContainer.style.display = 'none';
                    loadShipments();
                } else {
                    const err = await response.json();
                    showToast(err.message || 'Error creating shipment', 'error');
                }
            } catch (error) {
                console.error(error);
            } finally {
                if (typeof setButtonLoading === 'function' && submitBtn) {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    }

    // 4b. Pick & Delivery Requests Management
    const loadPickupDeliveries = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/pickup-deliveries`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            const tbody = document.getElementById('admin-pickup-tbody');
            if (response.ok) {
                const requests = await response.json();
                if (requests.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center;">No pickup & delivery requests found.</td></tr>`;
                    return;
                }
                tbody.innerHTML = requests.map(r => `
                    <tr>
                        <td><strong>${r.request_id}</strong></td>
                        <td>${r.user ? r.user.name : r.name}<br><small style="color: #6b7280;">${r.email} (${r.phone})</small></td>
                        <td>${r.pickup_address}</td>
                        <td>${r.delivery_address}</td>
                        <td>${r.notes ? (r.notes.length > 30 ? r.notes.substring(0, 30)+'...' : r.notes) : '<em>None</em>'}</td>
                        <td><span class="status-badge ${r.status}">${r.status ? r.status.toUpperCase() : 'PENDING'}</span></td>
                        <td>
                            <select onchange="updatePickupStatus('${r.id}', this.value)" style="padding: 6px; border-radius: 6px;">
                                <option value="pending" ${r.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="processing" ${r.status === 'processing' ? 'selected' : ''}>Processing</option>
                                <option value="completed" ${r.status === 'completed' ? 'selected' : ''}>Completed</option>
                                <option value="cancelled" ${r.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.updatePickupStatus = async (id, status) => {
        const token = localStorage.getItem("auth_token");
        try {
            const res = await fetch(`${CONFIG.API_URL}/admin/pickup-deliveries/${id}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                body: JSON.stringify({ status })
            });
            if (res.ok) {
                showToast('Pickup request status updated!', 'success');
            } else {
                showToast('Failed to update status', 'error');
            }
        } catch(err) {
            console.error(err);
        }
    };

    // 4c. Frozen Cargo Requests Management
    const loadFrozenCargos = async () => {
        try {
            const token = localStorage.getItem("auth_token");
            const response = await fetch(`${CONFIG.API_URL}/admin/frozen-cargos`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            const tbody = document.getElementById('admin-frozen-tbody');
            if (response.ok) {
                const requests = await response.json();
                if (requests.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center;">No frozen cargo requests found.</td></tr>`;
                    return;
                }
                tbody.innerHTML = requests.map(r => `
                    <tr>
                        <td><strong>${r.request_id}</strong></td>
                        <td>${r.user ? r.user.name : r.name}<br><small style="color: #6b7280;">${r.email} (${r.phone})</small></td>
                        <td>${r.temperature_requirement || 'Frozen'}</td>
                        <td>${r.origin} &rarr; ${r.destination}</td>
                        <td>${r.notes ? (r.notes.length > 30 ? r.notes.substring(0, 30)+'...' : r.notes) : '<em>None</em>'}</td>
                        <td><span class="status-badge ${r.status}">${r.status ? r.status.toUpperCase() : 'PENDING'}</span></td>
                        <td>
                            <select onchange="updateFrozenStatus('${r.id}', this.value)" style="padding: 6px; border-radius: 6px;">
                                <option value="pending" ${r.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="processing" ${r.status === 'processing' ? 'selected' : ''}>Processing</option>
                                <option value="completed" ${r.status === 'completed' ? 'selected' : ''}>Completed</option>
                                <option value="cancelled" ${r.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.updateFrozenStatus = async (id, status) => {
        const token = localStorage.getItem("auth_token");
        try {
            const res = await fetch(`${CONFIG.API_URL}/admin/frozen-cargos/${id}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                body: JSON.stringify({ status })
            });
            if (res.ok) {
                showToast('Frozen cargo request status updated!', 'success');
            } else {
                showToast('Failed to update status', 'error');
            }
        } catch(err) {
            console.error(err);
        }
    };

    // 10. Sidebar Navigation
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    const panels = document.querySelectorAll('.panel');

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            const targetId = item.getAttribute('data-target');
            if(!targetId) return;
            
            e.preventDefault();

            if (targetId === 'overview') loadDashboardData();
            if (targetId === 'procurements') loadProcurements();
            if (targetId === 'shipments') { loadShipments(); loadUsers(); }
            if (targetId === 'pickup-deliveries') loadPickupDeliveries();
            if (targetId === 'frozen-cargos') loadFrozenCargos();
            if (targetId === 'quotes') loadQuotes();
            if (targetId === 'users') loadUsers();
            if (targetId === 'messages') loadMessages();
            if (targetId === 'subscribers') loadSubscribers();
            if (targetId === 'settings') loadSettings();

            navItems.forEach(nav => nav.classList.remove('active'));
            panels.forEach(panel => panel.classList.remove('active'));

            item.classList.add('active');
            const targetPanel = document.getElementById(`panel-${targetId}`);
            if(targetPanel) {
                targetPanel.classList.add('active');
            }

            if (window.innerWidth <= 768) {
                const sb = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sb) sb.classList.remove('open');
                if (overlay) overlay.classList.remove('open');
            }
        });
    });

    // 11. Mobile Sidebar Toggle & Backdrop Overlay
    const menuToggle = document.getElementById('menuToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    const openMobileSidebar = () => {
        if (sidebar) sidebar.classList.add('open');
        if (sidebarOverlay) sidebarOverlay.classList.add('open');
    };

    const closeMobileSidebar = () => {
        if (sidebar) sidebar.classList.remove('open');
        if (sidebarOverlay) sidebarOverlay.classList.remove('open');
    };

    if (menuToggle) menuToggle.addEventListener('click', openMobileSidebar);
    if (closeSidebar) closeSidebar.addEventListener('click', closeMobileSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeMobileSidebar);

    // 12. Logout Functionality
    const adminLogoutButton = document.getElementById('adminLogoutButton');
    if (adminLogoutButton) {
        adminLogoutButton.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const token = localStorage.getItem("auth_token");
                if (token) {
                    await fetch(`${CONFIG.API_URL}/logout`, {
                        method: "POST",
                        headers: {
                            "Accept": "application/json",
                            "Content-Type": "application/json",
                            "Authorization": `Bearer ${token}`
                        }
                    });
                }
            } catch (error) {
                console.error("Logout error", error);
            } finally {
                localStorage.removeItem("loggedIn");
                localStorage.removeItem("userEmail");
                localStorage.removeItem("auth_token");
                window.location.href = "signin.html";
            }
        });
    }

    // 13. Admin Password Change Handler
    const adminChangePasswordForm = document.getElementById('adminChangePasswordForm');
    if (adminChangePasswordForm) {
        adminChangePasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = adminChangePasswordForm.querySelector("button[type='submit']") || adminChangePasswordForm.querySelector("button");

            const currentPassword = document.getElementById('adminCurrentPassword').value;
            const newPassword = document.getElementById('adminNewPassword').value;
            const confirmNewPassword = document.getElementById('adminConfirmNewPassword').value;

            if (newPassword !== confirmNewPassword) {
                showToast('New passwords do not match.', 'error');
                return;
            }

            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, true, 'Updating Password...');
            }

            try {
                const token = localStorage.getItem("auth_token");
                const res = await fetch(`${CONFIG.API_URL}/change-password`, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword,
                        new_password_confirmation: confirmNewPassword
                    })
                });

                const data = await res.json();

                if (res.ok) {
                    showToast(data.message || 'Password changed successfully!', 'success');
                    adminChangePasswordForm.reset();
                } else {
                    const err = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    showToast(err || 'Failed to update password.', 'error');
                }
            } catch (err) {
                console.error("Change password error", err);
                showToast('An error occurred while updating password.', 'error');
            } finally {
                if (typeof setButtonLoading === 'function' && submitBtn) {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    }

    // 14. Toggle Password Visibility
    document.querySelectorAll('.toggle-password-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });

    // Run auth check on page load
    checkAdminAuth();
});
