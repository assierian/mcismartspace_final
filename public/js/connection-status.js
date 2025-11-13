/**
 * MCiSmartSpace Connection Status Manager
 * Handles online/offline transitions and page refresh
 */
(function() {
    let wasOffline = !navigator.onLine;
    
    // Function to update UI based on connection status
    function updateOnlineStatus() {
        const isOnline = navigator.onLine;
        console.log('Connection status changed:', isOnline ? 'ONLINE' : 'OFFLINE');
        
        // If we were offline and now we're online, reload the page
        if (wasOffline && isOnline) {
            console.log('Reconnected to network, refreshing page...');
            window.location.reload();
        }
        
        // Update offline status for next check
        wasOffline = !isOnline;
    }
    
    // Add event listeners for online/offline events
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    
    // Function to manually check connection to server
    function checkServerConnection() {
        if (!navigator.onLine) return; // Skip if browser reports offline
        
        // Create a unique URL to prevent caching
        const testUrl = `/connection-test.php?nocache=${Date.now()}`;
        
        fetch(testUrl, { 
            method: 'HEAD',
            mode: 'no-cors', // This prevents CORS issues
            cache: 'no-cache'
        })
        .then(() => {
            // We got a response, so we're definitely online
            if (wasOffline) {
                console.log('Server connection restored, refreshing page...');
                window.location.reload();
            }
            wasOffline = false;
        })
        .catch(err => {
            console.log('Server connection check failed:', err);
            // We might be online but unable to reach the server
            // No need to set wasOffline here as navigator.onLine is the source of truth
        });
    }
    
    // Check server connection periodically (every 30 seconds)
    setInterval(checkServerConnection, 30000);
    
    // Initial check
    updateOnlineStatus();
})();
