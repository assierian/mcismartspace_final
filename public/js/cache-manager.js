// Clear service worker caches
function clearServiceWorkerCache() {
    console.log('Checking if service worker cache needs clearing...');
    
    // First, check if this is needed by checking for session flag
    const needsCacheClear = document.cookie.includes('cache_invalidate');
    
    if (needsCacheClear) {
        console.log('Clearing service worker caches after login...');
        
        // Unregister all service workers
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for (let registration of registrations) {
                    registration.update();
                }
            });
            
            // Clear all caches
            caches.keys().then(function(cacheNames) {
                return Promise.all(
                    cacheNames.map(function(cacheName) {
                        console.log('Deleting cache:', cacheName);
                        return caches.delete(cacheName);
                    })
                );
            }).then(function() {
                console.log('Caches cleared successfully');
            });
        }
    }
}

// Execute on page load
document.addEventListener('DOMContentLoaded', function() {
    clearServiceWorkerCache();
});
