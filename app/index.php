<?php
/**
 * Direct access protection
 * This file should not be accessed directly
 */
http_response_code(403);
die('Direct access not permitted');
