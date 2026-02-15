<?php
/**
 * Refresh Token Helper Functions - MINIMAL VERSION
 */

function generateRefreshToken(): string {
    return bin2hex(random_bytes(32));
}
