<?php

/**
 * Cache configuration
 */

return [
    // Cache driver
    // 'file'
    'driver' => 'file',
    // Cache chunking
    // 0 - no chunking
    // 1 - chunking
    'chunk' => 1,
    // Chunking size in bytes
    // minimum 1024
    "chunk_size" => 10240
];
