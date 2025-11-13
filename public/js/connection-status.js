/**
 * MCiSmartSpace Connection Status Manager
 * Handles online/offline transitions and page refresh
 */
(function() {
    let wasOffline = false;
    let isCurrentlyOffline = false;
    let lastSuccessfulConnection = Date.now();
    let retryCount = 0;
    const MAX_RETRY_COUNT = 3;
    const CONNECTION_CHECK_INTERVAL = 10000; // Check every 10 seconds
    const SERVER_TIMEOUT = 5000; // 5 second timeout for server checks

    // Function to force page reload when connection is restored
    function forceReloadIfNeeded() {
        if (wasOffline && !isCurrentlyOffline) {
            console.log(' Connection restored! Reloading page...');
            // Add a small delay to ensure connection is stable
            setTimeout(() => {
                window.location.reload(true); // Force reload from server
            }, 1000);
        }
    }

    // Function to check actual server connectivity
    function checkServerConnectivity() {
        return new Promise((resolve, reject) => {
            const testUrl = `/connection-test.php?nocache=${Date.now()}`;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), SERVER_TIMEOUT);

            fetch(testUrl, {
                method: 'HEAD',
                mode: 'no-cors',
                cache: 'no-cache',
                signal: controller.signal
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (response.ok || response.type === 'opaque') {
                    resolve(true);
                } else {
                    resolve(false);
                }
            })
            .catch(err => {
                clearTimeout(timeoutId);
                console.log('Server check failed:', err.message);
                resolve(false);
            });
        });
    }

    // Function to update connection status
    async function updateConnectionStatus() {
        try {
            const serverReachable = await checkServerConnectivity();
            const browserOnline = navigator.onLine;

            // Determine if we're actually offline
            const currentlyOffline = !serverReachable || !browserOnline;

            console.log(' Connection check:', {
                browserOnline,
                serverReachable,
                currentlyOffline,
                wasOffline,
                lastSuccess: new Date(lastSuccessfulConnection).toLocaleTimeString()
            });

            // Update status
            if (!currentlyOffline) {
                lastSuccessfulConnection = Date.now();
                retryCount = 0;
            }

            // Handle transition from offline to online
            if (wasOffline && !currentlyOffline) {
                console.log(' Connection restored! Triggering reload...');
                forceReloadIfNeeded();
                return;
            }

            // Handle transition to offline
            if (!wasOffline && currentlyOffline) {
                console.log(' Connection lost');
                retryCount = 0;
            }

            // Update tracking variables
            wasOffline = currentlyOffline;
            isCurrentlyOffline = currentlyOffline;

        } catch (error) {
            console.error('Connection status check failed:', error);
            isCurrentlyOffline = true;
        }
    }

    // Function to handle manual retry
    window.retryConnection = async function() {
        console.log(' Manual retry requested...');
        await updateConnectionStatus();
        if (!isCurrentlyOffline) {
            window.location.reload(true);
        } else {
            alert('Still offline. Please check your network connection.');
        }
    };

    // Start periodic connection checks
    function startConnectionMonitoring() {
        // Initial check
        updateConnectionStatus();

        // Set up periodic checks
        setInterval(updateConnectionStatus, CONNECTION_CHECK_INTERVAL);

        // Also check when visibility changes (user returns to tab)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && isCurrentlyOffline) {
                console.log(' Tab became visible, checking connection...');
                updateConnectionStatus();
            }
        });

        // Check when window gains focus
        window.addEventListener('focus', function() {
            if (isCurrentlyOffline) {
                console.log(' Window focused, checking connection...');
                updateConnectionStatus();
            }
        });
    }

    // Listen for browser online/offline events as backup
    window.addEventListener('online', function() {
        console.log(' Browser reports online, verifying...');
        updateConnectionStatus();
    });

    window.addEventListener('offline', function() {
        console.log(' Browser reports offline');
        isCurrentlyOffline = true;
        wasOffline = true;
    });

    // Start monitoring
    console.log(' Starting connection monitoring...');
    startConnectionMonitoring();

    // Make status available globally for debugging
    window.connectionStatus = {
        get isOffline() { return isCurrentlyOffline; },
        get wasOffline() { return wasOffline; },
        checkNow: updateConnectionStatus
    };
})();
