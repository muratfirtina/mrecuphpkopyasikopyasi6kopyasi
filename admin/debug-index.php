<?php
/**
 * Error Catching Wrapper for Admin Pages
 */

// Enable all error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set custom error handler
set_error_handler(function($severity, $message, $file, $line) {
    echo "<div style='background:red;color:white;padding:10px;margin:5px;'>";
    echo "<strong>PHP Error:</strong> $message<br>";
    echo "<strong>File:</strong> $file<br>";
    echo "<strong>Line:</strong> $line<br>";
    echo "<strong>Severity:</strong> $severity<br>";
    echo "</div>";
    return false; // Don't stop normal error handling
});

// Set exception handler
set_exception_handler(function($exception) {
    echo "<div style='background:darkred;color:white;padding:10px;margin:5px;'>";
    echo "<strong>Uncaught Exception:</strong> " . $exception->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
    echo "<strong>Trace:</strong><br><pre>" . $exception->getTraceAsString() . "</pre>";
    echo "</div>";
});

echo "<h2>Admin Index Error Debug</h2>";
echo "<div style='background:lightblue;padding:10px;margin:10px;'>";
echo "Starting admin index with error catching...<br>";

try {
    // Include the actual admin index
    echo "About to include admin index.php...<br>";
    
    // Check if file exists first
    if (file_exists('index.php')) {
        echo "✅ index.php file exists<br>";
        
        // Buffer output to catch any issues
        ob_start();
        include 'index.php';
        $output = ob_get_contents();
        ob_end_clean();
        
        echo "✅ index.php included successfully<br>";
        echo "Output length: " . strlen($output) . " characters<br>";
        
        if (strlen($output) > 0) {
            echo "</div>";
            echo $output;
        } else {
            echo "❌ No output generated from index.php<br>";
            echo "</div>";
        }
    } else {
        echo "❌ index.php file not found<br>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "❌ Exception caught: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
} catch (Error $e) {
    echo "❌ Fatal Error caught: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
}

echo "<br><strong>End of error debug wrapper</strong>";
?>
