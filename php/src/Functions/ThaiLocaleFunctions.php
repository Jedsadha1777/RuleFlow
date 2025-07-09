<?php

declare(strict_types=1);

/**
 * ThaiLocaleFunctions - Thai-specific business calculations
 * วางไฟล์นี้ใน src/Functions/ThaiLocaleFunctions.php
 */
class ThaiLocaleFunctions implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'thai_vat' => [self::class, 'thaiVat'],
            'thai_vat_inclusive' => [self::class, 'thaiVatInclusive'],
            'thai_vat_exclusive' => [self::class, 'thaiVatExclusive'],
            'thai_withholding' => [self::class, 'thaiWithholding'],
            'thai_social_security' => [self::class, 'thaiSocialSecurity'],
            'thai_income_tax' => [self::class, 'thaiIncomeTax'],
            'baht_to_words' => [self::class, 'bahtToWords'],
            'thai_business_days' => [self::class, 'thaiBusinessDays']
        ];
    }
    
    public static function getInfo(): array
    {
        return [
            'name' => 'Thai Locale Functions',
            'version' => '1.0.0',
            'description' => 'Functions specific to Thai business calculations and regulations',
            'author' => 'RuleFlow Thailand'
        ];
    }
    
    // ============================================
    // 🔧 FUNCTION IMPLEMENTATIONS
    // ============================================
    
    /**
     * Calculate Thai VAT amount
     * Usage: thai_vat(1000) -> 70 (7% VAT)
     */
    public static function thaiVat(float $amount, float $vatRate = 7.0): float
    {
        return $amount * ($vatRate / 100);
    }
    
    /**
     * Add Thai VAT to amount
     * Usage: thai_vat_inclusive(1000) -> 1070
     */
    public static function thaiVatInclusive(float $amount, float $vatRate = 7.0): float
    {
        return $amount * (1 + ($vatRate / 100));
    }
    
    /**
     * Remove Thai VAT from VAT-inclusive amount
     * Usage: thai_vat_exclusive(1070) -> 1000
     */
    public static function thaiVatExclusive(float $vatInclusiveAmount, float $vatRate = 7.0): float
    {
        return $vatInclusiveAmount / (1 + ($vatRate / 100));
    }
    
    /**
     * Calculate Thai withholding tax
     * Usage: thai_withholding(10000, 3) -> 300 (3% withholding)
     */
    public static function thaiWithholding(float $amount, float $rate = 3.0): float
    {
        return $amount * ($rate / 100);
    }
    
    /**
     * Calculate Thai social security contribution
     * Usage: thai_social_security(25000) -> 750 (5% of max 15000)
     */
    public static function thaiSocialSecurity(float $salary, float $maxBase = 15000, float $rate = 5.0): float
    {
        $base = min($salary, $maxBase);
        return $base * ($rate / 100);
    }
    
    /**
     * Calculate simplified Thai personal income tax
     * Usage: thai_income_tax(500000) -> estimated tax
     */
    public static function thaiIncomeTax(float $annualIncome): float
    {
        // Simplified progressive tax calculation (2024 rates)
        $personalAllowance = 60000; // Basic personal allowance
        $taxableIncome = max(0, $annualIncome - $personalAllowance);
        
        $tax = 0;
        $brackets = [
            [0, 150000, 0.0],      // 0% for first 150,000
            [150000, 300000, 0.05], // 5% for 150,001-300,000
            [300000, 500000, 0.10], // 10% for 300,001-500,000
            [500000, 750000, 0.15], // 15% for 500,001-750,000
            [750000, 1000000, 0.20], // 20% for 750,001-1,000,000
            [1000000, 2000000, 0.25], // 25% for 1,000,001-2,000,000
            [2000000, 5000000, 0.30], // 30% for 2,000,001-5,000,000
            [5000000, PHP_INT_MAX, 0.35] // 35% for above 5,000,000
        ];
        
        foreach ($brackets as [$min, $max, $rate]) {
            if ($taxableIncome > $min) {
                $taxableAtThisBracket = min($taxableIncome, $max) - $min;
                $tax += $taxableAtThisBracket * $rate;
            }
        }
        
        return $tax;
    }
    
    /**
     * Convert amount to Thai words (simplified)
     * Usage: baht_to_words(1234.56) -> "หนึ่งพันสองร้อยสามสิบสี่บาทห้าสิบหกสตางค์"
     */
    public static function bahtToWords(float $amount): string
    {
        // Simplified implementation - in real usage, you'd want a full Thai number-to-words converter
        $formatted = number_format($amount, 2);
        return $formatted . ' บาท';
    }
    
    /**
     * Calculate business days excluding Thai public holidays
     * Usage: thai_business_days('2024-07-01', '2024-07-31') -> business days count
     */
    public static function thaiBusinessDays(string $startDate, string $endDate): int
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $days = 0;
        
        // Common Thai public holidays (simplified - would need full calendar integration)
        $holidays2024 = [
            '2024-01-01', // New Year
            '2024-02-26', // Makha Bucha Day
            '2024-04-06', // Chakri Day
            '2024-04-13', // Songkran Festival
            '2024-04-14', // Songkran Festival
            '2024-04-15', // Songkran Festival
            '2024-05-01', // Labour Day
            '2024-05-04', // Coronation Day
            '2024-05-22', // Visakha Bucha Day
            '2024-06-03', // Queen Suthida's Birthday
            '2024-07-20', // Asalha Bucha Day
            '2024-07-21', // Buddhist Lent Day
            '2024-07-28', // King Vajiralongkorn's Birthday
            '2024-08-12', // Mother's Day
            '2024-10-13', // King Bhumibol Memorial Day
            '2024-10-23', // Chulalongkorn Day
            '2024-12-05', // Father's Day
            '2024-12-10', // Constitution Day
            '2024-12-31'  // New Year's Eve
        ];
        
        while ($start <= $end) {
            $dateStr = $start->format('Y-m-d');
            $dayOfWeek = $start->format('N');
            
            // Count if it's a weekday and not a holiday
            if ($dayOfWeek < 6 && !in_array($dateStr, $holidays2024)) {
                $days++;
            }
            
            $start->add(new DateInterval('P1D'));
        }
        
        return $days;
    }
}