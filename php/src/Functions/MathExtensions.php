<?php

declare(strict_types=1);

/**
 * MathExtensions - Extended mathematical and financial functions
 * วางไฟล์นี้ใน src/Functions/MathExtensions.php
 */
class MathExtensions implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'percentage' => [self::class, 'percentage'],
            'percentage_of' => [self::class, 'percentageOf'],
            'compound_interest' => [self::class, 'compoundInterest'],
            'simple_interest' => [self::class, 'simpleInterest'],
            'markup' => [self::class, 'markup'],
            'margin' => [self::class, 'margin'],
            'discount_amount' => [self::class, 'discountAmount'],
            'tax_inclusive' => [self::class, 'taxInclusive'],
            'tax_exclusive' => [self::class, 'taxExclusive'],
            'depreciation' => [self::class, 'depreciation'],
            'present_value' => [self::class, 'presentValue'],
            'future_value' => [self::class, 'futureValue']
        ];
    }
    
    public static function getInfo(): array
    {
        return [
            'name' => 'Math Extensions',
            'version' => '1.0.0', 
            'description' => 'Extended mathematical and financial calculations',
            'author' => 'RuleFlow'
        ];
    }
    
    // ============================================
    // 🔧 FUNCTION IMPLEMENTATIONS
    // ============================================
    
    /**
     * Calculate percentage of a value from total
     * Usage: percentage(25, 100) -> 25.0 (25%)
     */
    public static function percentage(float $part, float $whole): float
    {
        return $whole != 0 ? ($part / $whole) * 100 : 0;
    }
    
    /**
     * Calculate percentage of a value
     * Usage: percentage_of(20, 1000) -> 200 (20% of 1000)
     */
    public static function percentageOf(float $percentage, float $amount): float
    {
        return $amount * ($percentage / 100);
    }
    
    /**
     * Calculate compound interest
     * Usage: compound_interest(10000, 0.05, 3) -> 11576.25
     */
    public static function compoundInterest(float $principal, float $rate, int $periods): float
    {
        return $principal * pow(1 + $rate, $periods);
    }
    
    /**
     * Calculate simple interest
     * Usage: simple_interest(10000, 0.05, 3) -> 11500
     */
    public static function simpleInterest(float $principal, float $rate, int $periods): float
    {
        return $principal * (1 + ($rate * $periods));
    }
    
    /**
     * Calculate markup price
     * Usage: markup(100, 25) -> 125 (25% markup)
     */
    public static function markup(float $cost, float $markupPercent): float
    {
        return $cost * (1 + ($markupPercent / 100));
    }
    
    /**
     * Calculate profit margin percentage
     * Usage: margin(125, 100) -> 20.0 (20% margin)
     */
    public static function margin(float $sellingPrice, float $cost): float
    {
        return $sellingPrice != 0 ? (($sellingPrice - $cost) / $sellingPrice) * 100 : 0;
    }
    
    /**
     * Calculate discount amount
     * Usage: discount_amount(1000, 15) -> 150 (15% discount)
     */
    public static function discountAmount(float $originalPrice, float $discountPercent): float
    {
        return $originalPrice * ($discountPercent / 100);
    }
    
    /**
     * Calculate tax-inclusive price
     * Usage: tax_inclusive(1000, 7) -> 1070 (7% tax added)
     */
    public static function taxInclusive(float $amount, float $taxRate): float
    {
        return $amount * (1 + ($taxRate / 100));
    }
    
    /**
     * Calculate tax-exclusive price from tax-inclusive
     * Usage: tax_exclusive(1070, 7) -> 1000 (remove 7% tax)
     */
    public static function taxExclusive(float $taxInclusiveAmount, float $taxRate): float
    {
        return $taxInclusiveAmount / (1 + ($taxRate / 100));
    }
    
    /**
     * Calculate straight-line depreciation
     * Usage: depreciation(10000, 1000, 5, 2) -> 6400 (after 2 years)
     */
    public static function depreciation(float $cost, float $salvageValue, int $usefulLife, int $years): float
    {
        $annualDepreciation = ($cost - $salvageValue) / $usefulLife;
        $totalDepreciation = $annualDepreciation * min($years, $usefulLife);
        return max($cost - $totalDepreciation, $salvageValue);
    }
    
    /**
     * Calculate present value
     * Usage: present_value(1000, 0.05, 3) -> 863.84
     */
    public static function presentValue(float $futureValue, float $discountRate, int $periods): float
    {
        return $futureValue / pow(1 + $discountRate, $periods);
    }
    
    /**
     * Calculate future value
     * Usage: future_value(1000, 0.05, 3) -> 1157.63
     */
    public static function futureValue(float $presentValue, float $interestRate, int $periods): float
    {
        return $presentValue * pow(1 + $interestRate, $periods);
    }
}