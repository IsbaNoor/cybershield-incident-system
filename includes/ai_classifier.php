<?php
class AIClassifier {
    private static array $categoryKeywords = [
        'Phishing'          => ['password reset','verify account','urgent action','click here',
                                'suspicious link','@','phish'],
        'Malware'           => ['attachment','virus','trojan','ransomware','.exe','malware','macro'],
        'Unauthorised Access'=> ['login attempt','unauthorized','unusual location','locked account'],
        'Data Leak'         => ['confidential','leaked','exposed','uploaded externally']
    ];

    private static array $severityKeywords = [
        'Critical' => ['ransomware','data leak','root access','all systems'],
        'High'     => ['phishing','unauthorized','malware','credential'],
        'Medium'   => ['suspicious','unusual','attempt'],
        'Low'      => ['spam','newsletter']
    ];

    public static function analyze(string $description): array {
        $descLower = strtolower($description);
        $scores = [];

        // Category scoring
        foreach (self::$categoryKeywords as $cat => $words) {
            $scores[$cat] = 0;
            foreach ($words as $w) {
                $scores[$cat] += substr_count($descLower, $w);
            }
        }
        arsort($scores);
        $category = array_key_first($scores) ?? 'Other';

        // Severity determination
        $severity = 'Low';
        foreach (['Critical','High','Medium','Low'] as $level) {
            foreach (self::$severityKeywords[$level] as $word) {
                if (strpos($descLower, $word) !== false) {
                    $severity = $level;
                    break 2;
                }
            }
        }
        return [$category, $severity];
    }
}