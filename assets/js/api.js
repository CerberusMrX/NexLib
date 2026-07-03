const API_BASE = 'api'; // Dynamic based on setup

const API = {
    async request(endpoint, method = 'GET', data = null) {
        const token = localStorage.getItem('token');
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': token ? `Bearer ${token}` : ''
            }
        };

        if (data) options.body = JSON.stringify(data);

        // Adjust endpoint for local testing (e.g., if index.php is the only entry point)
        // If the server doesn't have rewrites, we use api/index.php/endpoint
        // For simplicity, let's assume we call index.php directly or via a simple rewrite
        const url = `${API_BASE}/index.php/${endpoint}`;

        const response = await fetch(url, options);
        const result = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                localStorage.removeItem('token');
                window.location.href = 'auth.html';
            }
            throw new Error(result.error || 'Request failed');
        }

        return result;
    },

    get(endpoint) { return this.request(endpoint, 'GET'); },
    post(endpoint, data) { return this.request(endpoint, 'POST', data); },
    put(endpoint, data) { return this.request(endpoint, 'PUT', data); },
    delete(endpoint, id) { return this.request(`${endpoint}?id=${id}`, 'DELETE'); }
};
