<?php
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateLength(string $value, int $min, int $max, string $fieldName): ?string {
    $len = mb_strlen($value);
    if ($len < $min || $len > $max) {
        return "$fieldName must be between $min and $max characters.";
    }
    return null;
}