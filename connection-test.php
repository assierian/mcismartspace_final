<?php
// Simple endpoint that just returns a 200 OK status
// Used by the connection-status.js script to test server connectivity

// Set appropriate headers
header('Content-Type: text/plain');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Just return a simple success message
echo "OK";
