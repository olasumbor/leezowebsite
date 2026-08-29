const CONFIG = {
    API_URL: (typeof window !== "undefined" && (window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1" || window.location.hostname === "")) 
        ? "http://127.0.0.1:8000/api" 
        : "https://api.leezofoodexport.com/api"
};

// Global fetch interceptor to automatically redirect to signin.html on 401 Unauthenticated response
if (typeof window !== "undefined" && window.fetch) {
    const originalFetch = window.fetch;
    window.fetch = async function (...args) {
        const response = await originalFetch.apply(this, args);
        if (response && response.status === 401) {
            const url = typeof args[0] === "string" ? args[0] : (args[0] && args[0].url ? args[0].url : "");
            const isAuthEndpoint = url.includes('/login') || url.includes('/register');
            const isAuthPage = window.location.pathname.endsWith('signin.html') || window.location.pathname.endsWith('signup.html');
            
            if (!isAuthEndpoint && !isAuthPage) {
                console.warn("401 Unauthorized response received. Redirecting to signin.html...");
                localStorage.removeItem("loggedIn");
                localStorage.removeItem("userEmail");
                localStorage.removeItem("auth_token");
                window.location.href = "signin.html";
            }
        }
        return response;
    };
}

