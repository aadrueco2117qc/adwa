<?php
// modules/technician/conflict_resolver.php
// Handles offline sync conflict detection and resolution

/**
 * Detect conflicts between local (client) data and server data.
 * Returns an array of conflicting fields, or empty array if no conflicts.
 */
function detect_conflict(array $local_data, array $server_data): array {
    $conflicts = [];

    $fields_to_check = ['notes', 'findings', 'actions_taken'];

    foreach ($fields_to_check as $field) {
        $local_val  = trim((string)($local_data[$field] ?? ''));
        $server_val = trim((string)($server_data[$field] ?? ''));

        // Only flag as conflict if both sides have content and they differ
        if ($local_val !== '' && $server_val !== '' && $local_val !== $server_val) {
            $conflicts[] = $field;
        }
    }

    return $conflicts;
}

/**
 * Merge local and server work order data when a conflict is detected.
 * Strategy: server wins for status/assignment; local wins for text fields
 * unless server has newer content, in which case we append both.
 */
function merge_work_order(array $local_data, array $server_data, array $conflicts): array {
    $merged = $server_data; // start from server as base

    $text_fields = ['notes', 'findings', 'actions_taken'];

    foreach ($text_fields as $field) {
        $local_val  = trim((string)($local_data[$field] ?? ''));
        $server_val = trim((string)($server_data[$field] ?? ''));

        if (in_array($field, $conflicts)) {
            // Both sides have differing content — append local additions to server value
            // Avoid duplicating content that's already in the server version
            if (strpos($server_val, $local_val) === false) {
                $merged[$field] = $server_val . "\n\n[Offline update]\n" . $local_val;
            } else {
                $merged[$field] = $server_val;
            }
        } elseif ($local_val !== '' && $server_val === '') {
            // Server is empty, use local
            $merged[$field] = $local_val;
        }
        // else: server has content, local is empty or same — keep server value (already in $merged)
    }

    return $merged;
}
