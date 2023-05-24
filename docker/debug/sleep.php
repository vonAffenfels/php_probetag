<?php
register_shutdown_function(function() {
    echo "shutdown function called\n";
});

set_time_limit(1); // Set time limit to 1 second (optional)
for (;;) ;         // Busy wait
