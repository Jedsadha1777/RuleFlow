<?php

declare(strict_types=1);

/**
 * BusinessFunctions - Business logic functions
 * วางไฟล์นี้ใน src/Functions/BusinessFunctions.php
 * ระบบจะ auto-discovery โดยอัตโนมัติ
 */
class BusinessFunctions implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'is_weekend' => [self::class, 'isWeekend'],
            'weekend_pricing' => [self::class, 'weekendPricing'],
            'seasonal_multiplier' => [self::class, 'seasonalMultiplier'],
            'tier_discount' => [self::class, 'tierDiscount'],
            'business_days_between' => [self::class, 'businessDaysBetween'],
            'bulk_discount' => [self::class, 'bulkDiscount'],
            'working_hours_multiplier' => [self::class, 'workingHoursMultiplier']
        ];
    }
    
    public static function getInfo(): array
    {
        return [
            'name' => 'Business Functions',
            'version' => '1.0.0',
            'description' => 'Common business calculation functions',
            'author' => 'RuleFlow'
        ];
    }
    
    // ============================================
    // 🔧 FUNCTION IMPLEMENTATIONS
    // ============================================
    
    /**
     * Check if date is weekend
     * Usage: is_weekend('2024-07-06') -> true (Saturday)
     */
    public static function isWeekend(string $date): bool
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new InvalidArgumentException("Invalid date: {$date}");
        }
        
        $dayOfWeek = date('N', $timestamp); // 1 (Monday) to 7 (Sunday)
        return $dayOfWeek >= 6; // Saturday or Sunday
    }
    
    /**
     * Calculate weekend pricing multiplier
     * Usage: weekend_pricing(1000, '2024-07-06', 1.5) -> 1500 (Saturday)
     */
    public static function weekendPricing(float $basePrice, string $bookingDate, float $multiplier = 1.5): float
    {
        return self::isWeekend($bookingDate) ? $basePrice * $multiplier : $basePrice;
    }
    
    /**
     * Get seasonal multiplier based on month
     * Usage: seasonal_multiplier('2024-07-06') -> 1.2 (Summer)
     */
    public static function seasonalMultiplier(string $date): float
    {
        $month = (int)date('n', strtotime($date));
        
        return match(true) {
            in_array($month, [12, 1, 2]) => 1.3, // Winter (Dec, Jan, Feb)
            in_array($month, [6, 7, 8]) => 1.2,  // Summer (Jun, Jul, Aug)
            in_array($month, [3, 4, 5]) => 1.0,  // Spring (Mar, Apr, May)
            in_array($month, [9, 10, 11]) => 1.1 // Autumn (Sep, Oct, Nov)
        };
    }
    
    /**
     * Calculate tier-based discount
     * Usage: tier_discount('GOLD', 1000) -> 100 (10% discount)
     */
    public static function tierDiscount(string $tier, float $amount): float
    {
        $rates = [
            'VIP' => 0.15,      // 15%
            'PLATINUM' => 0.12, // 12%
            'GOLD' => 0.10,     // 10%
            'SILVER' => 0.05,   // 5%
            'BRONZE' => 0.02,   // 2%
            'BASIC' => 0.0      // 0%
        ];
        
        $rate = $rates[strtoupper($tier)] ?? 0.0;
        return $amount * $rate;
    }
    
    /**
     * Calculate business days between two dates
     * Usage: business_days_between('2024-07-01', '2024-07-05') -> 4
     */
    public static function businessDaysBetween(string $startDate, string $endDate): int
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $days = 0;
        
        // Include start date, exclude end date
        while ($start < $end) {
            if ($start->format('N') < 6) { // Monday-Friday (1-5)
                $days++;
            }
            $start->add(new DateInterval('P1D'));
        }
        
        return $days;
    }
    
    /**
     * Calculate bulk discount based on quantity
     * Usage: bulk_discount(150, 10) -> 225 (15% discount for 150+ items)
     */
    public static function bulkDiscount(int $quantity, float $unitPrice): float
    {
        $rates = [
            1000 => 0.25,  // 25% for 1000+ items
            500 => 0.20,   // 20% for 500+ items
            100 => 0.15,   // 15% for 100+ items
            50 => 0.10,    // 10% for 50+ items
            20 => 0.05,    // 5% for 20+ items
            10 => 0.02     // 2% for 10+ items
        ];
        
        $discountRate = 0.0;
        foreach ($rates as $minQty => $rate) {
            if ($quantity >= $minQty) {
                $discountRate = $rate;
                break;
            }
        }
        
        return $quantity * $unitPrice * $discountRate;
    }
    
    /**
     * Calculate working hours multiplier
     * Usage: working_hours_multiplier('2024-07-06 14:30:00') -> 1.0 (normal hours)
     */
    public static function workingHoursMultiplier(string $datetime): float
    {
        $dt = new DateTime($datetime);
        $hour = (int)$dt->format('H');
        $dayOfWeek = (int)$dt->format('N');
        
        // Weekend rates
        if ($dayOfWeek >= 6) {
            return 1.5; // 50% premium for weekends
        }
        
        // Weekday rates based on hour
        return match(true) {
            $hour >= 6 && $hour < 9 => 1.2,   // Early morning premium
            $hour >= 9 && $hour < 17 => 1.0,  // Normal business hours
            $hour >= 17 && $hour < 20 => 1.1, // After hours
            default => 1.3 // Night/very early morning premium
        };
    }
}