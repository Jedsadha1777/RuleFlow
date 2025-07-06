<?php

declare(strict_types=1);

/**
 * Registry for built-in and custom functions
 */
class FunctionRegistry
{
    private array $functions = [];
    
    public function __construct()
    {
        $this->registerBuiltInFunctions();
    }

    /**
     * Register a custom function
     */
    public function register(string $name, callable $handler): void
    {
        $this->functions[$name] = $handler;
    }

    /**
     * Call a registered function
     */
    public function call(string $name, array $args): float
    {
        if (!isset($this->functions[$name])) {
            throw new RuleFlowException("Unknown function: $name", [
                'function_name' => $name,
                'available_functions' => array_keys($this->functions)
            ]);
        }

        try {
            return (float)($this->functions[$name])(...$args);
        } catch (Exception $e) {
            throw new RuleFlowException("Error calling function '$name': " . $e->getMessage(), [
                'function_name' => $name,
                'arguments' => $args,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get list of available functions
     */
    public function getAvailableFunctions(): array
    {
        return array_keys($this->functions);
    }

    /**
     * Get functions grouped by category
     */
    public function getFunctionsByCategory(): array
    {
        return [
            'Math' => ['abs', 'min', 'max', 'sqrt', 'round', 'ceil', 'floor', 'pow', 'log', 'exp'],
            'Statistics' => ['avg', 'sum', 'count', 'median', 'variance', 'stddev'],
            'Business' => ['percentage', 'compound_interest', 'simple_interest', 'discount', 'markup'],
            'Utility' => ['clamp', 'normalize', 'coalesce', 'if_null']
        ];
    }

    /**
     * Register all built-in functions
     */
    private function registerBuiltInFunctions(): void
    {
        $this->registerMathFunctions();
        $this->registerStatisticsFunctions();
        $this->registerBusinessFunctions();
        $this->registerUtilityFunctions();
    }

    /**
     * Register math functions
     */
    private function registerMathFunctions(): void
    {
        // Basic math
        $this->functions['abs'] = fn($x) => abs($x);
        $this->functions['min'] = fn(...$args) => min($args);
        $this->functions['max'] = fn(...$args) => max($args);
        $this->functions['sqrt'] = function($x) {
            if ($x < 0) {
                throw new RuleFlowException("Cannot calculate square root of negative number: $x");
            }
            return sqrt($x);
        };
        
        // Rounding
        $this->functions['round'] = fn($x, $precision = 0) => round($x, (int)$precision);
        $this->functions['ceil'] = fn($x) => ceil($x);
        $this->functions['floor'] = fn($x) => floor($x);
        
        // Advanced math
        $this->functions['pow'] = fn($base, $exp) => pow($base, $exp);
        $this->functions['log'] = function($x, $base = M_E) {
            if ($x <= 0) {
                throw new RuleFlowException("Cannot calculate logarithm of non-positive number: $x");
            }
            return log($x, $base);
        };
        $this->functions['exp'] = fn($x) => exp($x);
        
        // Trigonometric
        $this->functions['sin'] = fn($x) => sin($x);
        $this->functions['cos'] = fn($x) => cos($x);
        $this->functions['tan'] = fn($x) => tan($x);
    }

    /**
     * Register statistics functions
     */
    private function registerStatisticsFunctions(): void
    {
        $this->functions['sum'] = fn(...$args) => array_sum($args);
        
        $this->functions['avg'] = function(...$args) {
            if (empty($args)) {
                throw new RuleFlowException("Cannot calculate average of empty set");
            }
            return array_sum($args) / count($args);
        };
        
        $this->functions['count'] = fn(...$args) => count($args);
        
        $this->functions['median'] = function(...$args) {
            if (empty($args)) {
                throw new RuleFlowException("Cannot calculate median of empty set");
            }
            sort($args);
            $count = count($args);
            $middle = floor($count / 2);
            
            if ($count % 2 === 0) {
                return ($args[$middle - 1] + $args[$middle]) / 2;
            } else {
                return $args[$middle];
            }
        };
        
        $this->functions['variance'] = function(...$args) {
            if (count($args) < 2) {
                throw new RuleFlowException("Variance requires at least 2 values");
            }
            $mean = array_sum($args) / count($args);
            $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $args);
            return array_sum($squaredDiffs) / count($args);
        };
        
        $this->functions['stddev'] = function(...$args) {
            return sqrt($this->functions['variance'](...$args));
        };
    }

    /**
     * Register business functions
     */
    private function registerBusinessFunctions(): void
    {
        $this->functions['percentage'] = function($part, $whole) {
            if ($whole == 0) {
                throw new RuleFlowException("Cannot calculate percentage with zero denominator");
            }
            return ($part / $whole) * 100;
        };
        
        $this->functions['compound_interest'] = function($principal, $rate, $time, $compounds_per_year = 1) {
            if ($principal < 0 || $rate < 0 || $time < 0) {
                throw new RuleFlowException("Principal, rate, and time must be non-negative");
            }
            return $principal * pow(1 + ($rate / $compounds_per_year), $compounds_per_year * $time);
        };
        
        $this->functions['simple_interest'] = function($principal, $rate, $time) {
            if ($principal < 0 || $rate < 0 || $time < 0) {
                throw new RuleFlowException("Principal, rate, and time must be non-negative");
            }
            return $principal * (1 + ($rate * $time));
        };
        
        $this->functions['discount'] = function($original_price, $discount_percent) {
            if ($discount_percent < 0 || $discount_percent > 100) {
                throw new RuleFlowException("Discount percentage must be between 0 and 100");
            }
            return $original_price * (1 - ($discount_percent / 100));
        };
        
        $this->functions['markup'] = function($cost, $markup_percent) {
            if ($markup_percent < 0) {
                throw new RuleFlowException("Markup percentage cannot be negative");
            }
            return $cost * (1 + ($markup_percent / 100));
        };
        
        // Loan payment calculation (PMT)
        $this->functions['pmt'] = function($principal, $rate, $periods) {
            if ($rate == 0) {
                return $principal / $periods;
            }
            if ($principal < 0 || $rate < 0 || $periods <= 0) {
                throw new RuleFlowException("Invalid loan parameters");
            }
            return $principal * ($rate * pow(1 + $rate, $periods)) / (pow(1 + $rate, $periods) - 1);
        };
    }

    /**
     * Register utility functions
     */
    private function registerUtilityFunctions(): void
    {
        $this->functions['clamp'] = function($value, $min, $max) {
            if ($min > $max) {
                throw new RuleFlowException("Min value cannot be greater than max value");
            }
            return min(max($value, $min), $max);
        };
        
        $this->functions['normalize'] = function($value, $min, $max) {
            if ($min == $max) {
                throw new RuleFlowException("Cannot normalize when min equals max");
            }
            return ($value - $min) / ($max - $min);
        };
        
        $this->functions['coalesce'] = function(...$args) {
            foreach ($args as $arg) {
                if ($arg !== null && $arg !== '' && !is_nan($arg)) {
                    return $arg;
                }
            }
            return 0; // Default to 0 for numeric context
        };
        
        $this->functions['if_null'] = function($value, $default = 0) {
            return ($value === null || $value === '' || is_nan($value)) ? $default : $value;
        };
        
        // Age calculation helpers
        $this->functions['age_from_year'] = function($birth_year, $current_year = null) {
            $current_year = $current_year ?? date('Y');
            return $current_year - $birth_year;
        };
        
        // BMI calculation
        $this->functions['bmi'] = function($weight_kg, $height_m) {
            if ($height_m <= 0) {
                throw new RuleFlowException("Height must be positive");
            }
            if ($weight_kg <= 0) {
                throw new RuleFlowException("Weight must be positive");
            }
            return $weight_kg / ($height_m * $height_m);
        };
        
        // Grade point average
        $this->functions['gpa'] = function($total_points, $total_credits) {
            if ($total_credits <= 0) {
                throw new RuleFlowException("Total credits must be positive");
            }
            return $total_points / $total_credits;
        };
        
        // Percentile calculation
        $this->functions['percentile'] = function($value, $min, $max) {
            if ($min == $max) {
                return 50; // Middle percentile when no range
            }
            return (($value - $min) / ($max - $min)) * 100;
        };
    }
}