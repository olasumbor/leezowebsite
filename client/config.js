const CONFIG = {
    API_URL: (typeof window !== "undefined" && (window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1" || window.location.hostname === "")) 
        ? "http://127.0.0.1:8000/api" 
        : "https://api.leezofoodexport.com/api"
};
