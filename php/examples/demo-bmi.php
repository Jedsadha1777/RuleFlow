<?php

require_once "../src/RuleEngine.php";

$engine = new RuleFlow();
$config = [
   "formulas" => [
       [
           "id" => "bmi",
           "expression" => "round(weight / ((height / 100) ** 2), 2)",
           "inputs" => ["weight", "height"],
           "store_as" => "bmi_value"
       ],
       [
           "id" => "bmi_risk",
           "switch_on" => "bmi_value",
           "cases" => [
               ["condition" => ["operator" => "<", "value" => 18.5], "result" => "High"],
               ["condition" => ["operator" => "between", "value" => [18.5, 24.9]], "result" => "Low"],
               ["condition" => ["operator" => "between", "value" => [25, 29.9]], "result" => "Medium"],
               ["condition" => ["operator" => ">=", "value" => 30], "result" => "High"]
           ],
           "weight_score" => [
               "condition" => ["operator" => "between", "value" => [18.5, 24.9]],
               "score" => 20
           ]
       ],
       [
           "id" => "age_risk",
           "switch_on" => "age",
           "cases" => [
               ["condition" => ["operator" => "<", "value" => 40], "result" => "Low"],
               ["condition" => ["operator" => "between", "value" => [40, 60]], "result" => "Medium"],
               ["condition" => ["operator" => ">", "value" => 60], "result" => "High"]
           ],
           "weight_score" => [
               "condition" => ["operator" => "<", "value" => 40],
               "score" => 15
           ]
       ],
       [
           "id" => "lifestyle_assessment",
           "switch_on" => "exercise_hours",
           "cases" => [
               ["condition" => ["operator" => ">=", "value" => 5], "result" => "Excellent"],
               ["condition" => ["operator" => "between", "value" => [3, 4]], "result" => "Good"],
               ["condition" => ["operator" => "between", "value" => [1, 2]], "result" => "Fair"]
           ],
           "default" => "Poor",
           "weight_score" => [
               "condition" => ["operator" => ">=", "value" => 3],
               "score" => 15
           ]
       ],
       [
           "id" => "overall_risk_score",
           "expression" => "bmi_risk_score + age_risk_score + lifestyle_assessment_score",
           "inputs" => ["bmi_risk_score", "age_risk_score", "lifestyle_assessment_score"]
       ],
       [
           "id" => "health_category",
           "switch_on" => "overall_risk_score",
           "cases" => [
               ["condition" => ["operator" => ">=", "value" => 40], "result" => "Excellent Health"],
               ["condition" => ["operator" => ">=", "value" => 25], "result" => "Good Health"],
               ["condition" => ["operator" => ">=", "value" => 15], "result" => "Fair Health"]
           ],
           "default" => "Poor Health"
       ]
   ]
];
$inputs = [
   "weight" => 70,
   "height" => 175,
   "age" => 35,
   "exercise_hours" => 4
];
$result = $engine->evaluate($config, $inputs);
print_r($result);
?>