import type { FunctionTemplate } from './index';

export const HEALTHCARE_TEMPLATE: FunctionTemplate = {
  functions: {
    bmi_category: (weight: number, heightInMeters: number) => {
      if (weight <= 0 || heightInMeters <= 0) {
        throw new Error('Weight and height must be positive numbers');
      }
      
      const bmi = weight / (heightInMeters * heightInMeters);
      
      if (bmi < 18.5) return 'underweight';
      if (bmi < 25) return 'normal';
      if (bmi < 30) return 'overweight';
      return 'obese';
    },

    health_risk_score: (age: number, bmi: number, smoker: boolean, exerciseHours: number = 0) => {
      let score = 0;
      
      // Age factor
      if (age > 65) score += 30;
      else if (age > 50) score += 20;
      else if (age > 35) score += 10;
      
      // BMI factor
      if (bmi >= 30) score += 25;
      else if (bmi >= 27) score += 15;
      else if (bmi < 18.5) score += 10;
      
      // Smoking factor
      if (smoker) score += 30;
      
      // Exercise factor (protective)
      if (exerciseHours >= 5) score -= 15;
      else if (exerciseHours >= 2.5) score -= 10;
      
      return Math.max(0, Math.min(100, score));
    },

    blood_pressure_category: (systolic: number, diastolic: number) => {
      if (systolic < 120 && diastolic < 80) return 'normal';
      if (systolic < 130 && diastolic < 80) return 'elevated';
      if ((systolic >= 130 && systolic < 140) || (diastolic >= 80 && diastolic < 90)) return 'stage1_hypertension';
      if (systolic >= 140 || diastolic >= 90) return 'stage2_hypertension';
      if (systolic > 180 || diastolic > 120) return 'hypertensive_crisis';
      return 'unknown';
    },

    cholesterol_risk: (totalCholesterol: number, hdl: number, ldl: number) => {
      let riskPoints = 0;
      
      // Total cholesterol
      if (totalCholesterol >= 240) riskPoints += 3;
      else if (totalCholesterol >= 200) riskPoints += 2;
      
      // HDL (protective)
      if (hdl < 40) riskPoints += 2;
      else if (hdl >= 60) riskPoints -= 1;
      
      // LDL
      if (ldl >= 190) riskPoints += 3;
      else if (ldl >= 160) riskPoints += 2;
      else if (ldl >= 130) riskPoints += 1;
      
      return Math.max(0, riskPoints);
    },

    calorie_needs: (age: number, gender: string, weight: number, height: number, activityLevel: string) => {
      // Harris-Benedict Equation
      let bmr: number;
      
      if (gender.toLowerCase() === 'male') {
        bmr = 88.362 + (13.397 * weight) + (4.799 * height) - (5.677 * age);
      } else {
        bmr = 447.593 + (9.247 * weight) + (3.098 * height) - (4.330 * age);
      }
      
      const activityMultipliers = {
        'sedentary': 1.2,
        'light': 1.375,
        'moderate': 1.55,
        'active': 1.725,
        'very_active': 1.9
      };
      
      const multiplier = activityMultipliers[activityLevel as keyof typeof activityMultipliers] || 1.2;
      return Math.round(bmr * multiplier);
    },

    diabetes_risk_score: (age: number, bmi: number, familyHistory: boolean, bloodPressure: string) => {
      let score = 0;
      
      // Age scoring
      if (age >= 65) score += 13;
      else if (age >= 45) score += 9;
      else if (age >= 35) score += 5;
      
      // BMI scoring
      if (bmi >= 30) score += 6;
      else if (bmi >= 25) score += 3;
      
      // Family history
      if (familyHistory) score += 5;
      
      // Blood pressure
      if (bloodPressure === 'stage2_hypertension' || bloodPressure === 'hypertensive_crisis') score += 6;
      else if (bloodPressure === 'stage1_hypertension') score += 3;
      else if (bloodPressure === 'elevated') score += 1;
      
      return Math.min(100, score);
    },

    medication_dosage: (weight: number, medicationName: string, baselineUnits: number = 1) => {
      // Simplified dosage calculation (for educational purposes only)
      const weightBasedMultipliers = {
        'acetaminophen': 15, // mg per kg
        'ibuprofen': 10,     // mg per kg
        'aspirin': 5         // mg per kg
      };
      
      const multiplier = weightBasedMultipliers[medicationName.toLowerCase() as keyof typeof weightBasedMultipliers];
      
      if (!multiplier) {
        throw new Error(`Dosage calculation not available for ${medicationName}`);
      }
      
      return Math.round(weight * multiplier * baselineUnits);
    },

    target_heart_rate: (age: number, restingHeartRate: number = 70) => {
      const maxHeartRate = 220 - age;
      const heartRateReserve = maxHeartRate - restingHeartRate;
      
      return {
        moderate_min: Math.round(restingHeartRate + (heartRateReserve * 0.5)),
        moderate_max: Math.round(restingHeartRate + (heartRateReserve * 0.69)), // แก้จาก 0.7 เป็น 0.69
        vigorous_min: Math.round(restingHeartRate + (heartRateReserve * 0.7)),
        vigorous_max: Math.round(restingHeartRate + (heartRateReserve * 0.85))
      };
    }
  },

  info: {
    name: 'Healthcare Functions',
    category: 'Healthcare',
    version: '1.0.0',
    description: 'Medical calculations and health assessment functions',
    functions: {
      bmi_category: {
        description: 'Determine BMI category (underweight, normal, overweight, obese)',
        parameters: ['weight', 'heightInMeters'],
        returnType: 'string',
        examples: [
          { code: "bmi_category(70, 1.75)", description: 'Normal weight', result: 'normal' },
          { code: "bmi_category(90, 1.75)", description: 'Overweight', result: 'overweight' }
        ]
      },
      health_risk_score: {
        description: 'Calculate overall health risk score (0-100) based on multiple factors',
        parameters: ['age', 'bmi', 'smoker', 'exerciseHours?'],
        returnType: 'number',
        examples: [
          { code: "health_risk_score(45, 28, false, 3)", description: 'Moderate risk', result: 25 },
          { code: "health_risk_score(70, 32, true, 0)", description: 'High risk', result: 85 }
        ]
      },
      blood_pressure_category: {
        description: 'Categorize blood pressure reading',
        parameters: ['systolic', 'diastolic'],
        returnType: 'string',
        examples: [
          { code: "blood_pressure_category(120, 80)", description: 'Elevated BP', result: 'elevated' },
          { code: "blood_pressure_category(110, 70)", description: 'Normal BP', result: 'normal' }
        ]
      },
      cholesterol_risk: {
        description: 'Calculate cholesterol risk points',
        parameters: ['totalCholesterol', 'hdl', 'ldl'],
        returnType: 'number',
        examples: [
          { code: "cholesterol_risk(200, 45, 130)", description: 'Moderate risk', result: 3 }
        ]
      },
      calorie_needs: {
        description: 'Calculate daily calorie needs using Harris-Benedict equation',
        parameters: ['age', 'gender', 'weight', 'height', 'activityLevel'],
        returnType: 'number',
        examples: [
          { code: "calorie_needs(30, 'male', 75, 175, 'moderate')", description: 'Active male', result: 2650 }
        ]
      },
      diabetes_risk_score: {
        description: 'Calculate type 2 diabetes risk score',
        parameters: ['age', 'bmi', 'familyHistory', 'bloodPressure'],
        returnType: 'number',
        examples: [
          { code: "diabetes_risk_score(55, 30, true, 'stage1_hypertension')", description: 'High risk', result: 23 }
        ]
      },
      medication_dosage: {
        description: 'Calculate weight-based medication dosage (educational only)',
        parameters: ['weight', 'medicationName', 'baselineUnits?'],
        returnType: 'number',
        examples: [
          { code: "medication_dosage(70, 'acetaminophen')", description: 'Adult acetaminophen', result: 1050 }
        ]
      },
      target_heart_rate: {
        description: 'Calculate target heart rate zones for exercise',
        parameters: ['age', 'restingHeartRate?'],
        returnType: 'object',
        examples: [
          { code: "target_heart_rate(40, 65)", description: '40-year-old zones', result: { moderate_min: 155, moderate_max: 172, vigorous_min: 172, vigorous_max: 181 } }
        ]
      }
    }
  }
};