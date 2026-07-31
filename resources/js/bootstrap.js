import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && [401, 419].includes(error.response.status)) {
            // Prevent multiple modals
            if (document.getElementById('session-expired-modal')) {
                return Promise.reject(error);
            }

            // Create friendly modal
            const overlay = document.createElement('div');
            overlay.id = 'session-expired-modal';
            overlay.className = 'fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4';
            
            overlay.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden max-w-sm w-full animate-fade-in-up">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Session Expired</h3>
                        <p class="text-slate-500 text-sm mb-6">Your session has expired for security reasons. Please log in again to continue.</p>
                        <button onclick="window.location.reload();" class="w-full bg-[#2774AE] hover:bg-[#1E5A8A] text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-blue-900/10 transition-all active:scale-[0.98]">
                            Sign in again
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);

            // Auto-redirect after 5 seconds just in case
            setTimeout(() => {
                window.location.reload();
            }, 5000);
        }
        return Promise.reject(error);
    }
);
