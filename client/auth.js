// LOGOUT
const logoutButton = document.getElementById("logoutButton");

if (logoutButton) {
    logoutButton.addEventListener("click", async function () {
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
            localStorage.removeItem("loggedIn");
            localStorage.removeItem("userEmail");
            localStorage.removeItem("auth_token");
            window.location.href = "signin.html";
        } catch (error) {
            console.error("Logout failed", error);
        }
    });
}

// SIGN IN
const loginForm = document.getElementById("loginForm");

if (loginForm) {
    loginForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        console.log("Form submitted!");

        const email = document.getElementById("loginEmail").value;
        const password = document.getElementById("loginPassword").value;
        console.log("Email:", email, "Password length:", password.length);

        try {
            console.log("Sending login request...");
            const response = await fetch(`${CONFIG.API_URL}/login`, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ email, password })
            });

            console.log("Login response received, status:", response.status);

            if (response.ok) {
                const data = await response.json();
                localStorage.setItem("loggedIn", "true");
                localStorage.setItem("userEmail", data.user.email);
                localStorage.setItem("auth_token", data.token);
                
                console.log("Sign In successful", data);
                if (data.user.role === 'admin') {
                    window.location.href = "admin-dashboard.html";
                } else {
                    window.location.href = "dashboard.html";
                }
            } else {
                const errorData = await response.json();
                console.error("Login failed on server side:", errorData);
                showToast(errorData.message || "Invalid email or password.", "error");
            }
        } catch (error) {
            console.error("Login error caught in try/catch:", error);
            showToast("An error occurred during login. Check console for details: " + error.message, "error");
        }
    });
}

// AUTHENTICATION GUARD & PROTECTED PAGES CHECK
const protectedPages = [
    "procurement.html",
    "procurement-history.html",
    "procurement-details.html",
    "pickup-delivery.html",
    "pickup-delivery-history.html",
    "pickup-delivery-details.html",
    "frozen-cargo.html",
    "dashboard.html",
    "profile.html",
    "shipment-history.html",
    "shipment-details.html",
    "admin-dashboard.html"
];

const currentFilename = window.location.pathname.split("/").pop();

if (protectedPages.includes(currentFilename)) {
    const checkAuth = async () => {
        const token = localStorage.getItem("auth_token");
        const loggedIn = localStorage.getItem("loggedIn") === "true";

        if (!token && !loggedIn) {
            console.warn("Unauthenticated access attempt to protected page:", currentFilename);
            if (typeof showToast !== "undefined") {
                showToast("Please sign in to access this page.", "warning");
            }
            window.location.href = "signin.html";
            return;
        }

        if (token && typeof CONFIG !== "undefined") {
            try {
                const response = await fetch(`${CONFIG.API_URL}/user`, {
                    method: "GET",
                    headers: {
                        "Accept": "application/json",
                        "Authorization": `Bearer ${token}`
                    }
                });

                if (!response.ok) {
                    localStorage.removeItem("loggedIn");
                    localStorage.removeItem("userEmail");
                    localStorage.removeItem("auth_token");
                    window.location.href = "signin.html";
                } else {
                    const user = await response.json();
                    localStorage.setItem("loggedIn", "true");
                    localStorage.setItem("userEmail", user.email);

                    const welcomeHeader = document.getElementById("welcomeHeader");
                    if (welcomeHeader) {
                        welcomeHeader.textContent = `Welcome, ${user.name}!`;
                    }

                    // Auto-fill user details on pickup-delivery form if present
                    const nameInput = document.getElementById("name");
                    const emailInput = document.getElementById("email");
                    const phoneInput = document.getElementById("phone");
                    if (nameInput && !nameInput.value) nameInput.value = user.name || "";
                    if (emailInput && !emailInput.value) emailInput.value = user.email || "";
                    if (phoneInput && !phoneInput.value && user.phone) phoneInput.value = user.phone || "";
                }
            } catch (error) {
                console.error("Auth check error:", error);
            }
        }
    };
    checkAuth();
}

// SIGN UP
const signupForm = document.getElementById("signupForm");

if (signupForm) {
    signupForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const name = document.getElementById("signupName").value;
        const email = document.getElementById("signupEmail").value;
        const phone = document.getElementById("signupPhone").value;
        const password = document.getElementById("signupPassword").value;
        
        try {
            const response = await fetch(`${CONFIG.API_URL}/register`, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    name, 
                    email, 
                    phone,
                    password,
                    password_confirmation: password // Auto-confirm for this form
                })
            });

            if (response.ok) {
                const data = await response.json();
                localStorage.setItem("loggedIn", "true");
                localStorage.setItem("userEmail", data.user.email);
                localStorage.setItem("auth_token", data.token);
                showToast("Account created successfully!", "success");
                setTimeout(() => {
                    window.location.href = "dashboard.html";
                }, 800);
            } else {
                const errorData = await response.json();
                showToast(errorData.message || "Registration failed.", "error");
            }
        } catch (error) {
            console.error("Signup error", error);
            showToast("An error occurred during registration.", "error");
        }
    });
}

// FORGOT PASSWORD
const forgotPasswordForm = document.getElementById("forgotPasswordForm");
if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        
        const email = document.getElementById("forgotEmail").value.trim();
        const message = document.getElementById("forgotPasswordMessage");
        
        try {
            const response = await fetch(`${CONFIG.API_URL}/forgot-password`, {
                method: "POST",
                credentials: "include",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ email })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                message.textContent = data.message || "Password reset link sent! Check your logs (local dev).";
                message.style.color = "#00a94f";
            } else {
                message.textContent = data.message || "No account found with this email.";
                message.style.color = "#ef3340";
            }
        } catch (error) {
            console.error("Forgot password error", error);
            message.textContent = "An error occurred.";
            message.style.color = "#ef3340";
        }
    });
}

// RESET PASSWORD
const resetPasswordForm = document.getElementById("resetPasswordForm");
if (resetPasswordForm) {
    // Attempt to extract email and token from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const urlEmail = urlParams.get('email');
    const urlToken = urlParams.get('token');
    
    resetPasswordForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        
        const newPassword = document.getElementById("newPassword").value;
        const confirmPassword = document.getElementById("confirmPassword").value;
        const message = document.getElementById("resetPasswordMessage");
        
        if (newPassword !== confirmPassword) {
            message.textContent = "Passwords do not match.";
            message.style.color = "#ef3340";
            return;
        }
        
        if (!urlEmail || !urlToken) {
            message.textContent = "Invalid password reset link.";
            message.style.color = "#ef3340";
            return;
        }
        
        try {
            const response = await fetch(`${CONFIG.API_URL}/reset-password`, {
                method: "POST",
                credentials: "include",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    email: urlEmail,
                    token: urlToken,
                    password: newPassword,
                    password_confirmation: confirmPassword
                })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                message.textContent = "Password updated successfully!";
                message.style.color = "#00a94f";
                
                setTimeout(() => {
                    window.location.href = "signin.html";
                }, 1500);
            } else {
                message.textContent = data.message || "Password reset failed.";
                message.style.color = "#ef3340";
            }
        } catch (error) {
            console.error("Reset password error", error);
            message.textContent = "An error occurred.";
            message.style.color = "#ef3340";
        }
    });
}