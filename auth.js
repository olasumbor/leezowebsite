const logoutButton = document.getElementById("logoutButton");

if (logoutButton) {
    logoutButton.addEventListener("click", function () {

        localStorage.removeItem("loggedIn");
        localStorage.removeItem("userEmail");

        window.location.href = "signin.html";
    });
}



// SIGN IN
const loginForm = document.getElementById("loginForm");

if (loginForm) {
    loginForm.addEventListener("submit", function (event) {

        event.preventDefault();

        const email = document.getElementById("loginEmail").value;
        const password = document.getElementById("loginPassword").value;

        const registeredEmail = localStorage.getItem("registeredEmail");
        const registeredPassword = localStorage.getItem("registeredPassword");

        if (email === registeredEmail && password === registeredPassword) {

            localStorage.setItem("loggedIn", "true");
            localStorage.setItem("userEmail", email);

            console.log("Sign In successful");
            console.log("Logged in as:", email);

            window.location.href = "dashboard.html";

        } else {

            alert("Invalid email or password.");

        }
    });
}



// DASHBOARD ACCESS CHECK

if (window.location.pathname.includes("dashboard.html")) {

    const loggedIn = localStorage.getItem("loggedIn");

    if (loggedIn !== "true") {
        window.location.href = "signin.html";
    }
}



// SIGN UP
const signupForm = document.getElementById("signupForm");

if (signupForm) {

    signupForm.addEventListener("submit", function (event) {

        event.preventDefault();

        const email = document.getElementById("signupEmail").value;
        const phone = document.getElementById("signupPhone").value;
        const password = document.getElementById("signupPassword").value;

        if (email && phone && password) {

            // Save the user's registration details
            localStorage.setItem("registeredEmail", email);
            localStorage.setItem("registeredPhone", phone);
            localStorage.setItem("registeredPassword", password);

            console.log("Sign Up successful");
            console.log("Registered email:", email);

            alert("Account created successfully!");

            // Send user to Sign In
            window.location.href = "signin.html";
        }
    });
}




// FORGOT PASSWORD

const forgotPasswordForm = document.getElementById("forgotPasswordForm");

if (forgotPasswordForm) {

    forgotPasswordForm.addEventListener("submit", function (event) {

        event.preventDefault();

        const email = document.getElementById("forgotEmail").value.trim();

        const registeredEmail = localStorage.getItem("registeredEmail");

        const message = document.getElementById("forgotPasswordMessage");

        if (email === registeredEmail) {

            // Save the email that was verified
            localStorage.setItem("resetEmail", email);

            console.log("Password reset requested for:", email);

            // Send user to Reset Password page
            window.location.href = "reset-password.html";

        } else {

            message.textContent = "No account found with this email.";
            message.style.color = "#ef3340";

        }
    });

}



// ================================
// RESET PASSWORD
// ================================

const resetPasswordForm = document.getElementById("resetPasswordForm");

if (resetPasswordForm) {

    resetPasswordForm.addEventListener("submit", function (event) {

        event.preventDefault();

        const newPassword =
            document.getElementById("newPassword").value;

        const confirmPassword =
            document.getElementById("confirmPassword").value;

        const message =
            document.getElementById("resetPasswordMessage");


        // Check that both passwords match

        if (newPassword !== confirmPassword) {

            message.textContent = "Passwords do not match.";
            message.style.color = "#ef3340";

            return;
        }


        // Make sure the password isn't empty

        if (!newPassword) {

            message.textContent = "Please enter a new password.";
            message.style.color = "#ef3340";

            return;
        }


        // Get the email being reset

        const resetEmail =
            localStorage.getItem("resetEmail");

        const registeredEmail =
            localStorage.getItem("registeredEmail");


        // Make sure we have a valid reset account

        if (!resetEmail || resetEmail !== registeredEmail) {

            message.textContent =
                "Password reset session is invalid.";

            message.style.color = "#ef3340";

            return;
        }


        // Update the saved password

        localStorage.setItem(
            "registeredPassword",
            newPassword
        );


        // Remove the temporary reset email

        localStorage.removeItem("resetEmail");


        // Success message

        message.textContent =
            "Password updated successfully!";

        message.style.color = "#00a94f";


        console.log(
            "Password updated for:",
            resetEmail
        );


        // Send user back to Sign In

        setTimeout(function () {

            window.location.href = "signin.html";

        }, 1500);

    });
}