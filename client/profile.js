document.addEventListener('DOMContentLoaded', async () => {
    const profileForm = document.getElementById('profileForm');
    if (!profileForm) return;

    const nameInput = document.getElementById('profileName');
    const emailInput = document.getElementById('profileEmail');
    const phoneInput = document.getElementById('profilePhone');
    const messageEl = document.getElementById('profileMessage');

    // Fetch user profile
    try {
        const token = localStorage.getItem("auth_token");
        if (!token) {
            window.location.href = 'signin.html';
            return;
        }

        const response = await fetch(`${CONFIG.API_URL}/user`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (response.ok) {
            const user = await response.json();
            nameInput.value = user.name || '';
            emailInput.value = user.email || '';
            phoneInput.value = user.phone || '';
        } else {
            window.location.href = 'signin.html';
        }
    } catch (error) {
        console.error('Error fetching profile:', error);
    }

    // Update user profile
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        messageEl.textContent = 'Updating...';
        messageEl.style.color = '#0b1a53';

        try {
            const token = localStorage.getItem("auth_token");

            const response = await fetch(`${CONFIG.API_URL}/profile`, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    name: nameInput.value,
                    email: emailInput.value,
                    phone: phoneInput.value
                })
            });

            const data = await response.json();

            if (response.ok) {
                messageEl.textContent = 'Profile updated successfully!';
                messageEl.style.color = '#00a94f';
                
                // Update local storage if email changed
                localStorage.setItem("userEmail", emailInput.value);
            } else {
                messageEl.textContent = data.message || 'Failed to update profile.';
                messageEl.style.color = '#ef3340';
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            messageEl.textContent = 'An error occurred while updating profile.';
            messageEl.style.color = '#ef3340';
        }
    });

    // Toggle Password Visibility
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

    // Change Password Handler
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;

            if (newPassword !== confirmNewPassword) {
                showToast("New passwords do not match.", "error");
                return;
            }

            try {
                const token = localStorage.getItem("auth_token");
                const response = await fetch(`${CONFIG.API_URL}/change-password`, {
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

                const data = await response.json();

                if (response.ok) {
                    showToast(data.message || "Password changed successfully!", "success");
                    changePasswordForm.reset();
                } else {
                    const err = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    showToast(err || "Failed to change password.", "error");
                }
            } catch (error) {
                console.error("Change password error:", error);
                showToast("An error occurred while changing password.", "error");
            }
        });
    }
});
